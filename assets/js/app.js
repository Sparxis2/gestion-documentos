/*
 * Gestión de Documentos · comportamiento de la interfaz
 * Usa jQuery 3.7 y los componentes de Bootstrap 5 (Modal, Toast y Tooltip).
 */
$(function () {
  'use strict';

  var $body = $('body');

  /* ============================================================
     Avisos flotantes (Bootstrap Toast)
     ============================================================ */
  function avisar(mensaje, tipo) {
    var $toast = $(
      '<div class="toast aviso-toast" role="status" aria-live="polite" aria-atomic="true">' +
        '<div class="d-flex"><div class="toast-body"></div>' +
        '<button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button></div>' +
      '</div>'
    );
    $toast.addClass(tipo === 'error' ? 'aviso-toast-error' : 'aviso-toast-ok');
    $toast.find('.toast-body').text(mensaje);
    $('#avisos').append($toast);
    var toast = new bootstrap.Toast($toast[0], { delay: tipo === 'error' ? 7000 : 4000 });
    $toast.on('hidden.bs.toast', function () { $toast.remove(); });
    toast.show();
  }
  window.avisar = avisar;

  /* Los avisos que manda el servidor (verdes) se desvanecen solos */
  $('.aviso-ok').delay(6000).fadeOut(400);

  /* ============================================================
     Menú deslizable en teléfono
     ============================================================ */
  $('#abrirMenu').on('click', function () { $body.toggleClass('menu-abierto'); });
  $('#velo').on('click', function () { $body.removeClass('menu-abierto'); });
  $(document).on('keydown', function (ev) {
    if (ev.key === 'Escape') { $body.removeClass('menu-abierto'); }
  });

  /* ============================================================
     Tooltips de Bootstrap en los botones de icono
     (solo con ratón: en pantallas táctiles estorbarían el toque)
     ============================================================ */
  var conRaton = window.matchMedia && window.matchMedia('(hover: hover)').matches;
  function activarTooltips($zona) {
    if (!conRaton) { return; }
    $zona.find('.btn-ico[title]').each(function () {
      bootstrap.Tooltip.getOrCreateInstance(this, { placement: 'top', trigger: 'hover' });
    });
  }
  activarTooltips($body);

  /* ============================================================
     Confirmación con ventana modal de Bootstrap
     (reemplaza la alerta del navegador al eliminar)
     ============================================================ */
  var $modal = $('#modalConfirmar');
  var modal = $modal.length ? new bootstrap.Modal($modal[0]) : null;
  var formPendiente = null;

  $(document).on('submit', 'form[data-confirmar]', function (ev) {
    if (!modal || $(this).data('confirmado')) { return; } // sin modal: se envía normal
    ev.preventDefault();
    formPendiente = this;
    $('#modalConfirmarTexto').text($(this).attr('data-confirmar'));
    modal.show();
  });

  $('#modalConfirmarSi').on('click', function () {
    if (!formPendiente) { return; }
    $(formPendiente).data('confirmado', true);
    $(this).prop('disabled', true);
    formPendiente.submit();
  });

  $modal.on('hidden.bs.modal', function () {
    formPendiente = null;
    $('#modalConfirmarSi').prop('disabled', false);
  });

  /* ============================================================
     Cambiar estatus desde la tabla sin recargar (AJAX)
     ============================================================ */
  $(document).on('change', 'select[data-autoenviar]', function () {
    var $sel = $(this);
    var $form = $sel.closest('form');
    var anterior = $sel.data('anterior') || $sel.find('option[selected]').val();

    $sel.prop('disabled', true);
    $.ajax({
      url: $form.attr('action'),
      method: 'POST',
      data: $form.serialize() + '&estatus=' + encodeURIComponent($sel.val()),
      dataType: 'json'
    }).done(function (r) {
      if (r.ok) {
        $sel.removeClass('etiqueta-recibido etiqueta-proceso etiqueta-atendido').addClass('etiqueta-' + r.clase);
        $sel.data('anterior', r.estatus);
        avisar(r.mensaje, 'ok');
      } else {
        $sel.val(anterior);
        avisar(r.mensaje, 'error');
      }
    }).fail(function () {
      // Si AJAX falla (sesión vencida, sin conexión...), se envía el formulario normal
      $sel.prop('disabled', false);
      $form.trigger('submit');
    }).always(function () {
      $sel.prop('disabled', false);
    });
  });

  /* ============================================================
     Búsqueda en vivo: los resultados se actualizan mientras se escribe
     ============================================================ */
  $('form[data-vivo]').each(function () {
    var $form = $(this);
    var destino = $form.attr('data-vivo');
    var espera = null;
    var peticion = null;

    function actualizar() {
      var url = $form.attr('action') + '?' + $form.serialize();
      if (peticion) { peticion.abort(); }
      $(destino).addClass('cargando');
      peticion = $.get(url).done(function (html) {
        var $nuevo = $('<div>').append($.parseHTML(html)).find(destino);
        $(destino).html($nuevo.html());
        activarTooltips($(destino));
        if (window.history && history.replaceState) { history.replaceState(null, '', url); }
      }).always(function () {
        $(destino).removeClass('cargando');
      });
    }

    $form.find('input[type=search]').on('input', function () {
      clearTimeout(espera);
      espera = setTimeout(actualizar, 350);
    });
    $form.find('select, input[type=date]').on('change', actualizar);
  });

  /* ============================================================
     Foto de perfil: recorte cuadrado y envío automático
     ============================================================ */
  var $entradaPerfil = $('#entradaPerfil');
  $entradaPerfil.on('change', function () {
    var file = this.files && this.files[0];
    var input = this;
    if (!file) { return; }

    function enviar() { $('#formFoto')[0].submit(); }

    if (typeof DataTransfer === 'undefined' || !/^image\//.test(file.type)) { enviar(); return; }

    var url = URL.createObjectURL(file);
    var img = new Image();
    img.onload = function () {
      try {
        // Toma el cuadrado central y lo deja en 400 x 400 px
        var lado = Math.min(img.naturalWidth, img.naturalHeight);
        var lienzo = document.createElement('canvas');
        lienzo.width = lienzo.height = Math.min(400, lado);
        var ctx = lienzo.getContext('2d');
        ctx.fillStyle = '#fff';
        ctx.fillRect(0, 0, lienzo.width, lienzo.height);
        ctx.drawImage(img, (img.naturalWidth - lado) / 2, (img.naturalHeight - lado) / 2, lado, lado,
                      0, 0, lienzo.width, lienzo.height);
        lienzo.toBlob(function (blob) {
          URL.revokeObjectURL(url);
          if (blob) {
            try {
              var dt = new DataTransfer();
              dt.items.add(new File([blob], 'perfil.jpg', { type: 'image/jpeg' }));
              input.files = dt.files;
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

  /* ============================================================
     Formulario de documento: foto o PDF
     ============================================================ */
  var $form = $('#formDocumento');
  if (!$form.length) { return; }

  var $entradaFoto = $('#entradaFoto');
  var $entradaArchivo = $('#entradaArchivo');
  var $caja = $('#archivoElegido');
  var $guardar = $('#botonGuardar');
  var LIMITE = parseInt($form.attr('data-limite'), 10) || 10 * 1024 * 1024;

  function peso(bytes) {
    return bytes >= 1048576 ? (bytes / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(bytes / 1024)) + ' KB';
  }

  function mostrar(file) {
    $('#archivoNombre').text(file.name);
    $('#archivoPeso').text(peso(file.size));
    $caja.prop('hidden', false).hide().fadeIn(200);
  }

  function limpiar() {
    $entradaFoto.val('');
    $entradaArchivo.val('');
    $caja.prop('hidden', true);
  }

  /*
   * Las fotos del teléfono pesan varios MB. Antes de enviarlas se reducen a
   * 2000 px por lado en JPG para que suban rápido. Si el navegador no lo
   * permite, se envía la foto original.
   */
  function reducirFoto(file) {
    var listo = $.Deferred();
    if (!/^image\/(jpeg|png|webp)$/.test(file.type) || file.size < 1500000 || typeof DataTransfer === 'undefined') {
      return listo.resolve(file).promise();
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
          if (!blob || blob.size >= file.size) { listo.resolve(file); return; }
          listo.resolve(new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' }));
        }, 'image/jpeg', 0.82);
      } catch (err) {
        URL.revokeObjectURL(url);
        listo.resolve(file);
      }
    };
    img.onerror = function () { URL.revokeObjectURL(url); listo.resolve(file); };
    img.src = url;
    return listo.promise();
  }

  function alElegir($entrada, $otra) {
    $entrada.on('change', function () {
      var input = this;
      var file = input.files && input.files[0];
      if (!file) { return; }
      $otra.val(''); // solo se envía uno: la foto o el archivo

      $guardar.prop('disabled', true);
      reducirFoto(file).done(function (final) {
        if (final !== file) {
          try {
            var dt = new DataTransfer();
            dt.items.add(final);
            input.files = dt.files;
          } catch (err) {
            final = file;
          }
        }
        $guardar.prop('disabled', false);
        if (final.size > LIMITE) {
          limpiar();
          avisar('El archivo pesa ' + peso(final.size) + '. El máximo es ' + peso(LIMITE) + '.', 'error');
          return;
        }
        mostrar(final);
      });
    });
  }

  alElegir($entradaFoto, $entradaArchivo);
  alElegir($entradaArchivo, $entradaFoto);
  $('#archivoQuitar').on('click', limpiar);

  $form.on('submit', function (ev) {
    var hay = $entradaFoto[0].files.length > 0 || $entradaArchivo[0].files.length > 0;
    if ($form.attr('data-archivo-obligatorio') === '1' && !hay) {
      ev.preventDefault();
      avisar('Adjunta el documento: toma una foto o selecciona un archivo PDF.', 'error');
      return;
    }
    $guardar.prop('disabled', true); // evita registrar dos veces con doble toque
  });

  // Al volver con el botón "atrás" el botón debe quedar activo otra vez
  $(window).on('pageshow', function () { $guardar.prop('disabled', false); });
});
