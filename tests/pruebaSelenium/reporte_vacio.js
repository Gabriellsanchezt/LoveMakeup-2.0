// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
const ReportGenerator = require('./ReportGenerator');

// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = '48'; 
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: ERROR AL GENERAR UN REPORTE VACÍO ===
async function runTest() {
  let driver;
  let status = 'f';
  let notes = '';
  const startTime = new Date();
  const testSteps = [];
  const reportGenerator = new ReportGenerator();
  const testName = 'Error al generar un reporte vacio';

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

    // === Paso 5: Seleccionar filtros que no tengan datos ===
    testSteps.push('Seleccionar filtros que no tengan datos');
    console.log('Seleccionando filtros sin datos...');
    
    // Seleccionar fechas muy antiguas que no tengan registros
    const fechaInicio = await driver.findElement(By.css('#modalCompra input[name="f_start"]'));
    await driver.wait(until.elementIsVisible(fechaInicio), 10000);
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", fechaInicio);
    await driver.sleep(300);
    
    // Establecer una fecha muy antigua (ej: 2000-01-01)
    await driver.executeScript("arguments[0].value = '2000-01-01';", fechaInicio);
    await driver.executeScript("arguments[0].dispatchEvent(new Event('change', { bubbles: true }));", fechaInicio);
    await driver.sleep(500);
    
    const fechaFin = await driver.findElement(By.css('#modalCompra input[name="f_end"]'));
    await driver.wait(until.elementIsVisible(fechaFin), 10000);
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", fechaFin);
    await driver.sleep(300);
    
    // Establecer una fecha antigua (ej: 2000-12-31)
    await driver.executeScript("arguments[0].value = '2000-12-31';", fechaFin);
    await driver.executeScript("arguments[0].dispatchEvent(new Event('change', { bubbles: true }));", fechaFin);
    await driver.sleep(1000);
    
    console.log('Filtros sin datos seleccionados.');
    testSteps.push('Filtros sin datos seleccionados');

    // === Paso 6: Hacer click en GENERAR PDF ===
    testSteps.push('Hacer click en GENERAR PDF');
    console.log('Generando PDF con filtros sin datos...');
    const generarPdfBtn = await driver.findElement(By.css('#modalCompra form button[type="submit"]'));
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center', behavior: 'smooth'});", generarPdfBtn);
    await driver.sleep(500);
    await driver.wait(until.elementIsVisible(generarPdfBtn), 10000);
    await driver.wait(until.elementIsEnabled(generarPdfBtn), 10000);
    
    try {
      await generarPdfBtn.click();
    } catch (e) {
      await driver.executeScript("arguments[0].click();", generarPdfBtn);
    }

    // === Paso 7: Verificar mensaje de error ===
    testSteps.push('Verificar mensaje de error');
    console.log('Verificando mensaje de error...');
    
    // Esperar a que aparezca el mensaje de error
    await driver.sleep(3000);
    
    // Buscar mensaje de error en diferentes formatos posibles
    let errorEncontrado = false;
    let errorText = '';
    
    try {
      // Opción 1: Buscar en alertas
      const errorAlerts = await driver.findElements(By.css('.alert-danger, .alert-warning, .toast-error, .toast-warning'));
      for (let alert of errorAlerts) {
        const text = await alert.getText();
        if (text && (text.includes('Sin datos') || text.includes('no hay registros') || text.includes('generar el pdf'))) {
          errorEncontrado = true;
          errorText = text;
          console.log('Mensaje de error encontrado en alerta: ' + text);
          break;
        }
      }
    } catch (e) {
      // Continuar con otras opciones
    }
    
    if (!errorEncontrado) {
      try {
        // Opción 2: Buscar en el contenido de la página
        const pageSource = await driver.getPageSource();
        if (pageSource.includes('Sin datos') || pageSource.includes('no hay registros') || pageSource.includes('generar el pdf')) {
          errorEncontrado = true;
          errorText = 'Mensaje de error encontrado en el contenido de la página';
          console.log('Mensaje de error encontrado en el contenido de la página');
        }
      } catch (e) {
        // Continuar
      }
    }
    
    if (!errorEncontrado) {
      try {
        // Opción 3: Verificar si se abrió una nueva ventana con error
        const windows = await driver.getAllWindowHandles();
        if (windows.length > 1) {
          await driver.switchTo().window(windows[windows.length - 1]);
          await driver.sleep(2000);
          const pageSource = await driver.getPageSource();
          if (pageSource.includes('Sin datos') || pageSource.includes('no hay registros') || pageSource.includes('generar el pdf')) {
            errorEncontrado = true;
            errorText = 'Mensaje de error encontrado en nueva ventana';
            console.log('Mensaje de error encontrado en nueva ventana');
          }
          await driver.close();
          await driver.switchTo().window(windows[0]);
        }
      } catch (e) {
        // Continuar
      }
    }
    
    if (!errorEncontrado) {
      throw new Error('No se encontro el mensaje de error esperado: "Sin datos, no hay registros para generar el pdf"');
    }
    
    console.log('Mensaje de error verificado correctamente: ' + errorText);
    testSteps.push('Mensaje de error verificado correctamente');

    console.log('Error al generar reporte vacio verificado exitosamente.');
    notes = 'Error al generar reporte vacio verificado exitosamente. Mensaje de error: ' + errorText;
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

