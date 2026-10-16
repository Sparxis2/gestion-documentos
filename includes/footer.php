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

<script src="assets/js/app.js?v=1"></script>
</body>
</html>
