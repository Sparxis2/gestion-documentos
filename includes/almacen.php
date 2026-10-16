<?php
/**
 * Dónde viven los archivos subidos (documentos y fotos de perfil).
 *
 *  - Modo "carpeta" (XAMPP): en las carpetas pdf/ y perfiles/; MySQL guarda solo el nombre.
 *  - Modo "bd" (Vercel):     en la tabla `archivos` de MySQL, porque Vercel no conserva
 *                            los archivos escritos en disco.
 *
 * El resto del sistema usa estas funciones y no necesita saber cuál modo está activo.
 */

const CARPETA_ARCHIVOS = __DIR__ . '/../pdf';
const CARPETA_PERFILES = __DIR__ . '/../perfiles';

function almacen_en_bd()
{
    return config_app()['almacenamiento'] === 'bd';
}

/** Carpeta física de una zona ("pdf" o "perfiles"). */
function almacen_carpeta($zona)
{
    return $zona === 'perfiles' ? CARPETA_PERFILES : CARPETA_ARCHIVOS;
}

function almacen_clave($zona, $nombre)
{
    return $zona . '/' . basename((string) $nombre);
}

/** Guarda el archivo temporal subido con el nombre indicado. */
function almacen_guardar($zona, $rutaTemporal, $nombre, $mime)
{
    if (almacen_en_bd()) {
        $st = db()->prepare('INSERT INTO archivos (clave, tipo, tamano, datos) VALUES (?, ?, ?, ?)');
        $st->bindValue(1, almacen_clave($zona, $nombre));
        $st->bindValue(2, $mime);
        $st->bindValue(3, filesize($rutaTemporal), PDO::PARAM_INT);
        $st->bindValue(4, file_get_contents($rutaTemporal), PDO::PARAM_LOB);
        $st->execute();
        return;
    }

    $carpeta = almacen_carpeta($zona);
    if (!is_dir($carpeta)) {
        @mkdir($carpeta, 0775, true);
    }
    if (!is_writable($carpeta)) {
        throw new RuntimeException('La carpeta ' . $zona . '/ no tiene permisos de escritura.');
    }
    if (!move_uploaded_file($rutaTemporal, $carpeta . '/' . $nombre)) {
        throw new RuntimeException('No se pudo guardar el archivo en la carpeta ' . $zona . '/.');
    }
}

function almacen_existe($zona, $nombre)
{
    if ($nombre === null || $nombre === '') {
        return false;
    }
    if (almacen_en_bd()) {
        $st = db()->prepare('SELECT 1 FROM archivos WHERE clave = ?');
        $st->execute([almacen_clave($zona, $nombre)]);
        return (bool) $st->fetchColumn();
    }
    return is_file(almacen_carpeta($zona) . '/' . basename($nombre));
}

/**
 * Envía el archivo al navegador con sus encabezados.
 * $amigable: nombre sugerido al guardar; $adjunto: true descarga, false lo muestra en el navegador.
 * Devuelve false si el archivo no existe.
 */
function almacen_enviar($zona, $nombre, $amigable = null, $adjunto = false, $cache = 'private, max-age=0, must-revalidate')
{
    $tipos = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
    $ext   = strtolower(pathinfo((string) $nombre, PATHINFO_EXTENSION));
    $tipo  = isset($tipos[$ext]) ? $tipos[$ext] : 'application/octet-stream';

    $datos = null;
    $ruta  = null;
    if (almacen_en_bd()) {
        $st = db()->prepare('SELECT datos FROM archivos WHERE clave = ?');
        $st->execute([almacen_clave($zona, $nombre)]);
        $datos = $st->fetchColumn();
        if ($datos === false) {
            return false;
        }
        $tamano = strlen($datos);
    } else {
        $ruta = almacen_carpeta($zona) . '/' . basename((string) $nombre);
        if (!is_file($ruta)) {
            return false;
        }
        $tamano = filesize($ruta);
    }

    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: ' . $tipo);
    header('Content-Length: ' . $tamano);
    header('Content-Disposition: ' . ($adjunto ? 'attachment' : 'inline') . ($amigable ? '; filename="' . $amigable . '"' : ''));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: ' . $cache);

    if ($ruta !== null) {
        readfile($ruta);
    } else {
        echo $datos;
    }
    return true;
}

function almacen_borrar($zona, $nombre)
{
    if ($nombre === null || $nombre === '') {
        return;
    }
    if (almacen_en_bd()) {
        db()->prepare('DELETE FROM archivos WHERE clave = ?')->execute([almacen_clave($zona, $nombre)]);
        return;
    }
    $ruta = almacen_carpeta($zona) . '/' . basename($nombre);
    if (is_file($ruta)) {
        @unlink($ruta);
    }
}

/** Dirección para mostrar una foto de perfil en <img>. */
function almacen_url_foto($nombre)
{
    if (almacen_en_bd()) {
        return 'foto.php?f=' . rawurlencode(basename($nombre));
    }
    return 'perfiles/' . rawurlencode(basename($nombre));
}

/** Tamaño máximo de documento. Vercel no acepta peticiones de más de 4.5 MB. */
function tamano_maximo()
{
    return en_la_nube() ? 4 * 1048576 : 10 * 1048576;
}

/* ============================================================
   Sesiones guardadas en MySQL (necesario en Vercel, donde cada
   petición puede atenderla un servidor distinto)
   ============================================================ */

class SesionesMysql implements SessionHandlerInterface
{
    public function open($ruta, $nombre): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    #[\ReturnTypeWillChange]
    public function read($id)
    {
        $st = db()->prepare('SELECT datos FROM sesiones WHERE id = ?');
        $st->execute([$id]);
        $datos = $st->fetchColumn();
        return $datos === false ? '' : (string) $datos;
    }

    public function write($id, $datos): bool
    {
        $st = db()->prepare(
            'INSERT INTO sesiones (id, datos, actualizado) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE datos = VALUES(datos), actualizado = VALUES(actualizado)'
        );
        return $st->execute([$id, $datos, time()]);
    }

    public function destroy($id): bool
    {
        return db()->prepare('DELETE FROM sesiones WHERE id = ?')->execute([$id]);
    }

    #[\ReturnTypeWillChange]
    public function gc($segundos)
    {
        $st = db()->prepare('DELETE FROM sesiones WHERE actualizado < ?');
        $st->execute([time() - (int) $segundos]);
        return $st->rowCount();
    }
}

/* ============================================================
   Instalación y actualización automática de la base de datos
   ============================================================ */

const VERSION_BD = 3;

/**
 * Crea las tablas si la base está vacía y aplica los cambios pendientes
 * a bases creadas con versiones anteriores. Así en la nube no hace falta
 * importar database.sql a mano.
 *   v2: usuarios.foto        v3: tablas archivos y sesiones
 */
function preparar_bd()
{
    try {
        $version = (int) config_valor('version_bd', '1');
    } catch (PDOException $ex) {
        if ($ex->getCode() !== '42S02') { // 42S02 = la tabla no existe
            throw $ex;
        }
        ejecutar_sql_instalacion(false);
        config_todo(true);
        return;
    }

    if ($version >= VERSION_BD) {
        return;
    }
    if ($version < 2 && !db()->query("SHOW COLUMNS FROM usuarios LIKE 'foto'")->fetch()) {
        db()->exec('ALTER TABLE usuarios ADD COLUMN foto VARCHAR(255) DEFAULT NULL AFTER rol');
    }
    if ($version < 3) {
        ejecutar_sql_instalacion(true); // solo crea las tablas que falten
    }
    config_guardar('version_bd', (string) VERSION_BD);
}

/** Ejecuta database.sql. Con $soloTablas no inserta datos (no recrea el usuario admin). */
function ejecutar_sql_instalacion($soloTablas)
{
    $sql = (string) file_get_contents(__DIR__ . '/../database.sql');
    $sql = preg_replace('/^\s*--.*$/m', '', $sql); // quita comentarios
    foreach (preg_split('/;\s*(\r?\n|$)/', $sql) as $sentencia) {
        $sentencia = trim($sentencia);
        if ($sentencia === '') {
            continue;
        }
        if ($soloTablas && stripos($sentencia, 'CREATE TABLE') !== 0) {
            continue;
        }
        db()->exec($sentencia);
    }
}
