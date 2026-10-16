<?php
/**
 * Tabla de documentos (se usa en Inicio, Documentos y Buscar).
 * En teléfono cada fila se convierte en una tarjeta gracias a data-label.
 */
function tabla_documentos(array $docs, $conAcciones = true)
{
    $volver = basename($_SERVER['SCRIPT_NAME'])
        . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
    $estatus = lista_estatus();
    ?>
    <div class="tabla-caja">
    <table class="tabla">
      <thead>
        <tr>
          <th>No. Oficio</th><th>Fecha</th><th>Asunto</th><th>Remitente</th>
          <th>Destinatario</th><th>Estatus</th>
          <?php if ($conAcciones): ?><th class="col-acciones">Acciones</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($docs as $d): ?>
        <?php $tieneArchivo = !empty($d['archivo']); ?>
        <tr>
          <td data-label="No. Oficio" class="celda-oficio">
            <?php if ($conAcciones): ?>
              <?= e($d['no_oficio']) ?>
            <?php else: ?>
              <a class="enlace-oficio" href="ver.php?id=<?= (int) $d['id'] ?>&amp;volver=<?= e(rawurlencode($volver)) ?>" title="Ver documento"><?= e($d['no_oficio']) ?></a>
            <?php endif; ?>
          </td>
          <td data-label="Fecha"><?= e(fecha_mx($d['fecha'])) ?></td>
          <td data-label="Asunto"><?= e($d['asunto']) ?></td>
          <td data-label="Remitente"><?= e($d['remitente']) ?></td>
          <td data-label="Destinatario"><?= e($d['destinatario']) ?></td>
          <td data-label="Estatus">
            <?php if ($conAcciones): ?>
              <form method="post" action="documento_accion.php" class="form-estatus">
                <?= csrf_campo() ?>
                <input type="hidden" name="accion" value="estatus">
                <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                <input type="hidden" name="volver" value="<?= e($volver) ?>">
                <select name="estatus" class="etiqueta etiqueta-<?= e($estatus[$d['estatus']]) ?>"
                        data-autoenviar aria-label="Cambiar estatus">
                  <?php foreach ($estatus as $nombre => $clase): ?>
                    <option value="<?= e($nombre) ?>" <?= $d['estatus'] === $nombre ? 'selected' : '' ?>><?= e($nombre) ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
            <?php else: ?>
              <span class="etiqueta etiqueta-<?= e($estatus[$d['estatus']]) ?>"><?= e($d['estatus']) ?></span>
            <?php endif; ?>
          </td>
          <?php if ($conAcciones): ?>
          <td data-label="Acciones" class="col-acciones">
            <div class="acciones">
              <?php if ($tieneArchivo): ?>
                <a class="btn-ico" href="ver.php?id=<?= (int) $d['id'] ?>&amp;volver=<?= e(rawurlencode($volver)) ?>" title="Ver documento" aria-label="Ver documento"><?= icono('eye') ?></a>
                <a class="btn-ico" href="archivo.php?id=<?= (int) $d['id'] ?>&amp;descargar=1" title="Descargar" aria-label="Descargar"><?= icono('download') ?></a>
              <?php else: ?>
                <span class="btn-ico apagado" title="Sin archivo"><?= icono('eye') ?></span>
                <span class="btn-ico apagado" title="Sin archivo"><?= icono('download') ?></span>
              <?php endif; ?>
              <a class="btn-ico" href="documento_form.php?id=<?= (int) $d['id'] ?>" title="Editar" aria-label="Editar"><?= icono('edit') ?></a>
              <?php if (es_admin()): ?>
                <form method="post" action="documento_accion.php"
                      data-confirmar="¿Eliminar el oficio <?= e($d['no_oficio']) ?>? También se borra su archivo.">
                  <?= csrf_campo() ?>
                  <input type="hidden" name="accion" value="eliminar">
                  <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                  <input type="hidden" name="volver" value="<?= e($volver) ?>">
                  <button class="btn-ico peligro" type="submit" title="Eliminar" aria-label="Eliminar"><?= icono('trash') ?></button>
                </form>
              <?php endif; ?>
            </div>
          </td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div>
    <?php
}

/** Pie de la tabla: "Mostrando 1 a 5 de 28 registros" y los números de página. */
function paginacion($total, $pagina, $porPagina)
{
    $totalPaginas = max(1, (int) ceil($total / $porPagina));
    $desde = $total === 0 ? 0 : ($pagina - 1) * $porPagina + 1;
    $hasta = min($total, $pagina * $porPagina);
    ?>
    <div class="paginacion">
      <span>Mostrando <?= $desde ?> a <?= $hasta ?> de <?= $total ?> registros</span>
      <?php if ($totalPaginas > 1): ?>
      <div class="paginas">
        <?php if ($pagina > 1): ?><a href="<?= e(url_con(['p' => $pagina - 1])) ?>" aria-label="Anterior">&lsaquo;</a><?php endif; ?>
        <?php foreach (ventana_paginas($pagina, $totalPaginas) as $n): ?>
          <a href="<?= e(url_con(['p' => $n])) ?>" class="<?= $n === $pagina ? 'activo' : '' ?>"><?= $n ?></a>
        <?php endforeach; ?>
        <?php if ($pagina < $totalPaginas): ?><a href="<?= e(url_con(['p' => $pagina + 1])) ?>" aria-label="Siguiente">&rsaquo;</a><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php
}
