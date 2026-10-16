<?php
/**
 * Ver un documento dentro del sistema: sus datos y el archivo (foto o PDF),
 * con botón para regresar a la página de donde se llegó.
 *   ver.php?id=7&volver=documentos.php%3Fp%3D2
 */
require __DIR__ . '/includes/init.php';
requerir_login();

$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$volver = destino_seguro(isset($_GET['volver']) ? $_GET['volver'] : '', 'documentos.php');

$st = db()->prepare(
    'SELECT d.*, u.nombre AS registrado_nombre
       FROM documentos d LEFT JOIN usuarios u ON u.id = d.registrado_por
      WHERE d.id = ?'
);
$st->execute([$id]);
$doc = $st->fetch();

if (!$doc) {
    flash('error', 'Ese documento ya no existe.');
    redirigir($volver);
}

$hayArchivo = almacen_existe('pdf', $doc['archivo']);
$ext        = strtolower(pathinfo((string) $doc['archivo'], PATHINFO_EXTENSION));
$esPdf      = $ext === 'pdf';
$urlArchivo = 'archivo.php?id=' . (int) $doc['id'];
$estatus    = lista_estatus();

$titulo  = 'Oficio ' . $doc['no_oficio'];
$seccion = 'documentos';
require __DIR__ . '/includes/header.php';
?>
<div class="encabezado">
  <h1><?= icono('doc') ?> Oficio <?= e($doc['no_oficio']) ?></h1>
  <div class="encabezado-botones">
    <a class="btn btn-suave" href="<?= e($volver) ?>"><?= icono('back') ?> Volver</a>
    <a class="btn btn-suave" href="documento_form.php?id=<?= (int) $doc['id'] ?>"><?= icono('edit') ?> Editar</a>
    <?php if ($hayArchivo): ?>
      <a class="btn btn-primario" href="<?= e($urlArchivo) ?>&amp;descargar=1"><?= icono('download') ?> Descargar</a>
    <?php endif; ?>
  </div>
</div>

<div class="visor-rejilla">
  <section class="panel visor-datos">
    <div class="panel-titulo"><h2>Datos del documento</h2></div>
    <dl class="datos">
      <dt>No. de oficio</dt><dd><strong><?= e($doc['no_oficio']) ?></strong></dd>
      <dt>Fecha</dt><dd><?= e(fecha_mx($doc['fecha'])) ?></dd>
      <dt>Asunto</dt><dd><?= e($doc['asunto']) ?></dd>
      <dt>Remitente</dt><dd><?= e($doc['remitente']) ?></dd>
      <dt>Destinatario</dt><dd><?= e($doc['destinatario']) ?></dd>
      <dt>Estatus</dt>
      <dd><span class="etiqueta etiqueta-<?= e($estatus[$doc['estatus']]) ?>"><?= e($doc['estatus']) ?></span></dd>
      <dt>Registrado por</dt><dd><?= $doc['registrado_nombre'] ? e($doc['registrado_nombre']) : '—' ?></dd>
      <dt>Registrado el</dt><dd><?= e(date('d/m/Y H:i', strtotime($doc['creado_en']))) ?></dd>
      <?php if (!empty($doc['archivo_original'])): ?>
        <dt>Archivo original</dt><dd class="partir"><?= e($doc['archivo_original']) ?></dd>
      <?php endif; ?>
    </dl>
  </section>

  <section class="panel visor">
    <?php if (!$hayArchivo): ?>
      <div class="vacio">
        <?= icono('doc') ?>
        <p>Este documento no tiene archivo, o ya no se encuentra.</p>
        <a class="btn btn-primario" href="documento_form.php?id=<?= (int) $doc['id'] ?>"><?= icono('upload') ?> Adjuntar archivo</a>
      </div>
    <?php elseif ($esPdf): ?>
      <!-- Si el navegador no puede mostrar PDF aquí (varios teléfonos), se ve el contenido de respaldo -->
      <object class="visor-pdf" data="<?= e($urlArchivo) ?>" type="application/pdf">
        <div class="vacio">
          <?= icono('doc') ?>
          <p>Este navegador no muestra el PDF dentro de la página.</p>
          <a class="btn btn-primario" href="<?= e($urlArchivo) ?>"><?= icono('eye') ?> Abrir PDF</a>
        </div>
      </object>
    <?php else: ?>
      <a href="<?= e($urlArchivo) ?>" title="Ver a tamaño completo">
        <img class="visor-img" src="<?= e($urlArchivo) ?>" alt="Documento <?= e($doc['no_oficio']) ?>">
      </a>
    <?php endif; ?>
  </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
