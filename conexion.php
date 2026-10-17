<?php
/**
 * Conexión desde teléfono.
 * Cada vez que se abre esta página, PHP le pregunta a Windows qué IP tiene
 * la laptop en ese momento y arma la dirección que se abre en el teléfono.
 */
require __DIR__ . '/includes/init.php';
requerir_login();

// Direcciones IPv4 de la computadora (sin 127.x, que solo sirve en la misma laptop)
$ips   = [];
$lista = @gethostbynamel(gethostname());
if (is_array($lista)) {
    foreach ($lista as $ip) {
        if (strpos($ip, '127.') !== 0 && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ips[] = $ip;
        }
    }
}

$carpeta = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/';
$puerto  = isset($_SERVER['SERVER_PORT']) ? (int) $_SERVER['SERVER_PORT'] : 80;
$sufijo  = in_array($puerto, [80, 443], true) ? '' : ':' . $puerto;
$host    = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$esLocal = in_array(strtolower(preg_replace('/:\d+$/', '', $host)), ['localhost', '127.0.0.1', '[::1]'], true);
// ¿Se entró por una IP de red (laptop) o por un dominio de internet (Vercel)?
$porIp   = $esLocal || (bool) filter_var(preg_replace('/:\d+$/', '', $host), FILTER_VALIDATE_IP);

// Direcciones a mostrar
$direcciones = [];
if (!$esLocal && $host !== '') {
    // Ya se entró por IP o por dominio: esa misma dirección sirve en cualquier dispositivo
    // Detrás del proxy de Vercel el protocolo real llega en X-Forwarded-Proto
    $esHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');
    $esquema = $esHttps ? 'https' : 'http';
    $direcciones[] = $esquema . '://' . $host . $carpeta;
} else {
    foreach ($ips as $ip) {
        $direcciones[] = 'http://' . $ip . $sufijo . $carpeta;
    }
}

$titulo  = 'Conexión desde teléfono';
$seccion = 'conexion';
require __DIR__ . '/includes/header.php';
?>
<div class="encabezado">
  <h1><?= icono('phone') ?> Conexión desde teléfono</h1>
  <button class="btn btn-suave" type="button" onclick="location.reload()">Actualizar</button>
</div>

<section class="panel">
  <?php if ($direcciones): ?>
    <p class="nota">
      <?php if ($esLocal): ?>
        Conecta el teléfono a la misma red WiFi que esta computadora y escanea el código o escribe la dirección en su navegador.
      <?php else: ?>
        <?php if ($porIp): ?>
          Esta es la dirección por la que estás entrando; sirve igual en cualquier otro dispositivo de la misma red.
        <?php else: ?>
          El sistema está publicado en internet: esta dirección abre desde cualquier teléfono, con WiFi o datos móviles.
        <?php endif; ?>
      <?php endif; ?>
    </p>

    <div class="conexiones">
      <?php foreach ($direcciones as $url): ?>
        <div class="conexion">
          <div class="qr" data-qr="<?= e($url) ?>" role="img" aria-label="Código QR de <?= e($url) ?>"></div>
          <p class="direccion"><?= e($url) ?></p>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($esLocal && count($direcciones) > 1): ?>
      <p class="nota">
        Aparece más de una porque la computadora tiene varios adaptadores de red.
        Usa la del WiFi: en el Símbolo del sistema escribe <strong>ipconfig</strong> y busca la
        «Dirección IPv4» del «Adaptador de LAN inalámbrica Wi-Fi».
      </p>
    <?php endif; ?>
  <?php else: ?>
    <div class="vacio">
      <?= icono('phone') ?>
      <p>No se detectó la IP de la red. Revisa que el WiFi esté conectado y presiona <strong>Actualizar</strong>.<br>
        También puedes escribir <strong>ipconfig</strong> en el Símbolo del sistema y abrir en el teléfono
        http://ESA-IP<?= e($sufijo . $carpeta) ?></p>
    </div>
  <?php endif; ?>
</section>

<?php if ($porIp): ?>
<section class="panel">
  <div class="panel-titulo"><h2>Si el teléfono no abre la página</h2></div>
  <ul class="lista-ayuda">
    <li>Verifica que el teléfono y la laptop estén en la <strong>misma red WiFi</strong>.</li>
    <li>La IP cambia al cambiar de red: presiona <strong>Actualizar</strong> después de conectarte a otro WiFi.</li>
    <li>Permite <strong>Apache HTTP Server</strong> en el Firewall de Windows (redes privadas y públicas).</li>
    <li>Algunas redes escolares bloquean la conexión entre dispositivos; en ese caso conecta la laptop al punto de acceso del teléfono.</li>
  </ul>
</section>
<?php endif; ?>

<script src="assets/js/qrcode.js"></script>
<script>
  document.querySelectorAll('[data-qr]').forEach(function (caja) {
    try {
      var qr = qrcode(0, 'M');
      qr.addData(caja.getAttribute('data-qr'));
      qr.make();
      caja.innerHTML = qr.createSvgTag({ cellSize: 6, margin: 2, scalable: true });
    } catch (err) {
      caja.remove();
    }
  });
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
