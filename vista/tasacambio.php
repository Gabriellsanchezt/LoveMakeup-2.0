<!DOCTYPE html>
<html lang="es">

<head> 
  <!-- php barra de navegacion-->
  <?php include 'complementos/head.php' ?> 
  <link rel="stylesheet" href="assets/css/formulario.css">
  <title> Tasa de Cambio | LoveMakeup  </title> 

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
       <h4 class="mb-0 texto-quinto"><i class="fa-solid fa-comments-dollar  me-2" style="color: #f6c5b4;"></i>
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
          <h4 class="card-subtitle mb-2 text-dark">Bs. 200.55</h4>
          <p class="card-text">Actualmente estás es la tasa de cambio de USD a Bolívares (Bs) guardada en nuestra base de datos. puedes modificarla manualmente en cualquier momento según tu preferencia o la tasa vigente.</p>
          <label for="">Modificarla manualmente la tasa del $ a Bs</label>
          <div class="input-group">
          <input type="text" class="form-control mb-2" placeholder="100.50">
        <button class="btn btn-primary btn-sm">Registrar</button>
        </div>
          

          
        </div>
      </div>
    </div>

    <!-- Card 2 -->
    <div class="col-md-6">
      <div class="card" style="background-color: #e3f2fd;">
        <div class="card-body">
          <h5 class="card-title">Tasa del Dolar (Actual - Via Internet) </h5>
         <h4 class="card-subtitle mb-2 text-dark">Bs. 200.55</h4>
          <p class="card-text">Estás utilizando la tasa de cambio USD a Bs obtenida automáticamente desde internet. Si lo prefieres, puedes sincronizar esta tasa y actualizar la que está guardada en la base de datos.</p>
          <button class="btn btn-info btn-sm">Sincronizar y Actualizar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Fila 2: Tabla -->
  <div class="row">
    <div class="col-12 mb-5">
      <table class="table table-bordered table-striped w-100">
        <thead class="table-light">
          <tr>
            <th>FECHA</th>
            <th>TASA BS</th>
            <th>FUENTE</th>
          </tr>
        </thead>
        <tbody>
          <tr><td>1</td><td>Registro A</td><td>Activo</td></tr>
          <tr><td>2</td><td>Registro B</td><td>Inactivo</td></tr>
          <tr><td>3</td><td>Registro C</td><td>Activo</td></tr>
          <tr><td>4</td><td>Registro D</td><td>Inactivo</td></tr>
          <tr><td>5</td><td>Registro E</td><td>Activo</td></tr>
        </tbody>
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
<script src="assets/js/demo/datatables-demo.js"></script>

</body>

</html>