<?php
/**
 * Acciones rápidas desde la tabla: cambiar estatus y eliminar.
 */
require __DIR__ . '/includes/init.php';
requerir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirigir('documentos.php');
}
csrf_verificar();

$accion = isset($_POST['accion']) ? (string) $_POST['accion'] : '';
$id     = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$volver = destino_seguro(isset($_POST['volver']) ? $_POST['volver'] : '');

$st = db()->prepare('SELECT id, no_oficio, archivo FROM documentos WHERE id = ?');
$st->execute([$id]);
$doc = $st->fetch();

if (!$doc) {
    flash('error', 'Ese documento ya no existe.');
    redirigir($volver);
}

if ($accion === 'estatus') {
    $estatus = isset($_POST['estatus']) ? (string) $_POST['estatus'] : '';
    if (isset(lista_estatus()[$estatus])) {
        db()->prepare('UPDATE documentos SET estatus = ? WHERE id = ?')->execute([$estatus, $id]);
        flash('ok', 'Oficio ' . $doc['no_oficio'] . ' marcado como «' . $estatus . '».');
    }
} elseif ($accion === 'eliminar') {
    if (!es_admin()) {
        flash('error', 'Solo un administrador puede eliminar documentos.');
    } else {
        db()->prepare('DELETE FROM documentos WHERE id = ?')->execute([$id]);
        borrar_archivo($doc['archivo']);
        flash('ok', 'Oficio ' . $doc['no_oficio'] . ' eliminado.');
    }
}

redirigir($volver);
