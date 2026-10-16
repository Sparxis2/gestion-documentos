<?php
require __DIR__ . '/includes/init.php';
requerir_admin();

$yo     = usuario_actual();
$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editar = $id > 0;
$u      = null;

if ($editar) {
    $st = db()->prepare('SELECT * FROM usuarios WHERE id = ?');
    $st->execute([$id]);
    $u = $st->fetch();
    if (!$u) {
        flash('error', 'Ese usuario ya no existe.');
        redirigir('usuarios.php');
    }
}

$datos = [
    'nombre'  => $u ? $u['nombre'] : '',
    'usuario' => $u ? $u['usuario'] : '',
    'rol'     => $u ? $u['rol'] : 'capturista',
];
$errores = [];
$soyYo   = $editar && (int) $u['id'] === (int) $yo['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $datos['nombre']  = isset($_POST['nombre']) ? trim((string) $_POST['nombre']) : '';
    $datos['usuario'] = isset($_POST['usuario']) ? trim((string) $_POST['usuario']) : '';
    $datos['rol']     = isset($_POST['rol']) ? (string) $_POST['rol'] : '';
    $clave            = isset($_POST['clave']) ? (string) $_POST['clave'] : '';

    if ($datos['nombre'] === '' || mb_strlen($datos['nombre']) > 120) {
        $errores[] = 'Escribe el nombre (máximo 120 caracteres).';
    }
    if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $datos['usuario'])) {
        $errores[] = 'El usuario debe tener de 3 a 50 caracteres: letras, números, punto, guion o guion bajo.';
    }
    if (!in_array($datos['rol'], ['admin', 'capturista'], true)) {
        $errores[] = 'El rol no es válido.';
    }
    if ($soyYo && $datos['rol'] !== 'admin') {
        $errores[] = 'No puedes quitarte a ti mismo el rol de administrador.';
    }
    if (!$editar && $clave === '') {
        $errores[] = 'Escribe una contraseña.';
    }
    if ($clave !== '' && strlen($clave) < 6) {
        $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
    }

    $st = db()->prepare('SELECT id FROM usuarios WHERE usuario = ? AND id <> ?');
    $st->execute([$datos['usuario'], $id]);
    if ($st->fetch()) {
        $errores[] = 'Ya existe otro usuario con ese nombre de usuario.';
    }

    if (!$errores) {
        if ($editar) {
            db()->prepare('UPDATE usuarios SET nombre = ?, usuario = ?, rol = ? WHERE id = ?')
                ->execute([$datos['nombre'], $datos['usuario'], $datos['rol'], $id]);
            if ($clave !== '') {
                db()->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?')
                    ->execute([password_hash($clave, PASSWORD_DEFAULT), $id]);
            }
            flash('ok', 'Usuario ' . $datos['usuario'] . ' actualizado.');
        } else {
            db()->prepare('INSERT INTO usuarios (nombre, usuario, password_hash, rol) VALUES (?, ?, ?, ?)')
                ->execute([$datos['nombre'], $datos['usuario'], password_hash($clave, PASSWORD_DEFAULT), $datos['rol']]);
            flash('ok', 'Usuario ' . $datos['usuario'] . ' creado.');
        }
        redirigir('usuarios.php');
    }
}

$titulo  = $editar ? 'Editar usuario' : 'Nuevo usuario';
$seccion = 'usuarios';
require __DIR__ . '/includes/header.php';
?>
<div class="encabezado">
  <h1><?= icono('users') ?> <?= $editar ? 'Editar Usuario' : 'Nuevo Usuario' ?></h1>
</div>

<section class="panel panel-form">
  <?php if ($errores): ?>
    <div class="aviso aviso-error" role="alert">
      <?php foreach ($errores as $er): ?><div><?= e($er) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" autocomplete="off">
    <?= csrf_campo() ?>
    <label class="campo">
      <span>Nombre completo *</span>
      <input type="text" name="nombre" value="<?= e($datos['nombre']) ?>" maxlength="120" required>
    </label>
    <div class="rejilla-2">
      <label class="campo">
        <span>Usuario *</span>
        <input type="text" name="usuario" value="<?= e($datos['usuario']) ?>" maxlength="50" required
               autocapitalize="none" autocomplete="off">
      </label>
      <label class="campo">
        <span>Rol *</span>
        <select name="rol" required>
          <option value="capturista" <?= $datos['rol'] === 'capturista' ? 'selected' : '' ?>>Capturista</option>
          <option value="admin" <?= $datos['rol'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
        </select>
      </label>
    </div>
    <label class="campo">
      <span>Contraseña <?= $editar ? '(déjala vacía para no cambiarla)' : '*' ?></span>
      <input type="password" name="clave" minlength="6" autocomplete="new-password" <?= $editar ? '' : 'required' ?>>
    </label>

    <div class="form-botones">
      <a class="btn btn-suave" href="usuarios.php">Cancelar</a>
      <button class="btn btn-primario" type="submit"><?= icono('save') ?> Guardar</button>
    </div>
  </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
