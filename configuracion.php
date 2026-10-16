<?php
require __DIR__ . '/includes/init.php';
requerir_login();

$yo = usuario_actual();

if (post_demasiado_grande()) {
    flash('error', 'La foto es demasiado grande para el servidor. Usa una más ligera.');
    redirigir('configuracion.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $accion = isset($_POST['accion']) ? (string) $_POST['accion'] : '';

    if ($accion === 'foto') {
        $f = isset($_FILES['foto']) ? $_FILES['foto'] : null;
        if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) {
            flash('error', 'Elige una foto.');
        } else {
            try {
                $nueva = guardar_foto_perfil($f, $yo['id']);
                db()->prepare('UPDATE usuarios SET foto = ? WHERE id = ?')->execute([$nueva, $yo['id']]);
                borrar_foto_perfil($yo['foto']); // la anterior ya no se usa
                flash('ok', 'Foto de perfil actualizada.');
            } catch (PDOException $ex) {
                throw $ex;
            } catch (RuntimeException $ex) {
                flash('error', $ex->getMessage());
            }
        }
    } elseif ($accion === 'quitar_foto') {
        db()->prepare('UPDATE usuarios SET foto = NULL WHERE id = ?')->execute([$yo['id']]);
        borrar_foto_perfil($yo['foto']);
        flash('ok', 'Foto de perfil eliminada.');
    } elseif ($accion === 'cuenta') {
        $nombre = isset($_POST['nombre']) ? trim((string) $_POST['nombre']) : '';
        if ($nombre === '' || mb_strlen($nombre) > 120) {
            flash('error', 'Escribe tu nombre (máximo 120 caracteres).');
        } else {
            db()->prepare('UPDATE usuarios SET nombre = ? WHERE id = ?')->execute([$nombre, $yo['id']]);
            flash('ok', 'Nombre actualizado.');
        }
    } elseif ($accion === 'clave') {
        $actual  = isset($_POST['actual']) ? (string) $_POST['actual'] : '';
        $nueva   = isset($_POST['nueva']) ? (string) $_POST['nueva'] : '';
        $repetir = isset($_POST['repetir']) ? (string) $_POST['repetir'] : '';

        $st = db()->prepare('SELECT password_hash FROM usuarios WHERE id = ?');
        $st->execute([$yo['id']]);
        $hash = (string) $st->fetchColumn();

        if (!password_verify($actual, $hash)) {
            flash('error', 'La contraseña actual no es correcta.');
        } elseif (strlen($nueva) < 6) {
            flash('error', 'La nueva contraseña debe tener al menos 6 caracteres.');
        } elseif ($nueva !== $repetir) {
            flash('error', 'La confirmación no coincide con la nueva contraseña.');
        } else {
            db()->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?')
                ->execute([password_hash($nueva, PASSWORD_DEFAULT), $yo['id']]);
            flash('ok', 'Contraseña cambiada.');
        }
    } elseif ($accion === 'sistema' && es_admin()) {
        $campos = ['institucion' => 60, 'sistema' => 60, 'subtitulo' => 60, 'area' => 60];
        $ok = true;
        foreach ($campos as $clave => $max) {
            $valor = isset($_POST[$clave]) ? trim((string) $_POST[$clave]) : '';
            if (($clave !== 'area' && $clave !== 'subtitulo' && $valor === '') || mb_strlen($valor) > $max) {
                $ok = false;
                continue;
            }
            config_guardar($clave, $valor);
        }
        $porPagina = isset($_POST['por_pagina']) ? (int) $_POST['por_pagina'] : 10;
        if (in_array($porPagina, [5, 10, 20, 50], true)) {
            config_guardar('por_pagina', (string) $porPagina);
        }
        flash($ok ? 'ok' : 'error', $ok ? 'Configuración guardada.' : 'Institución y nombre del sistema son obligatorios (máximo 60 caracteres).');
    }
    redirigir('configuracion.php');
}

$titulo  = 'Configuración';
$seccion = 'configuracion';
require __DIR__ . '/includes/header.php';
?>
<div class="encabezado">
  <h1><?= icono('gear') ?> Configuración</h1>
</div>

<div class="rejilla-2 rejilla-paneles">
  <section class="panel">
    <div class="panel-titulo"><h2>Mi cuenta</h2></div>

    <div class="perfil-foto">
      <?= avatar($yo, 'avatar-grande') ?>
      <div class="perfil-foto-botones">
        <form method="post" enctype="multipart/form-data" id="formFoto">
          <?= csrf_campo() ?>
          <input type="hidden" name="accion" value="foto">
          <label class="btn btn-suave btn-archivo">
            <input type="file" name="foto" id="entradaPerfil" accept="image/jpeg,image/png,image/webp">
            <?= icono('camera') ?> <?= empty($yo['foto']) ? 'Poner foto' : 'Cambiar foto' ?>
          </label>
        </form>
        <?php if (!empty($yo['foto'])): ?>
          <form method="post" data-confirmar="¿Quitar tu foto de perfil?">
            <?= csrf_campo() ?>
            <input type="hidden" name="accion" value="quitar_foto">
            <button class="btn btn-suave" type="submit"><?= icono('trash') ?> Quitar</button>
          </form>
        <?php endif; ?>
        <p class="nota">JPG, PNG o WEBP. Se recorta en cuadro automáticamente.</p>
      </div>
    </div>

    <form method="post">
      <?= csrf_campo() ?>
      <input type="hidden" name="accion" value="cuenta">
      <label class="campo">
        <span>Nombre</span>
        <input type="text" name="nombre" value="<?= e($yo['nombre']) ?>" maxlength="120" required>
      </label>
      <p class="nota">Usuario: <strong><?= e($yo['usuario']) ?></strong> · Rol: <?= $yo['rol'] === 'admin' ? 'Administrador' : 'Capturista' ?></p>
      <div class="form-botones">
        <button class="btn btn-primario" type="submit"><?= icono('save') ?> Guardar nombre</button>
      </div>
    </form>
  </section>

  <section class="panel">
    <div class="panel-titulo"><h2>Cambiar contraseña</h2></div>
    <form method="post" autocomplete="off">
      <?= csrf_campo() ?>
      <input type="hidden" name="accion" value="clave">
      <label class="campo">
        <span>Contraseña actual</span>
        <input type="password" name="actual" required autocomplete="current-password">
      </label>
      <div class="rejilla-2">
        <label class="campo">
          <span>Nueva contraseña</span>
          <input type="password" name="nueva" minlength="6" required autocomplete="new-password">
        </label>
        <label class="campo">
          <span>Repetir nueva</span>
          <input type="password" name="repetir" minlength="6" required autocomplete="new-password">
        </label>
      </div>
      <div class="form-botones">
        <button class="btn btn-primario" type="submit"><?= icono('save') ?> Cambiar contraseña</button>
      </div>
    </form>
  </section>

  <?php if (es_admin()): ?>
  <section class="panel">
    <div class="panel-titulo"><h2>Datos del sistema</h2></div>
    <form method="post">
      <?= csrf_campo() ?>
      <input type="hidden" name="accion" value="sistema">
      <div class="rejilla-2">
        <label class="campo">
          <span>Institución *</span>
          <input type="text" name="institucion" value="<?= e(config_valor('institucion')) ?>" maxlength="60" required>
        </label>
        <label class="campo">
          <span>Área</span>
          <input type="text" name="area" value="<?= e(config_valor('area')) ?>" maxlength="60">
        </label>
      </div>
      <div class="rejilla-2">
        <label class="campo">
          <span>Nombre del sistema *</span>
          <input type="text" name="sistema" value="<?= e(config_valor('sistema')) ?>" maxlength="60" required>
        </label>
        <label class="campo">
          <span>Subtítulo</span>
          <input type="text" name="subtitulo" value="<?= e(config_valor('subtitulo')) ?>" maxlength="60">
        </label>
      </div>
      <label class="campo">
        <span>Registros por página en los listados</span>
        <select name="por_pagina">
          <?php foreach ([5, 10, 20, 50] as $n): ?>
            <option value="<?= $n ?>" <?= registros_por_pagina() === $n ? 'selected' : '' ?>><?= $n ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div class="form-botones">
        <button class="btn btn-primario" type="submit"><?= icono('save') ?> Guardar</button>
      </div>
    </form>
  </section>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
