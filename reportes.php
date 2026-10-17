<?php
require __DIR__ . '/includes/init.php';
require __DIR__ . '/includes/tabla.php';
requerir_login();

$filtros = filtros_desde_url();
$filtros['q'] = ''; // el reporte se arma por fechas y estatus

list($where, $params) = where_documentos($filtros);
list($docs, $total)   = consultar_documentos($filtros, 1, 0);

/* ---------- Exportar a CSV (se abre en Excel) ---------- */
if (isset($_GET['exportar']) && $_GET['exportar'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="reporte_documentos_' . date('Ymd_His') . '.csv"');
    $salida = fopen('php://output', 'w');
    fwrite($salida, "\xEF\xBB\xBF"); // para que Excel respete los acentos
    fputcsv($salida, ['No. Oficio', 'Fecha', 'Asunto', 'Remitente', 'Destinatario', 'Estatus', 'Registrado por']);
    foreach ($docs as $d) {
        $fila = [
            $d['no_oficio'], fecha_mx($d['fecha']), $d['asunto'], $d['remitente'],
            $d['destinatario'], $d['estatus'], (string) $d['registrado_nombre'],
        ];
        foreach ($fila as $i => $celda) {
            // Evita que Excel interprete un texto como fórmula
            if ($celda !== '' && strpos('=+-@', $celda[0]) !== false) {
                $fila[$i] = "'" . $celda;
            }
        }
        fputcsv($salida, $fila);
    }
    fclose($salida);
    exit;
}

/* ---------- Resúmenes ---------- */
$porEstatus = ['Recibido' => 0, 'En proceso' => 0, 'Atendido' => 0];
$st = db()->prepare("SELECT d.estatus, COUNT(*) AS n FROM documentos d $where GROUP BY d.estatus");
$st->execute($params);
foreach ($st as $f) {
    $porEstatus[$f['estatus']] = (int) $f['n'];
}

$st = db()->prepare(
    "SELECT DATE_FORMAT(d.fecha, '%Y-%m') AS mes, COUNT(*) AS n
       FROM documentos d $where GROUP BY mes ORDER BY mes DESC LIMIT 12"
);
$st->execute($params);
$porMes = $st->fetchAll();

$st = db()->prepare(
    "SELECT d.remitente, COUNT(*) AS n
       FROM documentos d $where GROUP BY d.remitente ORDER BY n DESC, d.remitente ASC LIMIT 5"
);
$st->execute($params);
$porRemitente = $st->fetchAll();

$meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
$maxMes = 1;
foreach ($porMes as $m) {
    $maxMes = max($maxMes, (int) $m['n']);
}

$periodo = 'Todos los documentos';
if ($filtros['desde'] !== '' && $filtros['hasta'] !== '') {
    $periodo = 'Del ' . fecha_mx($filtros['desde']) . ' al ' . fecha_mx($filtros['hasta']);
} elseif ($filtros['desde'] !== '') {
    $periodo = 'Desde el ' . fecha_mx($filtros['desde']);
} elseif ($filtros['hasta'] !== '') {
    $periodo = 'Hasta el ' . fecha_mx($filtros['hasta']);
}
if ($filtros['estatus'] !== '') {
    $periodo .= ' · Estatus: ' . $filtros['estatus'];
}

$titulo  = 'Reportes';
$seccion = 'reportes';
require __DIR__ . '/includes/header.php';
?>
<div class="encabezado">
  <h1><?= icono('chart') ?> Reportes</h1>
  <div class="encabezado-botones no-imprimir">
    <button class="btn btn-suave" type="button" onclick="window.print()"><?= icono('print') ?> Imprimir</button>
    <a class="btn btn-primario" href="<?= e(url_con(['exportar' => 'csv'])) ?>"><?= icono('download') ?> Exportar CSV</a>
  </div>
</div>

<section class="panel no-imprimir">
  <form class="filtros filtros-reporte" method="get" action="reportes.php">
    <label class="campo">
      <span>Desde</span>
      <input type="date" name="desde" value="<?= e($filtros['desde']) ?>">
    </label>
    <label class="campo">
      <span>Hasta</span>
      <input type="date" name="hasta" value="<?= e($filtros['hasta']) ?>">
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
    <div class="filtros-botones">
      <button class="btn btn-primario" type="submit"><?= icono('filter') ?> Generar</button>
      <a class="btn btn-suave" href="reportes.php">Limpiar</a>
    </div>
  </form>
</section>

<p class="periodo"><strong><?= e(config_valor('institucion', '')) ?> · <?= e(config_valor('sistema', '')) ?></strong><br>
  <?= e($periodo) ?> · Generado el <?= date('d/m/Y H:i') ?></p>

<section class="row g-3 mb-3 resumen-bs">
  <div class="col-6 col-lg-3"><div class="tarjeta-dato h-100"><span class="dato-num"><?= $total ?></span><span class="dato-txt">Documentos</span></div></div>
  <div class="col-6 col-lg-3"><div class="tarjeta-dato h-100 dato-recibido"><span class="dato-num"><?= $porEstatus['Recibido'] ?></span><span class="dato-txt">Recibidos</span></div></div>
  <div class="col-6 col-lg-3"><div class="tarjeta-dato h-100 dato-proceso"><span class="dato-num"><?= $porEstatus['En proceso'] ?></span><span class="dato-txt">En proceso</span></div></div>
  <div class="col-6 col-lg-3"><div class="tarjeta-dato h-100 dato-atendido"><span class="dato-num"><?= $porEstatus['Atendido'] ?></span><span class="dato-txt">Atendidos</span></div></div>
</section>

<?php if ($total > 0): ?>
<div class="row g-3 mb-3">
  <div class="col-12 col-lg-6"><section class="panel h-100">
    <div class="panel-titulo"><h2>Documentos por estatus</h2></div>
    <?php foreach (lista_estatus() as $nombre => $clase): ?>
      <?php $pct = round($porEstatus[$nombre] * 100 / $total); ?>
      <div class="barra-fila">
        <span class="barra-nombre"><?= e($nombre) ?></span>
        <span class="barra"><i class="barra-<?= e($clase) ?>" style="width:<?= $pct ?>%"></i></span>
        <span class="barra-valor"><?= $porEstatus[$nombre] ?> · <?= $pct ?>%</span>
      </div>
    <?php endforeach; ?>

    <div class="panel-titulo separado"><h2>Remitentes con más documentos</h2></div>
    <table class="tabla-simple">
      <?php foreach ($porRemitente as $r): ?>
        <tr><td><?= e($r['remitente']) ?></td><td class="num"><?= (int) $r['n'] ?></td></tr>
      <?php endforeach; ?>
    </table>
  </section></div>

  <div class="col-12 col-lg-6"><section class="panel h-100">
    <div class="panel-titulo"><h2>Documentos por mes</h2></div>
    <?php foreach ($porMes as $m): ?>
      <?php list($anio, $num) = explode('-', $m['mes']); ?>
      <div class="barra-fila">
        <span class="barra-nombre"><?= e($meses[(int) $num] . ' ' . $anio) ?></span>
        <span class="barra"><i style="width:<?= round($m['n'] * 100 / $maxMes) ?>%"></i></span>
        <span class="barra-valor"><?= (int) $m['n'] ?></span>
      </div>
    <?php endforeach; ?>
  </section></div>
</div>

<section class="panel">
  <div class="panel-titulo"><h2>Detalle (<?= $total ?>)</h2></div>
  <?php tabla_documentos($docs, false); ?>
</section>
<?php else: ?>
<section class="panel">
  <div class="vacio">
    <?= icono('chart') ?>
    <p>No hay documentos en ese periodo.</p>
  </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
