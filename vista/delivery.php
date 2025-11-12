<!DOCTYPE html>
<html lang="es">

<head> 
  <!-- php barra de navegacion-->
  <?php include 'complementos/head.php' ?> 
  <link rel="stylesheet" href="assets/css/formulario.css">
  <title> Marca | LoveMakeup  </title> 

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
        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-white" href="#">Clientes y Entregas</a></li>
        <li class="breadcrumb-item text-sm text-white active" aria-current="page">Delivery</li>
      </ol>
      <h6 class="font-weight-bolder text-white mb-0">Gestionar Delivery</h6>
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
       <h4 class="mb-0 texto-quinto"><i class="fa-solid fa-bicycle me-2" style="color: #f6c5b4;"></i>
        Delivery</h4>
           
        <div class="d-flex gap-2">
      <?php if ($_SESSION["nivel_rol"] >= 2 && tieneAcceso(11, 'registrar')): ?>
  <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#registro" id="btnAbrirRegistrar">
    <span class="icon text-white">
      <i class="fas fa-file-medical"></i>
    </span>
    <span class="text-white">Registrar</span>
  </button>
  <?php endif; ?>
  <button type="button" class="btn btn-primary" id="btnAyuda">
    <span class="icon text-white">
      <i class="fas fa-info-circle"></i>
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
                 
                </tr>
              </thead>
              <tbody>
              
              </tbody>
                               
          </table> <!-- Fin tabla-->
          
      </div>  <!-- Fin div table-->

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