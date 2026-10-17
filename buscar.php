<?php
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/tabla.php';
requerir_login();

$filtros = filtros_desde_url();
// Aquí solo se busca por texto; los demás filtros viven en Documentos.
$filtros['estatus'] = $filtros['desde'] = $filtros['hasta'] = '';

$pagina    = isset($_GET['p']) ? max(1, (int) $_GET['p']) : 1;
$porPagina = registros_por_pagina();
$docs      = [];
$total     = 0;

if ($filtros['q'] !== '') {
    list($docs, $total) = consultar_documentos($filtros, $pagina, $porPagina);
}

$titulo  = 'Buscar';
$seccion = 'buscar';
require __DIR__ . '/includes/header.php';
?>
<div class="encabezado">
  <h1><?= icono('search') ?> Buscar documento</h1>
</div>

<section class="panel">
  <form class="buscador" method="get" action="buscar.php" data-vivo="#resultados">
    <label class="campo campo-buscar">
      <span class="solo-lectores">Texto a buscar</span>
      <?= icono('search') ?>
      <input type="search" name="q" value="<?= e($filtros['q']) ?>" autofocus
             placeholder="No. de oficio, asunto, remitente o destinatario">
    </label>
    <button class="btn btn-primario" type="submit">Buscar</button>
  </form>

  <div id="resultados">
  <?php if ($filtros['q'] === ''): ?>
    <div class="vacio">
      <?= icono('search') ?>
      <p>Escribe el número de oficio o parte del asunto, remitente o destinatario.</p>
    </div>
  <?php elseif ($docs): ?>
    <p class="nota"><?= $total ?> resultado<?= $total === 1 ? '' : 's' ?> para «<?= e($filtros['q']) ?>»</p>
    <?php tabla_documentos($docs); ?>
    <?php paginacion($total, $pagina, $porPagina); ?>
  <?php else: ?>
    <div class="vacio">
      <?= icono('search') ?>
      <p>No se encontró ningún documento con «<?= e($filtros['q']) ?>».</p>
    </div>
  <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
