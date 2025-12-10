document.addEventListener('DOMContentLoaded', function () {
  const modoKey = 'modoOscuro_' + idUsuario;
  const modoActivo = localStorage.getItem(modoKey);

  if (modoActivo === 'activo') {
    document.body.classList.add('modo-oscuro');
    actualizarBotonModo(true);
  }
});

document.addEventListener('DOMContentLoaded', function () {
  const modoKey = 'modoOscuro_' + idUsuario;
  const modoActivo = localStorage.getItem(modoKey);

  if (modoActivo === 'activo') {
    document.body.classList.add('modo-oscuro');
    actualizarBotonModo(true);
  }

  // Buscar todos los botones dropdown-item
  const botones = document.querySelectorAll('.lk');

  botones.forEach(function (boton) {
    // Puedes usar un atributo personalizado o verificar el texto del botón
    boton.addEventListener('click', function () {
      // Si este botón es el que activa el modo oscuro
      if (boton.id === 'toggleModo') {
        document.body.classList.toggle('modo-oscuro');
        const activo = document.body.classList.contains('modo-oscuro');
        localStorage.setItem(modoKey, activo ? 'activo' : 'inactivo');
        actualizarBotonModo(activo);
      }
    });
  });
});


function actualizarBotonModo(activo) {
  const boton = document.querySelector('.lk');
  
  // Añadir animación al texto del botón
  boton.classList.add('animar-boton');
  setTimeout(() => boton.classList.remove('animar-boton'), 300);

  if (activo) {
    boton.classList.remove('text-dark');
    boton.classList.add('texto-secundario');
    boton.innerHTML = '<i class="fa-solid fa-sun me-2"></i> Modo Claro';
  } else {
    boton.classList.remove('text-light');
    boton.classList.add('text-dark');
    boton.innerHTML = '<i class="fa-solid fa-moon me-2"></i> Modo Oscuro';
  }
}

