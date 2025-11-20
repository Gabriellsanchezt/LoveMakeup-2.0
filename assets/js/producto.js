$(document).ready(function () {
  let productosStockBajo = [];

  const tabla = $('#myTable').DataTable();

  tabla.rows().every(function () {
    const fila = $(this.node());
    const nombreProducto = fila.find('td').eq(0).text().trim();
    const stockDisponible = parseInt(fila.find('td').eq(4).text().trim(), 10);
    const stockMinimo = parseInt(fila.data('stock-minimo'), 10);

    if (stockDisponible <= stockMinimo || stockDisponible <= stockMinimo + (stockMinimo * 0.1)) {
        productosStockBajo.push(`"${nombreProducto}"`);
        fila.find('td').eq(4).html('<i class="fa-solid fa-triangle-exclamation" style="color: red;"></i> ' + stockDisponible);
    }
  });

  if (productosStockBajo.length > 0) {
    Swal.fire({
      icon: "warning",
      title: "¡Atención! Stock bajo",
      html: `En los productos: <strong>${productosStockBajo.join(', ')}</strong>.`,
      toast: true,
      position: "top",
      showConfirmButton: false,
      timer: 5000,
      timerProgressBar: true,
      didOpen: (toast) => {
          toast.onmouseenter = Swal.stopTimer;
          toast.onmouseleave = Swal.resumeTimer;
      }
    });
  }
});


$('#btnLimpiar').on("click", function () {
  $('#nombre, #descripcion, #marca, #cantidad_mayor, #precio_mayor, #precio_detal, #stock_maximo, #stock_minimo, #categoria').val('').removeClass('is-valid is-invalid');
  $('#imagen').attr("src", "assets/img/logo.PNG");
  $('#preview').html('');
   $('#archivo').val('');
});




$(document).on('click', '.ver-detalles', function () {
  const fila = $(this).closest('tr');

  // Obtener el nombre del producto de la primera columna
  const nombreProducto = fila.find('td').eq(0).text().trim();

  // Acceder a los datos almacenados en los atributos data-*
  const cantidadMayor = fila.data('cantidad-mayor');
  const precioMayor = fila.data('precio-mayor');
  const stockMaximo = fila.data('stock-maximo');
  const stockMinimo = fila.data('stock-minimo');

  // Asignar los valores a los elementos del modal
  $('#modal-nombre-producto').text(nombreProducto);
  $('#modal-cantidad-mayor').text(cantidadMayor);
  $('#modal-precio-mayor').text(precioMayor);
  $('#modal-stock-maximo').text(stockMaximo);
  $('#modal-stock-minimo').text(stockMinimo);

  // Mostrar el modal con los detalles
  $('#modalDetallesProducto').modal('show');
});

function limpiarModal() {
    $('#u')[0].reset();
    $('#preview').html('');
    $('#imagen').attr('src', 'assets/img/logo.PNG').show(); 
    $('#archivo').val(''); 
    $('#imagenesEliminadas').val('[]'); 
}

  
  $('#btnAbrirRegistrar').on('click', function () {
    limpiarModal();
    $('#id_producto').val('');
    $('#accion').val('registrar');
    $('#modalTitle').text('Registrar Producto');
});
  
  $(document).ready(function () {
    $('#btnEnviar').on("click", function () {
  if (validarenvio()) {
    $('#btnEnviar').prop("disabled", true).html('<i class="fas fa-spinner fa-spin me-2"></i>Procesando...');

    var datos = new FormData($('#u')[0]);
    if ($('#accion').val() === 'registrar') {
      datos.append('registrar', 'registrar');
    } else if ($('#accion').val() === 'modificar') {
      datos.append('actualizar', 'actualizar');
    } else {
      alert('Acción no definida');
      $('#btnEnviar').prop("disabled", false).html('<i class="fas fa-save me-2"></i>Guardar Producto');
      return;
    }

    enviaAjax(datos);
  }
});
});
  
  // modificar al abrir el modal
  function abrirModalModificar(boton) {
    limpiarModal()
    const fila = $(boton).closest('tr');

    // Obtener ID del producto desde el botón de eliminación
    const botonEliminarOnclick = fila.find('button.eliminar').attr('onclick');
    let id_producto = null;

    if (botonEliminarOnclick) {
        const match = botonEliminarOnclick.match(/(\d+)/);
        if (match) {
            id_producto = match[0];
        }
    }

    if (!id_producto) {
        console.error('No se pudo obtener el id_producto para modificar');
        return;
    }

    // Obtener datos visibles desde la tabla
    const nombre = fila.find('td').eq(0).text().trim();
    const descripcion = fila.find('td').eq(1).text().trim();
    const marca = fila.find('td').eq(2).text().trim();
    const precioDetal = fila.find('td').eq(3).text().trim();
    const imagenSrc = fila.find('td').eq(5).find('img').attr('src');
    const categoriaTexto = fila.find('td').eq(6).text().trim();

    // Obtener datos ocultos desde data-*
    const cantidadMayor = fila.data('cantidad-mayor');
    const precioMayor = fila.data('precio-mayor');
    const stockMaximo = fila.data('stock-maximo');
    const stockMinimo = fila.data('stock-minimo');

    // Asignar valores al formulario
    $('#id_producto').val(id_producto);
    $('#nombre').val(nombre);
    $('#descripcion').val(descripcion);

    const marcaSelect = $("#marca option").filter(function () {
    return $(this).text().trim() === marca;
}).val();
if (marcaSelect !== undefined) {
    $('#marca').val(marcaSelect);
} else {
    $('#marca').val('');
}

    $('#cantidad_mayor').val(cantidadMayor);
    $('#precio_mayor').val(precioMayor);
    $('#precio_detal').val(precioDetal);
    $('#stock_maximo').val(stockMaximo);
    $('#stock_minimo').val(stockMinimo);

    // Mantener la lógica original de búsqueda de categoría
    const categoriaSelect = $("#categoria option").filter(function () {
        return $(this).text().trim() === categoriaTexto;
    }).val();

    if (categoriaSelect !== undefined) {
        $('#categoria').val(categoriaSelect);
    } else {
        $('#categoria').val('');
    }

    $('#accion').val('modificar');

    
   $.post('', { accion: 'obtenerImagenes', id_producto }, function(respuesta) {
  const data = JSON.parse(respuesta);
  if (data.respuesta == 1) {
    const preview = $('#preview');
    preview.html(''); 

    if (data.imagenes.length > 0) {
      $('#imagen').hide();

      data.imagenes.forEach(item => {
        
        const imgWrapper = $('<div class="position-relative d-inline-block m-1">');

        const imgTag = $('<img>')
          .attr('src', item.url_imagen)
          .attr('data-id', item.id_imagen)             
          .addClass('img-thumbnail')
          .css({ width: '100px', height: '100px', objectFit: 'cover' });

        const btnEliminar = $('<button type="button" class="btn-close position-absolute top-0 end-0"></button>');
        btnEliminar.on('click', function () {
          const idImagen = imgTag.data('id');           
          imgWrapper.remove();

          let eliminadas = $('#imagenesEliminadas').val() ? JSON.parse($('#imagenesEliminadas').val()) : [];
          if (!eliminadas.includes(idImagen)) eliminadas.push(idImagen);
          $('#imagenesEliminadas').val(JSON.stringify(eliminadas));

          if (preview.children().length === 0) $('#imagen').show();
        });

        imgWrapper.append(imgTag).append(btnEliminar);
        preview.append(imgWrapper);
      });
    } else {
      $('#imagen').show();
    }
  }
});

    // Abrir modal
    $('#modalTitle').text('Modificar Producto');
    $('#registro').modal('show');
}

  function eliminarproducto(id_producto) {
  Swal.fire({
    title: '¿Eliminar producto?',
    text: '¿Desea eliminar este producto?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Sí, eliminar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      const datos = new FormData();
      datos.append('id_producto', id_producto);
      datos.append('eliminar', 'eliminar');
      enviaAjax(datos); // Aquí sí usas tu flujo normal con muestraMensaje()
    }
  });
}

function cambiarEstatusProducto(id_producto, estatus_actual) {
  Swal.fire({
      title: estatus_actual == 2 ? '¿Reactivar producto?' : '¿Desactivar producto?',
      text: estatus_actual == 2 ? '¿Quieres volver a activar este producto?' : '¿Quieres desactivar este producto?',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: estatus_actual == 2 ? 'Sí, activar' : 'Sí, desactivar',
      cancelButtonText: 'Cancelar'
  }).then((result) => {
      if (result.isConfirmed) {
          const datos = new FormData();
          datos.append('id_producto', id_producto);
          datos.append('estatus_actual', estatus_actual);
          datos.append('accion', 'cambiarEstatus');
          enviaAjax(datos)
        }
      });
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
    beforeSend: function () {},
    timeout: 10000,
    success: function (respuesta) {
      console.log(respuesta);
      var lee;
      try {
        lee = JSON.parse(respuesta);
      } catch (parseError) {
        console.error('Error parseando JSON del servidor:', parseError, respuesta);
        muestraMensaje("error", 2000, "Error", "Respuesta inválida del servidor. Intente nuevamente.");
        $('#btnEnviar').prop("disabled", false).html('<i class="fas fa-save me-2"></i>Guardar Producto');
        return;
      }
      try {
        if (lee.accion == 'consultar') {
          crearConsulta(lee.datos);
        }

        else if (lee.accion == 'incluir') {
          if (lee.respuesta == 1) {
            $('#u')[0].reset();
            muestraMensaje("success", 1000, "Se ha registrado con éxito", "Su registro se ha completado exitosamente");
            setTimeout(function () {
              location.href = "?pagina=producto";
            }, 1000);
          } else {
            let mensajeError = lee.error || lee.mensaje || lee.text || "Ha ocurrido un error inesperado. Inténtelo nuevamente.";

            if (mensajeError.includes("Ya existe un producto con el mismo nombre y marca")) {
              muestraMensaje("error", 1000, "Registro duplicado", mensajeError);
            } else {
              muestraMensaje("error", 1000, "Error en el registro", mensajeError);
            }
            $('#btnEnviar').prop("disabled", false).html('<i class="fas fa-save me-2"></i>Guardar Producto');
          }
        }

        else if (lee.accion == 'actualizar') {
          if (lee.respuesta == 1) {
            muestraMensaje("success", 1000, "Se ha Modificado con éxito", "Su registro se ha Actualizado exitosamente");
            setTimeout(function () {
              location = '?pagina=producto';
            }, 1000);
          } else {
            muestraMensaje("error", 2000, "ERROR", lee.text);
            $('#btnEnviar').prop("disabled", false).html('<i class="fas fa-save me-2"></i>Guardar Producto');
          }
        }

        else if (lee.accion == 'eliminar') {
          if (lee.respuesta == 1) {
            muestraMensaje("success", 1000, "Se ha eliminado con éxito", "Los datos se han borrado correctamente");
            setTimeout(function () {
              location.href = "?pagina=producto";
            }, 1000);
          } else {
            let mensajeError = lee.error || lee.mensaje || lee.text || "Ha ocurrido un error inesperado. Inténtelo nuevamente.";

            if (mensajeError.includes("No se puede eliminar un producto con stock disponible")) {
              muestraMensaje("error", 1000, "Error al eliminar", mensajeError);
            } else {
              muestraMensaje("error", 1000, "Error en la eliminación", mensajeError);
            }
          }
        }

        else if (lee.accion == 'cambiarEstatus') {
          if (lee.respuesta == 1) {
            muestraMensaje("success", 1000, "Se ha Cambiado el estatus con éxito", "Los datos se han actualizado correctamente");
            setTimeout(function () {
              location.href = "?pagina=producto";
            }, 1000);
          } else {
            muestraMensaje("error", 2000, "ERROR", lee.text);
          }
        }
        else {
          const mensajeServidor = lee.mensaje || lee.error || lee.text || '';
          if (mensajeServidor.includes('Ya existe un producto')) {
            muestraMensaje("error", 1200, "Registro duplicado", mensajeServidor);
            $('#btnEnviar').prop("disabled", false).html('<i class="fas fa-save me-2"></i>Guardar Producto');
          } else if (mensajeServidor.includes('No se puede eliminar un producto con stock disponible')) {
            muestraMensaje("error", 1200, "Error al eliminar", mensajeServidor);
          } else if (lee.respuesta == 1) {
            muestraMensaje("success", 1000, "Operación exitosa", mensajeServidor || 'Operación completada');
            setTimeout(function () { location.href = '?pagina=producto'; }, 1000);
          } else {
            muestraMensaje("error", 2000, "Error", mensajeServidor || 'Ocurrió un error inesperado');
            $('#btnEnviar').prop("disabled", false).html('<i class="fas fa-save me-2"></i>Guardar Producto');
          }
        }
      } catch (e) {
        alert("Error en JSON " + e.name);
        $('#btnEnviar').prop("disabled", false).html('<i class="fas fa-save me-2"></i>Guardar Producto');
      }
    },

    error: function (request, status, err) {
      Swal.close();
      if (status == "timeout") {
        muestraMensaje("error", 2000, "Error", "Servidor ocupado, intente de nuevo");
      } else {
        muestraMensaje("error", 2000, "Error", "ERROR: <br/>" + request + status + err);
      }

      $('#btnEnviar').prop("disabled", false).html('<i class="fas fa-save me-2"></i>Guardar Producto');
    }
  });
}

  $("#imagen").on("error", function () {
    $(this).prop("src", "assets/img/logo.PNG");
  });
  
  function mostrarImagen(input) {
    const preview = $('#preview');
    const imagenPrincipal = $('#imagen');
    preview.html(''); 
    imagenPrincipal.show(); 

    if (input.files && input.files.length > 0) {
        const archivos = Array.from(input.files);

        if (archivos.length === 1) {
            
            const file = archivos[0];
            if (file.size / 1024 > 1024) {
                muestraMensaje("error", 2000, "Error", "La imagen debe ser igual o menor a 1024 K");
                input.value = "";
                return;
            }

            const reader = new FileReader();
            reader.onload = function (e) {
                imagenPrincipal.attr('src', e.target.result).show();
                preview.html('');
            };
            reader.readAsDataURL(file);
        } else {
            // Varias imágenes
            imagenPrincipal.hide();
            archivos.forEach((file, index) => {
                if (file.size / 1024 > 1024) {
                    muestraMensaje("error", 2000, "Error", `La imagen ${file.name} supera 1024 K`);
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    const imgWrapper = $('<div class="position-relative d-inline-block m-1">');
                    const img = $('<img>')
                        .attr('src', e.target.result)
                        .addClass('img-thumbnail')
                        .css({ width: '100px', height: '100px', objectFit: 'cover' });

                    // Botón para eliminar miniatura
                    const btnEliminar = $('<button type="button" class="btn-close position-absolute top-0 end-0" aria-label="Close"></button>');
                    btnEliminar.on('click', function () {
                        imgWrapper.remove();
                        if (preview.children().length === 0) imagenPrincipal.show();
                    });

                    imgWrapper.append(img).append(btnEliminar);
                    preview.append(imgWrapper);
                };
                reader.readAsDataURL(file);
            });
        }
    } else {
        // Si no hay imagen seleccionada
        imagenPrincipal.attr('src', 'assets/img/logo.PNG').show();
        preview.html('');
    }
}


let imagenSeleccionadaParaReemplazar = null;

$(document).on("click", ".img-thumbnail", function () {
  $(".img-thumbnail").css("border", "none");
  $(this).css("border", "3px solid #007bff");
  imagenSeleccionadaParaReemplazar = $(this); // aquí ya tienes data-id
  muestraMensaje("info", 1500, "Imagen seleccionada", "Ahora puedes subir otra imagen para reemplazarla.");
});

$("#archivo").on("change", function () {
  const input = this;
  if (!input.files || input.files.length === 0) return;

  const file = input.files[0];
  if (!file.type.match('image.*')) { /* valida tipo */ return; }
  if (file.size / 1024 > 1024) { /* valida tamaño */ return; }

  const reader = new FileReader();
  reader.onload = function (e) {
    if (imagenSeleccionadaParaReemplazar) {
      const idImagen = imagenSeleccionadaParaReemplazar.data('id'); 
      imagenSeleccionadaParaReemplazar.attr("src", e.target.result);

      let reemplazos = JSON.parse($('#imagenesReemplazadas').val() || "[]");
      reemplazos.push({ id_imagen: idImagen, nombre: file.name });
      $('#imagenesReemplazadas').val(JSON.stringify(reemplazos));

      imagenSeleccionadaParaReemplazar.css("border", "none");
      imagenSeleccionadaParaReemplazar = null;
      return;
    }
    mostrarImagen(input);
  };
  reader.readAsDataURL(file);
});


$('#reemplazarImagenInput').on('change', function(e){
    const file = e.target.files[0];
    if(file){
        $('#imagenesReemplazadas').val(JSON.stringify([file.name])); 
        $('#imagen').attr('src', URL.createObjectURL(file)).show();
        $('#preview').html(''); 
    }
});

	$("#nombre").on("keypress",function(e){
		validarkeypress(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]*$/,e);
	});
	
	$("#nombre").on("keyup",function(){
		validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/,
		$(this),$("#snombre"),"Solo letras  entre 3 y 30 caracteres");
	});
	
	$("#cantidad_mayor").on("keypress",function(e){
		validarkeypress(/^[0-9-\b]*$/,e);
	});
	
	$("#cantidad_mayor").on("keyup", function() {
    let cantidadMayor = parseInt($(this).val());

    if (cantidadMayor === 0) {
      $("#scantidad_mayor").text("No puedes poner un 0 inicial");
        $(this).val("").removeClass('is-valid').addClass('is-invalid');
    } else {
        validarkeyup(/^[0-9]{1,8}$/, $(this), $("#scantidad_mayor"), "Solo números hasta 8 dígitos");
    }
});

	$("#precio_detal").on("keypress", function(e) {
    validarkeypress(/^[0-9.]$/, e);
  });
	
  $("#precio_detal").on("keyup", function() {
    let precioDetal = parseFloat($(this).val());
    let precioMayor = parseFloat($("#precio_mayor").val());

    if (precioDetal < precioMayor) {
        $("#sprecio_detal").text("El precio al detal no puede ser menor que el precio al mayor");
        $(this).val("").removeClass('is-valid').addClass('is-invalid');
    } else {
        $("#sprecio_detal").text("");
        validarkeyup(/^[0-9]{1,8}(\.[0-9]{1,2})?$/, $(this), $("#sprecio_detal"), "Solo numeros hasta 8 digitos y 2 decimales");
    }
});
  
	$("#precio_mayor").on("keypress", function(e) {
    validarkeypress(/^[0-9.]$/, e);
  });
	
  $("#precio_mayor").on("keyup", function() {
    let precioDetal = parseFloat($("#precio_detal").val());
    let precioMayor = parseFloat($(this).val());

    if (precioMayor > precioDetal) {
        $("#sprecio_mayor").text("El precio al mayor no puede ser mayor que el precio al detal");
        $(this).val("").removeClass('is-valid').addClass('is-invalid');
    } else {
        $("#sprecio_mayor").text("");
        validarkeyup(/^[0-9]{1,8}(\.[0-9]{1,2})?$/, $(this), $("#sprecio_mayor"), "Solo numeros hasta 8 digitos y 2 decimales");
    }
});



$("#stock_maximo").on("keypress", function(e){
    validarkeypress(/^[0-9-\b]*$/, e);
});

$("#stock_maximo").on("keyup", function() {
  let stockMaximo = parseInt($(this).val());
  let stockMinimo = parseInt($("#stock_minimo").val());

  if (stockMaximo === 0) {
    $("#sstock_maximo").text("No puedes poner un 0 inicial");
    $(this).val("").removeClass('is-valid').addClass('is-invalid');
} else if (stockMaximo < stockMinimo) {
    $("#sstock_maximo").text("El stock máximo no puede ser menor que el stock mínimo");
    $(this).val("").removeClass('is-valid').addClass('is-invalid');
} else {
    $("#sstock_maximo").text("");
    validarkeyup(/^[0-9]{1,8}$/, $(this), $("#sstock_maximo"), "Solo números hasta 8 dígitos");
}
});


$("#stock_minimo").on("keypress", function(e){
    validarkeypress(/^[0-9-\b]*$/, e);
});

$("#stock_minimo").on("keyup", function() {
  let stockMaximo = parseInt($("#stock_maximo").val());
  let stockMinimo = parseInt($(this).val());

  if (stockMinimo === 0) {
    $("#sstock_minimo").text("No puedes poner un 0 inicial");
    $(this).val("").removeClass('is-valid').addClass('is-invalid');
} else if (stockMinimo > stockMaximo) {
    $("#sstock_minimo").text("El stock mínimo no puede ser mayor que el stock máximo");
    $(this).val("").removeClass('is-valid').addClass('is-invalid');
} else {
    $("#sstock_minimo").text("");
    validarkeyup(/^[0-9]{1,8}$/, $(this), $("#sstock_minimo"), "Solo números hasta 8 dígitos");
}
});

	
  
function validarenvio() {
  if (validarkeyup(/^[A-Za-z\b\s\u00f1\u00d1\u00E0-\u00FC]{3,30}$/,
      $("#nombre"), $("#snombre"), "Solo letras entre 3 y 30 caracteres") == 0) {
      muestraMensaje("error", 2000, "Error", "Datos incorrectos en campo nombre");
      return false;
  }
  
  else if ($("#marca").val() === null || $("#marca").val() === "" || $("#marca").val() === undefined) {
    muestraMensaje("error", 2000, "Error", "Debes seleccionar una marca");
    return false;
}

  else if ($("#descripcion").val().trim().length === 0) {
      muestraMensaje("error", 2000, "Error", "La descripción no puede estar vacía");
      return false;
  }
  
  else if (validarkeyup(/^[0-9]{1,8}$/,
      $("#cantidad_mayor"), $("#scantidad_mayor"), "Solo numeros hasta 8 digitos") == 0) {
      muestraMensaje("error", 2000, "Error", "Datos incorrectos en campo cantidad mayor");
      return false;
  }

  
  else if (validarkeyup(/^[0-9]{1,8}(\.[0-9]{1,2})?$/,
  $("#precio_detal"), $("#sprecio_detal"), "Solo numeros hasta 8 digitos y 2 decimales") == 0) {
    muestraMensaje("error", 2000, "Error", "Datos incorrectos en campo precio detal");
    return false;
  }
  
  else if (validarkeyup(/^[0-9]{1,8}(\.[0-9]{1,2})?$/,
  $("#precio_mayor"), $("#sprecio_mayor"), "Solo numeros hasta 8 digitos y 2 decimales") == 0) {
    muestraMensaje("error", 2000, "Error", "Datos incorrectos en campo precio mayor");
    return false;
  }
  else if (validarkeyup(/^[0-9]{1,8}$/,
      $("#stock_maximo"), $("#sstock_maximo"), "Solo numeros hasta 8 digitos") == 0) {
      muestraMensaje("error", 2000, "Error", "Datos incorrectos en campo stock máximo");
      return false;
  }

  else if (validarkeyup(/^[0-9]{1,8}$/,
      $("#stock_minimo"), $("#sstock_minimo"), "Solo numeros hasta 8 digitos") == 0) {
      muestraMensaje("error", 2000, "Error", "Datos incorrectos en campo stock mínimo");
      return false;
  }

  else if ($("#categoria").val() === null || $("#categoria").val() === "") {
      muestraMensaje("error", 2000, "Error", "Debes seleccionar una categoría");
      return false;
  }
  
  return true;
}


  function validarkeypress(er,e){
	
    key = e.keyCode;
    
    
      tecla = String.fromCharCode(key);
    
    
      a = er.test(tecla);
    
      if(!a){
    
      e.preventDefault();
      }
    
      
  }
  //Función para validar por keyup
  function validarkeyup(er, $input, $mensaje, mensaje) {
    const valor = $input.val().trim();
    if (er.test(valor)) {
        $input.removeClass('is-invalid').addClass('is-valid');
        $mensaje.text('');
        return 1;
    }
    else {
        $input.removeClass('is-valid').addClass('is-invalid');
        $mensaje.text(mensaje);
        return 0;
    }
}
  
// Inicializar Driver.js
$('#btnAyuda').on("click", function () {
  
  const driver = window.driver.js.driver;
  
  const driverObj = new driver({
    nextBtnText: 'Siguiente',
        prevBtnText: 'Anterior',
        doneBtnText: 'Listo',
    popoverClass: 'driverjs-theme',
    closeBtn:false,
    steps: [
      { element: '.table-color', popover: { title: 'Tabla de productos', description: 'Aqui es donde se guardaran los registros de productos', side: "left", }},
      { element: '#btnAbrirRegistrar', popover: { title: 'Boton de registrar', description: 'Darle click aqui te llevara a un modal para poder registrar', side: "bottom", align: 'start' }},
      { element: '.modificar', popover: { title: 'Modificar producto', description: 'Este botón te permite editar la información de un producto registrado.', side: "left", align: 'start' }},
      { element: '.eliminar', popover: { title: 'Eliminar producto', description: 'Usa este botón para eliminar un producto de la lista.', side: "left", align: 'start' }},
      { element: '.ver-detalles', popover: { title: 'Ver detalles', description: 'Haz clic aquí para ver más información sobre un producto específico.', side: "left", align: 'start' }},
      { element: '.btn-desactivar', popover: { title: 'Cambiar estatus', description: 'Este botón te permite desactivar o activar un producto', side: "left", align: 'start' }},
      { element: '.dt-search', popover: { title: 'Buscar', description: 'Te permite buscar un producto en la tabla', side: "right", align: 'start' }},
      { popover: { title: 'Eso es todo', description: 'Este es el fin de la guia espero hayas entendido'} }
    ]
  });
  
  // Iniciar el tour
  driverObj.drive();
});

// Ejecutar la guía interactiva cuando la página cargue
