// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = 'Prueba-69';
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: ERROR AL MODIFICAR CATEGORIA POR INCUMPLIMIENTO DEL RANGO PERMITIDO DE CARACTERES ===
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
    await driver.sleep(2000); // Esperar a que cargue completamente
    console.log('Login exitoso.');
    testSteps.push('Login completado exitosamente');

    // === Paso 2: Ir al módulo Categoria ===
    testSteps.push('Navegar al módulo de Categoria');
    console.log('Accediendo al módulo Categoria...');
    await driver.get(BASE_URL + '?pagina=categoria');
    await driver.sleep(2000);
    
    await driver.wait(until.elementLocated(By.css('.btnModif')), 15000);
    console.log('Modulo Categoria cargado correctamente.');
    testSteps.push('Módulo Categoria cargado correctamente');

    // === Paso 3: Seleccionar una categoria y hacer click en Modificar ===
    testSteps.push('Seleccionar una categoria y hacer click en Modificar');
    console.log('Buscando botón de modificar...');
    
    const modificarButtons = await driver.findElements(By.css('.btnModif'));
    if (modificarButtons.length === 0) {
      throw new Error('No se encontró ningún botón de modificar');
    }
    
    const modificarBtn = modificarButtons[0];
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center', behavior: 'smooth'});", modificarBtn);
    await driver.sleep(500);
    await driver.wait(until.elementIsVisible(modificarBtn), 10000);
    await modificarBtn.click();
    await driver.sleep(2000); // Esperar a que se carguen los datos

    // === Paso 4: Verificar que el modal se abrió ===
    testSteps.push('Verificar que el modal de modificación se abrió');
    await driver.wait(until.elementLocated(By.id('registro')), 10000);
    const modal = await driver.findElement(By.id('registro'));
    const isModalVisible = await modal.getAttribute('class');
    if (!isModalVisible.includes('show')) {
      throw new Error('El modal de modificación no se abrió correctamente');
    }
    console.log('Modal de modificación abierto correctamente.');
    testSteps.push('Modal de modificación abierto');

    // === Paso 5: Modificar con valores fuera de rango ===
    testSteps.push('Modificar con valores fuera de rango (nombre muy corto: "il")');
    console.log('Modificando con valores fuera de rango...');

    // Nombre muy corto (il - menos de 3 caracteres)
    const nombreInput = await driver.findElement(By.id('nombre'));
    await driver.wait(until.elementIsVisible(nombreInput), 10000);
    await nombreInput.clear();
    await nombreInput.sendKeys('il');
    await driver.sleep(500);

    // === Paso 6: Intentar actualizar ===
    testSteps.push('Hacer click en el botón ACTUALIZAR');
    console.log('Intentando actualizar con valores fuera de rango...');
    const btnEnviar = await driver.findElement(By.id('btnEnviar'));
    await driver.wait(until.elementIsVisible(btnEnviar), 10000);
    await driver.wait(until.elementIsEnabled(btnEnviar), 10000);
    await btnEnviar.click();
    await driver.sleep(3000);

    // === Paso 7: Verificar mensaje de error ===
    testSteps.push('Verificar mensaje de error de validación');
    console.log('Verificando mensaje de error...');
    
    try {
      const errorNombre = await driver.findElement(By.id('snombre'));
      const errorNombreText = await errorNombre.getText();
      
      if (errorNombreText && errorNombreText.length > 0) {
        console.log('Mensaje de error en nombre detectado: ' + errorNombreText);
        testSteps.push('Mensaje de error en nombre: ' + errorNombreText);
        notes = 'Error de validación detectado correctamente. Nombre: ' + errorNombreText + '. Se validó que se debe cumplir el rango permitido de caracteres (3 a 30 caracteres).';
        status = 'p';
      } else {
        try {
          await driver.wait(until.elementLocated(By.css('.swal2-info, .swal2-title')), 5000);
          const alertElement = await driver.findElement(By.css('.swal2-title'));
          const alertText = await alertElement.getText();
          if (alertText.includes('obligatorios') || alertText.includes('error') || alertText.includes('caracteres') || alertText.includes('Complete todos los campos')) {
            console.log('Mensaje de alerta detectado: ' + alertText);
            notes = 'Error de validación detectado: ' + alertText + '. Se validó que se debe cumplir el rango permitido de caracteres.';
            status = 'p';
          } else {
            throw new Error('Mensaje de error de validación no encontrado');
          }
        } catch (e) {
          throw new Error('Mensaje de error de validación no encontrado');
        }
      }
    } catch (e) {
      throw new Error('No se pudo verificar el mensaje de error de validación. Error: ' + e.message);
    }

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
    // Reportar a TestLink (mapear status)
    const testLinkStatus = status === 'p' || status === 'passed' ? 'p' : 'f';
    await reportResultToTestLink(testLinkStatus, notes);
  }
}

// === FUNCIÓN: Reportar resultado a TestLink ===
async function reportResultToTestLink(status, notes) {
  return new Promise((resolve) => {
    try {
      const client = xmlrpc.createClient({ url: TESTLINK_URL });

      // Limpiar notas de HTML y caracteres especiales
      const cleanNotes = notes
        .replace(/<[^>]*>/g, '')
        .replace(/\n/g, ' ')
        .replace(/\s+/g, ' ')
        .trim()
        .substring(0, 500); // Limitar a 500 caracteres

      console.log('Intentando conectar con TestLink...');
      
      client.methodCall('tl.checkDevKey', [{ devKey: DEV_KEY }], function (error, value) {
        if (error) {
          console.error('DevKey invalido o conexion fallida:', error);
          resolve();
          return;
        }

        console.log('DevKey valido. Reportando resultado...');
        
        // Validar External ID
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

