<!DOCTYPE html>
<html lang="es">
<head>
  <?php include 'complementos/head.php'; ?>
  <title>Notificaciones | LoveMakeup</title>
  
  <style>
    /* Estilos para el buscador */
    .dataTables_wrapper {
      margin: 15px;
      padding: 0 15px;
    }
    
    .dataTables_filter {
      margin-bottom: 15px;
      text-align: right;
      padding: 10px 0;
    }
    
    .dataTables_filter input {
      border: 1px solid #dee2e6;
      border-radius: 0.375rem;
      padding: 0.375rem 0.75rem;
      margin-left: 0.5rem;
      width: 250px;
    }
    
    .dataTables_filter label {
      font-weight: 500;
      color: #6c757d;
      display: inline-flex;
      align-items: center;
    }
    
    .dataTables_length {
      margin-bottom: 15px;
    }
    
    .dataTables_length select {
      border: 1px solid #dee2e6;
      border-radius: 0.375rem;
      padding: 0.375rem;
      margin: 0 0.5rem;
    }
    
    .dataTables_length label {
      font-weight: 500;
      color: #6c757d;
    }
    
    /* Estilos para la tabla de notificaciones - igual que otros módulos */
    #myTable {
      width: 100%;
    }
    
    #myTable thead.table-color th {
      background-color: #d67888;
      color: #ffffff;
      font-weight: 600;
      text-align: center;
      vertical-align: middle;
    }
    
    #myTable tbody tr {
      transition: all 0.2s ease-in-out;
    }
    
    #myTable tbody tr:hover {
      background-color: rgba(0, 0, 0, 0.03);
    }
    
    #myTable tbody td {
      text-align: center;
      vertical-align: middle;
    }
  </style>
</head>
<body class="g-sidenav-show bg-gray-100">
  <?php include 'complementos/sidebar.php'; ?>

  <main class="main-content position-relative border-radius-lg ">
    <!-- Navbar -->
    <nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl"
         id="navbarBlur" data-scroll="false">
      <div class="container-fluid py-1 px-3">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
            <li class="breadcrumb-item text-sm">
              <a class="opacity-5 text-white" href="#">Inicio</a>
            </li>
            <li class="breadcrumb-item text-sm text-white active" aria-current="page">
              Notificaciones
            </li>
          </ol>
          <h6 class="font-weight-bolder text-white mb-0">Lista de notificaciones</h6>
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
    <div class="container-fluid py-4 ">
      <div class="card mb-4 div-oscuro-2">
        <div class="card-header d-flex align-items-center justify-content-between py-3 div-oscuro-2">
          <h6 class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 texto-secundario">
            Lista de notificaciones
          </h6>
          <div class="d-flex align-items-center gap-2">
            <!-- Al lado derecho del título, dentro de .card-header -->
            <button type="button" class="btn btn-primary btn-sm" id="btnAyudanoti">
              <i class="fas fa-info-circle me-1"></i> Ayuda
            </button>
          </div>
        </div>
        

        <div class="card-body p-0">
          <div class="table-responsive p-3">
            <table class="table table-bordered table-hover mb-0" id="myTable" data-page="notificaciones" data-nivel="<?= $nivel ?>">
              <thead class="table-color">
                <tr>
                  <th class="text-white">Título</th>
                  <th class="text-white">Mensaje</th>
                  <th class="text-white">Estado</th>
                  <th class="text-white">Fecha</th>
                  <?php if ($_SESSION["nivel_rol"] >= 2 && tieneAcceso(18, 'especial')): ?>
                      <th class="text-center text-white">Acciones</th>
                   <?php endif; ?>
                </tr>
              </thead>

<tbody>
  
  <?php if (!empty($notificaciones)): ?>
    <?php foreach ($notificaciones as $n): ?>
      <tr id="notif-<?= $n['id_notificacion'] ?>">
        <td>
          <p class="text-sm font-weight-normal mb-0">
            <?= htmlspecialchars($n['titulo']) ?>
          </p>
        </td>
        <td>
          <p class="text-sm font-weight-normal mb-0">
            <?= htmlspecialchars($n['mensaje']) ?>
          </p>
        </td>
        <td>
          <?php switch ((int)$n['estado']):
            case 1: ?>
              <span class="badge bg-danger">No leída</span>
            <?php break;
            case 2: ?>
              <span class="badge bg-secondary">Leída</span>
            <?php break;
            case 3: ?>
              <span class="badge bg-success">
                <?= $nivel === 3 ? 'Leída y entregada' : 'Entregada' ?>
              </span>
            <?php break;
          endswitch; ?>
        </td>
        <td>
          <span class="text-sm">
            <?= date('d-m-Y g:i a', strtotime($n['fecha'])) ?>
          </span>
        </td>
             <?php if ($_SESSION["nivel_rol"] >= 2 && tieneAcceso(18, 'especial')): ?>
        <td>
          <?php if ($nivel === 3 && in_array((int)$n['estado'], [1, 4])): ?>
            <button
              type="button"
              class="btn btn-info btn-sm btn-action"
              data-id="<?= $n['id_notificacion'] ?>"
              data-accion="marcarLeida"
              title="Marcar como leída">
              <i class="fa-solid fa-envelope-open"></i>
            </button>
          <?php elseif ($nivel === 2 && (int)$n['estado'] === 1): ?>
            <button
              type="button"
              class="btn btn-secondary btn-sm btn-action"
              data-id="<?= $n['id_notificacion'] ?>"
              data-accion="marcarLeidaAsesora"
              title="Leer (solo para mí)">
              <i class="fa-solid fa-envelope-open"></i>
            </button>
          <?php endif; ?>
        </td>
           <?php endif; ?>
      </tr>
    <?php endforeach; ?>
  <?php endif; ?>

  </tbody>
  </table>
  </div>
  </div>

  </div>
 </div>

  <?php include 'complementos/footer.php'; ?>
  </main>

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="assets/js/notificacion.js"></script>
  <!-- para el datatable-->
  <script src="assets/js/demo/datatables-demo.js"></script>
</body>
</html>