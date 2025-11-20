// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = 'Prueba-101';
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: REALIZAR PEDIDO - STOCK INSUFICIENTE ===
async function runTest() {
  let driver;
  let status = 'f';
  let notes = '';
  const startTime = new Date();
  const testSteps = [];

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

    // === Paso 1: Entrar a la página de login ===
    testSteps.push('Entrar a la página de login');
    console.log('Navegando al login...');
    await driver.get(BASE_URL + '?pagina=login');
    await driver.sleep(2000);
    
    // === Paso 2: Ingresar credenciales ===
    testSteps.push('Ingresar credenciales');
    console.log('Ingresando credenciales...');
    await driver.wait(until.elementLocated(By.id('usuario')), 15000);
    const usuarioInput = await driver.findElement(By.id('usuario'));
    await driver.wait(until.elementIsVisible(usuarioInput), 10000);
    await usuarioInput.clear();
    await usuarioInput.sendKeys('30559870');
    
    const passwordInput = await driver.findElement(By.id('pid'));
    await driver.wait(until.elementIsVisible(passwordInput), 10000);
    await passwordInput.clear();
    await passwordInput.sendKeys('love1234');
    
    // === Paso 3: Hacer click en Ingresar ===
    testSteps.push('Hacer click en Ingresar');
    console.log('Haciendo click en Ingresar...');
    const ingresarBtn = await driver.findElement(By.id('ingresar'));
    await driver.wait(until.elementIsEnabled(ingresarBtn), 10000);
    await ingresarBtn.click();
    
    // Esperar redirección a catálogo
    await driver.wait(until.urlContains('pagina=catalogo'), 15000);
    await driver.sleep(2000);
    console.log('Login exitoso.');
    testSteps.push('Login completado exitosamente');

    // === Paso 4: Ir a Todos los productos ===
    testSteps.push('Ir a Todos los productos');
    console.log('Accediendo a Todos los productos...');
    await driver.get(BASE_URL + '?pagina=catalogo_producto');
    await driver.sleep(2000);
    await driver.wait(until.urlContains('pagina=catalogo_producto'), 10000);
    testSteps.push('Redirección a catálogo de productos confirmada');

    // === Paso 5: Intentar agregar producto sin stock al carrito ===
    testSteps.push('Intentar agregar producto sin stock al carrito');
    console.log('Intentando agregar producto sin stock...');
    try {
      await driver.sleep(2000);
      // Buscar productos sin stock (puede requerir identificar productos específicos)
      const agregarBtns = await driver.findElements(By.css('.btn-agregar-carrito-exterior, #btn-agregar-carrito'));
      if (agregarBtns.length > 0) {
        // Intentar agregar el primer producto disponible
        await driver.executeScript("arguments[0].scrollIntoView({block: 'center', behavior: 'smooth'});", agregarBtns[0]);
        await driver.sleep(500);
        await agregarBtns[0].click();
        await driver.sleep(2000);
        try {
          await driver.wait(until.elementLocated(By.css('.swal2-popup, .swal2-title')), 3000);
          await driver.sleep(1000);
        } catch (e) {}
        
        // Verificar mensaje de error de stock
        try {
          await driver.wait(until.elementLocated(By.css('.swal2-title, .alert-danger, .swal2-error')), 5000);
          const errorMsg = await driver.findElement(By.css('.swal2-title, .alert-danger, .swal2-error'));
          const msgText = await errorMsg.getText();
          if (msgText.includes('Sin Stock') || msgText.includes('no hay stock') || msgText.includes('no esta disponible')) {
            console.log('Mensaje de stock insuficiente detectado: ' + msgText);
            testSteps.push('Alerta detectada: Sin Stock, no hay stock del producto, el producto no esta disponible');
            notes = 'Prueba exitosa. Se detectó correctamente el mensaje de stock insuficiente: ' + msgText;
            status = 'p';
          } else {
            throw new Error('Mensaje de error no coincide con el esperado. Mensaje recibido: ' + msgText);
          }
        } catch (e) {
          // Si no hay mensaje de error, verificar si hay mensaje de éxito (producto agregado)
          try {
            const successMsg = await driver.findElement(By.css('.swal2-success, .alert-success'));
            const successText = await successMsg.getText();
            throw new Error('Se agregó el producto cuando debería mostrar error de stock. Mensaje: ' + successText);
          } catch (e2) {
            throw new Error('No se encontró mensaje de error de stock insuficiente');
          }
        }
      } else {
        throw new Error('No se encontró botón para agregar al carrito');
      }
    } catch (error) {
      console.log('Error al verificar stock:', error.message);
      throw error;
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

