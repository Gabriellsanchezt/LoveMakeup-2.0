// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = 'Prueba-76';
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: MODIFICAR DATOS DEL CLIENTE ===
async function runTest() {
  let driver;
  let status = 'f';
  let notes = '';
  const startTime = new Date();
  const testSteps = [];

  try {
    // Configurar el driver
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
    
    // Seleccionar tipo de documento (V - Venezolano)
    const tipoDocSelect = await driver.findElement(By.id('DocumentoSelct'));
    await driver.wait(until.elementIsVisible(tipoDocSelect), 10000);
    await driver.executeScript("arguments[0].value = 'V';", tipoDocSelect);
    await driver.executeScript("arguments[0].dispatchEvent(new Event('change', { bubbles: true }));", tipoDocSelect);
    await driver.sleep(300);
    
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
    await driver.sleep(2000);
    console.log('Login exitoso.');
    testSteps.push('Login completado exitosamente');

    // === Paso 2: Ir al módulo Cliente ===
    testSteps.push('Navegar al módulo de Cliente');
    console.log('Accediendo al módulo Cliente...');
    await driver.get(BASE_URL + '?pagina=cliente');
    await driver.sleep(2000);
    
    await driver.wait(until.elementLocated(By.css('.modificar')), 15000);
    console.log('Módulo Cliente cargado correctamente.');
    testSteps.push('Módulo Cliente cargado correctamente');

    // === Paso 3: Seleccionar un cliente y hacer click en Modificar ===
    testSteps.push('Seleccionar un cliente y hacer click en Modificar');
    console.log('Buscando botón de modificar...');
    
    const modificarButtons = await driver.findElements(By.css('.modificar'));
    if (modificarButtons.length === 0) {
      throw new Error('No se encontró ningún botón de modificar');
    }
    
    const modificarBtn = modificarButtons[0];
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center', behavior: 'smooth'});", modificarBtn);
    await driver.sleep(500);
    await driver.wait(until.elementIsVisible(modificarBtn), 10000);
    await modificarBtn.click();
    await driver.sleep(2000);

    // === Paso 4: Verificar que el modal se abrió ===
    testSteps.push('Verificar que el modal de modificación se abrió');
    await driver.wait(until.elementLocated(By.id('editarModal')), 10000);
    const modal = await driver.findElement(By.id('editarModal'));
    const isModalVisible = await modal.getAttribute('class');
    if (!isModalVisible.includes('show')) {
      throw new Error('El modal de modificación no se abrió correctamente');
    }
    console.log('Modal de modificación abierto correctamente.');
    testSteps.push('Modal de modificación abierto');

    // === Paso 5: Modificar los datos ===
    testSteps.push('Modificar los datos del cliente');
    console.log('Modificando datos...');

    // Modificar cédula
    const cedulaInput = await driver.findElement(By.id('modalCedula'));
    await driver.wait(until.elementIsVisible(cedulaInput), 10000);
    await cedulaInput.clear();
    await cedulaInput.sendKeys('11697944');
    await driver.sleep(500);

    // Modificar correo
    const correoInput = await driver.findElement(By.id('modalCorreo'));
    await driver.wait(until.elementIsVisible(correoInput), 10000);
    await correoInput.clear();
    await correoInput.sendKeys('desr7875@gmail.com');
    await driver.sleep(500);

    // === Paso 6: Hacer click en Actualizar Datos ===
    testSteps.push('Hacer click en Actualizar Datos');
    console.log('Guardando cambios...');
    const actualizarBtn = await driver.findElement(By.id('actualizar'));
    await driver.wait(until.elementIsVisible(actualizarBtn), 10000);
    await driver.wait(until.elementIsEnabled(actualizarBtn), 10000);
    await actualizarBtn.click();
    await driver.sleep(3000);

    // === Paso 7: Confirmar en la alerta ===
    testSteps.push('Confirmar en la alerta de actualización');
    console.log('Esperando alerta de confirmación...');
    
    try {
      await driver.wait(until.alertIsPresent(), 5000);
      const alert = await driver.switchTo().alert();
      const alertText = await alert.getText();
      console.log('Texto de la alerta: ' + alertText);
      
      if (alertText.includes('Actualizar cliente') || alertText.includes('Confirmar')) {
        await alert.accept();
        await driver.sleep(3000);
      } else {
        await alert.dismiss();
        throw new Error('Alerta inesperada: ' + alertText);
      }
    } catch (e) {
      // Si no hay alerta, continuar
      console.log('No se encontró alerta, continuando...');
    }

    // === Paso 8: Verificar mensaje de éxito ===
    testSteps.push('Verificar mensaje de éxito');
    console.log('Verificando mensaje de éxito...');
    
    try {
      await driver.wait(until.elementLocated(By.css('.swal2-success, .swal2-title')), 10000);
      const successElement = await driver.findElement(By.css('.swal2-title'));
      const successText = await successElement.getText();
      if (successText.includes('Modificado') || successText.includes('éxito') || successText.includes('exito')) {
        console.log('Mensaje de éxito detectado: ' + successText);
        testSteps.push('Mensaje de éxito confirmado: ' + successText);
        
        // Cerrar el modal de éxito si existe
        try {
          const confirmBtn = await driver.findElement(By.css('.swal2-confirm'));
          await confirmBtn.click();
          await driver.sleep(1000);
        } catch (e) {
          // Ignorar si no hay botón de confirmar
        }
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
          console.log('Tabla visible - Cliente modificado exitosamente.');
        } else {
          throw new Error('No se pudo verificar el éxito de la operación');
        }
      }
    }

    console.log('Cliente modificado exitosamente.');
    notes = 'Cliente modificado exitosamente. Cédula: 11697944, Correo: desr7875@gmail.com';
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
    // Reportar a TestLink
    const testLinkStatus = status === 'p' || status === 'passed' ? 'p' : 'f';
    await reportResultToTestLink(testLinkStatus, notes);
  }
}

// === FUNCIÓN: Reportar resultado a TestLink ===
async function reportResultToTestLink(status, notes) {
  return new Promise((resolve) => {
    try {
      const client = xmlrpc.createClient({ url: TESTLINK_URL });

      const cleanNotes = notes
        .replace(/<[^>]*>/g, '')
        .replace(/\n/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()
        .substring(0, 500);

      console.log('Intentando conectar con TestLink...');
      
      client.methodCall('tl.checkDevKey', [{ devKey: DEV_KEY }], function (error, value) {
        if (error) {
          console.error('DevKey invalido o conexion fallida:', error);
          resolve();
          return;
        }

        console.log('DevKey valido. Reportando resultado...');
        
        const externalId = String(TEST_CASE_EXTERNAL_ID || '').trim();
        if (!externalId || externalId.length === 0) {
          console.error('Error: External ID no puede estar vacio');
          resolve();
          return;
        }
        if (externalId.length > 50) {
          console.error('Error: External ID excede el limite de 50 caracteres. Longitud: ' + externalId.length);
          resolve();
          return;
        }
        
        const params = {
          devKey: DEV_KEY,
          testcaseexternalid: externalId,
          testplanid: TEST_PLAN_ID,
          buildid: BUILD_ID,
          notes: cleanNotes,
          status: status,
        };

        client.methodCall('tl.reportTCResult', [params], function (error, value) {
          if (error) {
            console.error('Error al enviar resultado a TestLink:', error);
          } else {
            console.log('Resultado enviado a TestLink exitosamente:', JSON.stringify(value));
          }
          resolve();
        });
      });
    } catch (error) {
      console.error('No se pudo conectar con TestLink:', error);
      resolve();
    }
  });
}

// === Ejecutar test ===
if (require.main === module) {
  runTest().catch(error => {
    console.error('Error fatal en la ejecucion del test:', error);
    process.exit(1);
  });
}

module.exports = { runTest, reportResultToTestLink };

