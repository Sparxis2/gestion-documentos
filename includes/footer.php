<?php $seccion = isset($seccion) ? $seccion : ''; ?>
</main>

<!-- Barra inferior: solo se ve en teléfono -->
<nav class="tabbar">
  <a href="index.php" class="<?= $seccion === 'inicio' ? 'activo' : '' ?>"><?= icono('home') ?><span>Inicio</span></a>
  <a href="documentos.php" class="<?= $seccion === 'documentos' ? 'activo' : '' ?>"><?= icono('doc') ?><span>Documentos</span></a>
  <a href="documento_form.php" class="tab-nuevo <?= $seccion === 'nuevo' ? 'activo' : '' ?>"><i><?= icono('plus') ?></i><span>Nuevo</span></a>
  <a href="buscar.php" class="<?= $seccion === 'buscar' ? 'activo' : '' ?>"><?= icono('search') ?><span>Buscar</span></a>
  <a href="configuracion.php" class="<?= $seccion === 'configuracion' ? 'activo' : '' ?>"><?= icono('user') ?><span>Perfil</span></a>
</nav>

<!-- Ventana de confirmación (Bootstrap modal) -->
<div class="modal fade" id="modalConfirmar" tabindex="-1" aria-labelledby="modalConfirmarTitulo" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title" id="modalConfirmarTitulo">¿Estás seguro?</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body" id="modalConfirmarTexto"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-suave" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-peligro" id="modalConfirmarSi"><?= icono('trash') ?> Eliminar</button>
      </div>
    </div>
  </div>
</div>

<!-- Avisos flotantes (Bootstrap toasts) -->
<div class="toast-container position-fixed avisos-flotantes" id="avisos" aria-live="polite"></div>

<script src="assets/vendor/jquery/jquery.min.js"></script>
<script src="assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js?v=2"></script>
</body>
</html>
