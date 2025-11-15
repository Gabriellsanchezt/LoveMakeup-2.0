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

$(document).ready(function() {

  // Establecer fechas
  if (document.getElementById('fecha_1')) {
    document.getElementById('fecha_1').value = moment().format('YYYY-MM-DD');
  }
  if (document.getElementById('fecha_2')) {
    document.getElementById('fecha_2').value = moment().format('YYYY-MM-DD');
  }

  // Botón actualizar manualmente
  $('#btnActualizarManual').on("click", function () {
    Swal.fire({
      title: 'Actualizar Tasa',
      html: `
        <div style="text-align: center; margin: 15px 0;">
          <input type="text" id="swal-tasa" class="swal2-input" placeholder="Ej: 100.50" style="width: 200px; text-align: center; font-size: 18px;">
        </div>
      `,
      width: '350px',
      showCancelButton: true,
      confirmButtonColor: '#50c063ff',
      cancelButtonColor: '#42515A',
      confirmButtonText: 'Guardar',
      cancelButtonText: 'Cancelar',
      focusConfirm: false,
      allowOutsideClick: false,
      preConfirm: () => {
        const tasa = document.getElementById('swal-tasa').value;
        if (!tasa) {
          Swal.showValidationMessage('Ingrese la tasa');
          return false;
        }
        let valorTasa = parseFloat(tasa);
        let tasavalida = /^\d{1,6}(\.\d{1,2})?$/.test(tasa);
        
        if (!tasavalida || isNaN(valorTasa)) {
          Swal.showValidationMessage('Formato inválido');
          return false;
        } else if (valorTasa === 0 || valorTasa < 0) {
          Swal.showValidationMessage('Debe ser mayor a 0');
          return false;
        } else if (valorTasa > 999999.99) {
          Swal.showValidationMessage('Máximo: 999,999.99');
          return false;
        }
        return tasa;
      }
    }).then((result) => {
      if (result.isConfirmed && result.value) {
        $('#tasa').val(result.value);
        Swal.fire({
          title: '¿Confirmar cambios?',
          text: 'Tasa: Bs. ' + result.value,
          icon: 'question',
          width: '320px',
          showCancelButton: true,
          confirmButtonColor: '#50c063ff',
          cancelButtonColor: '#42515A',
          confirmButtonText: 'Si',
          cancelButtonText: 'No'
        }).then((confirmResult) => {
          if (confirmResult.isConfirmed) {
            activarLoaderBoton('#btnActualizarManual');
            var datos = new FormData($('#for_modificar')[0]);
            datos.append('modificar', 'modificar');
            enviaAjax(datos);
          }
        });
      }
    });
  });

  // Botón sincronizar automáticamente
  $('#btnSincronizar').on("click", function () {
    var tasaInternet = $('#tasabcv').val();
    var tasaTexto = 'Error al cargar';
    
    if (tasaInternet && tasaInternet !== '0') {
      tasaTexto = 'Bs. ' + parseFloat(tasaInternet).toFixed(2);
    }
    
    Swal.fire({
      title: 'Sincronizar Tasa',
      html: `<div style="text-align: center; padding: 10px 0;"><strong style="font-size: 20px;">${tasaTexto}</strong></div>`,
      icon: 'question',
      width: '320px',
      showCancelButton: true,
      confirmButtonColor: '#50c063ff',
      cancelButtonColor: '#42515A',
      confirmButtonText: 'Sincronizar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        activarLoaderBoton('#btnSincronizar');
        var datos = new FormData($('#for_sincronizar')[0]);
        datos.append('sincronizar', 'sincronizar');
        enviaAjax(datos);
      }
    });
  });

  // Validación para el input dentro de SweetAlert
  $(document).on("keypress", "#swal-tasa", function (e) {
    let char = String.fromCharCode(e.which);
    let valor = $(this).val();
    
    // Permitir números
    if (!/[0-9]/.test(char)) {
      // Permitir punto decimal solo si no existe uno
      if (char === '.' && valor.indexOf('.') === -1) {
        return true;
      }
      e.preventDefault();
      return false;
    }
  });

  // Tour de ayuda
  $('#btnAyuda').on("click", function () {
  
  const driver = window.driver.js.driver;
  
  const driverObj = new driver({
    nextBtnText: 'Siguiente',
        prevBtnText: 'Anterior',
        doneBtnText: 'Listo',
    popoverClass: 'driverjs-theme',
    closeBtn:false,
    steps: [
      { element: '.row.mb-4 .col-md-6:first-child .card', popover: { title: 'Tasa del Dolar (Guardada)', description: 'Aquí puedes ver la tasa de cambio de USD a Bolívares (Bs) que está guardada en la base de datos. Esta es la tasa que se utiliza actualmente en el sistema.', side: "bottom", align: 'start' }},
      { element: '#btnActualizarManual', popover: { title: 'Actualizar Manualmente', description: 'Este botón te permite actualizar la tasa de cambio manualmente. Al hacer clic, podrás ingresar una nueva tasa según tu preferencia o la tasa vigente.', side: "top", align: 'start' }},
      { element: '.row.mb-4 .col-md-6:last-child .card', popover: { title: 'Tasa del Dolar (Actual - Via Internet)', description: 'Aquí se muestra la tasa de cambio obtenida automáticamente desde internet en tiempo real. Esta tasa se actualiza automáticamente desde una API externa.', side: "bottom", align: 'start' }},
      { element: '#btnSincronizar', popover: { title: 'Sincronizar y Actualizar', description: 'Este botón te permite sincronizar la tasa obtenida desde internet y actualizar la tasa guardada en la base de datos con el valor actual.', side: "top", align: 'start' }},
      { element: '.table-color', popover: { title: 'Historial de Tasas', description: 'En esta tabla puedes ver el historial de todas las tasas de cambio registradas, incluyendo la fecha, el valor y la fuente (Manual o Via Internet).', side: "top", align: 'start' }},
      { popover: { title: 'Eso es todo', description: 'Este es el fin de la guia espero hayas entendido'} }
    ]
  });
  
  // Iniciar el tour
  driverObj.drive();
});

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
  
           if (lee.accion == 'modificar') {
                if (lee.respuesta == 1) {
                  muestraMensaje("success", 2000, "Se ha Modificado con éxito", "");
                  desactivarLoaderBoton('#btnActualizarManual'); 
                  setTimeout(function () {
                     location = '?pagina=tasacambio';
                  }, 2000);
                } else {
                  muestraMensaje("error", 2000, "ERROR", lee.text);
                  desactivarLoaderBoton('#btnActualizarManual'); 
                }
            }else  if (lee.accion == 'sincronizar') {
                if (lee.respuesta == 1) {
                  muestraMensaje("success", 1500, "Se ha Actualizado con éxito", "");
                  desactivarLoaderBoton('#btnSincronizar'); 
                  setTimeout(function () {
                     location = '?pagina=tasacambio';
                  }, 2000);
                } else {
                  muestraMensaje("error", 2000, "ERROR", lee.text);
                  desactivarLoaderBoton('#btnSincronizar'); 
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
