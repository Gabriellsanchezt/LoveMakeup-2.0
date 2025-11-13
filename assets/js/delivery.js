function mostrarMensaje(icono, tiempo, titulo, mensaje) {
  Swal.fire({ icon: icono, title: titulo, html: mensaje, timer: tiempo, showConfirmButton: false });
}

$(function(){

  // —— 1) DataTables ——  
  // Check if DataTable is already initialized to prevent reinitialization error
  if (!$.fn.DataTable.isDataTable('#myTable')) {
    $('#myTable').DataTable({
      order: [],
      "language": {
        "sProcessing": "Procesando...",
        "sLengthMenu": "Mostrar _MENU_ registros",
        "sZeroRecords": "No se encontraron resultados",
        "sEmptyTable": "Ningún dato disponible en esta tabla",
        "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
        "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
        "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
        "sInfoPostFix": "",
        "sSearch": "Buscador:",
        "sUrl": "",
        "sInfoThousands": ",",
        "sLoadingRecords": "Cargando...",
        "oPaginate": {
          "sFirst": "Primero",
          "sLast": "Último",
          "sNext": "Siguiente",
          "sPrevious": "Anterior"
        },
        "oAria": {
          "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
          "sSortDescending": ": Activar para ordenar la columna de manera descendente"
        }
      }
    });
  }

  // ————————————————————————————
  // Helpers (idénticos al módulo proveedor)
  // ————————————————————————————
  function muestraMensaje(icon, tiempo, titulo, msg) {
    Swal.fire({ icon, title: titulo, html: msg, timer: tiempo, showConfirmButton: false });
  }
  function mensajeOK(text) {
    Swal.fire({ icon:'success', title:text, timer:1000, showConfirmButton:false })
      .then(()=> location.reload());
  }
  function validarkeypress(er, e) {
    const chr = String.fromCharCode(e.which);
    if (!er.test(chr)) e.preventDefault();
  }
  function validarkeyup(er, $el, $span, msg) {
    const v = $el.val().trim();
    if (!v) {
      $el.addClass('is-invalid').removeClass('is-valid');
      $span.text('Este campo es obligatorio');
      return false;
    }
    if (!er.test(v)) {
      $el.addClass('is-invalid').removeClass('is-valid');
      $span.text(msg);
      return false;
    }
    $el.addClass('is-valid').removeClass('is-invalid');
    $span.text('');
    return true;
  }

  // 1) Abrir modal Registrar
  $('#btnAbrirRegistrar').click(()=>{ 
    $('#formDelivery')[0].reset();
    $('#formDelivery').find('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
    $('#formDelivery').find('span.error-message').text('');
    $('#accion').val('registrar');
    $('#modalTitleText').text('Registrar Delivery');
    $('#btnText').text('Registrar');
    $('#estatus').val('1');
    $('#registro').modal('show');
  });

  // 2) Abrir modal Modificar
  window.abrirModalModificar = function(id){
    $.post('?pagina=delivery', { id_delivery:id, consultar_delivery:1 }, function(data){
      $('#id_delivery').val(data.id_delivery);
      $('#nombre').val(data.nombre);
      $('#tipo').val(data.tipo);
      $('#contacto').val(data.contacto);
      $('#estatus').val(data.estatus);

      $('#formDelivery').find('.is-invalid, .is-valid').removeClass('is-invalid is-valid');
      $('#formDelivery').find('span.error-message').text('');

      $('#accion').val('actualizar');
      $('#modalTitleText').text('Modificar Delivery');
      $('#btnText').text('Actualizar');
      $('#registro').modal('show');
    }, 'json')
    .fail(()=> muestraMensaje('error',2000,'Error','No se cargaron datos'));
  };

  // 3) Eliminar
  window.eliminarDelivery = function(id){
    Swal.fire({
      title:'¿Eliminar delivery?',
      text:'Esta acción no se puede deshacer',
      icon:'warning',
      showCancelButton:true,
      confirmButtonText:'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then(res=>{
      if(res.isConfirmed){
        const fd = new FormData();
        fd.append('id_delivery', id);
        fd.append('eliminar', '1');
        enviaAjax(fd);
      }
    });
  };

  // 4) Guardar (Registrar o Modificar)
  $('#btnEnviar').click(()=>{
    let esValido = true;
    
    const $nombre = $('#nombre');
    const $tipo = $('#tipo');
    const $contacto = $('#contacto');
    const $estatus = $('#estatus');

    if (!$nombre.val().trim()) {
        $nombre.addClass('is-invalid');
        $('#snombre').text('Este campo es obligatorio');
        esValido = false;
    } else if (!validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,80}$/, $nombre, $('#snombre'), 'Solo letras entre 3 y 80 caracteres')) {
        esValido = false;
    }

    if (!$tipo.val()) {
        $tipo.addClass('is-invalid');
        $('#stipo').text('Seleccione un tipo de vehículo');
        esValido = false;
    } else {
        $tipo.removeClass('is-invalid').addClass('is-valid');
        $('#stipo').text('');
    }

    if (!$contacto.val().trim()) {
        $contacto.addClass('is-invalid');
        $('#scontacto').text('Este campo es obligatorio');
        esValido = false;
    } else if (!/^[0-9]{4}[-]{1}[0-9]{7}$/.test($contacto.val())) {
        $contacto.addClass('is-invalid');
        $('#scontacto').text('El formato debe ser 0414-0000000');
        esValido = false;
    } else {
        $contacto.removeClass('is-invalid').addClass('is-valid');
        $('#scontacto').text('');
    }

    if (!$estatus.val()) {
      $estatus.addClass('is-invalid');
      $('#sestatus').text('Seleccione un estatus');
      esValido = false;
    } else {
      $estatus.removeClass('is-invalid').addClass('is-valid');
      $('#sestatus').text('');
    }
    
    if (!esValido) {
      muestraMensaje('error',2000,'Error','Por favor, complete todos los campos obligatorios');
      return;
    }
    
    const form = $('#formDelivery')[0];
    const fd = new FormData(form);
    const accion = $('#accion').val();

    if(accion==='registrar')    fd.append('registrar', '1');
    else if(accion==='actualizar') fd.append('actualizar','1');

    enviaAjax(fd);
  });

  // 5) Ajax genérico
function enviaAjax(fd) {
  $.ajax({
    url: '?pagina=delivery',
    type: 'POST',
    data: fd,
    cache: false,
    processData: false,
    contentType: false,
    dataType: 'json',
    timeout: 10000,
    success: function(res) {
      if (res.accion === 'incluir'    && res.respuesta == 1) return mensajeOK('Delivery registrado');
      if (res.accion === 'actualizar' && res.respuesta == 1) return mensajeOK('Delivery modificado');
      if (res.accion === 'eliminar'   && res.respuesta == 1) return mensajeOK('Delivery eliminado');
      muestraMensaje('error', 2000, 'Error', res.mensaje);
    },
    error: function(jqXHR, textStatus, errorThrown) {
      console.groupCollapsed('AJAX Error – delivery.js');
      console.error('Status:', textStatus);
      console.error('Thrown:', errorThrown);
      console.error('Response:', jqXHR.responseText);
      console.groupEnd();
      muestraMensaje('error', 2000, 'Error', 'Comunicación fallida');
    }
  });
}

  function mensajeOK(texto){
    Swal.fire({ icon:'success', title:texto, timer:1200, showConfirmButton:false });
    setTimeout(()=>location.reload(), 1200);
  }

$("#nombre").on("keypress", function(e) {
    validarkeypress(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, e);
});
$("#nombre").on("keyup", function() {
    validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,80}$/, $(this), $("#snombre"), "Solo letras entre 3 y 80 caracteres");
});

$("#tipo").on("keypress", function(e) {
    validarkeypress(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, e);
});
$("#tipo").on("keyup", function() {
    validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,80}$/, $(this), $("#stipo"), "Solo letras entre 3 y 80 caracteres");
});

$("#contacto").on("keypress", function (e) {
    validarkeypress(/^[0-9-\-]*$/, e);
});

$("#contacto").on("keyup", function () {
    validarCampo($(this),/^[0-9]{4}[-]{1}[0-9]{7}$/,
    $("#scontacto"), "El formato debe ser 0414-0000000");
});

$("#contacto").on("input", function () {
    var input = $(this).val().replace(/[^0-9]/g, '');
    if (input.length > 4) {
        input = input.substring(0, 4) + '-' + input.substring(4, 11);
    }
    $(this).val(input);
});

$("#tipo").on("change", function () {
  if (!$(this).val()) {
    $(this).removeClass('is-valid').addClass('is-invalid');
    $('#stipo').text('Seleccione un tipo de vehículo');
  } else {
    $(this).removeClass('is-invalid').addClass('is-valid');
    $('#stipo').text('');
  }
});

function validarCampo(campo, regex, textoError, mensaje) {
  const valor = campo.val();

  if (campo.is("select")) {
   
    if (valor === "") {
      campo.removeClass("is-valid").addClass("is-invalid");
      textoError.text(mensaje);
    } else {
      campo.removeClass("is-invalid").addClass("is-valid");
      textoError.text("");
    }
  } else {
   
    if (regex.test(valor)) {
      campo.removeClass("is-invalid").addClass("is-valid");
      textoError.text("");
    } else {
      campo.removeClass("is-valid").addClass("is-invalid");
      textoError.text(mensaje);
    }
  }
}

// Función para validar por keypress - permite solo caracteres que pasen la regex
function validarkeypress(er, e) {
    let key = e.keyCode || e.which;
    let tecla = String.fromCharCode(key);
    if (!er.test(tecla)) {
        e.preventDefault();
    }
}

// Función para validar por keyup - muestra mensaje y retorna 1 si válido, 0 si no
function validarkeyup(er, etiqueta, etiquetamensaje, mensaje) {
  let valor = etiqueta.val();
  if (valor.trim() === '') {
      etiqueta.removeClass('is-valid').addClass('is-invalid');
      etiquetamensaje.text("Este campo es obligatorio");
      return 0;
  }
  if (er.test(valor)) {
      etiquetamensaje.text('');
      etiqueta.removeClass('is-invalid').addClass('is-valid');
      return 1;
  } else {
      etiquetamensaje.text(mensaje);
      etiqueta.removeClass('is-valid').addClass('is-invalid');
      return 0;
  }
}

// 7) Toggle collapse icons
document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(el => {
  el.addEventListener('click', function() {
    const icon = this.querySelector('.fas.fa-chevron-down, .fas.fa-chevron-up');
    if (!icon) return;
    icon.classList.toggle('fa-chevron-down');
    icon.classList.toggle('fa-chevron-up');
  });
});

// Ayuda interactiva
$('#btnAyuda').on('click', function() {
  const DriverClass = window.driver.js.driver;
  const driverObj = new DriverClass({
    nextBtnText: 'Siguiente',
    prevBtnText: 'Anterior',
    doneBtnText: 'Listo',
    popoverClass: 'driverjs-theme',
    closeBtn: false,
    steps: [
      {
        element: '.table-color',
        popover: { title: 'Tabla de Deliveries', description: 'Aquí ves la lista de deliveries registrados.', side: 'top' }
      },
      {
        element: '#btnAbrirRegistrar',
        popover: { title: 'Registrar Delivery', description: 'Abre el modal para registrar un nuevo delivery.', side: 'bottom' }
      },
      {
        element: 'button[data-bs-target^="#verDetallesModal"]',
        popover: {
          title: 'Ver Detalles',
          description: 'Haz clic aquí para observar toda la información del delivery.',
          side: 'left'
        }
      },
      {
        element: '.modificar',
        popover: { title: 'Editar Delivery', description: 'Haz clic aquí para modificar los datos de un delivery.', side: 'left' }
      },
      {
        element: '.eliminar',
        popover: { title: 'Eliminar Delivery', description: 'Elimina un delivery de la lista.', side: 'left' }
      },
      {
        popover: { title: '¡Eso es todo!', description: 'Ahora ya sabes cómo funciona este módulo.' }
      }
    ]
  });
  driverObj.drive();
});

});