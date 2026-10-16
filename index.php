<?php
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/tabla.php';
requerir_login();

// Totales por estatus
$conteo = ['Recibido' => 0, 'En proceso' => 0, 'Atendido' => 0];
foreach (db()->query('SELECT estatus, COUNT(*) AS n FROM documentos GROUP BY estatus') as $f) {
    $conteo[$f['estatus']] = (int) $f['n'];
}
$total = array_sum($conteo);

// Últimos registrados
$ultimos = db()->query(
    'SELECT d.*, u.nombre AS registrado_nombre
       FROM documentos d LEFT JOIN usuarios u ON u.id = d.registrado_por
      ORDER BY d.creado_en DESC, d.id DESC LIMIT 5'
)->fetchAll();

$titulo  = 'Inicio';
$seccion = 'inicio';
require __DIR__ . '/includes/header.php';
?>
<div class="encabezado">
  <h1><?= icono('home') ?> Inicio</h1>
  <a class="btn btn-primario" href="documento_form.php"><?= icono('plus') ?> Nuevo Documento</a>
</div>

<section class="resumen">
  <a class="tarjeta-dato" href="documentos.php">
    <span class="dato-num"><?= $total ?></span>
    <span class="dato-txt">Documentos</span>
  </a>
  <a class="tarjeta-dato dato-recibido" href="documentos.php?estatus=Recibido">
    <span class="dato-num"><?= $conteo['Recibido'] ?></span>
    <span class="dato-txt">Recibidos</span>
  </a>
  <a class="tarjeta-dato dato-proceso" href="documentos.php?estatus=En+proceso">
    <span class="dato-num"><?= $conteo['En proceso'] ?></span>
    <span class="dato-txt">En proceso</span>
  </a>
  <a class="tarjeta-dato dato-atendido" href="documentos.php?estatus=Atendido">
    <span class="dato-num"><?= $conteo['Atendido'] ?></span>
    <span class="dato-txt">Atendidos</span>
  </a>
</section>

<section class="panel">
  <div class="panel-titulo">
    <h2>Últimos documentos registrados</h2>
    <a href="documentos.php">Ver todos</a>
  </div>
  <?php if ($ultimos): ?>
    <?php tabla_documentos($ultimos); ?>
  <?php else: ?>
    <div class="vacio">
      <?= icono('doc') ?>
      <p>Todavía no hay documentos registrados.</p>
      <a class="btn btn-primario" href="documento_form.php"><?= icono('plus') ?> Registrar el primero</a>
    </div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
