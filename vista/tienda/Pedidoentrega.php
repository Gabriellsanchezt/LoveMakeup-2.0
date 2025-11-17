<!DOCTYPE html>
<html lang="en">

<head>
<!-- php CSS, Meta y titulo--> 
<?php include 'vista/complementos/head_catalogo.php' ?>

</head>
<style>
  .text-color1{
    color: #ff009a;
  }

    .pasos-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      max-width: 600px;
      margin: 50px auto;
    }

    .paso {
      text-align: center;
      position: relative;
      flex: 1;
    }

    .paso:not(:last-child)::after {
      content: '';
      position: absolute;
      top: 15px;
      right: -50%;
      width: 100%;
      height: 2px;
      background-color: #ccc;
      z-index: 0;
    }

    .circulo {
      width: 30px;
      height: 30px;
      border-radius: 50%;
      margin: 0 auto 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      color: white;
      position: relative;
      z-index: 1;
    }

    .completado .circulo {
      background-color: #f679d4; /* amarillo */
    }

    .actual .circulo {
      background-color: #4fa7fa; /* naranja */
    }

    .pendiente .circulo {
      background-color: #adb5bd; /* gris */
    }

    .paso span {
      font-size: 14px;
    }

    .sombra-suave {
box-shadow: 0 4px 12px rgba(255, 105, 180, 0.3); 
}

.opcion-custom {
  display: block;
  padding: 15px;
  border: 2px solid #dee2e6;
  border-radius: 15px;
  cursor: pointer;
  transition: all 0.3s ease;
  background-color: #fff;
  color: #f679d4;
  font-weight: 500;
}

.opcion-custom i {
  font-size: 24px;
  margin-bottom: 8px;
}


input[type="radio"]:checked + .opcion-custom {
  border-color: #f679d4;
  background-color: #ffe9f9;
  color: black;
}

/* ===== INDICADOR DE PASOS ===== */

.progress-steps {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  position: relative;
  padding: 0 20px;
}

/* Línea base */
.progress-steps::before {
  content: '';
  position: absolute;
  top: 50%;
  left: 40px;
  right: 40px;
  height: 6px;
  background: linear-gradient(90deg, #f3f4f6, #e5e7eb);
  z-index: 1;
  transform: translateY(-50%);
  border-radius: 10px;
  box-shadow: inset 0 2px 4px rgba(0,0,0,0.06);
}

/* Contenedor de barra rosada */
.progress-bar-container {
  position: absolute;
  top: 50%;
  left: 40px;
  height: 6px;
  z-index: 2;
  transform: translateY(-50%);
  transition: width 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
  overflow: hidden;
  border-radius: 10px;
}

.progress-bar-fill {
  height: 100%;
  width: 100%;
  background: linear-gradient(
    90deg,
    #f472b6 0%,
    #ec4899 25%,
    #f472b6 50%,
    #ec4899 75%,
    #f472b6 100%
  );
  background-size: 200% 100%;
  animation: progress-animation 2.5s ease-in-out infinite;
  border-radius: 10px;
  box-shadow:
    0 0 20px rgba(236,72,153,0.4),
    0 0 10px rgba(244,114,182,0.3),
    0 2px 6px rgba(236,72,153,0.2),
    inset 0 1px 0 rgba(255,255,255,0.4);
  position: relative;
}

.progress-bar-fill::after {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(
    90deg,
    transparent,
    rgba(255,255,255,0.4),
    transparent
  );
  animation: shine 2s ease-in-out infinite;
}

@keyframes progress-animation {
  0%, 100% { background-position: 0% 0; }
  50% { background-position: 100% 0; }
}

@keyframes shine {
  0% { left: -100%; }
  50%, 100% { left: 100%; }
}

/* ===== STEP ===== */

.step {
  position: relative;
  z-index: 3;
  display: flex;
  flex-direction: column;
  align-items: center;
  background-color: white;
  padding: 0.5rem;
}

.step-number {
  width: 45px;
  height: 45px;
  border-radius: 50%;
  background-color: #e9ecef;
  color: #6c757d;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 600;
  font-size: 1rem;
  margin-bottom: 0.5rem;
  transition: all 0.4s;
  border: 3px solid transparent;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.step-label {
  font-size: 0.875rem;
  color: #6c757d;
  text-align: center;
  font-weight: 500;
  transition: all 0.3s ease;
}

/* Activo */
.step.active .step-number {
  background: linear-gradient(135deg, #ec4899, #f472b6);
  color: white;
  border-color: #ec4899;
  box-shadow:
    0 0 20px rgba(236,72,153,0.4),
    0 4px 12px rgba(236,72,153,0.3),
    inset 0 1px 0 rgba(255,255,255,0.3);
  transform: scale(1.1);
}

.step.active .step-label {
  color: #ec4899;
  font-weight: 600;
  transform: scale(1.05);
}

/* Completado */
.step.completed .step-number {
  background: linear-gradient(135deg, #10b981, #34d399);
  color: white;
  border-color: #10b981;
  box-shadow:
    0 0 15px rgba(16,185,129,0.3),
    0 3px 10px rgba(16,185,129,0.2);
}

.step.completed .step-label {
  color: #10b981;
  font-weight: 600;
}

/* Estilos para las secciones del formulario */
.seccion-formulario {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  border-left: 4px solid #f578d4;
  transition: all 0.3s ease;
}

.seccion-formulario {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  margin-bottom: 1.5rem;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  border-left: 4px solid #f786de;
  transition: all 0.3s ease;
}

.seccion-formulario:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

.seccion-formulario h6 {
  color: #2c3e50;
  font-weight: 600;
  font-size: 1.1rem;
  margin-bottom: 1.2rem;
  display: flex;
  align-items: center;
  gap: 8px;
}

.seccion-formulario h6 i {
  color: #f6c5b4;
  font-size: 1.2rem;
}


  </style>
<!-- |||||||||||||||| LOADER ||||||||||||||||||||-->
  <div class="preloader-wrapper">
    <div class="preloader">
    </div>
  </div>
<!-- |||||||||||||||| LOADER ||||||||||||||||||||-->

<!-- php CARRITO--> 
<?php include 'vista/complementos/carrito.php' ?>

<!-- php ENCABEZADO LOGO, ICONO CARRITO Y LOGIN--> 
<?php include 'vista/complementos/nav_catalogo.php' ?>

<section id="latest-blog" class="section-padding pt-0">
<div class="modal-body bg-s">
    <div class="mb-4">
        <div class="row">
            <div class="col-12">
                <div class="modal-body bg-s">
    <div class="mb-4">
        <div class="row">
            <div class="col-12">
                <div class="progress-steps">

                    <!-- BARRA ANIMADA -->
                    <div class="progress-bar-container" id="progress-bar-pasos">
                        <div class="progress-bar-fill"></div>
                    </div>

                    <div class="step active" id="step-cliente">
            <div class="step-number">1</div>
            <div class="step-label">Producto</div>
          </div>

          <div class="step" id="step-productos">
            <div class="step-number">2</div>
            <div class="step-label">Entrega</div>
          </div>

          <div class="step" id="step-pago">
            <div class="step-number">3</div>
            <div class="step-label">Pago</div>
          </div>

          <div class="step" id="step-confirmar">
            <div class="step-number">4</div>
            <div class="step-label">Confirmacion</div>
          </div>

                </div>
            </div>
        </div>
    </div>
</div>

            </div>
        </div>
    </div>
</div>

  
  <div class="container py-2"> 
    <!-- FORMULARIO ENTREGA -->
<form id="formEntrega" class="row g-4">
  <div class="row text-center justify-content-center mb-4">
    <p>Seleccione el metodo de entrega</p>


    <div class="col-md-3">
      <input type="radio" id="op1" name="metodo_entrega" value="4" class="d-none">
      <label for="op1" class="opcion-custom">
        <i class="fa-solid fa-shop"></i><br>
        Tienda física
      </label>
    </div>


    <div class="col-md-3">
      <input type="radio" id="op2" name="metodo_entrega" value="2" class="d-none">
      <label for="op2" class="opcion-custom">
        <i class="fa-solid fa-truck"></i><br>
        Envíos nacionales
      </label>
    </div>


    <div class="col-md-3">
      <input type="radio" id="op3" name="metodo_entrega" value="1" class="d-none">
      <label for="op3" class="opcion-custom">
      <i class="fa-solid fa-motorcycle fa-2x"></i><br>
        Delivery
      </label>
    </div>
  </div>

  <input type="hidden" name="continuar_entrega" value="1">
 
    <div id="formulario-opciones">
      <!-- Aquí se cargará el contenido según la opción seleccionada -->
      </div>
    </div>

    
      <br>
    <div class="d-flex justify-content-between">
    <a href="?pagina=vercarrito" id="btn-atras" class="btn btn-secondary"><i class="fa-solid fa-arrow-left me-2"></i> Atrás</a>
      <button type="button" id="btn-continuar-entrega" class="btn btn-primary me-2"> <i class="fa-solid fa-arrow-right"> </i> Continuar</button>
      </div>

  
</form>
    </div>

  </section>



<template id="form-op1">
  <div class="card p-3 sombra-rosada seccion-formulario">
     <div class="mb-3">
      <p class="text-center text-color1">Retiro en Tienda fisica</p>
    <label for="retira" class="form-label text-dark">Tienda física ubicada en la av 20 con calles 29 y 30 CC Barquisimeto plaza, Estado Lara, Venezuela </label>
    <input type="text" class="form-control text-dark" name="direccion_envio" id="retira" value="Retiro en Tienda Fisica" readonly>
  </div>
  </div>
</template>

<template id="form-op2">
  <div class="card p-3 sombra-rosada seccion-formulario">
     <div class="row g-3 mb-3">
      <p class="text-center text-color1">Envios Nacionales</p>
      <div class="col-md-6">
      <label class="form-label">Empresa</label>
      <select name="empresa_envio" class="form-select">
        <option value="">Selecciona</option>
        <?php foreach($metodos_entrega as $me): ?>
          <?php if (in_array($me['id_entrega'], [2,3])): ?>
            <option  value="<?= $me['id_entrega'] ?>"><?= $me['nombre'] ?></option>
          <?php endif; ?>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6">
      <label for="codigoSucursal" class="form-label text-dark">Código de la sucursal</label>
      <input type="text" name="sucursal_envio" class="form-control text-dark" id="codigoSucursal" placeholder="Ej. 2140">
    </div>
  </div>
  <div class="mb-3">
    <label for="nombreSucursal" class="form-label text-dark">Nombre de la sucursal</label>
    <input type="text"   name="direccion_envio" class="form-control text-dark" id="nombreSucursal" placeholder="Ej. Sucursal Barquisimeto Oeste">
  </div>
  </div>
</template>

<!-- delivery -->
<template id="form-op3">
  <div class="card p-3 sombra-rosada seccion-formulario">
   <div class="row g-3 mb-3">
    <p class="text-center text-color1">Delivery</p> 
    
    
    
                             <div class="col-md-3">
                             <label for="delivery" class="labeldel">Delivery:</label>
                                <select id="delivery" name="id_delivery" class="form-select">
                                <option value="">Seleccione un Delivery</option>
                                <?php foreach ($delivery_activos as $d): ?>
                                    <option value="<?= $d['id_delivery'] ?>">
                                        <?= $d['tipo'] ?> ---
                                        <?= $d['nombre'] ?>
                                      
                                    </option>
                                <?php endforeach; ?>
                                </select>
                              </div>
     
  
                            <div class="col-md-3"> 
                              <label for="zona" class="labeldel">Zona:</label>
                              <select id="zona" name="zona" class="form-select text-gray-900" id="zona">
                                <option value="">-- Selecciona una zona --</option>
                                <option value="norte">Norte</option>
                                <option value="sur">Sur</option>
                                <option value="este">Este</option>
                                <option value="oeste">Oeste</option>
                                <option value="centro">Centro</option>
                              </select>
                              </div>

                              <div class="col-md-3">
                              <label for="parroquia" class="labeldel">Parroquia:</label>
                              <select id="parroquia" name="parroquia" class="form-select text-gray-900" id="parroquia">
                                <option value="">-- Selecciona una parroquia --</option>
                              </select>
                              </div>
                              <div class="col-md-3">
                              <label for="sector" class="labeldel">Sector:</label>
                              <select id="sector" name="sector" class="form-select text-gray-900" id="sector">
                                <option value="">-- Selecciona un sector --</option>
                              </select>
                            </div>
  </div>
  <div class="mb-3">
    <label for="direccion" class="form-label text-dark">Dirección exacta</label>
    <input type="text" name="direccion_envio" class="form-control text-dark" id="direccion" placeholder="Ej. Calle 5, casa 12, frente a panadería...">
  </div>
  </div>
</template>



<!-- php Publicidad Insta, Publicidad calidad, footer y JS--> 
<?php include 'vista/complementos/footer_catalogo.php' ?>
<script src="assets/js/Pedidoentrega.js"></script>

</script>
</body>

</html>