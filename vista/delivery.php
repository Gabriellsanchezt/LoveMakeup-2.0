<!DOCTYPE html>
<html lang="es">
<head>
  <?php include 'complementos/head.php'; ?> 
  <title> Delivery | LoveMakeup </title> 
  <link rel="stylesheet" href="assets/css/formulario.css">
  
  <style>
    /* Estilos para los botones modernos */
    .btn-modern {
      border-radius: 8px;
      padding: 8px 16px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      transition: all 0.3s ease;
      border: none;
      position: relative;
      overflow: hidden;
    }
    
    .btn-modern::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
      transition: left 0.5s;
    }
    
    .btn-modern:hover::before {
      left: 100%;
    }
    
    .btn-guardar {
      background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
      color: white;
    }
    
    .btn-guardar:hover {
      background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(40, 167, 69, 0.3);
    }
    
    .btn-limpiar {
      background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
      color: white;
    }
    
    .btn-limpiar:hover {
      background: linear-gradient(135deg, #5a6268 0%, #343a40 100%);
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(108, 117, 125, 0.3);
    }
  </style>
</head>

<body class="g-sidenav-show bg-gray-100">
  <?php include 'complementos/sidebar.php'; ?>

  <main class="main-content position-relative border-radius-lg ">
    <nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl " id="navbarBlur" data-scroll="false">
      <div class="container-fluid py-1 px-3">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
            <li class="breadcrumb-item text-sm"><a class="opacity-5 text-white" href="#">Clientes y Entregas</a></li>
            <li class="breadcrumb-item text-sm text-white active" aria-current="page">Delivery</li>
          </ol>
          <h6 class="font-weight-bolder text-white mb-0">Gestionar Delivery</h6>
        </nav>
        <?php include 'complementos/nav.php'; ?>
      </div>
    </nav>
<!-- |||||||||||||||| LOADER ||||||||||||||||||||-->
  <div class="preloader-wrapper">
    <div class="preloader">
    </div>
  </div> 
<!-- |||||||||||||||| LOADER ||||||||||||||||||||-->
<div class="container-fluid py-4"> <!-- DIV CONTENIDO -->
  <div class="row"> <!-- CARD PRINCIPAL-->  
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-header pb-0 div-oscuro-2">  <!-- CARD N-1 -->  
          <!--Titulo de página -->
          <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h4 class="mb-0 texto-quinto">
              <i class="fa-solid fa-motorcycle me-2 icoM" style="color: #f6c5b4;"></i> Delivery
            </h4>
            
            <div class="d-flex align-items-center gap-2">
              <?php if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(11, 'registrar')): ?>
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#registro" id="btnAbrirRegistrar" title="(CONTROL + ALT + N) Registrar delivery">
                  <span class="icon text-white">
                    <i class="fas fa-file-medical me-2"></i>
                  </span>
                  <span class="text-white">Registrar</span>
                </button>
              <?php endif; ?>
              
              <button type="button" class="btn btn-primary" id="btnAyuda" title="(CONTROL + ALT + A) click para ver la ayuda">
                <span class="icon text-white">
                  <i class="fas fa-info-circle me-2"></i>
                </span>
                <span class="text-white">Ayuda</span>
              </button>
            </div>
          </div>
          
          <div class="table-responsive"> <!-- comienzo div table-->
            <!-- comienzo de tabla-->                      
            <table class="table table-m table-hover" id="myTable" width="100%" cellspacing="0">
              <thead class="table-color">
                <tr>
                  <th class="text-white text-center">Nombre</th>
                  <th class="text-white text-center">Tipo</th>
                  <th class="text-white text-center">Contacto</th>
                  <th class="text-white text-center">Estatus</th>
                  <th class="text-white text-center">Acción</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  $estatus_texto = array(
                    1 => "Activo",
                    2 => "Inactivo"
                  );
              
                  // Usar los mismos colores que en el módulo de usuario
                  $estatus_classes = array(
                    1 => 'badge bg-success text-dark',
                    2 => 'badge bg-danger'
                  );

                  foreach ($registro as $dato): ?>
                <tr>
                  <td>
                    <div class="d-flex align-items-center">
                      <div class="me-3">
                        <i class="fa-solid fa-motorcycle fa-2x" style="color: #f6c5b4;"></i>
                      </div>
                      <div>
                        <div class="text-dark texto-secundario">
                          <b><?php echo $dato['nombre']; ?></b>
                        </div>
                      </div>
                    </div>
                  </td>
                  <td class="text-center text-dark texto-secundario">
                    <div><?php echo $dato['tipo']; ?></div>
                  </td>
                  <td class="text-center text-dark texto-secundario">
                    <div><?php echo $dato['contacto']; ?></div>
                  </td>
                  <td class="text-center">
                    <span class="<?php echo $estatus_classes[$dato['estatus']]; ?>">
                      <?php echo $estatus_texto[$dato['estatus']]; ?>
                    </span>
                  </td>
                  <td class="text-center">
                    <?php if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(11, 'ver')): ?>
                      <button type="button"
                              class="btn btn-info btn-sm me-1"
                              data-bs-toggle="modal"
                              data-bs-target="#verDetallesModal<?php echo $dato['id_delivery']; ?>">
                        <i class="fas fa-eye" title="Ver Detalles"></i>
                      </button>
                    <?php endif; ?>

                    <?php if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(11, 'editar')): ?>
                      <button type="button" class="btn btn-primary btn-sm modificar me-1" 
                              onclick="abrirModalModificar(<?php echo $dato['id_delivery']; ?>)" title="Editar datos del delivery"> 
                        <i class="fas fa-pencil-alt" title="Editar"> </i> 
                      </button>
                    <?php endif; ?>

                    <?php if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(11, 'eliminar')): ?>
                      <button type="button" class="btn btn-danger btn-sm eliminar" 
                              onclick="eliminarDelivery(<?php echo $dato['id_delivery']; ?>)" title="Eliminar delivery">
                        <i class="fas fa-trash-alt" title="Eliminar"> </i>
                      </button>
                    <?php endif; ?>
                  </td>
                </tr>
                
                <!-- Modal para Ver Detalles -->
                <div class="modal fade" id="verDetallesModal<?php echo $dato['id_delivery']; ?>" tabindex="-1" aria-hidden="true">
                  <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content modal-producto">
                      <div class="modal-header">
                        <h5 class="modal-title text-white">
                          <i class="fa-solid fa-motorcycle me-2"></i>Detalles del Delivery
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" title="(CONTROL + ALT + X) Cerrar"></button>
                      </div>
                      <div class="modal-body bg-s">
                        <div class="seccion-formulario">
                          <h6 class="texto-quinto"><i class="fas fa-truck"></i> Información del Delivery</h6>
                          <div class="row">
                            <div class="col-md-12">
                              <p><strong>Nombre:</strong> <?php echo htmlspecialchars($dato['nombre']); ?></p>
                            </div>
                            <div class="col-md-12">
                              <p><strong>Tipo:</strong> <?php echo htmlspecialchars($dato['tipo']); ?></p>
                            </div>
                            <div class="col-md-12">
                              <p><strong>Contacto:</strong> <?php echo htmlspecialchars($dato['contacto']); ?></p>
                            </div>
                            <div class="col-md-12">
                              <p><strong>Estatus:</strong> 
                                <span class="<?php echo $estatus_classes[$dato['estatus']]; ?>">
                                  <?php echo $estatus_texto[$dato['estatus']]; ?>
                                </span>
                              </p>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>  <!-- Fin div table-->
        </div><!-- FIN CARD N-1 -->  
      </div>
    </div>  
  </div><!-- FIN CARD PRINCIPAL-->  
</div>

<!-- Modal único para Registrar/Modificar -->
<div class="modal fade" id="registro" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content modal-producto">
      <div class="modal-header">
        <h5 class="modal-title fs-5" id="modalTitle">
          <i class="fa-solid fa-motorcycle"></i>
          <span id="modalTitleText">Registrar Delivery</span>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" title="(CONTROL + ALT + X) Cerrar" aria-label="Close"></button>
      </div>
      <div class="modal-body bg-s">
        <form id="formDelivery" enctype="multipart/form-data" autocomplete="off">
          <input type="hidden" name="id_delivery" id="id_delivery" value="">
          <input type="hidden" name="accion" id="accion" value="registrar">
          
          <div class="seccion-formulario">
            <h6 class="texto-quinto"><i class="fas fa-truck"></i> Información del Delivery</h6>
            <div class="row g-3">
              <div class="col-md-12">
                <label for="nombre">NOMBRE</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                  <input type="text" class="form-control" name="nombre" id="nombre" placeholder="Nombre del delivery" maxlength="80" required>
                </div>
                <span id="snombre" class="error-message"></span>
              </div>
            </div>
            
            <div class="row g-3 mt-2">
              <div class="col-md-12">
                <label for="contacto">NÚMERO DE TELÉFONO</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="fa-solid fa-phone"></i></span>
                  <input type="text" class="form-control" name="contacto" id="contacto" placeholder="0414-0000000" maxlength="12" required>
                </div>
                <span id="scontacto" class="error-message"></span>
              </div>
            </div>

            <div class="row g-3 mt-2">
              <div class="col-md-12">
                <label for="tipo">TIPO DE VEHÍCULO</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="fa-solid fa-motorcycle"></i></span>
                  <select class="form-select" name="tipo" id="tipo" required>
                    <option value="">Seleccione un tipo</option>
                    <option value="Carro">Carro</option>
                    <option value="Moto">Moto</option>
                    <option value="Bicicleta">Bicicleta</option>
                    <option value="Camión">Camión</option>
                  </select>
                </div>
                <span id="stipo" class="error-message"></span>
              </div>
            </div>
            
            <div class="row g-3 mt-2">
              <div class="col-md-12">
                <label for="estatus">ESTATUS</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="fa-solid fa-toggle-on"></i></span>
                  <select class="form-select" name="estatus" id="estatus" required>
                    <option value="1">Activo</option>
                    <option value="2">Inactivo</option>
                  </select>
                </div>
                <span id="sestatus" class="error-message"></span>
              </div>
            </div>
          </div>

          <div class="col">
            <div class="info-box">
              <div class="info-icon">
                <i class="fa-solid fa-circle-info"></i>
              </div>
              <div class="info-content">
                <strong>Información Importante:</strong>
                <p>Los deliveries registrados podrán ser asignados para gestionar la entrega de productos del sistema.</p>
                <p><b>Estatus:</b></p>
                <ul class="text-muted">
                  <li><b>Activo:</b> El delivery está disponible</li>
                  <li><b>Inactivo:</b> El delivery no está disponible</li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Botones -->
          <div class="col-12 text-center">
            <button type="button" class="btn btn-modern btn-guardar me-3" id="btnEnviar">
              <i class="fa-solid fa-floppy-disk me-2"></i> <span id="btnText">Registrar</span>
            </button>
            <button type="reset" class="btn btn-modern btn-limpiar">
              <i class="fa-solid fa-eraser me-2"></i> Limpiar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include 'complementos/footer.php'; ?>
</main>

<!-- para el datatable-->
<script src="assets/js/demo/datatables-demo.js"></script>
<script src="assets/js/delivery.js"></script>
</body>
</html>