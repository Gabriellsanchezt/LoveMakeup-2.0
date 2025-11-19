document.addEventListener('DOMContentLoaded', () => {
  const BASE    = '?pagina=notificacion';
  const bellBtn = document.querySelector('.notification-icon');
  const helpBtn = document.getElementById('btnAyudanoti');
  let lastId    = Number(localStorage.getItem('lastPedidoId') || 0);

  // 1) Contador de badge - Mejorado para tiempo real
  async function updateBadge() {
    if (!bellBtn) return;
    try {
      const res         = await fetch(`${BASE}&accion=count`);
      const contentType  = res.headers.get('content-type') || '';
      if (!res.ok) {
        const txt = await res.text();
        console.error('updateBadge HTTP error', res.status, txt);
        return;
      }
      if (!contentType.includes('application/json')) {
        const txt = await res.text();
        console.error('updateBadge expected JSON but got:', txt);
        return;
      }
      const { count }   = await res.json();
      const dotExisting = bellBtn.querySelector('.notif-dot');

      if (count > 0 && !dotExisting) {
        bellBtn.insertAdjacentHTML('beforeend',
          '<span class="notif-dot"></span>');
      } else if (count === 0 && dotExisting) {
        dotExisting.remove();
      }
    } catch (err) {
      console.error('updateBadge error:', err);
    }
  }

  // 2) Polling de nuevos pedidos/reservas
  async function pollPedidos() {
    if (!bellBtn) return;
    try {
      const res               = await fetch(`${BASE}&accion=nuevos&lastId=${lastId}`);
      const contentType2      = res.headers.get('content-type') || '';
      if (!res.ok) {
        const txt = await res.text();
        console.error('pollPedidos HTTP error', res.status, txt);
        return;
      }
      if (!contentType2.includes('application/json')) {
        const txt = await res.text();
        console.error('pollPedidos expected JSON but got:', txt);
        return;
      }
      const { count, pedidos} = await res.json();

      if (count > 0) {
        pedidos.forEach(p => {
          const title = p.tipo === 3
            ? `Nueva reserva #${p.id_pedido}`
            : `Nuevo pedido #${p.id_pedido} – Bs. ${p.total}`;

          Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'info',
            title,
            showConfirmButton: false,
            timer: 4000
          });
        });

        // inject dot if missing
        if (!bellBtn.querySelector('.notif-dot')) {
          bellBtn.insertAdjacentHTML('beforeend',
            '<span class="notif-dot"></span>');
        }

        // update lastId
        lastId = pedidos[pedidos.length - 1].id_pedido;
        localStorage.setItem('lastPedidoId', lastId);
      }
    } catch (err) {
      console.error('pollPedidos error:', err);
    }
  }

  // 3) Delegación: marcar como leída (solo si existe la tabla)
  document.addEventListener('click', async e => {
    const btn = e.target.closest('.btn-action');
    if (!btn) return;
    e.preventDefault();

    const id     = btn.dataset.id;
    const accion = btn.dataset.accion;
    const row    = btn.closest('tr');

    const { isConfirmed } = await Swal.fire({
      title: '¿Marcar como leída?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Sí',
      cancelButtonText: 'Cancelar'
    });
    if (!isConfirmed) return;

    try {
      const res  = await fetch(`${BASE}&accion=${accion}`, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({ id })
      });
      const ct = res.headers.get('content-type') || '';
      if (!res.ok) {
        const txt = await res.text();
        console.error('marcarLeida HTTP error', res.status, txt);
        Swal.fire('Error','No se pudo conectar','error');
        return;
      }
      if (!ct.includes('application/json')) {
        const txt = await res.text();
        console.error('marcarLeida expected JSON but got:', txt);
        Swal.fire('Error','Respuesta inesperada del servidor','error');
        return;
      }
      const data = await res.json();

      await Swal.fire(
        data.success ? '¡Listo!' : 'Error',
        data.mensaje,
        data.success ? 'success' : 'error'
      );

if (data.success && row) {
  // 1) Actualizar visualmente el estado de la notificación
  const estadoCell = row.querySelector('td:nth-child(3)');
  if (estadoCell) {
    if (accion === 'marcarLeida') {
      estadoCell.innerHTML = '<span class="badge bg-secondary">Leída</span>';
    } else if (accion === 'marcarLeidaAsesora') {
      estadoCell.innerHTML = '<span class="badge bg-success">Leída por asesora</span>';
    }
  }
  
  // 2) Eliminar el botón de acción
  const actionCell = row.querySelector('td:last-child');
  if (actionCell) {
    actionCell.innerHTML = '<span class="text-muted">Actualizado</span>';
  }
  
  // 3) actualizo el badge
  updateBadge();
}

    } catch (err) {
      console.error('marcarLeida error:', err);
      Swal.fire('Error','No se pudo conectar','error');
    }
  });

  // 4) Guía interactiva (solo si existe el botón)
  if (helpBtn) {
    helpBtn.addEventListener('click', () => {
      const Driver = window.driver?.js?.driver;
      if (typeof Driver !== 'function') {
        Swal.fire({
          title: 'Guía no disponible',
          text: 'La guía interactiva no está disponible en este momento.',
          icon: 'info',
          confirmButtonText: 'Entendido'
        });
        return;
      }

      const steps = [
        {
          element: '#myTable',
          popover: {
            title: 'Panel de Notificaciones',
            description: 'Aquí puedes ver todas las notificaciones del sistema, incluyendo nuevos pedidos y reservas.',
            side: 'top',
            align: 'start'
          }
        },
        {
          element: '.notification-icon',
          popover: {
            title: 'Icono de Notificaciones',
            description: 'Este icono muestra un punto rojo cuando hay notificaciones nuevas. Haz clic para acceder al panel.',
            side: 'bottom',
            align: 'start'
          }
        }
      ];

      // Agregar guía específica según el rol del usuario
      const isAdmin = document.querySelector('.btn-action[data-accion="marcarLeida"]');
      const isAsesora = document.querySelector('.btn-action[data-accion="marcarLeidaAsesora"]');
      
      if (isAdmin) {
        steps.push({
          element: '.btn-action[data-accion="marcarLeida"]',
          popover: {
            title: 'Marcar como Leída (Admin)',
            description: 'Como administrador, cuando marcas una notificación como leída, desaparece para ambos (administrador y asesora).',
            side: 'left'
          }
        });
      }
      
      if (isAsesora) {
        steps.push({
          element: '.btn-action[data-accion="marcarLeidaAsesora"]',
          popover: {
            title: 'Leer Notificación (Asesora)',
            description: 'Como asesora, cuando lees una notificación, solo desaparece para ti. El administrador aún la verá hasta que él también la lea.',
            side: 'left'
          }
        });
      }
      
      // Agregar guía sobre estados
      if (document.querySelector('td:nth-child(3)')) {
        steps.push({
          element: 'td:nth-child(3)',
          popover: {
            title: 'Estados de Notificaciones',
            description: 'Las notificaciones tienen diferentes estados: "No leída" (rojo), "Leída por asesora" (verde), "Leída" (gris).',
            side: 'top'
          }
        });
      }

      steps.push({
        popover: {
          title: '¡Listo!',
          description: 'Terminaste la guía de notificaciones. Ahora conoces cómo funciona el sistema de notificaciones en tiempo real.'
        }
      });

      new Driver({
        nextBtnText:  'Siguiente',
        prevBtnText:  'Anterior',
        doneBtnText:  'Listo',
        popoverClass: 'driverjs-theme',
        closeBtn:     true,
        steps
      }).drive();
    });
  }

  // 5) Inicialización
  updateBadge();
  pollPedidos();
  setInterval(updateBadge, 5000); // Actualizar cada 5 segundos para mejor experiencia en tiempo real
  setInterval(pollPedidos, 15000); // Verificar nuevos pedidos cada 15 segundos
});