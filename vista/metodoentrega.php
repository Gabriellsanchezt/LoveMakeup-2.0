<!DOCTYPE html>
<html lang="es">
<head>
  <?php include 'complementos/head.php'; ?> 
  <link rel="stylesheet" href="assets/css/formulario.css">
  <title>Método de Entrega | LoveMakeup</title>
</head>

<style>
  .is-valid {
    border: 2px solid #28a745 !important; /* verde */
  }

  .is-invalid {
    border: 2px solid #dc3545 !important; /* rojo */
  }

  .text-danger {
    color: #dc3545;
    font-size: 0.9em;
  }
</style>
<body class="g-sidenav-show bg-gray-100">

<?php include 'complementos/sidebar.php'; ?>
<main class="main-content position-relative border-radius-lg">
  <nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl" id="navbarBlur" data-scroll="false">
    <div class="container-fluid py-1 px-3">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
          <li class="breadcrumb-item text-sm"><a class="opacity-5 text-white" href="#">Clientes y Entregas</a></li>
          <li class="breadcrumb-item text-sm text-white active" aria-current="page">Método de Entrega</li>
        </ol>
        <h6 class="font-weight-bolder text-white mb-0">Gestionar Método de Entrega</h6>
      </nav>

      <?php include 'complementos/nav.php'; ?>
<!-- |||||||||||||||| LOADER ||||||||||||||||||||-->
  <div class="preloader-wrapper">
    <div class="preloader">
    </div>
  </div> 
<!-- |||||||||||||||| LOADER ||||||||||||||||||||-->
      <div class="container-fluid py-4">
        <div class="row">  
          <div class="col-12">
            <div class="card mb-4">
              <div class="card-header pb-0 div-oscuro-2">  
                <div class="d-sm-flex align-items-center justify-content-between mb-5">
                  <h4 class="mb-0 texto-quinto"><i class="fa-solid fa-truck me-2 icoM" style="color: #f6c5b4;"></i> Método de Entrega</h4>
                   <?php if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(12, 'registrar')): ?>  
                  <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#registro">
                    <i class="fas fa-file-medical"></i> Registrar
                  </button>
                  <?php endif; ?>
                </div>
                <div class="table-responsive">
                  <table class="table table-m table-bordered table-hover" id="myTable" width="100%" cellspacing="0">
                    <thead class="table-color">
                      <tr>
                        <th class="text-white">NOMBRE</th>
                        <th class="text-white">DESCRIPCIÓN</th>
                        <th class="text-white">ACCIONES</th>
                      </tr>
                    </thead>
                    <tbody id="entregaTableBody">
                      <?php foreach ($metodos as $dato): ?>
                      <tr id="fila-<?= $dato['id_entrega']; ?>">
                        <td class="texto-secundario"><?= htmlspecialchars($dato['nombre']); ?></td>
                        <td class="texto-secundario"><?= htmlspecialchars($dato['descripcion']); ?></td>
                        <td>
                           <?php if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(12, 'editar')): ?>  
                            <button class="btn btn-primary btn-sm btn-editar"
                              data-id="<?= $dato['id_entrega']; ?>"
                              data-nombre="<?= htmlspecialchars($dato['nombre']); ?>"
                              data-descripcion="<?= htmlspecialchars($dato['descripcion']); ?>">
                              <i class="fas fa-pencil-alt"></i>
                            </button>
                          <?php endif; ?>

                             <?php if ($_SESSION["nivel_rol"] == 3 && tieneAcceso(12, 'eliminar')): ?>  
                          <button class="btn btn-danger btn-sm" onclick="eliminarMetodoEntrega(<?= $dato['id_entrega']; ?>)">
                            <i class="fas fa-trash-alt"></i>
                          </button>
                            <?php endif; ?>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>

    <!-- Modal registrar -->
<div class="modal fade" id="registro" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header header-color">
        <h1 class="modal-title fs-5">Registrar Método de Entrega</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body bg-s">
        <form id="formRegistrar" autocomplete="off">

          <div class="seccion-formulario">
            <h6 class="texto-quinto"><i class="fas fa-tag"></i> Información del Método de Entrega</h6>

            <label for="nombre" class="form-label">NOMBRE DEL MÉTODO DE ENTREGA</label>
            <input type="text" class="form-control" name="nombre" id="nombre" placeholder="Ej: Envío express" required>
            <span id="snombre" class="text-danger"></span>

            <div class="mb-3">
              <label for="descripcion" class="form-label">DESCRIPCION</label>
              <input type="text" class="form-control" name="descripcion" id="descripcion" placeholder="Ej: Entrega en 24 horas" required>
              <span id="sdescripcion" class="text-danger"></span>
            </div>
          </div>

          <div class="col-12">
            <div class="info-box p-3 d-flex gap-3 align-items-start rounded shadow-sm bg-light">
              <div class="info-icon fs-3 text-primary">
                <i class="fa-solid fa-circle-info"></i>
              </div>

              <div class="info-content flex-grow-1">
                <strong>Información Importante:</strong>
                <p>Los métodos de entrega determinan cómo el cliente recibirá su pedido...</p>

                <p><b>Recomendaciones:</b></p>
                <ul class="text-muted mb-0">
                <li>Asigna nombres claros para identificar cada tipo de entrega</li>
        <li>Agrega descripciones que expliquen cuándo debe usarse cada método</li>
        <li>Evita duplicar tipos de entrega similares</li>
        <li>Verifica que los métodos activos coincidan con los realmente disponibles en tienda</li>
                </ul>
              </div>
            </div>
          </div>

          <div class="col-12 text-center mt-4">
            <button type="button" class="btn btn-modern px-4 m-2 btn-guardar" id="registrar">
              <i class="fa-solid fa-floppy-disk me-2"></i> Registrar
            </button>
            <button type="reset" class="btn btn-modern px-4 m-2 btn-limpiar">
              <i class="fa-solid fa-eraser me-2"></i> Limpiar
            </button>
          </div>

        </form>
      </div>
    </div>
  </div>
</div>


      <!-- Modal modificar -->
      <div class="modal fade" id="modificar" tabindex="-1" aria-labelledby="modificarLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header header-color">
              <h5 class="modal-title">Modificar Método de Entrega</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body bg-s">
              <form id="formModificar" autocomplete="off">
              <div class="seccion-formulario">
              <h6 class="texto-quinto"><i class="fas fa-tag"></i> Información del Método de Entrega</h6>
                <input type="hidden" name="id_entrega" id="id_entrega_modificar">
                <div class="mb-3">
                  <label for="nombre_modificar" class="form-label">NOMBRE DEL MÉTODO DE ENTREGA</label>
                  <input type="text" class="form-control" name="nombre" id="nombre_modificar" required>
                   <span id="snombre_modificar" class="text-danger"></span>
                </div>
                <div class="mb-3">
                  <label for="descripcion_modificar" class="form-label">DESCRIPCION</label>
                  <input type="text" class="form-control" name="descripcion" id="descripcion_modificar" required>
                   <span id="sdescripcion_modificar" class="text-danger"></span>
                </div>
              </div>

              <div class="col-12">
            <div class="info-box p-3 d-flex gap-3 align-items-start rounded shadow-sm bg-light">
              <div class="info-icon fs-3 text-primary">
                <i class="fa-solid fa-circle-info"></i>
              </div>

              <div class="info-content flex-grow-1">
                <strong>Información Importante:</strong>
                <p>Los métodos de entrega determinan cómo el cliente recibirá su pedido...</p>

                <p><b>Recomendaciones:</b></p>
                <ul class="text-muted mb-0">
                <li>Asigna nombres claros para identificar cada tipo de entrega</li>
        <li>Agrega descripciones que expliquen cuándo debe usarse cada método</li>
        <li>Evita duplicar tipos de entrega similares</li>
        <li>Verifica que los métodos activos coincidan con los realmente disponibles en tienda</li>
                </ul>
              </div>
            </div>
          </div>


          <div class="col-12 text-center mt-4">
                  <button type="button" class="btn btn-modern px-4 m-2 btn-guardar" id="btnModificar"><i class="fa-solid fa-floppy-disk me-2"></i> actualizar</button>
                  <button type="button" class="btn px-4 m-2 btn-limpiar" data-bs-dismiss="modal"> <i class="fa-solid fa-eraser me-2"></i> Limpiar</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

<?php include 'complementos/footer.php'; ?>
<script src="assets/js/metodoentrega.js"></script>
<script src="assets/js/demo/datatables-demo.js"></script>

</main>
</body>
</html>
