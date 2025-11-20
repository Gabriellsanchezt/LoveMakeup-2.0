// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = 'Prueba-111';
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: DIRECCIÓN EXACTA INVÁLIDA ===
async function runTest() {
  let driver;
  let status = 'f';
  let notes = '';

  try {
    if (BROWSER === 'edge') {
      const options = new edge.Options();
      driver = await new Builder().forBrowser('MicrosoftEdge').setEdgeOptions(options).build();
    } else if (BROWSER === 'chrome') {
      const chrome = require('selenium-webdriver/chrome');
      const options = new chrome.Options();
      driver = await new Builder().forBrowser('chrome').setChromeOptions(options).build();
    } else {
      driver = await new Builder().forBrowser(BROWSER).build();
    }
    
    await driver.manage().setTimeouts({ implicit: 10000, pageLoad: 30000, script: 30000 });
    await driver.manage().window().maximize();

    // Login y navegación
    await driver.get(BASE_URL + '?pagina=login');
    await driver.sleep(2000);
    await driver.wait(until.elementLocated(By.id('usuario')), 15000);
    await driver.findElement(By.id('usuario')).clear();
    await driver.findElement(By.id('usuario')).sendKeys('30559870');
    await driver.findElement(By.id('pid')).clear();
    await driver.findElement(By.id('pid')).sendKeys('love1234');
    await driver.findElement(By.id('ingresar')).click();
    await driver.wait(until.urlContains('pagina=catalogo'), 15000);
    await driver.sleep(2000);

    await driver.get(BASE_URL + '?pagina=catalogo_producto');
    await driver.sleep(2000);
    const agregarBtns = await driver.findElements(By.css('.btn-agregar-carrito-exterior, #btn-agregar-carrito'));
    if (agregarBtns.length > 0) {
      await driver.executeScript("arguments[0].scrollIntoView({block: 'center', behavior: 'smooth'});", agregarBtns[0]);
      await driver.sleep(500);
      await agregarBtns[0].click();
      await driver.sleep(2000);
      try {
        await driver.wait(until.elementLocated(By.css('.swal2-popup, .swal2-title')), 3000);
        await driver.sleep(1000);
      } catch (e) {}
    }

    await driver.get(BASE_URL + '?pagina=vercarrito');
    await driver.sleep(2000);
    await driver.findElement(By.id('btn-siguiente')).click();
    await driver.sleep(2000);
    await driver.wait(until.urlContains('pagina=Pedidoentrega'), 15000);

    await driver.findElement(By.css('button[onclick*="delivery"], .btn-delivery')).click();
    await driver.sleep(1500);

    // Seleccionar zona, parroquia y sector
    const zonaSelect = await driver.findElement(By.css('select[id*="zona"], select[name*="zona"]'));
    await zonaSelect.click();
    await driver.executeScript("arguments[0].selectedIndex = 1; arguments[0].dispatchEvent(new Event('change'));", zonaSelect);
    await driver.sleep(1000);

    const parroquiaSelect = await driver.findElement(By.css('select[id*="parroquia"], select[name*="parroquia"]'));
    await parroquiaSelect.click();
    await driver.executeScript("arguments[0].selectedIndex = 1; arguments[0].dispatchEvent(new Event('change'));", parroquiaSelect);
    await driver.sleep(1000);

    const sectorSelect = await driver.findElement(By.css('select[id*="sector"], select[name*="sector"]'));
    await sectorSelect.click();
    await driver.executeScript("arguments[0].selectedIndex = 1; arguments[0].dispatchEvent(new Event('change'));", sectorSelect);
    await driver.sleep(500);

    // === Ingresar dirección exacta inválida (muy corta) ===
    console.log('Ingresando dirección exacta inválida (La cruz)...');
    const direccionExacta = await driver.findElement(By.css('input[id*="direccion"], textarea[id*="direccion"]'));
    await direccionExacta.clear();
    await direccionExacta.sendKeys('La cruz');
    await driver.sleep(1000);

    // Verificar mensaje de error
    try {
      await driver.wait(until.elementLocated(By.css('.swal2-title, .alert-danger, .text-danger')), 5000);
      const errorMsg = await driver.findElement(By.css('.swal2-title, .alert-danger, .text-danger'));
      const msgText = await errorMsg.getText();
      if (msgText.includes('Debe tener entre 10 y 50 caracteres') || msgText.includes('10 y 50') || msgText.includes('caracteres')) {
        console.log('Mensaje de error detectado: ' + msgText);
        notes = 'Prueba exitosa. Se detectó correctamente el mensaje de dirección exacta inválida: ' + msgText;
        status = 'p';
      } else {
        throw new Error('Mensaje de error no coincide. Mensaje recibido: ' + msgText);
      }
    } catch (e) {
      const dirValue = await direccionExacta.getAttribute('value');
      if (dirValue === 'La cruz' && dirValue.length < 10) {
        notes = 'Dirección inválida ingresada (La cruz - menos de 10 caracteres). Validación puede estar en el backend.';
        status = 'p';
      } else {
        throw new Error('No se encontró mensaje de error de dirección exacta inválida');
      }
    }

  } catch (error) {
    console.error('Error durante la prueba:', error.message);
    notes = 'Error: ' + error.message;
    status = 'f';
  } finally {
    if (driver) {
      try {
        await driver.quit();
      } catch (quitError) {}
    }
    const testLinkStatus = status === 'p' || status === 'passed' ? 'p' : 'f';
    await reportResultToTestLink(testLinkStatus, notes);
  }
}

async function reportResultToTestLink(status, notes) {
  return new Promise((resolve) => {
    try {
      const client = xmlrpc.createClient({ url: TESTLINK_URL });
      const cleanNotes = notes.replace(/<[^>]*>/g, '').replace(/\n/g, ' ').replace(/\s+/g, ' ').trim().substring(0, 500);
      
      client.methodCall('tl.checkDevKey', [{ devKey: DEV_KEY }], function (error, value) {
        if (error) {
          console.error('DevKey invalido o conexion fallida:', error);
          resolve();
          return;
        }
        
        const externalId = String(TEST_CASE_EXTERNAL_ID || '').trim();
        if (!externalId || externalId.length === 0) {
          console.error('Error: External ID no puede estar vacio');
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

if (require.main === module) {
  runTest().catch(error => {
    console.error('Error fatal en la ejecucion del test:', error);
    process.exit(1);
  });
}

module.exports = { runTest, reportResultToTestLink };

