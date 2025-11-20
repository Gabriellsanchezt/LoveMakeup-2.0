// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = 'Prueba-109';
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: PARROQUIA NO SELECCIONADA ===
async function runTest() {
  let driver;
  let status = 'f';
  let notes = '';
  const startTime = new Date();
  const testSteps = [];

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

    // Seleccionar zona pero NO parroquia
    const zonaSelect = await driver.findElement(By.css('select[id*="zona"], select[name*="zona"]'));
    await zonaSelect.click();
    await driver.executeScript("arguments[0].selectedIndex = 1; arguments[0].dispatchEvent(new Event('change'));", zonaSelect);
    await driver.sleep(1000);

    // NO seleccionar parroquia
    const direccionExacta = await driver.findElement(By.css('input[id*="direccion"], textarea[id*="direccion"]'));
    await direccionExacta.clear();
    await direccionExacta.sendKeys('Urb Tierra del Sol III , n° casa 59');
    await driver.sleep(500);

    await driver.findElement(By.css('button[onclick*="continuar"], .btn-continuar')).click();
    await driver.sleep(1500);
    
    try {
      await driver.wait(until.elementLocated(By.css('.swal2-confirm, button.swal2-confirm')), 5000);
      const confirmarBtn = await driver.findElement(By.css('.swal2-confirm, button.swal2-confirm'));
      await confirmarBtn.click();
      await driver.sleep(2000);
      
      await driver.wait(until.elementLocated(By.css('.swal2-title, .alert-danger')), 5000);
      const errorMsg = await driver.findElement(By.css('.swal2-title, .alert-danger'));
      const msgText = await errorMsg.getText();
      if (msgText.includes('parroquia') || msgText.includes('sector') || msgText.includes('Seleccione')) {
        notes = 'Prueba exitosa. Se detectó mensaje de parroquia/sector no seleccionada: ' + msgText;
        status = 'p';
      } else {
        throw new Error('Mensaje de error no coincide: ' + msgText);
      }
    } catch (e) {
      throw new Error('No se encontró mensaje de error de parroquia no seleccionada');
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

