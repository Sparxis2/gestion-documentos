<?php
/**
 * Encabezado común. Antes de incluirlo define:
 *   $titulo  -> título de la pestaña
 *   $seccion -> opción del menú que se resalta
 */
$yo         = usuario_actual();
$titulo     = isset($titulo) ? $titulo : '';
$seccion    = isset($seccion) ? $seccion : '';
$sistema    = config_valor('sistema', 'Gestión de Documentos');
$subtitulo  = config_valor('subtitulo', 'Oficina Digital');
$institucion = config_valor('institucion', 'UNACH');
$area       = config_valor('area', '');

$menu = [
    ['inicio',        'index.php',          'home',   'Inicio'],
    ['documentos',    'documentos.php',     'doc',    'Documentos'],
    ['nuevo',         'documento_form.php', 'plus',   'Nuevo Documento'],
    ['buscar',        'buscar.php',         'search', 'Buscar'],
    ['reportes',      'reportes.php',       'chart',  'Reportes'],
];
if (es_admin()) {
    $menu[] = ['usuarios', 'usuarios.php', 'users', 'Usuarios'];
}
$menu[] = ['conexion', 'conexion.php', 'phone', 'Conexión desde teléfono'];
$menu[] = ['configuracion', 'configuracion.php', 'gear', 'Configuración'];
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#2a2c57">
<title><?= e($titulo) ?> · <?= e($sistema) ?></title>
<link rel="icon" href="assets/img/icono.svg" type="image/svg+xml">
<link rel="stylesheet" href="assets/css/app.css?v=1">
</head>
<body>
<?php include __DIR__ . '/iconos.php'; ?>

<header class="topbar">
  <button class="btn-menu" type="button" id="abrirMenu" aria-label="Abrir menú"><?= icono('menu') ?></button>
  <a class="marca" href="index.php">
    <span class="escudo"><?= icono('emblema') ?></span>
    <span class="marca-inst"><?= e($institucion) ?></span>
  </a>
  <div class="marca-sis">
    <strong><?= e($sistema) ?></strong>
    <span><?= e($subtitulo) ?></span>
  </div>
  <div class="topbar-der">
    <a class="chip-usuario" href="configuracion.php" title="Mi cuenta">
      <?= avatar($yo) ?>
      <span class="chip-texto">
        <strong><?= e($yo['nombre']) ?></strong>
        <span><?= $yo['rol'] === 'admin' ? 'Administrador' : 'Capturista' ?><?= $area !== '' ? ' · ' . e($area) : '' ?></span>
      </span>
    </a>
    <a class="btn-salir" href="logout.php" title="Cerrar sesión" aria-label="Cerrar sesión"><?= icono('logout') ?></a>
  </div>
</header>

<div class="velo" id="velo"></div>

<aside class="sidebar" id="sidebar">
  <nav>
    <?php foreach ($menu as $m): ?>
      <a href="<?= e($m[1]) ?>" class="<?= $seccion === $m[0] ? 'activo' : '' ?>">
        <?= icono($m[2]) ?><span><?= e($m[3]) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>
  <a class="sidebar-salir" href="logout.php"><?= icono('logout') ?><span>Cerrar sesión</span></a>
</aside>

<main class="contenido">
<?php foreach (flashes() as $fl): ?>
  <div class="aviso aviso-<?= e($fl['tipo']) ?>" role="status"><?= e($fl['mensaje']) ?></div>
<?php endforeach; ?>
