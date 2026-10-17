<?php
/**
 * Registrar un documento nuevo o editar uno existente (?id=).
 * Los datos van a MySQL; el archivo va a la carpeta pdf/.
 */
require __DIR__ . '/includes/init.php';
requerir_login();

$id     = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$editar = $id > 0;
$doc    = null;

if ($editar) {
    $st = db()->prepare('SELECT * FROM documentos WHERE id = ?');
    $st->execute([$id]);
    $doc = $st->fetch();
    if (!$doc) {
        flash('error', 'Ese documento ya no existe.');
        redirigir('documentos.php');
    }
}

$datos = [
    'no_oficio'    => $doc ? $doc['no_oficio'] : '',
    'fecha'        => $doc ? $doc['fecha'] : date('Y-m-d'),
    'asunto'       => $doc ? $doc['asunto'] : '',
    'remitente'    => $doc ? $doc['remitente'] : '',
    'destinatario' => $doc ? $doc['destinatario'] : '',
    'estatus'      => $doc ? $doc['estatus'] : 'Recibido',
];
$errores = [];

if (post_demasiado_grande()) {
    $errores[] = 'El archivo es demasiado grande para el servidor. Usa uno más ligero (máximo ' . round(tamano_maximo() / 1048576) . ' MB).';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();

    foreach ($datos as $campo => $valor) {
        $datos[$campo] = isset($_POST[$campo]) ? trim((string) $_POST[$campo]) : '';
    }

    $limites = ['no_oficio' => 30, 'asunto' => 255, 'remitente' => 150, 'destinatario' => 150];
    $nombres = ['no_oficio' => 'No. de oficio', 'asunto' => 'Asunto', 'remitente' => 'Remitente', 'destinatario' => 'Destinatario'];
    foreach ($limites as $campo => $max) {
        if ($datos[$campo] === '') {
            $errores[] = 'Falta el campo ' . $nombres[$campo] . '.';
        } elseif (mb_strlen($datos[$campo]) > $max) {
            $errores[] = $nombres[$campo] . ' admite máximo ' . $max . ' caracteres.';
        }
    }
    if (!fecha_valida($datos['fecha'])) {
        $errores[] = 'La fecha no es válida.';
    }
    if (!isset(lista_estatus()[$datos['estatus']])) {
        $errores[] = 'El estatus no es válido.';
    }

    $subido = archivo_enviado();
    if (!$subido && (!$editar || empty($doc['archivo']))) {
        $errores[] = 'Adjunta el documento: toma una foto o selecciona un archivo PDF.';
    }

    if (!$errores) {
        $nuevoArchivo = null;
        try {
            if ($subido) {
                $nuevoArchivo = guardar_archivo($subido); // [nombre, original]
            }

            if ($editar) {
                $st = db()->prepare(
                    'UPDATE documentos
                        SET no_oficio = ?, fecha = ?, asunto = ?, remitente = ?, destinatario = ?, estatus = ?,
                            archivo = ?, archivo_original = ?
                      WHERE id = ?'
                );
                $st->execute([
                    $datos['no_oficio'], $datos['fecha'], $datos['asunto'], $datos['remitente'],
                    $datos['destinatario'], $datos['estatus'],
                    $nuevoArchivo ? $nuevoArchivo[0] : $doc['archivo'],
                    $nuevoArchivo ? $nuevoArchivo[1] : $doc['archivo_original'],
                    $id,
                ]);
                if ($nuevoArchivo) {
                    borrar_archivo($doc['archivo']); // el anterior ya no se usa
                }
                flash('ok', 'Documento ' . $datos['no_oficio'] . ' actualizado.');
            } else {
                $st = db()->prepare(
                    'INSERT INTO documentos
                        (no_oficio, fecha, asunto, remitente, destinatario, estatus, archivo, archivo_original, registrado_por)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $st->execute([
                    $datos['no_oficio'], $datos['fecha'], $datos['asunto'], $datos['remitente'],
                    $datos['destinatario'], $datos['estatus'],
                    $nuevoArchivo[0], $nuevoArchivo[1], usuario_actual()['id'],
                ]);
                flash('ok', 'Documento ' . $datos['no_oficio'] . ' registrado.');
            }
            redirigir('documentos.php');
        } catch (PDOException $ex) {
            // Si la BD falló, no se deja un archivo huérfano en pdf/
            if ($nuevoArchivo) {
                borrar_archivo($nuevoArchivo[0]);
            }
            throw $ex;
        } catch (RuntimeException $ex) {
            $errores[] = $ex->getMessage();
        }
    }
}

$titulo  = $editar ? 'Editar documento' : 'Registrar documento';
$seccion = $editar ? 'documentos' : 'nuevo';
require __DIR__ . '/includes/header.php';
?>
<div class="encabezado">
  <h1><?= icono('doc') ?> <?= $editar ? 'Editar Documento' : 'Registrar Documento' ?></h1>
</div>

<section class="panel panel-form">
  <?php if ($errores): ?>
    <div class="aviso aviso-error" role="alert">
      <?php foreach ($errores as $er): ?><div><?= e($er) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" id="formDocumento"
        data-limite="<?= tamano_maximo() ?>"
        data-archivo-obligatorio="<?= (!$editar || empty($doc['archivo'])) ? '1' : '0' ?>">
    <?= csrf_campo() ?>

    <div class="row gx-3">
      <label class="campo col-12 col-sm-6">
        <span>No. de Oficio *</span>
        <input type="text" name="no_oficio" value="<?= e($datos['no_oficio']) ?>" maxlength="30"
               placeholder="Ej. 045/2026" required>
      </label>
      <label class="campo col-12 col-sm-6">
        <span>Fecha *</span>
        <input type="date" name="fecha" value="<?= e($datos['fecha']) ?>" required>
      </label>
    </div>

    <label class="campo">
      <span>Asunto *</span>
      <input type="text" name="asunto" value="<?= e($datos['asunto']) ?>" maxlength="255"
             placeholder="Descripción breve del asunto del documento" required>
    </label>

    <div class="row gx-3">
      <label class="campo col-12 col-sm-6">
        <span>Remitente *</span>
        <input type="text" name="remitente" value="<?= e($datos['remitente']) ?>" maxlength="150"
               placeholder="Nombre o dependencia" required>
      </label>
      <label class="campo col-12 col-sm-6">
        <span>Destinatario *</span>
        <input type="text" name="destinatario" value="<?= e($datos['destinatario']) ?>" maxlength="150"
               placeholder="Nombre o área" required>
      </label>
    </div>

    <label class="campo">
      <span>Estatus *</span>
      <select name="estatus" required>
        <?php foreach (lista_estatus() as $nombre => $clase): ?>
          <option value="<?= e($nombre) ?>" <?= $datos['estatus'] === $nombre ? 'selected' : '' ?>><?= e($nombre) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <div class="campo">
      <span>Documento (foto o PDF) <?= (!$editar || empty($doc['archivo'])) ? '*' : '' ?></span>

      <?php if ($editar && !empty($doc['archivo'])): ?>
        <p class="nota">
          Archivo actual:
          <a href="ver.php?id=<?= (int) $doc['id'] ?>&amp;volver=<?= e(rawurlencode('documento_form.php?id=' . (int) $doc['id'])) ?>"><?= e($doc['archivo']) ?></a>.
          Si eliges otro, lo reemplaza.
        </p>
      <?php endif; ?>

      <div class="subida">
        <!-- capture abre la cámara del teléfono; en laptop abre el explorador de archivos -->
        <label class="subida-opcion">
          <input type="file" name="foto" id="entradaFoto" accept="image/*" capture="environment">
          <?= icono('camera') ?>
          <strong>Tomar foto</strong><small>del documento</small>
        </label>
        <label class="subida-opcion">
          <input type="file" name="archivo" id="entradaArchivo" accept="application/pdf,image/jpeg,image/png,image/webp">
          <?= icono('upload') ?>
          <strong>Seleccionar archivo</strong><small>PDF o imagen</small>
        </label>
      </div>

      <div class="archivo-elegido" id="archivoElegido" hidden>
        <?= icono('doc') ?>
        <div><strong id="archivoNombre"></strong><small id="archivoPeso"></small></div>
        <button type="button" class="btn-ico" id="archivoQuitar" aria-label="Quitar archivo"><?= icono('x') ?></button>
      </div>
      <p class="nota">PDF, JPG, PNG o WEBP · máximo <?= round(tamano_maximo() / 1048576) ?> MB. Las fotos se reducen automáticamente.</p>
    </div>

    <div class="form-botones">
      <a class="btn btn-suave" href="documentos.php">Cancelar</a>
      <button class="btn btn-primario" type="submit" id="botonGuardar">
        <?= icono('save') ?> <?= $editar ? 'Guardar cambios' : 'Guardar Documento' ?>
      </button>
    </div>
  </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
