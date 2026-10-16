<?php
/**
 * Ver o descargar el archivo de un documento.
 * Busca en MySQL el NOMBRE del archivo y lo entrega (desde pdf/ o desde MySQL en la nube).
 *   archivo.php?id=7              -> lo abre en el navegador
 *   archivo.php?id=7&descargar=1  -> lo descarga
 */
require __DIR__ . '/includes/init.php';
requerir_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$st = db()->prepare('SELECT no_oficio, archivo FROM documentos WHERE id = ?');
$st->execute([$id]);
$doc = $st->fetch();

if (!$doc || empty($doc['archivo'])) {
    http_response_code(404);
    flash('error', 'Ese documento no tiene archivo.');
    redirigir('documentos.php');
}

// Nombre amigable para la descarga: oficio_045-2026.pdf
$ext    = strtolower(pathinfo($doc['archivo'], PATHINFO_EXTENSION));
$limpio = trim(preg_replace('/[^A-Za-z0-9_-]+/', '-', $doc['no_oficio']), '-');
$nombre = 'oficio_' . ($limpio !== '' ? $limpio : $id) . '.' . $ext;

if (!almacen_enviar('pdf', $doc['archivo'], $nombre, !empty($_GET['descargar']))) {
    http_response_code(404);
    flash('error', 'No se encontró el archivo de ese documento.');
    redirigir('documentos.php');
}
exit;
