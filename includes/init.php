<?php
/**
 * Arranque común: todas las páginas incluyen este archivo primero.
 */
date_default_timezone_set('America/Mexico_City');
mb_internal_encoding('UTF-8');

require __DIR__ . '/db.php';
require __DIR__ . '/funciones.php';
require __DIR__ . '/almacen.php';
require __DIR__ . '/auth.php';

// Si algo falla (MySQL apagado, variables mal puestas...) se muestra una pista clara.
set_exception_handler(function ($ex) {
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    $nube  = en_la_nube();
    $donde = $nube ? 'las variables de entorno de Vercel (DATABASE_URL, DB_SSL_CA)' : 'config/config.php';
    $pista = 'Ocurrió un error inesperado.';
    if ($ex instanceof PDOException) {
        $msg = $ex->getMessage();
        if (strpos($msg, '1049') !== false) {
            $pista = 'La base de datos no existe. Revisa el nombre de la base en ' . $donde . '.';
        } elseif (strpos($msg, '1045') !== false) {
            $pista = 'Usuario o contraseña de MySQL incorrectos. Revisa ' . $donde . '.';
        } elseif (strpos($msg, '3159') !== false || stripos($msg, 'SSL') !== false) {
            $pista = 'El servidor MySQL exige conexión segura (SSL). Revisa que la dirección termine en ?ssl-mode=REQUIRED y que DB_SSL_CA tenga el certificado.';
        } elseif ($nube) {
            $pista = 'No se pudo conectar a MySQL. Revisa ' . $donde . ' y que el servicio de base de datos esté encendido.';
        } else {
            $pista = 'No se pudo conectar a MySQL. Verifica que MySQL esté iniciado en XAMPP y revisa config/config.php.';
        }
    } elseif ($ex instanceof RuntimeException) {
        $pista = $ex->getMessage();
    }
    error_log('appweb: ' . $ex);
    echo '<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>Error</title><body style="font-family:system-ui,sans-serif;background:#faf5f7;color:#2b1922;padding:24px">';
    echo '<div style="max-width:560px;margin:8vh auto;background:#fff;border-radius:14px;padding:24px;box-shadow:0 6px 24px rgba(100,20,50,.12)">';
    echo '<h1 style="font-size:20px;margin:0 0 10px">No se pudo cargar el sistema</h1>';
    echo '<p style="margin:0 0 14px;line-height:1.5">' . htmlspecialchars($pista, ENT_QUOTES, 'UTF-8') . '</p>';
    if (!$nube) { // en internet no se muestran detalles internos
        echo '<p style="margin:0;font-size:12px;color:#886a77">Detalle técnico: ' . htmlspecialchars($ex->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>';
    }
    echo '</div></body></html>';
});

// Crea o actualiza las tablas si hace falta (antes de la sesión: puede vivir en MySQL)
preparar_bd();

if (almacen_en_bd()) {
    session_set_save_handler(new SesionesMysql(), true);
}

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

session_name('appweb_sesion');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => $https,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();
