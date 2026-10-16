<?php
/**
 * Funciones de apoyo usadas en todo el sistema.
 */

const TAMANO_FOTO = 4194304; // 4 MB por foto de perfil (ya llega recortada a 400 px)

/** Estatus permitidos y la clase CSS de su etiqueta. */
function lista_estatus()
{
    return [
        'Recibido'   => 'recibido',
        'En proceso' => 'proceso',
        'Atendido'   => 'atendido',
    ];
}

/** Escapa texto para imprimirlo en HTML. */
function e($texto)
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

function redirigir($url)
{
    header('Location: ' . $url);
    exit;
}

/** Convierte 2026-10-05 en 05/10/2026. */
function fecha_mx($fecha)
{
    $t = strtotime((string) $fecha);
    return $t ? date('d/m/Y', $t) : '';
}

function fecha_valida($fecha)
{
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', (string) $fecha, $m)) {
        return false;
    }
    return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
}

/* ---------- Mensajes de una sola vez ---------- */

function flash($tipo, $mensaje)
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function flashes()
{
    $lista = isset($_SESSION['flash']) ? $_SESSION['flash'] : [];
    unset($_SESSION['flash']);
    return $lista;
}

/* ---------- Protección CSRF de los formularios ---------- */

function csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo()
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verificar()
{
    $enviado = isset($_POST['csrf']) ? (string) $_POST['csrf'] : '';
    if ($enviado === '' || !hash_equals(csrf_token(), $enviado)) {
        http_response_code(400);
        exit('La sesión del formulario expiró. Regresa, recarga la página e inténtalo de nuevo.');
    }
}

/* ---------- Configuración guardada en la BD ---------- */

function config_todo($recargar = false)
{
    static $cache = null;
    if ($cache === null || $recargar) {
        $cache = [];
        foreach (db()->query('SELECT clave, valor FROM configuracion') as $fila) {
            $cache[$fila['clave']] = $fila['valor'];
        }
    }
    return $cache;
}

function config_valor($clave, $porDefecto = '')
{
    $todo = config_todo();
    return isset($todo[$clave]) ? $todo[$clave] : $porDefecto;
}

function config_guardar($clave, $valor)
{
    $st = db()->prepare(
        'INSERT INTO configuracion (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)'
    );
    $st->execute([$clave, $valor]);
    config_todo(true);
}

function registros_por_pagina()
{
    $n = (int) config_valor('por_pagina', '10');
    return in_array($n, [5, 10, 20, 50], true) ? $n : 10;
}

/* ---------- Iconos (definidos una vez en header.php) ---------- */

function icono($nombre)
{
    return '<svg class="ico" aria-hidden="true"><use href="#i-' . e($nombre) . '"></use></svg>';
}

/* ---------- Archivos subidos ---------- */

/** Devuelve el archivo que mandó el formulario (foto o PDF), o null si no mandó ninguno. */
function archivo_enviado()
{
    foreach (['foto', 'archivo'] as $campo) {
        if (isset($_FILES[$campo]) && $_FILES[$campo]['error'] !== UPLOAD_ERR_NO_FILE) {
            return $_FILES[$campo];
        }
    }
    return null;
}

/**
 * Valida un archivo subido y lo guarda con un nombre único (ver includes/almacen.php).
 * Por defecto acepta PDF o imagen y lo guarda en la zona "pdf" (documentos).
 * Devuelve [nombre_guardado, nombre_original]. Lanza RuntimeException si algo falla.
 */
function guardar_archivo(array $f, array $op = [])
{
    $op = array_merge([
        'zona'       => 'pdf',
        'prefijo'    => 'doc',
        'maximo'     => tamano_maximo(),
        'permitidos' => [
            'application/pdf' => 'pdf',
            'image/jpeg'      => 'jpg',
            'image/png'       => 'png',
            'image/webp'      => 'webp',
        ],
        'mensaje_tipo' => 'Solo se permiten archivos PDF o imágenes (JPG, PNG, WEBP).',
    ], $op);
    $permitidos = $op['permitidos'];

    if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
        throw new RuntimeException('El archivo supera el límite de ' . round($op['maximo'] / 1048576) . ' MB.');
    }
    if ($f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        throw new RuntimeException('No se pudo recibir el archivo. Inténtalo de nuevo.');
    }
    if ($f['size'] > $op['maximo']) {
        throw new RuntimeException('El archivo supera el límite de ' . round($op['maximo'] / 1048576) . ' MB.');
    }

    if (function_exists('finfo_open')) {
        $fi   = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($fi, $f['tmp_name']);
        finfo_close($fi);
    } else {
        // Sin la extensión fileinfo se revisa la extensión del nombre.
        $ext  = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        $ext  = $ext === 'jpeg' ? 'jpg' : $ext;
        $mime = array_search($ext, $permitidos, true);
    }
    if (!$mime || !isset($permitidos[$mime])) {
        throw new RuntimeException($op['mensaje_tipo']);
    }

    // Nombre propio para que dos archivos llamados igual no se sobrescriban.
    $nombre = $op['prefijo'] . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $permitidos[$mime];
    almacen_guardar($op['zona'], $f['tmp_name'], $nombre, $mime);

    return [$nombre, mb_substr(basename($f['name']), 0, 255)];
}

/** Guarda una foto de perfil en la zona "perfiles". Devuelve el nombre del archivo. */
function guardar_foto_perfil(array $f, $usuarioId)
{
    list($nombre) = guardar_archivo($f, [
        'zona'         => 'perfiles',
        'prefijo'      => 'u' . (int) $usuarioId,
        'maximo'       => TAMANO_FOTO,
        'permitidos'   => ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'],
        'mensaje_tipo' => 'La foto de perfil debe ser JPG, PNG o WEBP.',
    ]);
    return $nombre;
}

function borrar_foto_perfil($nombre)
{
    almacen_borrar('perfiles', $nombre);
}

/** Círculo con la foto del usuario, o el ícono genérico si no tiene. */
function avatar($u, $clase = '')
{
    $clase = trim('avatar ' . $clase);
    // En la nube no se consulta si existe (sería una consulta por cada foto)
    if (!empty($u['foto']) && (almacen_en_bd() || almacen_existe('perfiles', $u['foto']))) {
        return '<span class="' . e($clase) . '"><img src="' . e(almacen_url_foto($u['foto'])) . '" alt=""></span>';
    }
    return '<span class="' . e($clase) . '">' . icono('user') . '</span>';
}

function borrar_archivo($nombre)
{
    almacen_borrar('pdf', $nombre);
}

/** true cuando el POST llegó vacío porque el archivo superó post_max_size de PHP. */
function post_demasiado_grande()
{
    return $_SERVER['REQUEST_METHOD'] === 'POST'
        && empty($_POST) && empty($_FILES)
        && isset($_SERVER['CONTENT_LENGTH']) && (int) $_SERVER['CONTENT_LENGTH'] > 0;
}

/* ---------- Consultas de documentos ---------- */

/** Lee y limpia los filtros que vienen en la URL. */
function filtros_desde_url()
{
    $f = [
        'q'       => isset($_GET['q']) ? trim((string) $_GET['q']) : '',
        'estatus' => isset($_GET['estatus']) ? (string) $_GET['estatus'] : '',
        'desde'   => isset($_GET['desde']) ? (string) $_GET['desde'] : '',
        'hasta'   => isset($_GET['hasta']) ? (string) $_GET['hasta'] : '',
    ];
    if (!isset(lista_estatus()[$f['estatus']])) {
        $f['estatus'] = '';
    }
    if (!fecha_valida($f['desde'])) {
        $f['desde'] = '';
    }
    if (!fecha_valida($f['hasta'])) {
        $f['hasta'] = '';
    }
    return $f;
}

/** Arma el WHERE según los filtros. Devuelve [sql, parametros]. */
function where_documentos(array $f)
{
    $partes = [];
    $params = [];

    if ($f['q'] !== '') {
        $like = '%' . addcslashes($f['q'], '%_\\') . '%';
        $partes[] = '(d.no_oficio LIKE :q1 OR d.asunto LIKE :q2 OR d.remitente LIKE :q3 OR d.destinatario LIKE :q4)';
        $params[':q1'] = $like;
        $params[':q2'] = $like;
        $params[':q3'] = $like;
        $params[':q4'] = $like;
    }
    if ($f['estatus'] !== '') {
        $partes[] = 'd.estatus = :estatus';
        $params[':estatus'] = $f['estatus'];
    }
    if ($f['desde'] !== '') {
        $partes[] = 'd.fecha >= :desde';
        $params[':desde'] = $f['desde'];
    }
    if ($f['hasta'] !== '') {
        $partes[] = 'd.fecha <= :hasta';
        $params[':hasta'] = $f['hasta'];
    }

    return [$partes ? 'WHERE ' . implode(' AND ', $partes) : '', $params];
}

/**
 * Documentos que cumplen los filtros.
 * $porPagina = 0 trae todos (para reportes). Devuelve [filas, total].
 */
function consultar_documentos(array $f, $pagina = 1, $porPagina = 10)
{
    list($where, $params) = where_documentos($f);

    $st = db()->prepare("SELECT COUNT(*) FROM documentos d $where");
    $st->execute($params);
    $total = (int) $st->fetchColumn();

    $limite = '';
    if ($porPagina > 0) {
        $pagina = max(1, (int) $pagina);
        $limite = 'LIMIT ' . (int) $porPagina . ' OFFSET ' . (($pagina - 1) * (int) $porPagina);
    }

    $st = db()->prepare(
        "SELECT d.*, u.nombre AS registrado_nombre
           FROM documentos d
           LEFT JOIN usuarios u ON u.id = d.registrado_por
           $where
          ORDER BY d.fecha DESC, d.id DESC
          $limite"
    );
    $st->execute($params);

    return [$st->fetchAll(), $total];
}

/* ---------- URLs ---------- */

/** URL de la página actual cambiando algunos parámetros (para la paginación). */
function url_con(array $cambios)
{
    $params = array_merge($_GET, $cambios);
    foreach ($params as $k => $v) {
        if ($v === '' || $v === null) {
            unset($params[$k]);
        }
    }
    $pagina = basename($_SERVER['SCRIPT_NAME']);
    return $pagina . ($params ? '?' . http_build_query($params) : '');
}

/** Solo acepta regresar a una página del propio sistema. */
function destino_seguro($url, $porDefecto = 'documentos.php')
{
    return preg_match('/^[a-z_]+\.php(\?[^\r\n]*)?$/i', (string) $url) ? $url : $porDefecto;
}

/** Lista de páginas a mostrar: la actual y dos a cada lado. */
function ventana_paginas($pagina, $totalPaginas)
{
    $ini = max(1, $pagina - 2);
    $fin = min($totalPaginas, $ini + 4);
    $ini = max(1, $fin - 4);
    return range($ini, $fin);
}
