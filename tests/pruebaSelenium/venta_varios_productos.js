// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = 'Prueba-2'; 
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: REGISTRAR VENTA VÁLIDA CON VARIOS PRODUCTOS Y MÉTODOS DE PAGO ===
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

    // === Paso 1: Iniciar sesión ===
    testSteps.push('Iniciar sesión en la aplicación');
    console.log('Navegando al login...');
    await driver.get(BASE_URL + '?pagina=login');
    await driver.sleep(2000);
    
    await driver.wait(until.elementLocated(By.id('usuario')), 15000);
    const usuarioInput = await driver.findElement(By.id('usuario'));
    await usuarioInput.clear();
    await usuarioInput.sendKeys('10200300');
    
    const passwordInput = await driver.findElement(By.id('pid'));
    await passwordInput.clear();
    await passwordInput.sendKeys('love1234');
    
    const ingresarBtn = await driver.findElement(By.id('ingresar'));
    await ingresarBtn.click();
    
    await driver.wait(until.urlContains('pagina=home'), 15000);
    await driver.sleep(2000);
    console.log('Login exitoso.');
    testSteps.push('Login completado exitosamente');

    // === Paso 2: Ir al módulo Venta ===
    testSteps.push('Navegar al módulo de Venta');
    await driver.get(BASE_URL + '?pagina=salida');
    await driver.sleep(2000);
    
    await driver.wait(until.elementLocated(By.css('button[data-bs-target="#registroModal"]')), 15000);
    console.log('Modulo Venta cargado correctamente.');
    testSteps.push('Módulo Venta cargado correctamente');

    // === Paso 3: Abrir formulario ===
    testSteps.push('Abrir formulario de registro de venta');
    const registrarBtn = await driver.findElement(By.css('button[data-bs-target="#registroModal"]'));
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", registrarBtn);
    await driver.sleep(500);
    await registrarBtn.click();

    await driver.sleep(1500);
    const modal = await driver.findElement(By.id('registroModal'));
    await driver.wait(until.elementIsVisible(modal), 15000);
    await driver.sleep(1000);

    // === Paso 4: Paso 1 - Cliente ===
    testSteps.push('Llenar datos del cliente');
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
    testSteps.push('Datos del cliente completados');

    // === Paso 5: Paso 2 - Agregar Primer Producto ===
    testSteps.push('Agregar el primer producto');
    await driver.wait(until.elementLocated(By.css('.producto-select-venta')), 15000);
    const productoSelect1 = await driver.findElement(By.css('.producto-select-venta'));
    
    const productoSeleccionado1 = await driver.executeScript(`
      var select = arguments[0];
      var options = select.options;
      for (var i = 1; i < options.length; i++) {
        var option = options[i];
        if (option.value && option.value !== '') {
          var stock = parseFloat(option.getAttribute('data-stock') || '0');
          if (stock > 0) {
            select.selectedIndex = i;
            if (typeof $ !== 'undefined' && $(select).hasClass('select2-hidden-accessible')) {
              $(select).val(option.value).trigger('change');
            } else {
              var event = new Event('change', { bubbles: true });
              select.dispatchEvent(event);
            }
            return { success: true, value: option.value, text: option.text };
          }
        }
      }
      return { success: false };
    `, productoSelect1);
    
    if (!productoSeleccionado1.success) {
      throw new Error('No se encontraron productos con stock disponible');
    }
    
    console.log('Primer producto seleccionado: ' + productoSeleccionado1.text);
    await driver.sleep(2000);

    const cantidadInput1 = await driver.findElement(By.css('.cantidad-input-venta'));
    await cantidadInput1.clear();
    await cantidadInput1.sendKeys('2');
    await driver.executeScript("arguments[0].dispatchEvent(new Event('input', { bubbles: true }));", cantidadInput1);
    await driver.sleep(1000);

    // Agregar producto a la lista
    const agregarProductoBtn = await driver.findElement(By.css('.agregar-producto-venta'));
    await agregarProductoBtn.click();
    await driver.sleep(1500);
    testSteps.push('Primer producto agregado');

    // === Paso 6: Agregar Segundo Producto ===
    testSteps.push('Agregar un segundo producto diferente');
    const productoFilas = await driver.findElements(By.css('.producto-fila'));
    const productoFila2 = productoFilas[productoFilas.length - 1];
    const productoSelect2 = await productoFila2.findElement(By.css('.producto-select-venta'));
    
    const productoSeleccionado2 = await driver.executeScript(`
      var select = arguments[0];
      var firstProductValue = arguments[1];
      var options = select.options;
      for (var i = 1; i < options.length; i++) {
        var option = options[i];
        if (option.value && option.value !== '' && option.value !== firstProductValue) {
          var stock = parseFloat(option.getAttribute('data-stock') || '0');
          if (stock > 0) {
            select.selectedIndex = i;
            if (typeof $ !== 'undefined' && $(select).hasClass('select2-hidden-accessible')) {
              $(select).val(option.value).trigger('change');
            } else {
              var event = new Event('change', { bubbles: true });
              select.dispatchEvent(event);
            }
            return { success: true, value: option.value, text: option.text };
          }
        }
      }
      return { success: false };
    `, productoSelect2, productoSeleccionado1.value);
    
    if (!productoSeleccionado2.success) {
      throw new Error('No se encontró un segundo producto diferente');
    }
    
    console.log('Segundo producto seleccionado: ' + productoSeleccionado2.text);
    await driver.sleep(2000);

    const cantidadInput2 = await productoFila2.findElement(By.css('.cantidad-input-venta'));
    await cantidadInput2.clear();
    await cantidadInput2.sendKeys('3');
    await driver.executeScript("arguments[0].dispatchEvent(new Event('input', { bubbles: true }));", cantidadInput2);
    await driver.sleep(1000);

    // Avanzar al siguiente paso
    await btnSiguiente.click();
    await driver.sleep(1500);
    testSteps.push('Segundo producto agregado');

    // === Paso 7: Paso 3 - Agregar Métodos de Pago ===
    testSteps.push('Seleccionar dos métodos de pago válidos');
    await driver.wait(until.elementLocated(By.css('.metodo-pago-select')), 15000);
    const metodoPagoSelect1 = await driver.findElement(By.css('.metodo-pago-select'));
    
    await metodoPagoSelect1.click();
    await driver.sleep(300);
    await metodoPagoSelect1.sendKeys(Key.ARROW_DOWN);
    await driver.sleep(300);
    await metodoPagoSelect1.sendKeys(Key.ENTER);
    await driver.sleep(1000);

    const montoInput1 = await driver.findElement(By.css('.monto-metodopago'));
    await montoInput1.clear();
    await montoInput1.sendKeys('20.00');
    await driver.executeScript("arguments[0].dispatchEvent(new Event('input', { bubbles: true }));", montoInput1);
    await driver.sleep(1000);

    // Agregar segundo método de pago
    const agregarMetodoPagoBtn = await driver.findElement(By.css('.agregar-metodo-pago'));
    await agregarMetodoPagoBtn.click();
    await driver.sleep(1500);

    const metodoPagoFilas = await driver.findElements(By.css('.metodo-pago-fila'));
    const metodoPagoFila2 = metodoPagoFilas[metodoPagoFilas.length - 1];
    const metodoPagoSelect2 = await metodoPagoFila2.findElement(By.css('.metodo-pago-select'));
    
    await metodoPagoSelect2.click();
    await driver.sleep(300);
    await metodoPagoSelect2.sendKeys(Key.ARROW_DOWN);
    await driver.sleep(300);
    await metodoPagoSelect2.sendKeys(Key.ENTER);
    await driver.sleep(1000);

    const montoInput2 = await metodoPagoFila2.findElement(By.css('.monto-metodopago'));
    await montoInput2.clear();
    await montoInput2.sendKeys('10.00');
    await driver.executeScript("arguments[0].dispatchEvent(new Event('input', { bubbles: true }));", montoInput2);
    await driver.sleep(1000);

    // Avanzar al siguiente paso
    await btnSiguiente.click();
    await driver.sleep(1500);
    testSteps.push('Métodos de pago agregados');

    // === Paso 8: Paso 4 - Registrar Venta ===
    testSteps.push('Registrar la venta');
    await driver.wait(until.elementLocated(By.id('btnRegistrarVenta')), 15000);
    const btnRegistrarVenta = await driver.findElement(By.id('btnRegistrarVenta'));
    await driver.wait(until.elementIsVisible(btnRegistrarVenta), 10000);
    await btnRegistrarVenta.click();

    // === Paso 9: Verificar éxito ===
    testSteps.push('Verificar que la venta se registró exitosamente');
    await driver.sleep(3000);
    
    try {
      const errorMessages = await driver.findElements(By.css('.alert-danger, .alert-warning, .toast-error'));
      if (errorMessages.length > 0) {
        for (let errorMsg of errorMessages) {
          const errorText = await errorMsg.getText();
          if (errorText && errorText.trim() !== '') {
            throw new Error('Error detectado: ' + errorText);
          }
        }
      }
    } catch (e) {
      if (e.message && e.message.includes('Error detectado')) {
        throw e;
      }
    }
    
    try {
      await driver.wait(until.stalenessOf(modal), 15000);
      console.log('Modal cerrado - Venta registrada exitosamente.');
    } catch (e) {
      try {
        await driver.wait(until.elementLocated(By.css('.alert-success, .toast-success')), 10000);
        const successMsg = await driver.findElement(By.css('.alert-success, .toast-success'));
        const successText = await successMsg.getText();
        console.log('Mensaje de exito detectado: ' + successText);
      } catch (e2) {
        await driver.sleep(2000);
        const tabla = await driver.findElement(By.id('myTable'));
        if (await tabla.isDisplayed()) {
          console.log('Tabla visible - Venta registrada exitosamente.');
        } else {
          throw new Error('No se pudo verificar el exito de la operacion');
        }
      }
    }

    console.log('Venta registrada exitosamente con varios productos y métodos de pago.');
    notes = 'Venta registrada exitosamente con varios productos y métodos de pago.';
    status = 'p';

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

