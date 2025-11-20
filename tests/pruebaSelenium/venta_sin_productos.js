// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = 'Prueba-3'; 
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: REGISTRAR VENTA SIN PRODUCTOS (INVÁLIDO) ===
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
    await driver.get(BASE_URL + '?pagina=login');
    await driver.sleep(2000);
    
    await driver.wait(until.elementLocated(By.id('usuario')), 15000);
    await driver.findElement(By.id('usuario')).sendKeys('10200300');
    await driver.findElement(By.id('pid')).sendKeys('love1234');
    await driver.findElement(By.id('ingresar')).click();
    
    await driver.wait(until.urlContains('pagina=home'), 15000);
    await driver.sleep(2000);
    console.log('Login exitoso.');
    testSteps.push('Login completado exitosamente');

    // === Paso 2: Ir al módulo Venta ===
    testSteps.push('Navegar al módulo de Venta');
    await driver.get(BASE_URL + '?pagina=salida');
    await driver.sleep(2000);
    
    await driver.wait(until.elementLocated(By.css('button[data-bs-target="#registroModal"]')), 15000);
    testSteps.push('Módulo Venta cargado correctamente');

    // === Paso 3: Abrir formulario ===
    testSteps.push('Abrir formulario de registro de venta');
    const registrarBtn = await driver.findElement(By.css('button[data-bs-target="#registroModal"]'));
    await registrarBtn.click();

    await driver.sleep(1500);
    const modal = await driver.findElement(By.id('registroModal'));
    await driver.wait(until.elementIsVisible(modal), 15000);
    await driver.sleep(1000);

    // === Paso 4: Paso 1 - Cliente ===
    testSteps.push('Seleccionar un cliente válido');
    const cedulaInput = await driver.findElement(By.id('cedula_cliente'));
    await cedulaInput.clear();
    await cedulaInput.sendKeys('12345678');
    await driver.executeScript("arguments[0].dispatchEvent(new Event('input', { bubbles: true }));", cedulaInput);
    await driver.sleep(2000);

    try {
      const camposCliente = await driver.findElement(By.id('campos-cliente'));
      const isVisible = await driver.executeScript("return window.getComputedStyle(arguments[0]).display !== 'none';", camposCliente);
      
      if (isVisible) {
        await driver.findElement(By.id('nombre_cliente')).sendKeys('Cliente');
        await driver.findElement(By.id('apellido_cliente')).sendKeys('Prueba');
        await driver.findElement(By.id('telefono_cliente')).sendKeys('04141234567');
        await driver.findElement(By.id('correo_cliente')).sendKeys('cliente@prueba.com');
      }
    } catch (e) {
      console.log('Cliente encontrado en el sistema');
    }

    const btnSiguiente = await driver.findElement(By.id('btnSiguiente'));
    await btnSiguiente.click();
    await driver.sleep(1500);
    testSteps.push('Cliente seleccionado correctamente');

    // === Paso 5: Paso 2 - NO Agregar Productos ===
    testSteps.push('No agregar ningún producto');
    await driver.wait(until.elementLocated(By.css('.producto-select-venta')), 15000);
    // No seleccionamos ningún producto, dejamos el select vacío
    console.log('No se agregaron productos (comportamiento esperado para esta prueba)');
    await driver.sleep(1000);

    // Intentar avanzar al siguiente paso (debería permitir avanzar, pero luego fallar al registrar)
    await btnSiguiente.click();
    await driver.sleep(1500);
    testSteps.push('Intentar avanzar sin productos');

    // === Paso 6: Paso 3 - Método de Pago (opcional, pero intentamos avanzar) ===
    // Si llegamos aquí, intentamos avanzar
    try {
      await driver.wait(until.elementLocated(By.css('.metodo-pago-select')), 5000);
      await btnSiguiente.click();
      await driver.sleep(1500);
    } catch (e) {
      // Puede que no se pueda avanzar sin productos
      console.log('No se pudo avanzar sin productos (comportamiento esperado)');
    }

    // === Paso 7: Intentar Registrar Venta Sin Productos ===
    testSteps.push('Intentar registrar la venta sin productos');
    console.log('Intentando registrar venta sin productos...');
    
    try {
      await driver.wait(until.elementLocated(By.id('btnRegistrarVenta')), 10000);
      const btnRegistrarVenta = await driver.findElement(By.id('btnRegistrarVenta'));
      
      if (await btnRegistrarVenta.isDisplayed()) {
        await btnRegistrarVenta.click();
        await driver.sleep(2000);
      }
    } catch (e) {
      // El botón puede no estar visible si no hay productos
      console.log('Botón de registrar no disponible sin productos');
    }

    // === Paso 8: Verificar Mensaje de Error ===
    testSteps.push('Verificar que se muestra mensaje de error');
    await driver.sleep(2000);
    
    let errorEncontrado = false;
    let mensajeError = '';
    
    try {
      const errorMessages = await driver.findElements(By.css('.alert-danger, .alert-warning, .toast-error, .toast-warning, .invalid-feedback'));
      for (let errorMsg of errorMessages) {
        const errorText = await errorMsg.getText();
        if (errorText && errorText.trim() !== '' && 
            (errorText.toLowerCase().includes('producto') || 
             errorText.toLowerCase().includes('debe agregar') ||
             errorText.toLowerCase().includes('al menos'))) {
          errorEncontrado = true;
          mensajeError = errorText;
          break;
        }
      }
    } catch (e) {
      // Continuar verificando
    }

    // También verificar en el paso de productos
    try {
      const productoSelect = await driver.findElement(By.css('.producto-select-venta'));
      const validationMessage = await driver.executeScript(`
        var select = arguments[0];
        return select.validationMessage || '';
      `, productoSelect);
      
      if (validationMessage && validationMessage.includes('producto')) {
        errorEncontrado = true;
        mensajeError = validationMessage;
      }
    } catch (e) {
      // Continuar
    }

    if (errorEncontrado) {
      console.log('Mensaje de error detectado correctamente: ' + mensajeError);
      notes = 'Prueba exitosa: El sistema mostro correctamente un mensaje de error indicando que debe agregar al menos un producto. Mensaje: ' + mensajeError;
      status = 'p';
    } else {
      // Verificar que la venta no se registró
      await driver.sleep(2000);
      const modalVisible = await driver.executeScript("return document.querySelector('#registroModal').classList.contains('show');");
      
      if (modalVisible) {
        console.log('El modal sigue abierto - La venta no se registro (comportamiento esperado)');
        notes = 'Prueba exitosa: El sistema no permitio registrar la venta sin productos. El formulario permanece abierto.';
        status = 'p';
      } else {
        throw new Error('No se detecto el error esperado. La venta puede haberse registrado incorrectamente.');
      }
    }

  } catch (error) {
    console.error('Error durante la prueba:', error.message);
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

    try {
      const reportData = {
        
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

      
      
      
      
      
    } catch (reportError) {
      
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

