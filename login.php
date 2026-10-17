<?php
require __DIR__ . '/includes/init.php';

if (usuario_actual()) {
    redirigir('index.php');
}

$error   = '';
$usuario = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $usuario = isset($_POST['usuario']) ? trim((string) $_POST['usuario']) : '';
    $clave   = isset($_POST['clave']) ? (string) $_POST['clave'] : '';

    if (iniciar_sesion($usuario, $clave)) {
        redirigir('index.php');
    }
    $error = 'Usuario o contraseña incorrectos.';
}

$sistema     = config_valor('sistema', 'Gestión de Documentos');
$subtitulo   = config_valor('subtitulo', 'Oficina Digital');
$institucion = config_valor('institucion', 'UNACH');
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#2b1121">
<title>Iniciar sesión · <?= e($sistema) ?></title>
<link rel="icon" href="assets/img/icono.svg" type="image/svg+xml">
<!-- Bootstrap 5 (rejilla, modal, avisos y tooltips); después, el diseño propio -->
<link rel="stylesheet" href="assets/vendor/bootstrap/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/app.css?v=2">
</head>
<body class="pagina-login">
<?php include __DIR__ . '/includes/iconos.php'; ?>
<main class="login-caja">
  <div class="login-marca">
    <span class="escudo grande"><?= icono('emblema') ?></span>
    <div>
      <strong><?= e($institucion) ?></strong>
      <span><?= e($sistema) ?> · <?= e($subtitulo) ?></span>
    </div>
  </div>

  <h1>Iniciar sesión</h1>

  <?php if ($error !== ''): ?>
    <div class="aviso aviso-error" role="alert"><?= e($error) ?></div>
  <?php endif; ?>

  <form method="post" autocomplete="on">
    <?= csrf_campo() ?>
    <label class="campo">
      <span>Usuario</span>
      <input type="text" name="usuario" value="<?= e($usuario) ?>" required autofocus
             autocapitalize="none" autocomplete="username">
    </label>
    <label class="campo">
      <span>Contraseña</span>
      <input type="password" name="clave" required autocomplete="current-password">
    </label>
    <button class="btn btn-primario btn-ancho" type="submit">Entrar</button>
  </form>
</main>
</body>
</html>
