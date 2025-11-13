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

  $('#modificar').on("click", function () {
    Swal.fire({
      title: '¿Deseas Modificar la Tasa?',
      text: 'Confirma los cambios.',
      icon: 'question',
      showCancelButton: true,
      color: "#00000",
      confirmButtonColor: '#50c063ff',
      cancelButtonColor: '#42515A',
      confirmButtonText: ' Si, Modificar ',
      cancelButtonText: 'NO'
    }).then((result) => {
     if (result.isConfirmed) {

           let tasavalida = /^\d{1,5}(\.\d{1,3})?$/.test($("#tasa").val());
            if (!tasavalida) {
                $("#tasa").addClass("is-invalid");
                $("#textotasa").show();
            } else {
                $("#tasa").removeClass("is-invalid").addClass("is-valid");
                $("#textotasa").hide();
            }
            
           let valorTasa = parseFloat($("#tasa").val());

            if (valorTasa === 0 || isNaN(valorTasa)) {
            $("#tasa").addClass("is-invalid");
            $("#textotasa").show().text("La tasa no puede ser 0.00");

            } else if (tasavalida) {
            activarLoaderBoton('#modificar');
            var datos = new FormData($('#for_modificar')[0]);
            datos.append('modificar', 'modificar');
            enviaAjax(datos);
            }
      }
    });
 }); 

 $('#sincronizar').on("click", function () {
    Swal.fire({
      title: '¿Deseas Actualizar la Tasa?',
      text: 'Confirma los cambios.',
      icon: 'question',
      showCancelButton: true,
      color: "#00000",
      confirmButtonColor: '#50c063ff',
      cancelButtonColor: '#42515A',
      confirmButtonText: ' Si, Actualizar ',
      cancelButtonText: 'NO'
    }).then((result) => {
     if (result.isConfirmed) {
  
    
                activarLoaderBoton('#sincronizar');
                var datos = new FormData($('#for_sincronizar')[0]);
                datos.append('sincronizar', 'sincronizar');
                enviaAjax(datos);
                
      }
    });
 }); 


    $("#tasa").on("input", function () {
        let valor = $(this).val().replace(/\D/g, ""); 
        if (valor.length > 0) {
            let decimal = (parseInt(valor) / 100).toFixed(2);
            $(this).val(decimal);
        } else {
            $(this).val("");
        }
    });

    $("#tasa").on("keyup", function () {
        validarCampo(
            $(this),
            /^\d{1,5}(\.\d{1,3})?$/,
            $("#textotasa"),
            "Debe tener entre 4 y 8 caracteres, incluyendo punto decimal. Ejemplo: 100.50"
        );
    });


    

});
document.getElementById('fecha_1').value = moment().format('YYYY-MM-DD');
document.getElementById('fecha_2').value = moment().format('YYYY-MM-DD');

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
                  desactivarLoaderBoton('#modificar'); 
                  setTimeout(function () {
                     location = '?pagina=tasacambio';
                  }, 2000);
                } else {
                  muestraMensaje("error", 2000, "ERROR", lee.text);
                  desactivarLoaderBoton('#modificar'); 
                }
            }else  if (lee.accion == 'sincronizar') {
                if (lee.respuesta == 1) {
                  muestraMensaje("success", 1500, "Se ha Actualizado con éxito", "");
                  desactivarLoaderBoton('#sincronizar'); 
                  setTimeout(function () {
                     location = '?pagina=tasacambio';
                  }, 2000);
                } else {
                  muestraMensaje("error", 2000, "ERROR", lee.text);
                  desactivarLoaderBoton('#sincronizar'); 
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
  
  let driverObj; // Definido globalmente
   $('#ayudacliente').on("click", function () {
  
  const driver = window.driver.js.driver;
  
  const driverObj = new driver({
    nextBtnText: 'Siguiente',
        prevBtnText: 'Anterior',
        doneBtnText: 'Listo',
    popoverClass: 'driverjs-theme',
    closeBtn:false,
    steps: [
      { element: '.table-color', popover: { title: 'Tabla de cliente', description: 'Aqui es donde se guardaran los registros de los clientes', side: "left", }},
      { element: '.Ayudatelefono', popover: { title: 'Boton para contactar el cliente "WhatsApp"', description: 'Este botón te lleva directo al chat de WhatsApp con el cliente para que puedas escribirle fácilmente..', side: "left", align: 'start' }},
      { element: '.Ayudacorreo', popover: { title: 'Boton para contactar el cliente "Correo"', description: 'Este botón Abre tu aplicación de correo para que puedas enviarle un email al cliente sin copiar su dirección.', side: "left", align: 'start' }},
      { element: '.modificar', popover: { title: 'Modificar datos del cliente', description: 'Este botón te permite editar la cedula y el correo de un cliente registrado.', side: "left", align: 'start' }},
      { element: '.dt-search', popover: { title: 'Buscar', description: 'Te permite buscar un cliente en la tabla', side: "right", align: 'start' }},
      { popover: { title: 'Eso es todo', description: 'Este es el fin de la guia espero hayas entendido'} }
    ]
  });
  
  // Iniciar el tour
  driverObj.drive();
})


