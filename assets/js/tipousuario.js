/*||| Funcion para cambiar el boton a loader |||*/
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

/*||| Funcion para validar compas de formulario |||*/
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

function eliminarRol(id_rol) {
  Swal.fire({
    title: '¿Eliminar Tipo de usuario?',
    text: '¿Desea eliminarlo?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#0d6b29',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      const datos = new FormData();
      datos.append('id_rol', id_rol);
      datos.append('eliminar', 'eliminar');
      enviaAjax(datos); // Aquí sí usas tu flujo normal con muestraMensaje()
    }
  });
}


//Función para validar por Keypress
function validarkeypress(er,e){
  key = e.keyCode;
    tecla = String.fromCharCode(key);
    a = er.test(tecla);
    if(!a){
    e.preventDefault();
    }
}
//Función para validar por keyup
function validarkeyup(er,etiqueta,etiquetamensaje,
mensaje){
  a = er.test(etiqueta.val());
  if(a){
    etiquetamensaje.text("");
    return 1;
  }
  else{
    etiquetamensaje.text(mensaje);
    return 0;
  }
} 

/* ||| FUNCION PARA VALIDAR ENVIO REGISTRO ||| */
function validarCampos() {
  
    let nombreValido = /^[a-zA-Z]{3,30}$/.test($("#nombre").val()); 
    let nivelValido = $("#nivel").val() !== "";

    function aplicarEstado(input, valido, feedback, mensaje = "") {
        if (valido) {
            $(input).removeClass("is-invalid").addClass("is-valid");
            $(feedback).hide();
        } else {
            $(input).removeClass("is-valid").addClass("is-invalid");
            $(feedback).text(mensaje).show();
        }
    }
    
    aplicarEstado("#nombre", nombreValido, "#snombre", "Solo letras (3 a 30 caracteres)");
    aplicarEstado("#nivel", nivelValido, "#snivel", "Por favor, seleccione un nivel válido.");
    
    return nombreValido &&  nivelValido ;
}

$(document).ready(function() {

  $("#nivel").on("change", function () {
    validarCampo($(this), null, $("#snivel"), "Por favor, seleccione un rol válido.");
  });

  $("#nombre").on("keypress", function (e) {
    validarkeypress(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/, e);
  });

  $("#nombre").on("keyup", function () {
    validarCampo($(this),/^[a-zA-Z]{3,30}$/,
    $("#snombre"), "El formato debe ser solo letras");
  });

  /*||| ENVIO AJAX FORMULARIO |||*/
  $('#registrar').on("click", function () {
      if (validarCampos()) {
          Swal.fire({
              title: '¿Deseas registrar?',
              text: 'Se asignarán permisos predeterminados según el nivel seleccionado.',
              icon: 'question',
              showCancelButton: true,
              confirmButtonText: 'Sí, registrar',
              cancelButtonText: 'Cancelar',
              confirmButtonColor: '#0d6b29',
              cancelButtonColor: '#ac2424'
          }).then((result) => {
              if (result.isConfirmed) {
                  activarLoaderBoton('#registrar');
                  var datos = new FormData($('#ForRegistrar')[0]);
                  datos.append('registrar', 'registrar');
                  enviaAjax(datos);
              }
          });
      }
  });

  $('#btnModificar').on("click", function () {
     
          Swal.fire({
              title: '¿Deseas Actualizar?',
              text: '',
              icon: 'question',
              showCancelButton: true,
              confirmButtonText: 'Sí, actualizar',
              cancelButtonText: 'Cancelar',
              confirmButtonColor: '#0d6b29',
              cancelButtonColor: '#ac2424'
          }).then((result) => {
              if (result.isConfirmed) {
                  activarLoaderBoton('#btnModificar');
                  var datos = new FormData($('#formModificar')[0]);
                  datos.append('actualizar', 'actualizar');
                  enviaAjax(datos);
              }
          });
     
  });

});

$(document).on('click', '.modificar', function () {

    let id     = $(this).data('id');
    let nombre = $(this).data('nombre');
    let nivel  = $(this).data('nivel');
  

    // Asignar valores al formulario del modal
    $('#id_tipo_modificar').val(id);
    $('#nombre_modificar').val(nombre);
    $('#nivel_modificar').val(nivel);
     $('#nivel_modificar_actual').val(nivel);
 

    // Abrir modal (por si no se abre automáticamente)
    $('#modificar').modal('show');
});


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
  
           if (lee.accion == 'registrar') {
                if (lee.respuesta == 1) {
                  muestraMensaje("success", 1500, "Se ha registrado con éxito", "Agregado Tipo Usuario");
                    desactivarLoaderBoton('#registrar'); 
                      setTimeout(function () {
                        location = '?pagina=tipousuario';
                      }, 2000);
                } else {
                  muestraMensaje("error", 3000, "Error", lee.text);
                  desactivarLoaderBoton('#registrar'); 
                }
            }else if (lee.accion == 'actualizar') {
                if (lee.respuesta == 1) {
                  muestraMensaje("success", 1500, "Se ha Actualizado con éxito", "");
                  desactivarLoaderBoton('#btnModificar'); 
                  setTimeout(function () {
                     location = '?pagina=tipousuario';
                  }, 2000);
                } else {
                  muestraMensaje("error", 3000, "Error", lee.text);
                  desactivarLoaderBoton('#btnModificar'); 
                }
            }else if (lee.accion == 'eliminar') {
                if (lee.respuesta == 1) {
                  muestraMensaje("success", 1500, "Se ha eliminado con éxito", "Tipo de usario Borrado");
                 
                    setTimeout(function () {
                      location = '?pagina=tipousuario';
                    }, 2000);
                } else {
                    muestraMensaje("error", 3000, "Error", lee.text);
                 
                }
            } else if (lee.accion == 'permisos') {
                if (lee.respuesta == 1) {

                } else {
                    muestraMensaje("error", 3000, "Error", lee.text);
                 
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

$(function(){
// ——— AYUDA con Driver.js v1 ———
$('#btnAyuda').on("click", function () {
  // instancia el driver (igual que en Proveedor)
  const driver = window.driver.js.driver;
  const driverObj = new driver({
    nextBtnText:  'Siguiente',
    prevBtnText:  'Anterior',
    doneBtnText:  'Listo',
    popoverClass: 'driverjs-theme',
    closeBtn:     false,
    steps: [
      {
        element: '.table-color',
        popover: {
          title:       'Tabla de Tipos de Usuario',
          description: 'Aquí ves la lista de tipos de usuario registrados.',
          side:        'top'
        }
      },
      {
        element: 'button[data-bs-target="#registro"]',
        popover: {
          title:       'Registrar Tipo de Usuario',
          description: 'Abre el modal para crear un nuevo tipo de usuario.',
          side:        'bottom'
        }
      },
      {
        element: '.permisotur',
        popover: {
          title:       'Modificar Permisos',
          description: 'Haz clic aquí para ver y modificar los permisos del tipo de usuario.',
          side:        'left'
        }
      },
      {
        element: '.modificar',
        popover: {
          title:       'Editar Tipo de Usuario',
          description: 'Modifica los datos de un tipo de usuario existente.',
          side:        'left'
        }
      },
      {
        element: '.eliminar',
        popover: {
          title:       'Eliminar Tipo de Usuario',
          description: 'Elimina un tipo de usuario del sistema.',
          side:        'left'
        }
      },
      {
        popover: {
          title:       '¡Listo!',
          description: 'Finalizaste la guía de ayuda del módulo Tipo de Usuario.'
        }
      }
    ]
  });

  driverObj.drive();
});

});