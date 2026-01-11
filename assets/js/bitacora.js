$(document).ready(function () {
  // DataTables se inicializa automáticamente con datatables-demo.js

  // Función para eliminar registros de bitácora
  $('.eliminar').on('click', function (e) {
    e.preventDefault();

    Swal.fire({
      title: '¿Desea eliminar este registro de la bitácora?',
      text: 'Esta acción no se puede deshacer',
      icon: 'warning',
      showCancelButton: true,
      color: "#00000",
      confirmButtonColor: '#38b96f',
      cancelButtonColor: '#EF233C',
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        var form = $(this).closest('form');
        var datos = new FormData(form[0]);
        enviaAjax(datos);
      }
  });
});



  // Función para limpiar bitácora (eliminar todos los registros)
  $('#limpiarBitacora').on('click', function() {
    Swal.fire({
      title: '¿Limpiar toda la bitácora?',
      text: 'Se eliminarán todos los registros. Esta acción no se puede deshacer.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Sí, limpiar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: '?pagina=bitacora',
          type: 'POST',
          dataType: 'json',
          data: { limpiar: 1 },
          beforeSend: function() {
            Swal.fire({
              title: 'Limpiando...',
              text: 'Por favor espere',
              allowOutsideClick: false,
              didOpen: () => {
                Swal.showLoading();
              }
            });
          },
          success: function(data) {
            Swal.close();
            if (data.success) {
              Swal.fire({
                title: '¡Éxito!',
                text: data.message,
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
              }).then(() => {
                // Recargar la página para mostrar el estado actualizado
                location.reload();
              });
            } else {
              Swal.fire('Error', data.message || 'Error al limpiar la bitácora', 'error');
            }
          },
          error: function(xhr, status, error) {
            Swal.close();
            // Intentar parsear la respuesta si viene como texto
            try {
              var data = typeof xhr.responseText === 'string' ? JSON.parse(xhr.responseText) : xhr.responseJSON;
              if (data && data.message) {
                Swal.fire('Error', data.message, 'error');
              } else {
                Swal.fire('Error', 'Error de conexión al limpiar la bitácora', 'error');
              }
            } catch (e) {
              Swal.fire('Error', 'Error de conexión al limpiar la bitácora', 'error');
            }
          }
        });
      }
    });
  });
});

// Función para mostrar mensajes
function muestraMensaje(icono, tiempo, titulo, mensaje) {
  Swal.fire({
    icon: icono,
    timer: tiempo,
    title: titulo,
    html: mensaje,
    showConfirmButton: false,
  });
}

// Función para enviar AJAX
function enviaAjax(datos) {
    $.ajax({
      async: true,
      url: "",
      type: "POST",
      contentType: false,
      data: datos,
      processData: false,
      cache: false,
    beforeSend: function () {
      Swal.fire({
        title: 'Procesando...',
        text: 'Por favor espere',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });
    },
      timeout: 10000,
      success: function (respuesta) {
        console.log(respuesta);
        var lee = JSON.parse(respuesta);
        try {
            if (lee.accion == 'eliminar') {
                if (lee.respuesta == 1) {
            muestraMensaje("success", 1000, "Eliminado con éxito", "El registro se ha eliminado correctamente");
                  setTimeout(function () {
              location.reload();
                  }, 1000);
                } else {
            muestraMensaje("error", 2000, "ERROR", lee.mensaje || lee.text);
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
      Swal.close();
      }
    });
  }

// Función para ver detalles de un registro
function verDetalles(id) {
  $.ajax({
    url: '?pagina=bitacora',
    type: 'POST',
    data: {detalles: id},
    dataType: 'json',
    beforeSend: function() {
      Swal.fire({
        title: 'Cargando...',
        text: 'Obteniendo detalles del registro',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });
    },
    success: function(response) {
      Swal.close();
      
      // Verificar si hay error en la respuesta
      if(response.error) {
        Swal.fire('Error', response.error, 'error');
        return;
      }
      
      // Verificar que la respuesta tenga los campos necesarios
      if(!response.nombre || !response.apellido || !response.nombre_usuario) {
        Swal.fire('Error', 'Datos incompletos en la respuesta', 'error');
        return;
      }
      
      // Información del Usuario
      $('#detalle-usuario').text(response.nombre + ' ' + response.apellido);
      $('#detalle-cedula').text(response.cedula || 'N/A');
      $('#detalle-correo').text(response.correo || 'N/A');
      $('#detalle-rol').text(response.nombre_usuario);
      
      // Información del Evento
      $('#detalle-fecha').text(response.fecha_hora || 'No disponible');
      
      // Tipo de Acción con badge
      let badgeClass = '';
      switch(response.accion) {
        case 'CREAR': badgeClass = 'bg-success'; break;
        case 'MODIFICAR': badgeClass = 'bg-primary'; break;
        case 'ELIMINAR': badgeClass = 'bg-danger'; break;
        case 'ACCESO A MÓDULO': badgeClass = 'bg-info'; break;
        case 'CAMBIO_ESTADO': badgeClass = 'bg-warning'; break;
        default: badgeClass = 'bg-secondary';
      }
      $('#detalle-accion').html(`<span class="badge ${badgeClass}">${response.accion || 'N/A'}</span>`);
      
       // Descripción limpia sin prefijos (igual que en la tabla)
       let desc = response.descripcion || '';
       let descHtml = '';
       
       if (!desc) {
         descHtml = '<span class="text-muted">Sin descripción</span>';
       } else {
         let descLimpia = desc;
         
         // Si tiene pipes, extraer solo la parte de Descripcion:
         if (desc.indexOf('|') !== -1) {
           // Buscar la parte después de "Descripcion:" o "Descripción:"
           let descMatch = desc.match(/Descripci[oó]n:\s*(.+?)(?:\s*\||$)/i);
           if (descMatch) {
             descLimpia = descMatch[1].trim();
           } else {
             // Si no encuentra "Descripcion:", tomar la última parte después del último pipe
             let partes = desc.split('|');
             descLimpia = partes[partes.length - 1].trim();
           }
         }
         
         // ELIMINAR CUALQUIER PREFIJO "Descripcion:" o "Descripción:" que pueda quedar (en cualquier caso)
         descLimpia = descLimpia.replace(/^Descripci[oó]n:\s*/gi, '');
         descLimpia = descLimpia.replace(/Descripci[oó]n:\s*/gi, '');
         descLimpia = descLimpia.replace(/DESCRIPCION:\s*/gi, '');
         descLimpia = descLimpia.replace(/descripcion:\s*/gi, '');
         
         // Extraer módulo si existe al final entre corchetes
         let moduloMatch = descLimpia.match(/\[(.*?)\]$/);
         let modulo = '';
         if (moduloMatch) {
           modulo = moduloMatch[1];
           descLimpia = descLimpia.replace(/\[(.*?)\]$/, '').trim();
         }
         
         // Si después de limpiar está vacía y hay módulo
         if (!descLimpia && response.accion === 'ACCESO A MÓDULO' && modulo) {
           descLimpia = 'Usuario accedió al módulo';
         }
         
         // Construir HTML - asegurar que no haya "Descripcion:" restante
         descLimpia = descLimpia.trim();
         descHtml = '<p class="mb-0">' + descLimpia;
         if (modulo) {
           descHtml += ' <span class="badge bg-primary">[' + modulo + ']</span>';
         }
         descHtml += '</p>';
       }
       
       $('#detalle-descripcion').html(descHtml);
      
      $('#detallesModal').modal('show');
    },
    error: function(xhr, status, error) {
      Swal.close();
      console.error('Error AJAX:', xhr.responseText);
      
      // Intentar parsear la respuesta para obtener más detalles del error
      let errorMessage = 'No se pudieron cargar los detalles';
      try {
        if (xhr.responseText) {
          // Si la respuesta contiene HTML, mostrar un mensaje genérico
          if (xhr.responseText.includes('<html') || xhr.responseText.includes('<br />')) {
            errorMessage = 'Error del servidor. Verifique la conexión.';
          } else {
            // Intentar parsear como JSON
            const errorResponse = JSON.parse(xhr.responseText);
            if (errorResponse.error) {
              errorMessage = errorResponse.error;
            }
          }
        }
      } catch (e) {
        // Si no se puede parsear, usar el mensaje por defecto
        errorMessage = 'Error de conexión con el servidor';
      }
      
      Swal.fire('Error', errorMessage, 'error');
    }
  });
}

$(document).ready(function() {


  $('#entrar').on("click", function () {
         
    var datos = new FormData($('#u')[0]);
    datos.append('entrar', 'entrar');
    enviaAjax(datos);
 
  });


});

// Al cargar la página, formatear las fechas a la hora local del usuario
$(document).ready(function () {
  $('.fecha-bitacora').each(function() {
    var fechaUTC = $(this).data('fecha');
    if (fechaUTC) {
      var fechaLocal = new Date(fechaUTC.replace(' ', 'T'));
      var opciones = { year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit' };
      var fechaFormateada = fechaLocal.toLocaleString('es-VE', opciones);
      $(this).text(fechaFormateada);
    }
  });
});




  