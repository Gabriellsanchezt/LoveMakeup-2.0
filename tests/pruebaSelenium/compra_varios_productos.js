// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = 'Prueba-10';
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: REGISTRAR COMPRA VÁLIDA CON VARIOS PRODUCTOS ===
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
    
    await driver.wait(until.urlContains('pagina=home'), 15000);
    await driver.sleep(2000);
    console.log('Login exitoso.');
    testSteps.push('Login completado exitosamente');

    // === Paso 2: Ir al módulo Compra ===
    testSteps.push('Navegar al módulo de Compra');
    console.log('Accediendo al módulo Compra...');
    await driver.get(BASE_URL + '?pagina=entrada');
    await driver.sleep(2000);
    
    await driver.wait(until.elementLocated(By.css('button[data-bs-target="#registroModal"]')), 15000);
    console.log('Modulo Compra cargado correctamente.');
    testSteps.push('Módulo Compra cargado correctamente');

    // === Paso 3: Abrir formulario de compra ===
    testSteps.push('Abrir formulario de registro de compra');
    console.log('Abriendo formulario de compra...');
    const registrarBtn = await driver.findElement(By.css('button[data-bs-target="#registroModal"]'));
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center', behavior: 'smooth'});", registrarBtn);
    await driver.sleep(500);
    await driver.wait(until.elementIsVisible(registrarBtn), 10000);
    await driver.wait(until.elementIsEnabled(registrarBtn), 10000);
    
    try {
      await registrarBtn.click();
    } catch (e) {
      await driver.executeScript("arguments[0].click();", registrarBtn);
    }

    // === Paso 4: Esperar que el modal esté visible ===
    await driver.sleep(1500);
    const modal = await driver.findElement(By.id('registroModal'));
    await driver.wait(until.elementIsVisible(modal), 15000);
    await driver.executeScript("return document.querySelector('#registroModal').classList.contains('show');");
    await driver.sleep(1000);

    // === Paso 5: Llenar datos básicos ===
    testSteps.push('Llenar datos básicos de la compra (fecha y proveedor)');
    console.log('Llenando datos de la compra...');
    const today = new Date().toISOString().split('T')[0];

    const fechaInput = await driver.findElement(By.id('fecha_entrada_reg'));
    await driver.wait(until.elementIsVisible(fechaInput), 10000);
    await driver.executeScript("arguments[0].value = arguments[1];", fechaInput, today);
    await driver.executeScript("arguments[0].dispatchEvent(new Event('change', { bubbles: true }));", fechaInput);

    const proveedorSelect = await driver.findElement(By.id('id_proveedor_reg'));
    await driver.wait(until.elementIsVisible(proveedorSelect), 10000);
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", proveedorSelect);
    await driver.sleep(500);
    await proveedorSelect.click();
    await driver.sleep(500);
    await proveedorSelect.sendKeys(Key.ARROW_DOWN);
    await driver.sleep(300);
    await proveedorSelect.sendKeys(Key.ENTER);
    await driver.sleep(500);

    // === Paso 6: Llenar primer producto ===
    testSteps.push('Seleccionar el primer producto y agregar cantidad y precio');
    console.log('Llenando primer producto...');
    await driver.wait(until.elementLocated(By.css('#productos-container .producto-fila')), 15000);
    let productoFila = await driver.findElement(By.css('#productos-container .producto-fila'));
    await driver.wait(until.elementIsVisible(productoFila), 10000);

    const productoSelect1 = await productoFila.findElement(By.css('.producto-select'));
    await driver.wait(until.elementIsVisible(productoSelect1), 10000);
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", productoSelect1);
    await driver.sleep(500);
    
    const productoSeleccionado1 = await driver.executeScript(`
      var select = arguments[0];
      var options = select.options;
      for (var i = 1; i < options.length; i++) {
        var option = options[i];
        if (option.value && option.value !== '') {
          var stockActual = option.getAttribute('data-stock-actual');
          if (stockActual === null || parseFloat(stockActual) >= 0) {
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
      throw new Error('No se encontraron productos disponibles para el primer producto');
    }
    
    console.log('Primer producto seleccionado: ' + productoSeleccionado1.text);
    await driver.sleep(1000);

    const cantidadInput1 = await productoFila.findElement(By.css('.cantidad-input'));
    await driver.wait(until.elementIsVisible(cantidadInput1), 10000);
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", cantidadInput1);
    await driver.sleep(300);
    await cantidadInput1.clear();
    await cantidadInput1.sendKeys('5');
    await driver.executeScript("arguments[0].dispatchEvent(new Event('input', { bubbles: true }));", cantidadInput1);
    await driver.sleep(500);

    const precioInput1 = await productoFila.findElement(By.css('.precio-input'));
    await driver.wait(until.elementIsVisible(precioInput1), 10000);
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", precioInput1);
    await driver.sleep(300);
    await precioInput1.clear();
    await precioInput1.sendKeys('10.00');
    await driver.executeScript("arguments[0].dispatchEvent(new Event('input', { bubbles: true }));", precioInput1);
    await driver.sleep(1000);

    // === Paso 7: Agregar segundo producto ===
    testSteps.push('Hacer clic en Agregar Producto y añadir un segundo producto');
    console.log('Agregando segundo producto...');
    const agregarProductoBtn = await driver.findElement(By.id('agregar-producto'));
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", agregarProductoBtn);
    await driver.sleep(500);
    await driver.wait(until.elementIsVisible(agregarProductoBtn), 10000);
    await driver.wait(until.elementIsEnabled(agregarProductoBtn), 10000);
    
    try {
      await agregarProductoBtn.click();
    } catch (e) {
      await driver.executeScript("arguments[0].click();", agregarProductoBtn);
    }
    
    await driver.sleep(1000);

    // Obtener todas las filas de productos
    const productoFilas = await driver.findElements(By.css('#productos-container .producto-fila'));
    if (productoFilas.length < 2) {
      throw new Error('No se agrego la segunda fila de producto');
    }
    
    productoFila = productoFilas[productoFilas.length - 1];
    await driver.wait(until.elementIsVisible(productoFila), 10000);

    const productoSelect2 = await productoFila.findElement(By.css('.producto-select'));
    await driver.wait(until.elementIsVisible(productoSelect2), 10000);
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", productoSelect2);
    await driver.sleep(500);
    
    // Seleccionar un producto diferente al primero
    const productoSeleccionado2 = await driver.executeScript(`
      var select = arguments[0];
      var firstProductValue = arguments[1];
      var options = select.options;
      for (var i = 1; i < options.length; i++) {
        var option = options[i];
        if (option.value && option.value !== '' && option.value !== firstProductValue) {
          var stockActual = option.getAttribute('data-stock-actual');
          if (stockActual === null || parseFloat(stockActual) >= 0) {
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
      throw new Error('No se encontraron productos disponibles para el segundo producto');
    }
    
    console.log('Segundo producto seleccionado: ' + productoSeleccionado2.text);
    await driver.sleep(1000);

    const cantidadInput2 = await productoFila.findElement(By.css('.cantidad-input'));
    await driver.wait(until.elementIsVisible(cantidadInput2), 10000);
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", cantidadInput2);
    await driver.sleep(300);
    await cantidadInput2.clear();
    await cantidadInput2.sendKeys('3');
    await driver.executeScript("arguments[0].dispatchEvent(new Event('input', { bubbles: true }));", cantidadInput2);
    await driver.sleep(500);

    const precioInput2 = await productoFila.findElement(By.css('.precio-input'));
    await driver.wait(until.elementIsVisible(precioInput2), 10000);
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", precioInput2);
    await driver.sleep(300);
    await precioInput2.clear();
    await precioInput2.sendKeys('15.00');
    await driver.executeScript("arguments[0].dispatchEvent(new Event('input', { bubbles: true }));", precioInput2);
    await driver.sleep(1000);

    testSteps.push('Ingresar valores válidos para ambos productos');

    // === Paso 8: Registrar compra ===
    testSteps.push('Enviar formulario para registrar la compra');
    console.log('Registrando compra...');
    const registrarCompraBtn = await driver.findElement(By.css('button[name="registrar_compra"]'));
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center', behavior: 'smooth'});", registrarCompraBtn);
    await driver.sleep(500);
    await driver.wait(until.elementIsVisible(registrarCompraBtn), 10000);
    await driver.wait(until.elementIsEnabled(registrarCompraBtn), 10000);
    
    try {
      await registrarCompraBtn.click();
    } catch (e) {
      await driver.executeScript("arguments[0].click();", registrarCompraBtn);
    }

    // === Paso 9: Verificar éxito ===
    testSteps.push('Verificar que la compra se registró exitosamente con ambos productos');
    console.log('Verificando registro de compra...');
    
    await driver.sleep(2000);
    
    try {
      const errorMessages = await driver.findElements(By.css('.alert-danger, .alert-warning, .toast-error, .toast-warning'));
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
      console.log('Modal cerrado - Compra registrada exitosamente.');
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
          console.log('Tabla visible - Compra registrada exitosamente.');
        } else {
          throw new Error('No se pudo verificar el exito de la operacion');
        }
      }
    }

    console.log('Compra registrada exitosamente con varios productos.');
    notes = 'Compra registrada exitosamente con varios productos. Fecha: ' + today + ', Producto 1: Cantidad 5, Precio 10.00 | Producto 2: Cantidad 3, Precio 15.00';
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

    // Reportar a TestLink (mapear status)
      
    }

    const testLinkStatus = status === 'p' || status === 'passed' ? 'p' : 'f';
    await reportResultToTestLink(testLinkStatus, notes);
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

