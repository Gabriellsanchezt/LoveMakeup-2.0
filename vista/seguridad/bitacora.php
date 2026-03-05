<!DOCTYPE html>
<html lang="es">

<head>
  <!-- php barra de navegacion-->
  <?php include 'vista/complementos/head.php' ?> 
  <title>Bitácora del Sistema | LoveMakeup</title>
  <!-- Estilos para la tabla de bitácora -->
  <style>
    .badge {
      font-size: 0.75em;
      padding: 0.25em 0.6em;
    }
    .btn-sm {
      padding: 0.25rem 0.5rem;
      font-size: 0.875rem;
    }
  </style>
 
</head>

<body class="g-sidenav-show bg-gray-100">
  
<!-- php barra de navegacion-->
<?php include 'vista/complementos/sidebar.php' ?>

<main class="main-content position-relative border-radius-lg ">
<!-- ||| Navbar ||-->
<nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl " id="navbarBlur" data-scroll="false">
  <div class="container-fluid py-1 px-3">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-white" href="#">Seguridad</a></li>
        <li class="breadcrumb-item text-sm text-white active" aria-current="page">Bitácora</li>
      </ol>
      <h6 class="font-weight-bolder text-white mb-0">Bitácora del Sistema</h6>
    </nav>
<!-- php barra de navegacion-->    
<?php include 'vista/complementos/nav.php' ?>
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
                  <i class="fas fa-history fa-sm text-primary-50"></i> Registro de Actividades
                </h4>
              <?php if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(15, 4)): ?>
                <div class="btn-group ms-2" role="group">
                  <button type="button" class="btn btn-warning btn-sm" id="limpiarBitacora" title="Limpiar bitácora antigua">
                    <i class="fas fa-broom me-1"></i> Limpiar
                  </button>
                </div>
              <?php endif; ?>
              </div>

              <div class="table-responsive"> <!-- comienzo div table-->
                <table class="table table-m table-bordered table-hover display responsive nowrap" id="myTable" width="100%" cellspacing="0">
                  <thead class="table-color">
                    <tr>
                      <th class="text-white">Fecha y Hora</th>
                      <th class="text-white">Acción</th>
                      <th class="text-white">Usuario</th>
                      <th class="text-white">Rol</th>
                      <th class="text-white">Detalles</th>
                    </tr>
                  </thead>
                  <tbody id="bitacora-tbody">
                  <?php 
                    // Cargar solo los primeros 100 registros inicialmente
                   
                   
                  
                    
            
                      foreach ($registro as $dato) { 
                        // Validar que los datos requeridos existan
                        $id_bitacora = isset($dato['id_bitacora']) ? (int)$dato['id_bitacora'] : 0;
                        $fecha_hora = isset($dato['fecha_hora']) ? htmlspecialchars($dato['fecha_hora'], ENT_QUOTES, 'UTF-8') : '';
                        $accion = isset($dato['accion']) ? htmlspecialchars($dato['accion'], ENT_QUOTES, 'UTF-8') : 'Sin acción';
                        $nombre = isset($dato['nombre']) ? htmlspecialchars($dato['nombre'], ENT_QUOTES, 'UTF-8') : '';
                        $apellido = isset($dato['apellido']) ? htmlspecialchars($dato['apellido'], ENT_QUOTES, 'UTF-8') : '';
                        $nombre_usuario = isset($dato['nombre_usuario']) ? htmlspecialchars($dato['nombre_usuario'], ENT_QUOTES, 'UTF-8') : 'N/A';
                        
                        if ($id_bitacora > 1) {
                      ?>
                        <tr>
                          <td class="fecha-bitacora texto-secundario" data-fecha="<?php echo $fecha_hora ?>">
                            <?php 
                              // Mostrar fecha formateada si existe
                              if (isset($dato['fecha_hora_formateada'])) {
                                echo $dato['fecha_hora_formateada'];
                              } else {
                                echo $fecha_hora;
                              }
                            ?>
                          </td>
                          <td>
                            <span class="badge bg-<?php 
                              switch($accion) {
                                case 'CREAR': echo 'success'; break;
                                case 'MODIFICAR': echo 'primary'; break;
                                case 'ELIMINAR': echo 'danger'; break;
                                case 'ACCESO A MÓDULO': echo 'secondary'; break;
                                case 'CAMBIO_ESTADO': echo 'warning'; break;
                                default: echo 'secondary';
                              }
                            ?>">
                              <?php echo $accion ?>
                            </span>
                          </td>
                          <td class="texto-secundario"><?php echo trim($nombre . ' ' . $apellido) ?: 'N/A' ?></td>
                          <td class="texto-secundario"><?php echo $nombre_usuario ?></td>
                          <td class="text-center">
                            <button class="btn btn-primary btn-sm" 
                                    onclick="verDetalles(<?php echo $id_bitacora ?>)"
                                    title="Ver detalles">
                              <i class="fas fa-info-circle me-2"></i> Ver
                            </button>
                          </td>
                        </tr>
                      <?php 
                        }
                      }
                   ?>
                    
                  </tbody>
                </table>
              </div>
              <?php if ($total_registros > $_SESSION['limite_bitacora']): ?>
                    <form method="POST" action="?pagina=bitacora">
                      <div class="text-center ">
                        <button type="submit" name="ver_mas" class="btn btn-primary w-50 mt-3">
                            <i class="fas fa-plus-circle"></i> Ver más registros (+100)
                        </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
          </div>
        </div>  
    </div>
    
    <!-- Variable JavaScript con información de paginación -->
  

<!-- Modal de Detalles -->
<div class="modal fade" id="detallesModal" tabindex="-1" aria-labelledby="detallesModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class=" modal-header table-color">
        <h5 class="modal-title text-white" id="detallesModalLabel">
          <i class="fas fa-info-circle me-2"></i>Detalles del Registro
        </h5>
        <button type="button" class="btn-close btn-close-white text-white" data-bs-dismiss="modal" aria-label="Close" style="filter: brightness(0) invert(1);"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <!-- Columna Izquierda - Información del Usuario (Tarjeta grande) -->
          <div class="col-md-6 d-flex">
            <div class="card shadow-sm w-100">
              <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-user me-2"></i>Información del Usuario</h6>
              </div>
              <div class="card-body">
                <div class="mb-3">
                  <label class="text-muted small">Nombre Completo:</label>
                  <p class="fw-bold mb-2" id="detalle-usuario"></p>
                </div>
                <div class="mb-3">
                  <label class="text-muted small">Cédula:</label>
                  <p class="fw-bold mb-2" id="detalle-cedula"></p>
                </div>
                <div class="mb-3">
                  <label class="text-muted small">Correo Electrónico:</label>
                  <p class="fw-bold mb-2" id="detalle-correo"></p>
                </div>
                <div class="mb-3">
                  <label class="text-muted small">Rol del Usuario:</label>
                  <p class="fw-bold mb-2" id="detalle-rol"></p>
                </div>
              </div>
            </div>
          </div>
          <!-- Columna Derecha - Evento y Descripción (dos tarjetas pequeñas) -->
          <div class="col-md-6 d-flex flex-column">
            <!-- Información del Evento -->
            <div class="card shadow-sm mb-3 flex-fill">
              <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-clock me-2"></i>Información del Evento</h6>
              </div>
              <div class="card-body">
                <div class="mb-3">
                  <label class="text-muted small">Fecha y Hora:</label>
                  <p class="fw-bold mb-2" id="detalle-fecha"></p>
                </div>
                <div class="mb-3">
                  <label class="text-muted small">Tipo de Acción:</label>
                  <div id="detalle-accion"></div>
                </div>
              </div>
            </div>
            <!-- Descripción Detallada -->
            <div class="card shadow-sm flex-fill">
              <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-file-alt me-2"></i>Descripción Detallada</h6>
              </div>
              <div class="card-body">
                <div id="detalle-descripcion"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Script para inicializar DataTable -->
<script src="assets/js/demo/datatables-demo.js"></script>

<!-- php barra de navegacion-->
<?php include 'vista/complementos/footer.php' ?>



<!-- Script para el manejo de bitácora -->
<script src="assets/js/bitacora.js"></script>


</body>
</html>
