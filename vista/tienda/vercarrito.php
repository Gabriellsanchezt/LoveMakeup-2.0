
<!DOCTYPE html>
<html lang="en">

<head>
<!-- php CSS, Meta y titulo--> 
<?php include 'vista/complementos/head_catalogo.php' ?>

</head>

<body>
<?php
$carrito = $_SESSION['carrito'] ?? [];
$total = 0;
?>

<!-- |||||||||||||||| LOADER ||||||||||||||||||||-->
  <div class="preloader-wrapper">
    <div class="preloader">
    </div>
  </div>
<!-- |||||||||||||||| LOADER ||||||||||||||||||||-->




<!-- php ENCABEZADO LOGO, ICONO CARRITO Y LOGIN--> 
<?php include 'vista/complementos/nav_catalogo.php' ?>


<section id="latest-blog" class="section-padding pt-0">
    

    <style>
         .cart-step {
      display: flex;
      align-items: center;
      margin-bottom: 30px;
    }
    .cart-step div {
      font-weight: bold;
      margin-right: 15px;
    }
    .cart-step .current-step {
      color: black;
    }
    .cart-step .step-number {
      width: 30px;
      height: 30px;
      background-color: #000;
      color: white;
      border-radius: 50%;
      text-align: center;
      line-height: 30px;
      margin-right: 10px;
    }

  .tabla-carrito {
    background-color: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    overflow: hidden;
  }

  .tabla-carrito th {
    background-color: #f8f9fa;
    color: #495057;
    font-weight: 600;
    text-align: center;
    padding: 12px;
  }

  .tabla-carrito td {
    vertical-align: middle;
    text-align: center;
    padding: 12px;
  }

  .btn-eliminar {
    background-color: #ffe6e6;
    color: #dc3545;
    border: none;
  }

  .btn-eliminar:hover {
    background-color: #ffcccc;
  }

  .quantity-control .btn {
    border-radius: 50%;
    width: 32px;
    height: 32px;
    font-size: 16px;
  }

  .img-thumbnail {
    border-radius: 8px;
  }

  .precio-unitario,
  .subtotal {
    font-weight: bold;
    color: #6c757d;
  }

  .cantidad {
    min-width: 32px;
    display: inline-block;
  }

  .detalle-compra-container {
    text-align: center;
    margin-top: 24px;
  }

  .enlace-compra {
    display: inline-block;
    background-color: #f3f4f6; /* gris claro */
    color: #374151; /* gris oscuro elegante */
    padding: 10px 24px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 500;
    font-size: 16px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
  }

  .enlace-compra:hover {
    background-color: #e5e7eb;
    color: #111827;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
    transform: translateY(-1px);
  }

  .enlace-compra:active {
    transform: scale(0.98);
  }

  .enlace-compra-disabled {
    display: inline-block;
    background-color: #f9fafb;
    color: #9ca3af;
    padding: 10px 24px;
    border-radius: 12px;
    font-weight: 500;
    font-size: 16px;
    text-decoration: none;
    cursor: not-allowed;
    box-shadow: none;}


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


    </style>
<?php
$carritoVacio = empty($_SESSION['carrito']);
?>
        <!-- Step Indicator -->
 

        <div class="modal-body bg-s">
  <div class="mb-4">
    <div class="row">
      <div class="col-12">
        <div class="progress-steps">

          <div class="progress-bar-container" id="progress-bar-pasos" style="width: 0%;">
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

    <?php if (empty($carrito)): ?>
      
        <div class="container" id="Vcard" style=" border: 1px solid #ccc;border-top-left-radius:5px;border-top-right-radius:5px; padding:10px; display:flex;flex-direction: column;
 justify-content:center;align-items:center;">
        <svg class="codevz-cart-empty-svg" style="width: 70px;" fill="#676767" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 231.523 231.523" xml:space="preserve">
        <g>
          <path d="M107.415,145.798c0.399,3.858,3.656,6.73,7.451,6.73c0.258,0,0.518-0.013,0.78-0.04c4.12-0.426,7.115-4.111,6.689-8.231
            l-3.459-33.468c-0.426-4.12-4.113-7.111-8.231-6.689c-4.12,0.426-7.115,4.111-6.689,8.231L107.415,145.798z"></path>
          <path d="M154.351,152.488c0.262,0.027,0.522,0.04,0.78,0.04c3.796,0,7.052-2.872,7.451-6.73l3.458-33.468
            c0.426-4.121-2.569-7.806-6.689-8.231c-4.123-0.421-7.806,2.57-8.232,6.689l-3.458,33.468
            C147.235,148.377,150.23,152.062,154.351,152.488z"></path>
          <path d="M96.278,185.088c-12.801,0-23.215,10.414-23.215,23.215c0,12.804,10.414,23.221,23.215,23.221
            c12.801,0,23.216-10.417,23.216-23.221C119.494,195.502,109.079,185.088,96.278,185.088z M96.278,216.523
            c-4.53,0-8.215-3.688-8.215-8.221c0-4.53,3.685-8.215,8.215-8.215c4.53,0,8.216,3.685,8.216,8.215
            C104.494,212.835,100.808,216.523,96.278,216.523z"></path>
          <path d="M173.719,185.088c-12.801,0-23.216,10.414-23.216,23.215c0,12.804,10.414,23.221,23.216,23.221
            c12.802,0,23.218-10.417,23.218-23.221C196.937,195.502,186.521,185.088,173.719,185.088z M173.719,216.523
            c-4.53,0-8.216-3.688-8.216-8.221c0-4.53,3.686-8.215,8.216-8.215c4.531,0,8.218,3.685,8.218,8.215
            C181.937,212.835,178.251,216.523,173.719,216.523z"></path>
          <path d="M218.58,79.08c-1.42-1.837-3.611-2.913-5.933-2.913H63.152l-6.278-24.141c-0.86-3.305-3.844-5.612-7.259-5.612H18.876
            c-4.142,0-7.5,3.358-7.5,7.5s3.358,7.5,7.5,7.5h24.94l6.227,23.946c0.031,0.134,0.066,0.267,0.104,0.398l23.157,89.046
            c0.86,3.305,3.844,5.612,7.259,5.612h108.874c3.415,0,6.399-2.307,7.259-5.612l23.21-89.25C220.49,83.309,220,80.918,218.58,79.08z
             M183.638,165.418H86.362l-19.309-74.25h135.895L183.638,165.418z"></path>
          <path d="M105.556,52.851c1.464,1.463,3.383,2.195,5.302,2.195c1.92,0,3.84-0.733,5.305-2.198c2.928-2.93,2.927-7.679-0.003-10.607
            L92.573,18.665c-2.93-2.928-7.678-2.927-10.607,0.002c-2.928,2.93-2.927,7.679,0.002,10.607L105.556,52.851z"></path>
          <path d="M159.174,55.045c1.92,0,3.841-0.733,5.306-2.199l23.552-23.573c2.928-2.93,2.925-7.679-0.005-10.606
            c-2.93-2.928-7.679-2.925-10.606,0.005l-23.552,23.573c-2.928,2.93-2.925,7.679,0.005,10.607
            C155.338,54.314,157.256,55.045,159.174,55.045z"></path>
          <path d="M135.006,48.311c0.001,0,0.001,0,0.002,0c4.141,0,7.499-3.357,7.5-7.498l0.008-33.311c0.001-4.142-3.356-7.501-7.498-7.502
            c-0.001,0-0.001,0-0.001,0c-4.142,0-7.5,3.357-7.501,7.498l-0.008,33.311C127.507,44.951,130.864,48.31,135.006,48.311z"></path>
        </g></svg>
                 <p style="font-weight: bold;
">¡Parece que tu carrito está vacío!</p>
<p>Es hora de empezar a comprar</p>
            </div>

    <?php else: ?>
        <div class="table-responsive">
      

<table class="table table-hover tabla-carrito">
  <thead>
    <tr>
      <th></th>
      <th>Imagen</th>
      <th>Producto</th>
      <th>Precio</th>
      <th>Cantidad</th>
      <th>Subtotal</th>
    </tr>
  </thead>
  <tbody>

    <?php foreach ($carrito as $item):
      $id = $item['id'];
      $cantidad = $item['cantidad'];
      $precioUnitario = $cantidad >= $item['cantidad_mayor'] ? $item['precio_mayor'] : $item['precio_detal'];
      $subtotal = $cantidad * $precioUnitario;
      $total += $subtotal;
     
    ?>
    <tr data-id="<?= $id ?>">
      <td>
        <button class="btn btn-eliminar" data-id="<?= $id ?>"><i class="fa-solid fa-x"></i></button>
      </td>
      <td><img src="<?= htmlspecialchars($item['imagen']) ?>" class="img-thumbnail" width="60"></td>
      <td><strong><?= htmlspecialchars($item['nombre']) ?></strong></td>
      <td class="precio-unitario">$<?= number_format($precioUnitario, 2) ?></td>
      <td class="quantity-control">
        <div class="d-flex justify-content-center align-items-center">
          <button class="btn btn-outline-secondary btn-sm btn-menos" data-id="<?= $id ?>">−</button>
          <span class="mx-2 cantidad"><?= $cantidad ?></span>
          <button class="btn btn-outline-secondary btn-sm btn-mas" data-id="<?= $id ?>" data-stock="<?= $item['stockDisponible'] ?>">+</button>
        </div>
      </td>
      <td class="subtotal">$<?= number_format($subtotal, 2) ?></td>
   
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

        </div>

       

          <!-- Total general -->
          <div class="row mt-3">
            <style>
.card-total {
  position: relative;
  display: flex;
  flex-direction: column;
  min-width: 0;
  word-wrap: break-word;
  background-color: #fff;
  background-clip: border-box;
  border: 1px solid rgba(0, 0, 0, 0.125);
  border-radius: 0.5rem; /* 8px */
  box-shadow: 0 0.5rem 1.2rem rgb(189 197 209 / 20%);
  border-color:#fff;
}
.card-body-total {
  flex: 1 1 auto;
  padding: 1.5rem; /* 24px */
 
 
 

}

.card-total, .card-body-total, h4, span {
    font-family: "Open Sans", "Helvetica Neue", Helvetica, Arial, sans-serif;
}

.text-success {
  color: #2dce89 !important; /* verde característico de Argon Dashboard */
}


            </style>
                <div class="col-md-10"></div>
                <div class="col-md-2">
                  <div class="card-total">
                    <div class=" card-body-total text-center ">
                      <h4>Total: $<span id="total-carrito" class="total-general text-success"><?= number_format($total, 2) ?></span></h4>
                    
                    </div>
                  </div>
                  
    <?php endif; ?>
</div>

<div class="d-flex justify-content-between container mt-3">
<div>  
<a href="?pagina=catalogo_producto"  class="btn btn-secondary "><i class="fa-solid fa-arrow-left me-1"></i> Atrás</a>
</div>
<div> 
<a href="?pagina=reserva_cliente" class="btn btn-primary "> <i class="fa-solid fa-arrow-right"> </i> Reservar</a>
</div>
<div>  
  <a  href="?pagina=Pedidoentrega" id="btn-siguiente" class="btn btn-primary"> <i class="fa-solid fa-arrow-right"> </i> Comprar</a>
  </div>
</div>
 </section>
<!-- php Publicidad Insta, Publicidad calidad, footer y JS--> 
<?php include 'vista/complementos/footer_catalogo.php' ?>
   <script src="assets/js/vercarrito.js"></script>
   <script src="assets/js/pasos.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    setStep(1); // Inicializar barra

    const totalSteps = 4;
    let currentStep = 1;

    const btnContinuar = document.getElementById("btn-siguiente");

    if (btnContinuar) {
        btnContinuar.addEventListener("click", function(e) {
            e.preventDefault(); // Evita que navegue inmediatamente

            // Avanzar la barra
            if (currentStep < totalSteps) currentStep++;
            setStep(currentStep);

            // Esperar 800ms antes de navegar
            setTimeout(() => {
                window.location.href = btnContinuar.href;
            }, 800); // 0.8 segundos de retardo
        });
    }
});
</script>

</body>

</html>
