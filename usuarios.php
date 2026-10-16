<?php
require __DIR__ . '/includes/init.php';
requerir_admin();

$yo = usuario_actual();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $accion = isset($_POST['accion']) ? (string) $_POST['accion'] : '';
    $id     = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    $st = db()->prepare('SELECT id, nombre, rol, activo, foto FROM usuarios WHERE id = ?');
    $st->execute([$id]);
    $u = $st->fetch();

    if (!$u) {
        flash('error', 'Ese usuario ya no existe.');
    } elseif ((int) $u['id'] === (int) $yo['id']) {
        flash('error', 'No puedes desactivar ni eliminar tu propia cuenta.');
    } elseif ($accion === 'alternar') {
        db()->prepare('UPDATE usuarios SET activo = 1 - activo WHERE id = ?')->execute([$id]);
        flash('ok', $u['nombre'] . ($u['activo'] ? ' fue desactivado.' : ' fue activado.'));
    } elseif ($accion === 'eliminar') {
        // Sus documentos se conservan; solo quedan sin "registrado por".
        db()->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]);
        borrar_foto_perfil($u['foto']);
        flash('ok', $u['nombre'] . ' fue eliminado.');
    }
    redirigir('usuarios.php');
}

$usuarios = db()->query(
    'SELECT u.*, (SELECT COUNT(*) FROM documentos d WHERE d.registrado_por = u.id) AS documentos
       FROM usuarios u ORDER BY u.nombre'
)->fetchAll();

$titulo  = 'Usuarios';
$seccion = 'usuarios';
require __DIR__ . '/includes/header.php';
?>
<div class="encabezado">
  <h1><?= icono('users') ?> Usuarios</h1>
  <a class="btn btn-primario" href="usuario_form.php"><?= icono('plus') ?> Nuevo Usuario</a>
</div>

<section class="panel">
  <div class="tabla-caja">
  <table class="tabla">
    <thead>
      <tr><th>Nombre</th><th>Usuario</th><th>Rol</th><th>Documentos</th><th>Estado</th><th class="col-acciones">Acciones</th></tr>
    </thead>
    <tbody>
    <?php foreach ($usuarios as $u): ?>
      <?php $soyYo = (int) $u['id'] === (int) $yo['id']; ?>
      <tr>
        <td data-label="Nombre" class="celda-oficio">
          <span class="usuario-nombre"><?= avatar($u, 'avatar-chico') ?><?= e($u['nombre']) ?><?= $soyYo ? ' (tú)' : '' ?></span>
        </td>
        <td data-label="Usuario"><?= e($u['usuario']) ?></td>
        <td data-label="Rol"><?= $u['rol'] === 'admin' ? 'Administrador' : 'Capturista' ?></td>
        <td data-label="Documentos"><?= (int) $u['documentos'] ?></td>
        <td data-label="Estado">
          <span class="etiqueta <?= $u['activo'] ? 'etiqueta-recibido' : 'etiqueta-inactivo' ?>"><?= $u['activo'] ? 'Activo' : 'Inactivo' ?></span>
        </td>
        <td data-label="Acciones" class="col-acciones">
          <div class="acciones">
            <a class="btn-ico" href="usuario_form.php?id=<?= (int) $u['id'] ?>" title="Editar" aria-label="Editar"><?= icono('edit') ?></a>
            <?php if (!$soyYo): ?>
              <form method="post">
                <?= csrf_campo() ?>
                <input type="hidden" name="accion" value="alternar">
                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                <button class="btn btn-suave btn-mini" type="submit"><?= $u['activo'] ? 'Desactivar' : 'Activar' ?></button>
              </form>
              <form method="post" data-confirmar="¿Eliminar a <?= e($u['nombre']) ?>? Sus documentos se conservan.">
                <?= csrf_campo() ?>
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                <button class="btn-ico peligro" type="submit" title="Eliminar" aria-label="Eliminar"><?= icono('trash') ?></button>
              </form>
            <?php endif; ?>
          </div>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <p class="nota">Administrador: acceso total. Capturista: registra, edita y consulta documentos, pero no elimina ni administra usuarios.</p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
