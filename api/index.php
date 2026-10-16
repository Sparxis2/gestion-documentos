<?php
/**
 * Punto de entrada en Vercel.
 *
 * Vercel solo ejecuta PHP dentro de la carpeta api/, así que vercel.json manda
 * todas las peticiones aquí. Este archivo mira la dirección pedida
 * (por ejemplo /documentos.php) y ejecuta la página correspondiente de la raíz.
 * En XAMPP no se usa: Apache abre las páginas directamente.
 */

$ruta   = parse_url(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/', PHP_URL_PATH);
$ruta   = rawurldecode((string) $ruta);
$pagina = ltrim($ruta, '/');
if ($pagina === '') {
    $pagina = 'index.php';
}

// Al probarlo con "php -S", los estilos y scripts los entrega el propio servidor.
if (PHP_SAPI === 'cli-server' && strpos($pagina, 'assets/') === 0 && is_file(__DIR__ . '/../' . $pagina)) {
    return false;
}

// Solo estas páginas se pueden abrir; nada de includes/, config/ ni otros archivos.
$paginas = [
    'index.php', 'login.php', 'logout.php',
    'documentos.php', 'documento_form.php', 'documento_accion.php',
    'ver.php', 'archivo.php', 'foto.php', 'buscar.php', 'reportes.php',
    'usuarios.php', 'usuario_form.php', 'configuracion.php', 'conexion.php',
];

if (!in_array($pagina, $paginas, true)) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
        . '<title>No encontrado</title><body style="font-family:system-ui,sans-serif;text-align:center;padding:12vh 16px;color:#2b1922">'
        . '<h1 style="font-size:22px">Página no encontrada</h1><p><a href="/index.php" style="color:#ad144b">Ir al inicio</a></p></body></html>';
    exit;
}

$raiz = dirname(__DIR__);

// Las páginas usan SCRIPT_NAME para armar enlaces (paginación, botón Volver...)
$_SERVER['SCRIPT_NAME']     = '/' . $pagina;
$_SERVER['PHP_SELF']        = '/' . $pagina;
$_SERVER['SCRIPT_FILENAME'] = $raiz . '/' . $pagina;

chdir($raiz);
require $raiz . '/' . $pagina;
