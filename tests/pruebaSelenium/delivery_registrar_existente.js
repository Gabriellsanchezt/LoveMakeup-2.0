// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
const ReportGenerator = require('./ReportGenerator');

// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = '40';
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: ERROR AL REGISTRAR DELIVERY EXISTENTE ===
async function runTest() {
  let driver;
  let status = 'f';
  let notes = '';
  const startTime = new Date();
  const testSteps = [];
  const reportGenerator = new ReportGenerator();
  const testName = 'Error al registrar un delivery existente';

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

    // Maximizar ventana
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

    // === Paso 2: Ir al módulo Delivery ===
    testSteps.push('Navegar al módulo de Delivery');
    console.log('Accediendo al módulo Delivery...');
    await driver.get(BASE_URL + '?pagina=delivery');
    await driver.sleep(2000);
    
    await driver.wait(until.elementLocated(By.id('btnAbrirRegistrar')), 15000);
    console.log('Módulo Delivery cargado correctamente.');
    testSteps.push('Módulo Delivery cargado correctamente');

    // === Paso 3: Obtener nombre de un delivery existente ===
    testSteps.push('Obtener nombre de un delivery existente');
    console.log('Buscando delivery existente...');
    
    // Buscar el primer delivery en la tabla para obtener su nombre
    let nombreExistente = 'Rhichard Virguez';
    try {
      await driver.wait(until.elementLocated(By.css('tbody tr')), 10000);
      const primeraFila = await driver.findElement(By.css('tbody tr'));
      const celdas = await primeraFila.findElements(By.css('td'));
      if (celdas.length > 0) {
        nombreExistente = await celdas[0].getText();
        console.log('Nombre de delivery existente encontrado: ' + nombreExistente);
      }
    } catch (e) {
      console.log('No se pudo obtener nombre existente, usando: ' + nombreExistente);
    }

    // === Paso 4: Abrir formulario de registro ===
    testSteps.push('Abrir formulario de registro de delivery');
    console.log('Abriendo formulario de registro...');
    const registrarBtn = await driver.findElement(By.id('btnAbrirRegistrar'));
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center', behavior: 'smooth'});", registrarBtn);
    await driver.sleep(500);
    await driver.wait(until.elementIsVisible(registrarBtn), 10000);
    await registrarBtn.click();
    await driver.sleep(1500);

    await driver.wait(until.elementLocated(By.id('registro')), 10000);
    const modal = await driver.findElement(By.id('registro'));
    const isModalVisible = await modal.getAttribute('class');
    if (!isModalVisible.includes('show')) {
      throw new Error('El modal de registro no se abrió correctamente');
    }
    console.log('Modal de registro abierto correctamente.');
    testSteps.push('Modal de registro abierto');

    // === Paso 5: Llenar formulario con nombre existente ===
    testSteps.push('Llenar datos con nombre existente: ' + nombreExistente);
    console.log('Llenando datos con nombre existente...');

    const nombreInput = await driver.findElement(By.id('nombre'));
    await driver.wait(until.elementIsVisible(nombreInput), 10000);
    await nombreInput.clear();
    await nombreInput.sendKeys(nombreExistente);
    await driver.sleep(500);

    const contactoInput = await driver.findElement(By.id('contacto'));
    await driver.wait(until.elementIsVisible(contactoInput), 10000);
    await contactoInput.clear();
    await contactoInput.sendKeys('04245071950');
    await driver.sleep(500);

    const tipoSelect = await driver.findElement(By.id('tipo'));
    await driver.wait(until.elementIsVisible(tipoSelect), 10000);
    await tipoSelect.click();
    await driver.sleep(300);
    await tipoSelect.sendKeys(Key.ARROW_DOWN);
    await driver.sleep(300);
    await tipoSelect.sendKeys(Key.ENTER);
    await driver.sleep(500);

    // === Paso 6: Intentar registrar ===
    testSteps.push('Hacer click en el botón REGISTRAR');
    console.log('Intentando registrar delivery existente...');
    const btnEnviar = await driver.findElement(By.id('btnEnviar'));
    await driver.wait(until.elementIsVisible(btnEnviar), 10000);
    await driver.wait(until.elementIsEnabled(btnEnviar), 10000);
    await btnEnviar.click();
    await driver.sleep(3000);

    // === Paso 7: Verificar mensaje de error ===
    testSteps.push('Verificar mensaje de error');
    console.log('Verificando mensaje de error...');
    
    try {
      await driver.wait(until.elementLocated(By.css('.swal2-error, .swal2-title, .swal2-html-container')), 10000);
      const errorElement = await driver.findElement(By.css('.swal2-title, .swal2-html-container'));
      const errorText = await errorElement.getText();
      
      if (errorText.includes('existe') || errorText.includes('registrado') || errorText.includes('mismo nombre')) {
        console.log('Mensaje de error detectado correctamente: ' + errorText);
        testSteps.push('Mensaje de error confirmado: ' + errorText);
        notes = 'Error detectado correctamente: ' + errorText + '. Se validó que no se puede registrar un delivery con nombre existente.';
        status = 'p';
      } else {
        const htmlContainer = await driver.findElement(By.css('.swal2-html-container'));
        const htmlText = await htmlContainer.getText();
        if (htmlText.includes('existe') || htmlText.includes('registrado') || htmlText.includes('mismo nombre')) {
          console.log('Mensaje de error detectado en HTML: ' + htmlText);
          testSteps.push('Mensaje de error confirmado: ' + htmlText);
          notes = 'Error detectado correctamente: ' + htmlText + '. Se validó que no se puede registrar un delivery con nombre existente.';
          status = 'p';
        } else {
          throw new Error('Mensaje de error no encontrado o incorrecto. Texto encontrado: ' + errorText);
        }
      }
    } catch (e) {
      try {
        const successElement = await driver.findElement(By.css('.swal2-success'));
        const successText = await successElement.getText();
        throw new Error('Se esperaba un error pero se detectó éxito: ' + successText);
      } catch (e2) {
        throw new Error('No se pudo verificar el mensaje de error. Error: ' + e.message);
      }
    }

    console.log('Error al registrar delivery existente verificado exitosamente.');

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

    // Generar reportes
    try {
      const reportData = {
        testName: testName,
        status: status,
        notes: notes,
        startTime: startTime,
        endTime: endTime,
        steps: testSteps,
        error: status === 'f' ? notes : null,
        browser: BROWSER,
        baseUrl: BASE_URL,
        testCaseId: TEST_CASE_EXTERNAL_ID
      };

      const reportPath = await reportGenerator.generateReport(reportData);
      
      console.log('\n========================================');
      console.log('REPORTE XML GENERADO');
      console.log('========================================');
      console.log(`XML: ${reportPath}`);
      console.log('========================================\n');
    } catch (reportError) {
      console.error('Error al generar reporte:', reportError.message);
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

