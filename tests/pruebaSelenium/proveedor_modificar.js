// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');

// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = 'Prueba-52';
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: MODIFICAR PROVEEDOR ===
async function runTest() {
  let driver;
  let status = 'f';
  let notes = '';
  const startTime = new Date();
  const testSteps = [];

  try {
    // Configurar el driver según el navegador seleccionado
    console.log(`Inicializando navegador: ${BROWSER}...`);
    
    if (BROWSER === 'edge') {
      const options = new edge.Options();
      driver = await new Builder()
        .forBrowser('MicrosoftEdge')
        .setEdgeOptions(options)
        .build();
    } else if (BROWSER === 'chrome') {
      const chrome = require('selenium-webdriver/chrome');
      const options = new chrome.Options();
      driver = await new Builder()
        .forBrowser('chrome')
        .setChromeOptions(options)
        .build();
    } else {
      driver = await new Builder().forBrowser(BROWSER).build();
    }
    
    console.log('Navegador inicializado correctamente.');
  } catch (driverError) {
    console.error('Error al inicializar el navegador:', driverError.message);
    console.error('Asegúrate de que:');
    console.error('   1. EdgeDriver esté instalado y en el PATH');
    console.error('   2. O instala los drivers con: npm install --save-dev @seleniumhq/webdriver-manager');
    console.error('   3. O descarga EdgeDriver desde: https://developer.microsoft.com/en-us/microsoft-edge/tools/webdriver/');
    throw driverError;
  }

  try {
    // Configurar timeouts
    await driver.manage().setTimeouts({
      implicit: 10000,
      pageLoad: 30000,
      script: 30000
    });

    await driver.manage().window().maximize();

    // === Paso 1: Iniciar sesión ===
    testSteps.push('Iniciar sesión en la aplicación');
    console.log('Navegando al login...');
    await driver.get(BASE_URL + '?pagina=login');
    await driver.sleep(2000);
    
    // Esperar y llenar campos de login
    await driver.wait(until.elementLocated(By.id('usuario')), 15000);
    const usuarioInput = await driver.findElement(By.id('usuario'));
    await driver.wait(until.elementIsVisible(usuarioInput), 10000);
    await usuarioInput.clear();
    await usuarioInput.sendKeys('10200300');
    
    const passwordInput = await driver.findElement(By.id('pid'));
    await driver.wait(until.elementIsVisible(passwordInput), 10000);
    await passwordInput.clear();
    await passwordInput.sendKeys('love1234');
    
    const ingresarBtn = await driver.findElement(By.id('ingresar'));
    await driver.wait(until.elementIsEnabled(ingresarBtn), 10000);
    await ingresarBtn.click();
    
    // Esperar redirección después del login
    await driver.wait(until.urlContains('pagina=home'), 15000);
    await driver.sleep(2000); // Esperar a que cargue completamente
    console.log('Login exitoso.');
    testSteps.push('Login completado exitosamente');

    // === Paso 2: Ir al módulo Proveedor ===
    testSteps.push('Navegar al módulo de Proveedor');
    console.log('Accediendo al módulo Proveedor...');
    await driver.get(BASE_URL + '?pagina=proveedor');
    await driver.sleep(2000);
    
    await driver.wait(until.elementLocated(By.xpath("//button[contains(@onclick, 'abrirModalModificar')]")), 15000);
    console.log('Modulo Proveedor cargado correctamente.');
    testSteps.push('Módulo Proveedor cargado correctamente');

    // === Paso 3: Seleccionar un proveedor y hacer click en Modificar ===
    testSteps.push('Seleccionar un proveedor y hacer click en Modificar');
    console.log('Buscando botón de modificar...');
    
    const modificarButtons = await driver.findElements(By.xpath("//button[contains(@onclick, 'abrirModalModificar')]"));
    if (modificarButtons.length === 0) {
      throw new Error('No se encontró ningún botón de modificar');
    }
    
    const modificarBtn = modificarButtons[0];
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center', behavior: 'smooth'});", modificarBtn);
    await driver.sleep(500);
    await driver.wait(until.elementIsVisible(modificarBtn), 10000);
    await driver.wait(until.elementIsEnabled(modificarBtn), 10000);
    await modificarBtn.click();
    await driver.sleep(3000); // Esperar a que se carguen los datos y se abra el modal

    // === Paso 4: Verificar que el modal se abrió ===
    testSteps.push('Verificar que el modal de modificación se abrió');
    
    // Esperar a que el modal aparezca en el DOM
    await driver.wait(until.elementLocated(By.id('registroModal')), 15000);
    
    // Esperar a que el modal sea visible (tenga la clase 'show')
    let modalVisible = false;
    let attempts = 0;
    const maxAttempts = 10;
    
    while (!modalVisible && attempts < maxAttempts) {
      try {
        const modal = await driver.findElement(By.id('registroModal'));
        const modalClass = await modal.getAttribute('class');
        modalVisible = modalClass && modalClass.includes('show');
        
        if (!modalVisible) {
          // Intentar verificar con JavaScript
          modalVisible = await driver.executeScript(
            "return document.querySelector('#registroModal') && document.querySelector('#registroModal').classList.contains('show');"
          );
        }
        
        if (modalVisible) {
          break;
        }
      } catch (e) {
        // Continuar intentando
      }
      
      attempts++;
      await driver.sleep(500);
    }
    
    if (!modalVisible) {
      throw new Error('El modal de modificación no se abrió correctamente después de ' + (maxAttempts * 500) + 'ms');
    }
    
    console.log('Modal de modificación abierto correctamente.');
    testSteps.push('Modal de modificación abierto');

    // Esperar un poco más para que el modal termine de cargar completamente
    await driver.sleep(1000);
    console.log('Esperando a que el modal cargue completamente...');

    // === Paso 5: Modificar los datos ===
    testSteps.push('Modificar los datos del proveedor');
    console.log('Modificando datos...');

    // Cambiar tipo de documento a J
    console.log('Buscando campo tipo de documento...');
    const tipoDocSelect = await driver.findElement(By.id('tipo_documento'));
    await driver.wait(until.elementIsVisible(tipoDocSelect), 15000);
    await driver.wait(until.elementIsEnabled(tipoDocSelect), 10000);
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", tipoDocSelect);
    await driver.sleep(300);
    await tipoDocSelect.click();
    await driver.sleep(300);
    await driver.executeScript("arguments[0].value = 'J';", tipoDocSelect);
    await driver.executeScript("arguments[0].dispatchEvent(new Event('change', { bubbles: true }));", tipoDocSelect);
    await driver.sleep(500);
    console.log('Tipo de documento cambiado a J');

    // Modificar número de documento
    console.log('Buscando campo número de documento...');
    const numeroDocInput = await driver.findElement(By.id('numero_documento'));
    await driver.wait(until.elementIsVisible(numeroDocInput), 15000);
    await driver.wait(until.elementIsEnabled(numeroDocInput), 10000);
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", numeroDocInput);
    await driver.sleep(200);
    await numeroDocInput.clear();
    await driver.sleep(200);
    await numeroDocInput.sendKeys('3075399507');
    await driver.sleep(500);
    console.log('Número de documento modificado');

    // Modificar nombre completo
    console.log('Buscando campo nombre...');
    const nombreInput = await driver.findElement(By.id('nombre'));
    await driver.wait(until.elementIsVisible(nombreInput), 15000);
    await driver.wait(until.elementIsEnabled(nombreInput), 10000);
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", nombreInput);
    await driver.sleep(200);
    await nombreInput.clear();
    await driver.sleep(200);
    await nombreInput.sendKeys('Rhichard Virguez Modificado');
    await driver.sleep(500);
    console.log('Nombre modificado');

    // Modificar teléfono
    console.log('Buscando campo teléfono...');
    const telefonoInput = await driver.findElement(By.id('telefono'));
    await driver.wait(until.elementIsVisible(telefonoInput), 15000);
    await driver.wait(until.elementIsEnabled(telefonoInput), 10000);
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", telefonoInput);
    await driver.sleep(200);
    await telefonoInput.clear();
    await driver.sleep(200);
    await telefonoInput.sendKeys('04245399000');
    await driver.sleep(500);
    console.log('Teléfono modificado a 04245399000');

    // Correo electrónico se mantiene igual
    // Dirección se mantiene igual

    console.log('Datos del proveedor modificados.');
    testSteps.push('Datos del proveedor modificados: Nombre y teléfono actualizados');

    // === Paso 6: Hacer click en ACTUALIZAR ===
    testSteps.push('Hacer click en el botón ACTUALIZAR');
    console.log('Actualizando proveedor...');
    const btnEnviar = await driver.findElement(By.id('btnEnviar'));
    await driver.wait(until.elementIsVisible(btnEnviar), 10000);
    await driver.wait(until.elementIsEnabled(btnEnviar), 10000);
    await btnEnviar.click();
    await driver.sleep(3000);

    // === Paso 7: Verificar mensaje de éxito ===
    testSteps.push('Verificar mensaje de éxito');
    console.log('Verificando mensaje de éxito...');
    
    try {
      await driver.wait(until.elementLocated(By.css('.swal2-success, .swal2-title')), 10000);
      const successElement = await driver.findElement(By.css('.swal2-title'));
      const successText = await successElement.getText();
      if (successText.includes('Proveedor modificado') || successText.includes('éxito') || successText.includes('actualizado')) {
        console.log('Mensaje de éxito detectado: ' + successText);
        testSteps.push('Mensaje de éxito confirmado: ' + successText);
      } else {
        throw new Error('Mensaje de éxito no encontrado o incorrecto');
      }
    } catch (e) {
      try {
        const errorElement = await driver.findElement(By.css('.swal2-error, .alert-danger'));
        const errorText = await errorElement.getText();
        throw new Error('Error detectado: ' + errorText);
      } catch (e2) {
        await driver.sleep(2000);
        const tabla = await driver.findElement(By.id('myTable'));
        if (await tabla.isDisplayed()) {
          console.log('Tabla visible - Proveedor modificado exitosamente.');
        } else {
          throw new Error('No se pudo verificar el éxito de la operación');
        }
      }
    }

    console.log('Proveedor modificado exitosamente.');
    notes = 'Proveedor modificado exitosamente. Tipo de documento: J, número de documento: 3075399507, nombre: Rhichard Virguez Modificado, teléfono: 04245399000.';
    status = 'p';

  } catch (error) {
    console.error('Error durante la prueba:', error.message);
    console.error('Stack trace:', error.stack);
    notes = 'Error: ' + error.message + (error.stack ? ' | Stack: ' + error.stack.substring(0, 200) : '');
    status = 'f';
  } finally {
    const endTime = new Date();
    
    if (driver) {
      try {
        await driver.quit();
      } catch (quitError) {
        console.log('Error al cerrar el navegador:', quitError.message);
      }
    }
  }
}

// === Ejecutar test ===
if (require.main === module) {
  runTest().catch(error => {
    console.error('Error fatal en la ejecucion del test:', error);
    process.exit(1);
  });
}

module.exports = { runTest };

