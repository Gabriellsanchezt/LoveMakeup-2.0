<!DOCTYPE html>
<html lang="en">

<head>
<!-- php CSS, Meta y titulo--> 
<?php include 'vista/complementos/head_catalogo.php' ?>
<link rel="stylesheet" href="assets/css/formulario.css">
<style>
  .input-group #rolSelect2 {
  flex: 0 0 25%;
  max-width: 50%;
}

.input-group .form-control {
  flex: 1 1 auto;
}
  .modal-productoo {
  border-radius: 15px;
  border: none;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-productoo .modal-header {
  background: linear-gradient(135deg, #f88af8ff 0%, #4dc0eeff 100%);
  border-radius: 15px 15px 0 0;
  border-bottom: none;
  padding: 1.5rem;
}

.modal-productoo .modal-title {
  color: #2c3e50;
  font-weight: 700;
  font-size: 1.5rem;
  display: flex;
  align-items: center;
  gap: 10px;
}

.modal-productoo .modal-title i {
  font-size: 1.8rem;
  color: #0a0909;
}

.modal-productoo .modal-body {
  padding: 2rem;
  background: #f8f9fa;
}

.modal-productoo .btn-close {
  background-color: rgba(8, 6, 6, 0.8);
  border-radius: 50%;
  padding: 8px;
  transition: all 0.3s ease;

}

.modal-productoo .btn-close:hover {
  background-color: #eb0f0f;
  transform: scale(1.1);
}

</style>
</head>

<body>

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
    <div class="container-lg">
        <nav style="--bs-breadcrumb-divider: '>';" aria-label="breadcrumb" class="custom-breadcrumb mt-3">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="?pagina=catalogo">Inicio</a></li>
            <li class="breadcrumb-item" aria-current="page">Ver</li>
             <li class="breadcrumb-item active" aria-current="page">Mis Datos</li>
        </ol>
      </nav>
      <br>

      <hr>
    <div class="conteiner">
      <div class="card-body">
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between">
          <!-- Foto y datos del usuario -->
          <div class="d-flex align-items-center mb-3 mb-md-0">
            <i class="fas fa-user-circle fa-3x text-titel me-3"></i>
            <div>
              <h5 class="mb-0"> <?php echo $nombreCompleto ?> </h5>
              <small class="text-muted"> <?php echo $_SESSION['nombre_usuario'];?> </small>
            </div>
          </div>

          <!-- Opciones -->
          <div class="d-flex flex-column flex-md-row gap-2">
           <button id="btn-personales" class="btn btn-custom active" onclick="mostrarFormulario('personales')">Datos personales</button>
            <button id="btn-seguridad" class="btn btn-custom" onclick="mostrarFormulario('seguridad')">Seguridad</button>
            
            </div>
          </div>
        </div>
      </div>
      <hr>
    <!-- Formularios -->
        <div id="form-personales" class="formulario mt-3"> <!-- F1 --->
          <div class="row">
        <div class="section-header d-flex align-items-center justify-content-between mb-lg-2">
          <h2 class="section-title text-titel-1">Datos Personales </h2>
        </div>
      </div>
       <form action="?pagina=catalogo_datos" method="POST" autocomplete="off" id="u">
      <div class="row">
         <h5>Información personal</h5>
  
        </div>  
          <div class="seccion-formularioo">
          <div class="row mb-3">
            <div class="col-md-4 mb-3">
              <label for="cedula">Cédula</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-id-card" style="color:#ff2bc3;"></i></span>
                 <select class="form-select text-dark" name="tipo_documento" id="rolSelect2"  required >
                  <option value="<?php echo $_SESSION['documento'] ?>"> <?php echo $_SESSION['documento'] . " (ACTUAL)";?> </option>
                  <option value="V"> V </option>
                  <option value="E"> E </option>
          
                </select>
                <input type="text" class="form-control text-dark" id="cedula" name="cedula" value="<?php echo $_SESSION['id'] ?>">
              </div>
              <p id="textocedula" class="text-danger"></p>
            </div>

            <div class="col-md-4 mb-3">
              <label for="nombre">Nombre</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-user" style="color:#ff2bc3;"></i></span>
                <input type="text" class="form-control text-dark" id="nombre" name="nombre" value="<?php echo $_SESSION['nombre'] ?>">
              </div>
              <p id="textonombre" class="text-danger"></p>
            </div>

            <div class="col-md-4 mb-3">
              <label for="apellido">Apellido</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-user" style="color:#ff2bc3;"></i></span>
                <input type="text" class="form-control text-dark" id="apellido" name="apellido" value="<?php echo $_SESSION['apellido'] ?>">
              </div>
              <p id="textoapellido" class="text-danger"></p>
            </div>

            <div class="col-md-6 mb-3">
              <label for="telefono">Teléfono</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-mobile-screen-button" style="color:#ff2bc3;"></i></span>
                <input type="text" class="form-control text-dark" id="telefono" name="telefono" value="<?php echo $_SESSION['telefono'] ?>">
              </div>
              <p id="textotelefono" class="text-danger"></p>
            </div>

            <div class="col-md-6 mb-3">
              <label for="correo">Correo Electrónico</label>
              <div class="input-group">
                <span class="input-group-text"><i class="fa-solid fa-envelope" style="color:#ff2bc3;"></i></span>
                <input type="text" class="form-control text-dark" id="correo" name="correo" value="<?php echo $_SESSION['correo'] ?>">
              </div>
              <p id="textocorreo" class="text-danger"></p>
            </div>
          </div>
  </div>  
        <div class="row">
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button class="btn-verde me-md-2" type="button" id="actualizar"> <i class="fa-solid fa-floppy-disk me-2"></i> Actualizar Datos</button>
                <button class="btn-reset" type="reset"> <i class="fa-solid fa-repeat me-2"></i> Restaurar</button>
            </div>
     

        </div>
     </form>   
    
  </div><!-- f1 /-->

        <div id="form-seguridad" class="formulario  mt-3 d-none"> <!-- f2-->
          <div class="row">
        <div class="section-header d-flex align-items-center justify-content-between mb-lg-2">
          <h2 class="section-title text-titel-1">Seguridad</h2>
        </div>
      </div>
            <div class="row">
        <div class="section-header d-flex align-items-center justify-content-between mb-lg-2">
          <h4 class=""> Cambio de clave </h4>
        </div>
      </div>
      <form action="?pagina=catalogo_datos" method="POST" autocomplete="off" id="formclave">
     <div class="seccion-formularioo">
  <div class="row mb-3">
  
    <div class="col-12">
      <label for="claveactual">Clave actual</label>
    </div>
    <div class="col-md-6 col-lg-5">
      <div class="input-group">
        <span class="input-group-text"><i class="fa-solid fa-key" style="color:#ff2bc3;"></i></span>
        <input type="text" class="form-control text-dark" id="clave" name="clave">
      </div>
      <p id="textoclave" class="text-danger"></p>
    </div>
  </div>

  <div class="row mb-3">
    <div class="col-md-6">
      <label for="clavenueva" class="text-dark">Clave nueva</label>
      <div class="input-group">
        <span class="input-group-text"><i class="fa-solid fa-unlock" style="color:#ff2bc3;"></i></span>
        <input type="text" class="form-control text-dark" id="clavenueva" name="clavenueva">
      </div>
      <p id="textoclavenueva" class="text-danger"></p>
    </div>

    <div class="col-md-6">
      <label for="clavenuevac" class="text-dark">Confirmar clave nueva</label>
      <div class="input-group">
        <span class="input-group-text"><i class="fa-solid fa-unlock" style="color:#ff2bc3;"></i></span>
        <input type="text" class="form-control text-dark" id="clavenuevac" name="clavenuevac">
      </div>
      <p id="textoclavenuevac" class="text-danger"></p>
    </div>
  </div>

  <input type="hidden" name="persona" value="<?php echo $_SESSION['id_usuario'] ?>">

  </div>
        <div class="row">
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
             <button class="btn-verde me-md-2" type="button" id="actualizarclave"> <i class="fa-solid fa-key"></i> Cambiar Clave</button>
             <button class="btn-reset" type="reset"> <i class="fa-solid fa-eraser"></i> Limpiar</button>
        </div>
             </form>
        </div>

<hr>
        <div class="row bg-light">
        <div class="section-header d-flex align-items-center justify-content-between mb-lg-2">
          <h2 class="section-title text-titel-1">Estado de la Cuenta </h2>
        </div>
      </div>
      
 
        <div class="row">
          <div class="col">
            <p class="text-dark">
              <i class="fa-solid fa-user-xmark"></i> ¿Deseas Eliminar la Cuenta? 
              <button class="btn-delete ms-2" data-bs-toggle="modal" data-bs-target="#cuenta"><i class="fa-solid fa-user-xmark me-2"></i>Eliminar Cuenta</button>
            </p>
          </div>
        </div>

        </div> <!-- f2 / -->



       
      
        </div> 
      </div>
    </div>   
  </div>

  </section>


<script>
  function mostrarFormulario(formularioId) {
    // Ocultar todos los formularios
    document.querySelectorAll('.formulario').forEach(form => {
      form.classList.add('d-none');
    });
    // Mostrar el formulario correspondiente
    document.getElementById('form-' + formularioId).classList.remove('d-none');

    document.querySelectorAll('.btn-custom').forEach(btn => {
      btn.classList.remove('active');
    });
    const btnActivo = document.getElementById('btn-' + formularioId);
    if (btnActivo) btnActivo.classList.add('active');
  }
</script>


<!-- php Publicidad Insta, Publicidad calidad, footer y JS--> 
<?php include 'vista/complementos/footer_catalogo.php' ?>
   <script src="assets/js/catalago_datos.js"></script>


<div class="modal fade" id="cuenta" tabindex="2" aria-labelledby="s" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content modal-productoo">
      <div class="modal-header bg-table">
        <h5 class="modal-title text-white" id="exampleModalLabel">¿Deseas Eliminar la Cuenta?</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style=" color: #ffffff;"></button>
      </div>
      <div class="modal-body"> 
        <h5 class="text-titel-2">Aviso Importante sobre la Eliminación de Cuenta</h5>
        <p class="text-dark"> <b>Estimado/a, <?php echo $nombreCompleto ?> </b></p>

        <p class="text-dark">Queremos informarte que al eliminar tu cuenta, se perderá de forma permanente toda la información relacionada con tus pedidos,
           tu historial de compras y la lista de tus productos favoritos y tu historial de compra en la tienda fisica</p>

        <p class="text-dark">Esta acción es irreversible, y una vez eliminada tu cuenta, no podremos recuperar la información eliminada.</p>

         <div class="seccion-formularioo">
      <form id="eliminarForm" action="?pagina=catalogo_datos" method="POST" autocomplete="off"> 
          <label>Escriba la palabra ACEPTAR, para confimar la eliminación</label>
          <input type="text" name="confirmar" id="confirmar" class="form-control text-dark" placeholder="ACEPTAR">
          <p id="textoconfirmar" class="text-danger"></p>
          <input type="hidden" name="persona" value="<?php echo $_SESSION['id'] ?>" >
           </div>
          <div class="modal-footer">
              <button type="button" class="btn-verde" name="eliminar" id="btnEliminar">Continuar</button>
          </div>
      </form>
    </div>
  </div>
</div>



</body>

</html>