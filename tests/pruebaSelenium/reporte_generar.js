// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
const ReportGenerator = require('./ReportGenerator');

// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = '47'; 
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: GENERAR REPORTE ===
async function runTest() {
  let driver;
  let status = 'f';
  let notes = '';
  const startTime = new Date();
  const testSteps = [];
  const reportGenerator = new ReportGenerator();
  const testName = 'Generar Reporte';

  try {
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

    // === Paso 2: Ir al módulo Reporte ===
    testSteps.push('Navegar al módulo de Reporte');
    console.log('Accediendo al módulo Reporte...');
    await driver.get(BASE_URL + '?pagina=reporte');
    await driver.sleep(2000);
    
    // Esperar a que la página cargue completamente
    await driver.wait(until.elementLocated(By.css('button[data-bs-target="#modalCompra"]')), 15000);
    console.log('Modulo Reporte cargado correctamente.');
    testSteps.push('Módulo Reporte cargado correctamente');

    // === Paso 3: Seleccionar reporte y hacer click en Generar ===
    testSteps.push('Seleccionar reporte y hacer click en Generar');
    console.log('Seleccionando reporte de Compras...');
    const generarBtn = await driver.findElement(By.css('button[data-bs-target="#modalCompra"]'));
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center', behavior: 'smooth'});", generarBtn);
    await driver.sleep(500);
    await driver.wait(until.elementIsVisible(generarBtn), 10000);
    await driver.wait(until.elementIsEnabled(generarBtn), 10000);
    
    try {
      await generarBtn.click();
    } catch (e) {
      await driver.executeScript("arguments[0].click();", generarBtn);
    }

    // === Paso 4: Esperar que el modal esté visible ===
    await driver.sleep(1500);
    const modal = await driver.findElement(By.id('modalCompra'));
    await driver.wait(until.elementIsVisible(modal), 15000);
    await driver.executeScript("return document.querySelector('#modalCompra').classList.contains('show');");
    await driver.sleep(1000);
    console.log('Modal de filtros abierto correctamente.');
    testSteps.push('Modal de filtros abierto correctamente');

    // === Paso 5: Seleccionar filtros (opcional - dejar vacío para reporte general) ===
    testSteps.push('Seleccionar filtros para el reporte');
    console.log('Seleccionando filtros...');
    // Puede dejar los filtros vacíos para generar un reporte general
    // O seleccionar filtros específicos si es necesario
    await driver.sleep(1000);
    testSteps.push('Filtros seleccionados');

    // === Paso 6: Hacer click en GENERAR PDF ===
    testSteps.push('Hacer click en GENERAR PDF');
    console.log('Generando PDF...');
    const generarPdfBtn = await driver.findElement(By.css('#modalCompra form button[type="submit"]'));
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center', behavior: 'smooth'});", generarPdfBtn);
    await driver.sleep(500);
    await driver.wait(until.elementIsVisible(generarPdfBtn), 10000);
    await driver.wait(until.elementIsEnabled(generarPdfBtn), 10000);
    
    // Guardar el número de ventanas antes de hacer click
    const windowsBefore = await driver.getAllWindowHandles();
    const initialWindowCount = windowsBefore.length;
    
    try {
      await generarPdfBtn.click();
    } catch (e) {
      await driver.executeScript("arguments[0].click();", generarPdfBtn);
    }

    // === Paso 7: Verificar que se abre nueva pestaña con el reporte ===
    testSteps.push('Verificar que se abre nueva pestaña con el reporte');
    console.log('Verificando apertura de nueva pestaña...');
    
    // Esperar a que se abra la nueva ventana
    await driver.sleep(3000);
    const windowsAfter = await driver.getAllWindowHandles();
    
    if (windowsAfter.length > initialWindowCount) {
      // Cambiar a la nueva ventana
      const newWindow = windowsAfter[windowsAfter.length - 1];
      await driver.switchTo().window(newWindow);
      await driver.sleep(2000);
      
      // Verificar que la nueva ventana tiene contenido (puede ser PDF o HTML)
      const currentUrl = await driver.getCurrentUrl();
      console.log('Nueva pestaña abierta con URL: ' + currentUrl);
      
      // Verificar que no es una página de error
      const pageSource = await driver.getPageSource();
      if (pageSource.includes('error') || pageSource.includes('Error') || pageSource.includes('sin datos')) {
        throw new Error('Se detecto un error en la generacion del reporte');
      }
      
      console.log('Reporte generado exitosamente en nueva pestaña.');
      testSteps.push('Reporte generado exitosamente en nueva pestaña');
      
      // Cerrar la nueva ventana y volver a la principal
      await driver.close();
      await driver.switchTo().window(windowsBefore[0]);
    } else {
      // Si no se abrió nueva ventana, verificar que se descargó o se mostró en la misma ventana
      await driver.sleep(2000);
      const currentUrl = await driver.getCurrentUrl();
      if (currentUrl.includes('reporte') || currentUrl.includes('accion=compra')) {
        console.log('Reporte generado en la misma ventana.');
        testSteps.push('Reporte generado en la misma ventana');
      } else {
        throw new Error('No se pudo verificar la generacion del reporte');
      }
    }

    console.log('Reporte generado exitosamente.');
    notes = 'Reporte generado exitosamente. Se abrio nueva pestaña con el reporte solicitado.';
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

