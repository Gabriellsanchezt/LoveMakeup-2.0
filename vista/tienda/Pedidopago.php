


<?php

// Recuperar datos de entrega y carrito
$entrega = $_SESSION['pedido_entrega'];
$carrito = $_SESSION['carrito'];





// Calcular total USD
$total = 0;
foreach ($carrito as $item) {
    $cantidad = $item['cantidad'];
    $precioUnitario = $cantidad >= $item['cantidad_mayor'] ? $item['precio_mayor'] : $item['precio_detal'];
    $total += $cantidad * $precioUnitario;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include 'vista/complementos/head_catalogo.php'; ?>
</head>
<body>
<!----  tasa dolar   --->
<script>
async function obtenerTasaDolarApi() {
    try {
        const respuesta = await fetch('https://ve.dolarapi.com/v1/dolares/oficial');
        if (!respuesta.ok) {
            throw new Error(`Error HTTP: ${respuesta.status}`);
        }
        const datos = await respuesta.json();
        const tasaBCV = parseFloat(datos.promedio).toFixed(2); 
        var totalBs = <?php echo $total; ?>;
        var resultadoBs = (totalBs * tasaBCV).toFixed(2); 
        document.getElementById("bs").textContent = "Resultado: " + resultadoBs + " Bs";  
        document.getElementById("precio_total_bs").value = resultadoBs ;  
    } catch (error) {
        document.getElementById("bs").textContent = "Error al cargar el total";
        console.error("Error al obtener la tasa:", error);
    }
}
document.addEventListener("DOMContentLoaded", obtenerTasaDolarApi);
</script>
<!----  tasa dolar   --->

  <?php include 'vista/complementos/nav_catalogo.php'; ?>

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

.seccion-formularioo {
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

.text-success {
  color: #2dce89 !important; /* verde característico de Argon Dashboard */
}

  </style>

  <section id="latest-blog" class="section-padding pt-0">
    <div class="container-lg">
        <div class="pasos-container">
    <div class="paso completado">
      <div class="circulo">1</div>
      <span>Producto</span>
    </div>
    <div class="paso completado">
      <div class="circulo">2</div>
      <span>Entrega</span>
    </div>
    <div class="paso actual">
      <div class="circulo">3</div>
      <span>Pago</span>
    </div>
    <div class="paso completado">
      <div class="circulo">4</div>
      <span>Confirmación</span>
    </div>
  </div>


   <div class="row g-5 m-5 ">

     <div class="col-md-6 sombra-suave seccion-formulario card mb-3" style="background-color:#ffff;">    <!-- D1 -->
       <br>
       <h4 class="mb-3">Completar pago | Pago Movil</h4>
          <form id="formPago" class="row g-4" enctype="multipart/form-data">
            <!-- flag para AJAX -->
           
            <input type="hidden" name="continuar_pago" value="1">

            <!-- Datos ocultos de entrega -->
            <?php foreach (
                ['id_metodoentrega','direccion_envio','sucursal_envio','empresa_envio','zona','parroquia','sector'] as $field
            ):
                if (isset($entrega[$field])):
                    $val = htmlspecialchars($entrega[$field], ENT_QUOTES);
            ?>
            <input type="hidden" name="<?= $field ?>" value="<?= $val ?>">
            <?php endif; endforeach; ?>

            <!-- Datos ocultos de persona -->
            <input type="hidden" name="id_persona" value="<?= $_SESSION['id'] ?>">

            <!-- Datos ocultos de carrito -->
            <?php foreach ($carrito as $i => $item): ?>
            <input type="hidden" name="carrito[<?= $i ?>][id]" value="<?= $item['id'] ?>">
            <input type="hidden" name="carrito[<?= $i ?>][cantidad]" value="<?= $item['cantidad'] ?>">
            <input type="hidden" name="carrito[<?= $i ?>][cantidad_mayor]" value="<?= $item['cantidad_mayor'] ?>">
            <input type="hidden" name="carrito[<?= $i ?>][precio_detal]" value="<?= $item['precio_detal'] ?>">
            <input type="hidden" name="carrito[<?= $i ?>][precio_mayor]" value="<?= $item['precio_mayor'] ?>">
            <?php endforeach; ?>

            <!-- Totales -->
            <input type="hidden" name="precio_total_usd" id="precio_total_usd" value="<?= $total ?> ">
           
            <input type="hidden" name ="precio_total_bs" id="precio_total_bs" value="">
          
            <!-- Método de Pago -->
       
            
              <input type="hidden" value="1" name="id_metodopago" id="metodopago">

              <div class="col-md-6">
              <label class="form-label">Banco de Origen</label>
              <select name="banco" id="banco" class="form-select" required>
              <option value="0102-Banco De Venezuela">0102-Banco De Venezuela</option>
                                       <option value="0156-100% Banco ">0156-100% Banco </option>
                                       <option value="0172-Bancamiga Banco Universal,C.A">0172-Bancamiga Banco Universal,C.A</option>
                                       <option value="0114-Bancaribe">0114-Bancaribe</option>
                                       <option value="0171-Banco Activo">0171-Banco Activo</option>
                                       <option value="0166-Banco Agricola De Venezuela">0166-Banco Agricola De Venezuela</option>
                                       <option value="0128-Bancon Caroni">0128-Bancon Caroni</option>
                                       <option value="0163-Banco Del Tesoro">0163-Banco Del Tesoro</option>
                                       <option value="0175-Banco Digital De Los Trabajadores, Banco Universal">0175-Banco Digital De Los Trabajadores, Banco Universal</option>
                                       <option value="0115-Banco Exterior">0115-Banco Exterior</option>
                                       <option value="0151-Banco Fondo Comun">0151-Banco Fondo Comun</option>
                                       <option value="0173-Banco Internacional De Desarrollo">0173-Banco Internacional De Desarrollo</option>
                                       <option value="0105-Banco Mercantil">0105-Banco Mercantil</option>
                                       <option value="0191-Banco Nacional De Credito">0191-Banco Nacional De Credito</option>
                                       <option value="0138-Banco Plaza">0138-Banco Plaza</option>
                                       <option value="0137-Banco Sofitasa">0137-Banco Sofitasa</option>
                                       <option value="0104-Banco Venezolano De Credito">0104-Banco Venezolano De Credito</option>
                                       <option value="0168-Bancrecer">0168-Bancrecer</option>
                                       <option value="0134-Banesco">0134-Banesco</option>
                                       <option value="0177-Banfanb">0177-Banfanb</option>
                                       <option value="0146-Bangente">0146-Bangente</option>
                                       <option value="0174-Banplus">0174-Banplus</option>
                                       <option value="0108-BBVA Provincial">0108-BBVA Provincial</option>
                                       <option value="0157-Delsur Banco Universal">0157-Delsur Banco Universal</option>
                                       <option value="0601-Instituto Municipal De Credito Popular">0601-Instituto Municipal De Credito Popular</option>
                                       <option value="0178-N58 Banco Digital Banco Microfinanciero S.A">0178-N58 Banco Digital Banco Microfinanciero S.A</option>
                                       <option value="0169-R4 Banco Microfinanciero C.A.">0169-R4 Banco Microfinanciero C.A.</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Banco de Destino</label>
              <select name="banco_destino" id="banco_destino" class="form-select" required>
                <option value="0102-Banco De Venezuela">0102-Banco De Venezuela</option>
                <option value="0105-Banco Mercantil">0105-Banco Mercantil</option>
              </select>
            </div>


            <div class="col-md-6">
              <label class="form-label">Referencia Bancaria</label>
              <input type="text" name="referencia_bancaria" id="referencia_bancaria" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Teléfono Emisor</label>
              <input type="text" name="telefono_emisor" id="telefono_emisor" class="form-control" required>
            </div>
          
            <div class="col-12">
              <label class="form-label">Subir comprobante</label>
              <input type="file" name="imagen" id="imagen" class="form-control" accept=".jpg, .jpeg, .png, .webp" required>
            </div>

            <!-- Vista previa -->
<div class="col-12">
  
  <img id="preview" src="#" alt="Vista previa" class="img-fluid border rounded d-none" style="max-height: 300px;">
</div>



         
            <div class="form-check form-switch mb-3 ml-3">
              <input class="form-check-input" type="checkbox" id="che">
              <label class="form-check-label" for="che">Acepto los 
              <a type="button" class=" " data-bs-toggle="modal" data-bs-target="#scrollableModal">
  Terminos y Condiciones
            </a>
              </label>
            </div>
          <button type="button" id="btn-guardar-pago" class="btn btn-primary w-100 " disabled >Realizar Pago <i class="fa-solid fa-credit-card ms-2"></i></button>
          <p class="text-muted mt-2"><small>Compra con confianza, tu mejor elección te espera.</small></p>

           
          </form>

        </div>


    <div class="col-md-6 mr-5">   
  <?php if ( !empty($entrega['delivery_nombre'])): ?>
  <div class="col-md-10">
  <div class="card  sombra-suave seccion-formulario">
        <h5 style="color: #ec4899;">Datos del Delivery</h5>
      
        <p><strong>Nombre:</strong> <?= htmlspecialchars($entrega['delivery_nombre'] ?? '—') ?></p>
        <p><strong>Transporte:</strong> <?= htmlspecialchars($entrega['delivery_tipo'] ?? '-') ?></p>
        <p><strong>Contacto:</strong> <?= htmlspecialchars($entrega['delivery_contacto']?? '-') ?></p>
    
  </div>
  </div>
    <?php endif; ?>
        <div class="col-md-10 ">
          <div class="card  sombra-suave seccion-formulario">
             <p class="mb-1 " style="color: #ec4899;"><b>Datos del pago movil</b></p>
            <p class="mb-1">•Venezuela(0102) C.I.:V-30.352.937 Telf.:0414-509.49.59</p>
             <p class="mb-1">•Mercantil(0105) C.I.:V-11.787.299 Telf.:0426-554.13.64</p>
           <p></p>
            </div>
          </div>

     

        <!-- Resumen del Pedido -->
        <div class="col-md-10 ">
          <div class="card p-3 sombra-suave seccion-formulario">
            <h5 style="color: #ec4899;">Resumen del Pedido</h5>
            <p><?= count($carrito) ?> producto<?= count($carrito)!==1?'s':'' ?></p>
            <?php foreach($carrito as $item): 
              $precio = $item['cantidad'] >= $item['cantidad_mayor']
                        ? $item['precio_mayor']
                        : $item['precio_detal'];
              $subtotal = $item['cantidad'] * $precio;
            ?>
              <div class="d-flex mb-2">
                <img src="<?= htmlspecialchars($item['imagen']) ?>" alt="" style="width:50px;height:50px;object-fit:cover;margin-right:8px">
                <div>
                  <div><?= htmlspecialchars($item['nombre']) ?></div>
                  <small>Cantidad: <?= $item['cantidad'] ?> × $<?= number_format($precio,2) ?></small>
                  <div><strong>Subtotal: $<?= number_format($subtotal,2) ?></strong></div>
                </div>
              </div>
            <?php endforeach; ?>
            <hr>
            <div class="d-flex justify-content-between">
              <strong>Total USD:</strong>
              <strong class="text-success">$<?= number_format($total,2) ?></strong>
            </div>
            <div class="d-flex justify-content-between">
              <strong>Total Bs:</strong>
              <strong class="text-success" id="bs">0</strong>
            </div>
            
          </div>
        </div>
      </div>
    </div>
</div> 
    <div class="col-6 text-end">

<a href="?pagina=Pedidoentrega" class="btn btn-secondary">
  <i class="fa-solid fa-arrow-left me-2"></i> Volver a Entrega
</a>
</div>
  </section>


   <!-- modal terminos y condiciones  -->


   <div class="modal fade" id="scrollableModal" tabindex="-1" aria-labelledby="scrollableModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title" id="scrollableModalLabel">Terminos y Condiciones</h3>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="container py-5">
  
  <div class="accordion" id="termsAccordion">

    <div class="accordion-item">
      <h2 class="accordion-header" id="heading1">
        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1" aria-expanded="true">
          1. Generalidades
        </button>
      </h2>
      <div id="collapse1" class="accordion-collapse collapse show" data-bs-parent="#termsAccordion">
        <div class="accordion-body">
          Al acceder y utilizar este sitio web, usted acepta cumplir con los presentes Términos y Condiciones. Estos aplican a todas las compras realizadas a través de nuestra plataforma de comercio electrónico.
        </div>
      </div>
    </div>

    <div class="accordion-item">
      <h2 class="accordion-header" id="heading2">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
          2. Productos y Precios
        </button>
      </h2>
      <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#termsAccordion">
        <div class="accordion-body">
          Todos los productos ofrecidos están sujetos a disponibilidad. Nos reservamos el derecho de modificar precios, descripciones y condiciones de venta sin previo aviso.
        </div>
      </div>
    </div>

    <div class="accordion-item">
      <h2 class="accordion-header" id="heading3">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
          3. Proceso de Compra
        </button>
      </h2>
      <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#termsAccordion">
        <div class="accordion-body">
          El cliente debe verificar cuidadosamente los detalles del producto antes de confirmar su compra. Una vez realizado el pago, no se aceptan modificaciones ni cancelaciones del pedido.
        </div>
      </div>
    </div>

    <div class="accordion-item">
      <h2 class="accordion-header" id="heading4">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse4">
          4. Pagos
        </button>
      </h2>
      <div id="collapse4" class="accordion-collapse collapse" data-bs-parent="#termsAccordion">
        <div class="accordion-body">
          Aceptamos los métodos de pago indicados en el sitio web. Todos los pagos deben realizarse en su totalidad antes del envío del producto.
        </div>
      </div>
    </div>

    <div class="accordion-item">
      <h2 class="accordion-header" id="heading5">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse5">
          5. Envíos
        </button>
      </h2>
      <div id="collapse5" class="accordion-collapse collapse" data-bs-parent="#termsAccordion">
        <div class="accordion-body">
          Los tiempos de entrega son estimados y pueden variar según la ubicación y condiciones externas. No nos hacemos responsables por retrasos ocasionados por terceros.
        </div>
      </div>
    </div>

    <div class="accordion-item">
      <h2 class="accordion-header" id="heading6">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse6">
          6. Política de No Devoluciones
        </button>
      </h2>
      <div id="collapse6" class="accordion-collapse collapse" data-bs-parent="#termsAccordion">
        <div class="accordion-body">
        No aceptamos devoluciones ni cambios bajo ninguna circunstancia.

          Al realizar una compra, el cliente reconoce y acepta esta política.

          En caso de recibir un producto defectuoso o incorrecto, se deberá contactar al servicio de atención al cliente dentro de las 48 horas siguientes a la recepción para evaluar posibles soluciones.
        </div>
      </div>
    </div>

    <div class="accordion-item">
      <h2 class="accordion-header" id="heading7">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse7">
          7. Responsabilidad
        </button>
      </h2>
      <div id="collapse7" class="accordion-collapse collapse" data-bs-parent="#termsAccordion">
        <div class="accordion-body">
        No nos responsabilizamos por el uso indebido de los productos adquiridos.
        Nuestra responsabilidad se limita al valor del producto adquirido.
        </div>
      </div>
    </div>

    <div class="accordion-item">
      <h2 class="accordion-header" id="heading8">
        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse8">
          8. Propiedad Intelectual
        </button>
      </h2>
      <div id="collapse8" class="accordion-collapse collapse" data-bs-parent="#termsAccordion">
        <div class="accordion-body">
        Todo el contenido del sitio web (textos, imágenes, logotipos, etc.) está protegido por derechos de autor y no puede ser reproducido sin autorización.
        </div>
      </div>
    </div>


  </div>
</div>
    </div>
  </div>
   </div>

   
  <?php include 'vista/complementos/footer_catalogo.php'?>
  <script src="assets/js/Pedidopago.js"></script>
  <script src="assets/js/pasos.js"></script>
</body>
</html>
