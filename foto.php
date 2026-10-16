<?php
/**
 * Entrega una foto de perfil guardada en MySQL (modo nube).
 * En XAMPP las fotos se sirven directo desde la carpeta perfiles/.
 *   foto.php?f=u1_20261016_101500_a1b2c3.jpg
 */
require __DIR__ . '/includes/init.php';
requerir_login();

$nombre = isset($_GET['f']) ? basename((string) $_GET['f']) : '';
// El nombre es único y nunca se reutiliza, así que el navegador puede guardarla en caché
if ($nombre === '' || !almacen_enviar('perfiles', $nombre, null, false, 'private, max-age=31536000, immutable')) {
    http_response_code(404);
}
exit;
