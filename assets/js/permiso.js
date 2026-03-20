  $(window).on('load', function() {
 $(document).ready(function() {
  $('input[name^="permiso"]').each(function() {
    const input = $(this);
    const name = input.attr('name');

    if (name.includes('[ver]')) {
      const moduloId = name.split('[')[1].split(']')[0];

      input.on('change', function() {
        const isChecked = $(this).is(':checked');

        const acciones = ['registrar', 'editar', 'eliminar', 'especial'];

        acciones.forEach(function(accion) {
          const selector = `input[name="permiso[${moduloId}][${accion}]"]`;
          const checkbox = $(selector);

          checkbox.prop('disabled', !isChecked);
          if (!isChecked) {
            checkbox.prop('checked', false);
          }
        });
      });
    }
  });

$('#actualizar_permisos').on("click", function () {
    Swal.fire({
        title: '¿Estás seguro?',
        text: 'Esto actualizará los permisos del usuario.',
        icon: 'warning',
        showCancelButton: true,
              confirmButtonColor: '#0d6b29',
              cancelButtonColor: '#ac2424',
        confirmButtonText: 'Sí, actualizar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
             activarLoaderBoton('#actualizar_permisos');
            var datos = new FormData($('#forpermiso')[0]);
            datos.append('actualizar_permisos', 'actualizar_permisos');
            enviaAjax(datos);
        }
    });
});

});

if (!isChecked) {
  Swal.fire({
    icon: 'info',
    title: 'Acceso limitado',
    text: 'Para activar otras acciones, primero debes permitir "ver".'
  });
}
});


function activarLoaderBoton(idBoton, texto = 'Cargando...') {
    const $boton = $(idBoton);
    const textoActual = $boton.html();
    $boton.data('texto-original', textoActual); // Guarda el texto original
    $boton.prop('disabled', true);
    $boton.html(`<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${texto}`);
}

function desactivarLoaderBoton(idBoton) {
    const $boton = $(idBoton);
    const textoOriginal = $boton.data('texto-original');
    $boton.prop('disabled', false);
    $boton.html(textoOriginal);
}

function muestraMensaje(icono, tiempo, titulo, mensaje) {
  Swal.fire({
    icon: icono,
    timer: tiempo,
    title: titulo,
    html: mensaje,
    showConfirmButton: false,
  });
}

function enviaAjax(datos) {
    $.ajax({
      async: true,
      url: "",
      type: "POST",
      contentType: false,
      data: datos,
      processData: false,
      cache: false,
      beforeSend: function () { },
      timeout: 10000, 
      success: function (respuesta) {
        console.log(respuesta);
        var lee = JSON.parse(respuesta);
        try {
  
            if (lee.accion == 'actualizar_permisos') {
                if (lee.respuesta == 1) {
                  muestraMensaje("success", 1500, "Se ha modificado los Permisos con éxito", "Permisos Actualizados");
                    desactivarLoaderBoton('#actualizar_permisos');
                      setTimeout(function () {
                        location = '?pagina=tipousuario';
                      }, 2000);
                } else {
                    muestraMensaje("error", 3000, lee.text,"" );
                    desactivarLoaderBoton('#actualizar_permisos');
                }
            }
  
        } catch (e) {
          alert("Error en JSON " + e.name);
        }
      },
      error: function (request, status, err) {
        Swal.close();
        if (status == "timeout") {
          muestraMensaje("error", 2000, "Error", "Servidor ocupado, intente de nuevo");
        } else {
          muestraMensaje("error", 2000, "Error", "ERROR: <br/>" + request + status + err);
        }
      },
      complete: function () {
      }
    });
  }
 
  $('#ayudapermiso').on("click", function () {
  
  const driver = window.driver.js.driver;
  
  const driverObj = new driver({
    nextBtnText: 'Siguiente',
        prevBtnText: 'Anterior',
        doneBtnText: 'Listo',
    popoverClass: 'driverjs-theme',
    closeBtn:false,
    steps: [
      { element: '.modulo', popover: { title: 'Modulo', description: 'Aqui es una sección del sistema que cumple una función específica. Por ejemplo, el módulo de clientes, productos o reportes.', side: "left", }},
      { element: '.ver', popover: { title: 'Ver Datos', description: 'Permite al usuario consultar información dentro del módulo, como listas, detalles o registros, sin poder cambiar nada', side: "bottom", align: 'start' }},
      { element: '.registrar', popover: { title: 'Registrar Datos', description: 'Da acceso para crear nuevos registros, como añadir un cliente, producto, venta entre otros...', side: "left", align: 'start' }},
      { element: '.editar', popover: { title: 'Editar Datos', description: 'Permite editar o actualizar información existente, como corregir datos de un cliente, producto, usuario entre otros...', side: "left", align: 'start' }},
      { element: '.eliminar', popover: { title: 'Eliminar Datos', description: 'Autoriza al usuario a borrar registros del sistema', side: "left", align: 'start' }},
      { element: '.especial', popover: { title: 'Accion Especial', description: 'Son funciones avanzadas o específicas del módulo, como confirmar un pedido, desactivar producto entre otros...', side: "left", align: 'start' }},
      { element: '.form-check-input', popover: { title: 'Casilla de permiso', description:
         'Azul: El permiso está activo. El usuario puede realizar esa acción (por ejemplo, Ver, Modificar, Eliminar) pero en Gris: El permiso está desactivado. El usuario no tiene acceso a esa acción.', side: "right", align: 'start' }},
       { element: '.guardar', popover: { title: 'Boton guardar permiso', description: 'Te permite guardar los cambios de los permiso del usuario', side: "right", align: 'start' }},
      { popover: { title: 'Eso es todo', description: 'Este es el fin de la guia espero hayas entendido'} }
    ]
  });
  
  // Iniciar el tour
  driverObj.drive();
});