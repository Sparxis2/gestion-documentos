<?php
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/tabla.php';
requerir_login();

$filtros   = filtros_desde_url();
$pagina    = isset($_GET['p']) ? max(1, (int) $_GET['p']) : 1;
$porPagina = registros_por_pagina();

list($docs, $total) = consultar_documentos($filtros, $pagina, $porPagina);

// Si borraron el último registro de una página, regresa a la última que exista.
$totalPaginas = max(1, (int) ceil($total / $porPagina));
if ($pagina > $totalPaginas) {
    redirigir(url_con(['p' => $totalPaginas]));
}

$hayFiltros = $filtros['q'] !== '' || $filtros['estatus'] !== '' || $filtros['desde'] !== '' || $filtros['hasta'] !== '';

$titulo  = 'Documentos';
$seccion = 'documentos';
require __DIR__ . '/includes/header.php';
?>
<div class="encabezado">
  <h1><?= icono('doc') ?> Listado de Documentos</h1>
  <a class="btn btn-primario" href="documento_form.php"><?= icono('plus') ?> Nuevo Documento</a>
</div>

<section class="panel">
  <form class="filtros" method="get" action="documentos.php" data-vivo="#resultados">
    <label class="campo campo-buscar">
      <span class="solo-lectores">Buscar</span>
      <?= icono('search') ?>
      <input type="search" name="q" value="<?= e($filtros['q']) ?>"
             placeholder="Buscar por No. de oficio, asunto, remitente o destinatario...">
    </label>
    <label class="campo">
      <span>Estatus</span>
      <select name="estatus">
        <option value="">Todos</option>
        <?php foreach (lista_estatus() as $nombre => $clase): ?>
          <option value="<?= e($nombre) ?>" <?= $filtros['estatus'] === $nombre ? 'selected' : '' ?>><?= e($nombre) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="campo">
      <span>Desde</span>
      <input type="date" name="desde" value="<?= e($filtros['desde']) ?>">
    </label>
    <label class="campo">
      <span>Hasta</span>
      <input type="date" name="hasta" value="<?= e($filtros['hasta']) ?>">
    </label>
    <div class="filtros-botones">
      <button class="btn btn-primario" type="submit"><?= icono('filter') ?> Filtrar</button>
      <?php if ($hayFiltros): ?><a class="btn btn-suave" href="documentos.php">Limpiar</a><?php endif; ?>
    </div>
  </form>

  <div id="resultados">
  <?php if ($docs): ?>
    <?php tabla_documentos($docs); ?>
    <?php paginacion($total, $pagina, $porPagina); ?>
  <?php else: ?>
    <div class="vacio">
      <?= icono('doc') ?>
      <?php if ($hayFiltros): ?>
        <p>Ningún documento coincide con esos filtros.</p>
        <a class="btn btn-suave" href="documentos.php">Quitar filtros</a>
      <?php else: ?>
        <p>Todavía no hay documentos registrados.</p>
        <a class="btn btn-primario" href="documento_form.php"><?= icono('plus') ?> Registrar el primero</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
