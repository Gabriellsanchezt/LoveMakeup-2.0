<!DOCTYPE html>
<html lang="es">

<head> 
  <!-- php barra de navegacion-->
  <?php include 'complementos/head.php' ?> 
  <link rel="stylesheet" href="assets/css/formulario.css">
  <title> Tasa de Cambio | LoveMakeup  </title> 

  <style>
.driver-popover.driverjs-theme {
  color: #000;
}

.driver-popover.driverjs-theme .driver-popover-title {
  font-size: 20px;
}

.driver-popover.driverjs-theme .driver-popover-title,
.driver-popover.driverjs-theme .driver-popover-description,
.driver-popover.driverjs-theme .driver-popover-progress-text {
  color: #000;
}

.driver-popover.driverjs-theme button {
  flex: 1;
  text-align: center;
  background-color: #000;
  color: #ffffff;
  border: 2px solid #000;
  text-shadow: none;
  font-size: 14px;
  padding: 5px 8px;
  border-radius: 6px;
}

.driver-popover.driverjs-theme button:hover {
  background-color: #000;
  color: #ffffff;
}

.driver-popover.driverjs-theme .driver-popover-navigation-btns {
  justify-content: space-between;
  gap: 3px;
}

.driver-popover.driverjs-theme .driver-popover-close-btn {
  color: #fff;
  width: 20px; /* Reducir el tamaño del botón */
  height: 20px;
  font-size: 16px;
  transition: all 0.5 ease-in-out;
}

.driver-popover.driverjs-theme .driver-popover-close-btn:hover {
 background-color: #fff;
 color: #000;
 border: #000;
}

.driver-popover.driverjs-theme .driver-popover-arrow-side-left.driver-popover-arrow {
  border-left-color: #fde047;
}

.driver-popover.driverjs-theme .driver-popover-arrow-side-right.driver-popover-arrow {
  border-right-color: #fde047;
}

.driver-popover.driverjs-theme .driver-popover-arrow-side-top.driver-popover-arrow {
  border-top-color: #fde047;
}

.driver-popover.driverjs-theme .driver-popover-arrow-side-bottom.driver-popover-arrow {
  border-bottom-color: #fde047;
}
  </style>

  <script>
    async function obtenerTasaDolarApi() {
    try {
        const respuesta = await fetch('https://ve.dolarapi.com/v1/dolares/oficial');
        if (!respuesta.ok) {
            throw new Error(`Error HTTP: ${respuesta.status}`);
        }

        const datos = await respuesta.json();
        const tasaBCV = datos.promedio.toFixed(2); // Redondea la tasa a 2 decimales

        document.getElementById("bcv").textContent =  tasaBCV + " Bs";
        document.getElementById("tasabcv").value = tasaBCV;
        
    } catch (error) {
        document.getElementById("bcv").textContent = "Error al cargar la tasa";
        document.getElementById("tasabcv").value = "0";
    
    }
}

document.addEventListener("DOMContentLoaded", obtenerTasaDolarApi);
  </script>

</head>
 
<body class="g-sidenav-show bg-gray-100">
  
<!-- php barra de navegacion-->
<?php include 'complementos/sidebar.php' ?>

<main class="main-content position-relative border-radius-lg ">
<!-- ||| Navbar ||-->
<nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl " id="navbarBlur" data-scroll="false">
  <div class="container-fluid py-1 px-3">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-white" href="#">Finanzas</a></li>
        <li class="breadcrumb-item text-sm text-white active" aria-current="page"> Tasa de Cambio
</li>
      </ol>
      <h6 class="font-weight-bolder text-white mb-0">Administrar Tasa de Cambio</h6>
    </nav>
<!-- php barra de navegacion-->    
<?php include 'complementos/nav.php' ?>
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
     <div class="d-sm-flex align-items-center justify-content-between mb-3">
       <h4 class="mb-0 texto-quinto"><i class="fa-solid fa-comments-dollar me-2 icoM" style="color: #f6c5b4;"></i>
        Tasa de Cambio</h4>
           
        <div class="d-flex gap-2">
      
  <button type="button" class="btn btn-primary" id="btnAyuda">
    <span class="icon text-white">
      <i class="fas fa-info-circle"></i>
    </span>
    <span class="text-white">Ayuda</span>
  </button>
</div>
</div>
        
        
  <!-- Fila 1: Cards -->
  <div class="row mb-4">
    <!-- Card 1 -->
    <div class="col-md-6">
      <div class="card" style="background-color: #fce4ec;">
        <div class="card-body">
          <h5 class="card-title">Tasa del Dolar (Guardada)</h5>
          <h4 class="card-subtitle mb-2 text-dark" id="tasaBD"> </h4>
          <p class="card-text">Actualmente estás es la tasa de cambio de USD a Bolívares (Bs) guardada en nuestra base de datos. puedes modificarla manualmente en cualquier momento según tu preferencia o la tasa vigente.</p>
             <?php if ($_SESSION["nivel_rol"] >= 2 && tieneAcceso(14, 'editar')): ?>
          <form action="?pagina=tasacambio" method="POST" autocomplete="off" id="for_modificar" style="display: none;">
            <input type="text" name="tasa" id="tasa">
            <input type="hidden" name="fuente" id="fuente_1" value="Manualmente">
            <input type="hidden" name="fecha" id="fecha_1">
          </form>
          <button type="button" class="btn btn-success" id="btnActualizarManual">
            <i class="fas fa-edit me-2"></i>Actualizar Manualmente
          </button>
    <?php endif; ?>
          
        </div>
      </div>
    </div>

    <!-- Card 2 -->
    <div class="col-md-6">
      <div class="card" style="background-color: #e3f2fd;">
        <div class="card-body">
          <h5 class="card-title">Tasa del Dolar (Actual - Via Internet) </h5>
         <h4 class="card-subtitle mb-2 text-dark" id="bcv"></h4>
          <p class="card-text">Estás utilizando la tasa de cambio USD a Bs obtenida automáticamente desde internet. Si lo prefieres, puedes sincronizar esta tasa y actualizar la que está guardada en la base de datos.</p>
<?php if ($_SESSION["nivel_rol"] >= 2 && tieneAcceso(14, 'editar')): ?>
          <form action="?pagina=tasacambio" method="POST" id="for_sincronizar" style="display: none;">
            <input type="hidden" name="fecha" id="fecha_2">
            <input type="hidden" name="tasa" id="tasabcv">
            <input type="hidden" name="fuente" value="Via Internet">
          </form>
          <button type="button" class="btn btn-primary" id="btnSincronizar">
            <i class="fas fa-sync-alt me-2"></i>Sincronizar y Actualizar
          </button>
               <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Fila 2: Tabla -->
  <div class="row">
    <div class="col-12 mb-5">
      <table class="table table-m table-hover w-100">
        <thead class="table-color">
          <tr>
            <th class="text-white text-center">FECHA</th>
            <th class="text-white text-center">TASA BS</th>
            <th class="text-white text-center">FUENTE</th>
          </tr>
        </thead>
        <tbody>
          <?php 
           foreach ($registro as $dato) {
          ?>
           <tr 
              data-fecha="<?php echo date("Y-m-d", strtotime($dato['fecha'])); ?>" 
              data-tasa="<?php echo $dato['tasa_bs']; ?>"
            >
            <td class="text-center text-dark"><i class="fa-solid fa-calendar-days me-2"></i><?php echo date("d/m/Y", strtotime($dato['fecha'])); ?></td>
            <td class="text-center text-dark">  <span class="badge badge-pill badge-lg bg-primary fs-6"><?php echo ' Bs. '.$dato['tasa_bs']; ?></span></td>
            <td class="text-center text-dark"><?php echo $dato['fuente']; ?></td>
          </tr>
         
        </tbody>
        <?php 
           }
          ?>
      </table>
    </div>
  </div>


     

    </div><!-- FIN CARD N-1 -->  
    </div>
    </div>  
    </div><!-- FIN CARD PRINCIPAL-->  
 </div>







<!-- php barra de navegacion-->
<?php include 'complementos/footer.php' ?>
<!-- para el datatable-->

<script src="assets/js/libreria/moment.js"></script>
<script src="assets/js/tasacambio.js"></script>
<script>
 $(document).ready(function () {
  let hoy = moment().format("YYYY-MM-DD");
  let ayer = moment().subtract(1, "days").format("YYYY-MM-DD");
  let tasaEncontrada = null;

  $("tbody tr").each(function () {
    let fecha = $(this).data("fecha");
    let tasa = $(this).data("tasa");

    if (fecha === hoy && !tasaEncontrada) {
      tasaEncontrada = tasa;
    } else if (fecha === ayer && !tasaEncontrada) {
      tasaEncontrada = tasa;
    }
  });

  if (tasaEncontrada) {
    $("#tasaBD").text("Bs. " + parseFloat(tasaEncontrada).toFixed(2));
  } else {
    $("#tasaBD").text("Bs. No disponible");
  }
});

</script>


</body>

</html>