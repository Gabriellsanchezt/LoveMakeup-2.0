$(document).ready(function () {

  // ===============================
  // CONFIRMAR RESERVA
  // ===============================
  $(document).on('click', '.btn-validar', function () {

    const idPedido = $(this).data('id');

    Swal.fire({
      title: '¿Confirmar reserva?',
      text: 'Una vez confirmada, no podrás modificarla',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, confirmar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {

      if (result.isConfirmed) {

        $.ajax({
          async: true,
          url: '',
          type: 'POST',
          data: {
            confirmar: 'confirmar',
            id_pedido: idPedido
          },
          dataType: 'json',

          success: function (res) {

            if (res.respuesta == 1) {

              Swal.fire({
                title: 'Confirmado',
                text: res.mensaje || 'Reserva confirmada correctamente',
                icon: 'success',
                timer: 1200,
                showConfirmButton: false
              }).then(() => location.reload());

            } else {

              Swal.fire({
                title: 'Error',
                text: res.mensaje || 'No se pudo confirmar la reserva',
                icon: 'error',
                timer: 1500,
                showConfirmButton: false
              });

            }

          },

          error: function () {

            Swal.fire({
              title: 'Error',
              text: 'Error en la comunicación con el servidor',
              icon: 'error',
              timer: 1500,
              showConfirmButton: false
            });

          }

        });

      }

    });

  });


  // ===============================
  // ELIMINAR RESERVA
  // ===============================
  $(document).on('click', '.btn-eliminar', function () {

    const idPedido = $(this).data('id');

    Swal.fire({
      title: '¿Eliminar reserva?',
      text: 'Esta acción no se puede deshacer',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {

      if (result.isConfirmed) {

        $.ajax({
          async: true,
          url: '',
          type: 'POST',
          data: {
            eliminar: 'eliminar',
            id_pedido: idPedido
          },
          dataType: 'json',

          success: function (res) {

            if (res.respuesta == 1) {

              Swal.fire({
                title: 'Eliminado',
                text: res.mensaje || 'Reserva eliminada correctamente',
                icon: 'success',
                timer: 1200,
                showConfirmButton: false
              }).then(() => location.reload());

            } else {

              Swal.fire({
                title: 'Error',
                text: res.mensaje || 'No se pudo eliminar la reserva',
                icon: 'error',
                timer: 1500,
                showConfirmButton: false
              });

            }

          },

          error: function () {

            Swal.fire({
              title: 'Error',
              text: 'Error en la comunicación con el servidor',
              icon: 'error',
              timer: 1500,
              showConfirmButton: false
            });

          }

        });

      }

    });

  });
  // Tour
  $('#btnAyuda').on('click', function() {
    const driver = window.driver.js.driver;
    const driverObj = new driver({
      nextBtnText: 'Siguiente',
      prevBtnText: 'Anterior',
      doneBtnText: 'Listo',
      popoverClass: 'driverjs-theme',
      closeBtn: false,
      steps: [
        { element: '.table-color', popover: { title: 'Tabla de Reservas', description: 'Aquí ves las reservas registradas.', side: "left" }},
        { element: '.btn-info', popover: { title: 'Ver Detalles', description: 'Abre los detalles completos de la reserva.', side: "bottom" }},
        { element: '.btn-validar', popover: { title: 'Confirmar Reserva', description: 'Confirma que la reserva ha sido pagada.', side: "left" }},
        { element: '.btn-eliminar', popover: { title: 'Eliminar Reserva', description: 'Elimina una reserva registrada.', side: "left" }},
        { element: '.dt-search', popover: { title: 'Buscar', description: 'Filtra las reservas fácilmente.', side: "right" }},
        { popover: { title: 'Fin del tour', description: 'Ya sabes cómo gestionar tus reservas.' }}
      ]
    });
    driverObj.drive();
  });

  // DataTable
  $('#tablaReservas').DataTable({
    responsive: true,
    language: { url: "https://cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" }
  });
});
