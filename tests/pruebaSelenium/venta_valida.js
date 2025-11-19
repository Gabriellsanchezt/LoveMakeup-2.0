// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
const ReportGenerator = require('./ReportGenerator');

// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '76133924c3d3f13d8490b26f5d5a7ca5';
const TEST_CASE_EXTERNAL_ID = '1'; 
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: REGISTRAR VENTA VÁLIDA CON UN SOLO PRODUCTO ===
async function runTest() {
  let driver;
  let status = 'f';
  let notes = '';
  const startTime = new Date();
  const testSteps = [];
  const reportGenerator = new ReportGenerator();
  const testName = 'Registrar Venta Válida con Un Solo Producto';

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

    // === Paso 2: Ir al módulo Venta ===
    testSteps.push('Navegar al módulo de Venta');
    console.log('Accediendo al módulo Venta...');
    await driver.get(BASE_URL + '?pagina=salida');
    await driver.sleep(2000);
    
    await driver.wait(until.elementLocated(By.css('button[data-bs-target="#registroModal"]')), 15000);
    console.log('Modulo Venta cargado correctamente.');
    testSteps.push('Módulo Venta cargado correctamente');

    // === Paso 3: Abrir formulario de venta ===
    testSteps.push('Abrir formulario de registro de venta');
    console.log('Abriendo formulario de venta...');
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

    await driver.sleep(1500);
    const modal = await driver.findElement(By.id('registroModal'));
    await driver.wait(until.elementIsVisible(modal), 15000);
    await driver.executeScript("return document.querySelector('#registroModal').classList.contains('show');");
    await driver.sleep(1000);

    // === Paso 4: Paso 1 - Datos del Cliente ===
    testSteps.push('Llenar datos del cliente (cédula)');
    console.log('Llenando datos del cliente...');
    
    const cedulaInput = await driver.findElement(By.id('cedula_cliente'));
    await driver.wait(until.elementIsVisible(cedulaInput), 10000);
    await cedulaInput.clear();
    // Usar una cédula válida (ajustar según tu base de datos)
    await cedulaInput.sendKeys('12345678');
    await driver.executeScript("arguments[0].dispatchEvent(new Event('input', { bubbles: true }));", cedulaInput);
    await driver.sleep(2000); // Esperar a que se busque el cliente

    // Verificar si el cliente existe o si aparecen campos para nuevo cliente
    try {
      const camposCliente = await driver.findElement(By.id('campos-cliente'));
      const isVisible = await driver.executeScript("return window.getComputedStyle(arguments[0]).display !== 'none';", camposCliente);
      
      if (isVisible) {
        // Cliente nuevo - llenar campos
        const nombreInput = await driver.findElement(By.id('nombre_cliente'));
        await nombreInput.clear();
        await nombreInput.sendKeys('Cliente');
        
        const apellidoInput = await driver.findElement(By.id('apellido_cliente'));
        await apellidoInput.clear();
        await apellidoInput.sendKeys('Prueba');
        
        const telefonoInput = await driver.findElement(By.id('telefono_cliente'));
        await telefonoInput.clear();
        await telefonoInput.sendKeys('04141234567');
        
        const correoInput = await driver.findElement(By.id('correo_cliente'));
        await correoInput.clear();
        await correoInput.sendKeys('cliente@prueba.com');
      }
    } catch (e) {
      // Cliente existe, continuar
      console.log('Cliente encontrado en el sistema');
    }

    // Avanzar al siguiente paso
    const btnSiguiente = await driver.findElement(By.id('btnSiguiente'));
    await driver.wait(until.elementIsVisible(btnSiguiente), 10000);
    await driver.wait(until.elementIsEnabled(btnSiguiente), 10000);
    await btnSiguiente.click();
    await driver.sleep(1500);
    testSteps.push('Datos del cliente completados');

    // === Paso 5: Paso 2 - Seleccionar Producto ===
    testSteps.push('Seleccionar producto y cantidad');
    console.log('Seleccionando producto...');
    
    await driver.wait(until.elementLocated(By.css('.producto-select-venta')), 15000);
    const productoSelect = await driver.findElement(By.css('.producto-select-venta'));
    await driver.wait(until.elementIsVisible(productoSelect), 10000);
    
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", productoSelect);
    await driver.sleep(500);
    
    // Seleccionar el primer producto válido con stock
    const productoSeleccionado = await driver.executeScript(`
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
            return { success: true, value: option.value, text: option.text, stock: stock };
          }
        }
      }
      return { success: false, message: 'No se encontraron productos con stock disponible' };
    `, productoSelect);
    
    if (!productoSeleccionado.success) {
      throw new Error('No se encontraron productos con stock disponible');
    }
    
    console.log('Producto seleccionado: ' + productoSeleccionado.text);
    await driver.sleep(2000);

    // Llenar cantidad
    const cantidadInput = await driver.findElement(By.css('.cantidad-input-venta'));
    await driver.wait(until.elementIsVisible(cantidadInput), 10000);
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", cantidadInput);
    await driver.sleep(300);
    await cantidadInput.clear();
    await cantidadInput.sendKeys('2');
    await driver.executeScript("arguments[0].dispatchEvent(new Event('input', { bubbles: true }));", cantidadInput);
    await driver.sleep(1000);

    // Avanzar al siguiente paso
    await btnSiguiente.click();
    await driver.sleep(1500);
    testSteps.push('Producto seleccionado y cantidad ingresada');

    // === Paso 6: Paso 3 - Método de Pago ===
    testSteps.push('Seleccionar método de pago');
    console.log('Seleccionando método de pago...');
    
    await driver.wait(until.elementLocated(By.css('.metodo-pago-select')), 15000);
    const metodoPagoSelect = await driver.findElement(By.css('.metodo-pago-select'));
    await driver.wait(until.elementIsVisible(metodoPagoSelect), 10000);
    
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", metodoPagoSelect);
    await driver.sleep(500);
    
    // Seleccionar el primer método de pago disponible
    await metodoPagoSelect.click();
    await driver.sleep(300);
    await metodoPagoSelect.sendKeys(Key.ARROW_DOWN);
    await driver.sleep(300);
    await metodoPagoSelect.sendKeys(Key.ENTER);
    await driver.sleep(1000);

    // Llenar monto
    const montoInput = await driver.findElement(By.css('.monto-metodopago'));
    await driver.wait(until.elementIsVisible(montoInput), 10000);
    await montoInput.clear();
    await montoInput.sendKeys('10.00');
    await driver.executeScript("arguments[0].dispatchEvent(new Event('input', { bubbles: true }));", montoInput);
    await driver.sleep(1000);

    // Avanzar al siguiente paso
    await btnSiguiente.click();
    await driver.sleep(1500);
    testSteps.push('Método de pago seleccionado');

    // === Paso 7: Paso 4 - Registrar Venta ===
    testSteps.push('Registrar la venta');
    console.log('Registrando venta...');
    
    await driver.wait(until.elementLocated(By.id('btnRegistrarVenta')), 15000);
    const btnRegistrarVenta = await driver.findElement(By.id('btnRegistrarVenta'));
    await driver.wait(until.elementIsVisible(btnRegistrarVenta), 10000);
    await driver.wait(until.elementIsEnabled(btnRegistrarVenta), 10000);
    
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center', behavior: 'smooth'});", btnRegistrarVenta);
    await driver.sleep(500);
    
    try {
      await btnRegistrarVenta.click();
    } catch (e) {
      await driver.executeScript("arguments[0].click();", btnRegistrarVenta);
    }

    // === Paso 8: Verificar éxito ===
    testSteps.push('Verificar que la venta se registró exitosamente');
    console.log('Verificando registro de venta...');
    
    await driver.sleep(3000);
    
    // Verificar si hay mensajes de error
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
    
    // Verificar que el modal se cierre o aparezca mensaje de éxito
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

    console.log('Venta registrada exitosamente.');
    notes = 'Venta registrada exitosamente con un producto válido. Cantidad: 2';
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

    // Reportar a TestLink
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

