/* Gestión de Documentos · comportamiento de la interfaz */
(function () {
  'use strict';

  /* ---------- Menú deslizable en teléfono ---------- */
  var abrir = document.getElementById('abrirMenu');
  var velo = document.getElementById('velo');
  if (abrir && velo) {
    abrir.addEventListener('click', function () { document.body.classList.toggle('menu-abierto'); });
    velo.addEventListener('click', function () { document.body.classList.remove('menu-abierto'); });
    document.addEventListener('keydown', function (ev) {
      if (ev.key === 'Escape') { document.body.classList.remove('menu-abierto'); }
    });
  }

  /* ---------- Confirmar antes de eliminar ---------- */
  document.querySelectorAll('form[data-confirmar]').forEach(function (form) {
    form.addEventListener('submit', function (ev) {
      if (!window.confirm(form.getAttribute('data-confirmar'))) { ev.preventDefault(); }
    });
  });

  /* ---------- Cambiar estatus desde la tabla ---------- */
  document.querySelectorAll('select[data-autoenviar]').forEach(function (sel) {
    sel.addEventListener('change', function () { sel.form.submit(); });
  });

  /* ---------- Los avisos verdes se ocultan solos ---------- */
  document.querySelectorAll('.aviso-ok').forEach(function (aviso) {
    setTimeout(function () { aviso.style.display = 'none'; }, 6000);
  });

  /* ---------- Foto de perfil: recorte cuadrado y envío automático ---------- */
  var formFoto = document.getElementById('formFoto');
  var entradaPerfil = document.getElementById('entradaPerfil');
  if (formFoto && entradaPerfil) {
    entradaPerfil.addEventListener('change', function () {
      var file = entradaPerfil.files && entradaPerfil.files[0];
      if (!file) { return; }

      function enviar() { formFoto.submit(); }

      if (typeof DataTransfer === 'undefined' || !/^image\//.test(file.type)) { enviar(); return; }

      var url = URL.createObjectURL(file);
      var img = new Image();
      img.onload = function () {
        try {
          // Toma el cuadrado central y lo deja en 400 x 400 px
          var lado = Math.min(img.naturalWidth, img.naturalHeight);
          var sx = (img.naturalWidth - lado) / 2;
          var sy = (img.naturalHeight - lado) / 2;
          var lienzo = document.createElement('canvas');
          lienzo.width = lienzo.height = Math.min(400, lado);
          var ctx = lienzo.getContext('2d');
          ctx.fillStyle = '#fff';
          ctx.fillRect(0, 0, lienzo.width, lienzo.height);
          ctx.drawImage(img, sx, sy, lado, lado, 0, 0, lienzo.width, lienzo.height);
          lienzo.toBlob(function (blob) {
            URL.revokeObjectURL(url);
            if (blob) {
              try {
                var dt = new DataTransfer();
                dt.items.add(new File([blob], 'perfil.jpg', { type: 'image/jpeg' }));
                entradaPerfil.files = dt.files;
              } catch (err) { /* se envía la original */ }
            }
            enviar();
          }, 'image/jpeg', 0.88);
        } catch (err) {
          URL.revokeObjectURL(url);
          enviar();
        }
      };
      img.onerror = function () { URL.revokeObjectURL(url); enviar(); };
      img.src = url;
    });
  }

  /* ---------- Formulario de documento: foto o PDF ---------- */
  var form = document.getElementById('formDocumento');
  if (!form) { return; }

  var entradaFoto = document.getElementById('entradaFoto');
  var entradaArchivo = document.getElementById('entradaArchivo');
  var caja = document.getElementById('archivoElegido');
  var cajaNombre = document.getElementById('archivoNombre');
  var cajaPeso = document.getElementById('archivoPeso');
  var quitar = document.getElementById('archivoQuitar');
  var guardar = document.getElementById('botonGuardar');
  var LIMITE = parseInt(form.getAttribute('data-limite'), 10) || 10 * 1024 * 1024;

  function peso(bytes) {
    return bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB';
  }

  function mostrar(file) {
    cajaNombre.textContent = file.name;
    cajaPeso.textContent = peso(file.size);
    caja.hidden = false;
  }

  function limpiar() {
    entradaFoto.value = '';
    entradaArchivo.value = '';
    caja.hidden = true;
  }

  /*
   * Las fotos del teléfono pesan varios MB. Antes de enviarlas se reducen a
   * 2000 px por lado en JPG para que suban rápido por WiFi. Si el navegador
   * no lo permite, se envía la foto original.
   */
  function reducirFoto(file) {
    return new Promise(function (resolve) {
      if (!/^image\/(jpeg|png|webp)$/.test(file.type) || file.size < 1500000 ||
          typeof DataTransfer === 'undefined') {
        resolve(file);
        return;
      }
      var url = URL.createObjectURL(file);
      var img = new Image();
      img.onload = function () {
        try {
          var escala = Math.min(1, 2000 / Math.max(img.naturalWidth, img.naturalHeight));
          var lienzo = document.createElement('canvas');
          lienzo.width = Math.round(img.naturalWidth * escala);
          lienzo.height = Math.round(img.naturalHeight * escala);
          var ctx = lienzo.getContext('2d');
          ctx.fillStyle = '#fff';
          ctx.fillRect(0, 0, lienzo.width, lienzo.height);
          ctx.drawImage(img, 0, 0, lienzo.width, lienzo.height);
          lienzo.toBlob(function (blob) {
            URL.revokeObjectURL(url);
            if (!blob || blob.size >= file.size) { resolve(file); return; }
            var nombre = file.name.replace(/\.[^.]+$/, '') + '.jpg';
            resolve(new File([blob], nombre, { type: 'image/jpeg' }));
          }, 'image/jpeg', 0.82);
        } catch (err) {
          URL.revokeObjectURL(url);
          resolve(file);
        }
      };
      img.onerror = function () { URL.revokeObjectURL(url); resolve(file); };
      img.src = url;
    });
  }

  function alElegir(entrada, otra) {
    entrada.addEventListener('change', function () {
      var file = entrada.files && entrada.files[0];
      if (!file) { return; }
      otra.value = ''; // solo se envía uno: la foto o el archivo

      guardar.disabled = true;
      reducirFoto(file).then(function (final) {
        if (final !== file) {
          try {
            var dt = new DataTransfer();
            dt.items.add(final);
            entrada.files = dt.files;
          } catch (err) {
            final = file;
          }
        }
        guardar.disabled = false;
        if (final.size > LIMITE) {
          limpiar();
          window.alert('El archivo pesa ' + peso(final.size) + '. El máximo es ' + peso(LIMITE) + '.');
          return;
        }
        mostrar(final);
      });
    });
  }

  alElegir(entradaFoto, entradaArchivo);
  alElegir(entradaArchivo, entradaFoto);
  quitar.addEventListener('click', limpiar);

  form.addEventListener('submit', function (ev) {
    var hay = entradaFoto.files.length > 0 || entradaArchivo.files.length > 0;
    if (form.getAttribute('data-archivo-obligatorio') === '1' && !hay) {
      ev.preventDefault();
      window.alert('Adjunta el documento: toma una foto o selecciona un archivo PDF.');
      return;
    }
    guardar.disabled = true; // evita registrar dos veces con doble toque
  });

  // Al volver con el botón "atrás" el botón debe quedar activo otra vez
  window.addEventListener('pageshow', function () { guardar.disabled = false; });
})();
