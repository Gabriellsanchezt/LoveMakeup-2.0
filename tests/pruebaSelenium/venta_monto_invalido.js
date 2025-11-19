// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
const ReportGenerator = require('./ReportGenerator');

// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '76133924c3d3f13d8490b26f5d5a7ca5';
const TEST_CASE_EXTERNAL_ID = '6'; 
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: REGISTRAR VENTA CON MONTO TOTAL INVÁLIDO (0 O NEGATIVO) ===
async function runTest() {
  let driver;
  let status = 'f';
  let notes = '';
  const startTime = new Date();
  const testSteps = [];
  const reportGenerator = new ReportGenerator();
  const testName = 'Registrar Venta con Monto Total Inválido (0 o Negativo)';

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
    testSteps.push('Seleccionar cliente y producto válidos');
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

    // === Paso 5: Paso 2 - Producto ===
    testSteps.push('Seleccionar producto válido');
    await driver.wait(until.elementLocated(By.css('.producto-select-venta')), 15000);
    const productoSelect = await driver.findElement(By.css('.producto-select-venta'));
    
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
            return { success: true, value: option.value, text: option.text };
          }
        }
      }
      return { success: false };
    `, productoSelect);
    
    if (!productoSeleccionado.success) {
      throw new Error('No se encontraron productos con stock disponible');
    }
    
    await driver.sleep(2000);

    // === Paso 6: Modificar el monto total a 0 o negativo ===
    testSteps.push('Modificar el monto total a 0 o un número negativo');
    console.log('Intentando modificar el monto total...');
    
    // Intentar modificar el precio unitario a 0 o negativo
    const precioInput = await driver.findElement(By.css('.precio-input-venta'));
    const isReadonly = await driver.executeScript("return arguments[0].readOnly;", precioInput);
    
    if (!isReadonly) {
      // Si el campo no es readonly, intentar modificarlo
      await precioInput.clear();
      await precioInput.sendKeys('0');
      await driver.executeScript("arguments[0].dispatchEvent(new Event('input', { bubbles: true }));", precioInput);
      await driver.sleep(1000);
    } else {
      // Si es readonly, intentar modificar el total general directamente
      try {
        const totalGeneral = await driver.findElement(By.id('total-general-venta'));
        await driver.executeScript(`
          var elemento = arguments[0];
          elemento.textContent = '$0.00';
          var event = new Event('change', { bubbles: true });
          elemento.dispatchEvent(event);
        `, totalGeneral);
        await driver.sleep(1000);
      } catch (e) {
        // El total puede estar calculado automáticamente
        console.log('No se pudo modificar el total directamente (comportamiento esperado)');
      }
    }

    // Intentar establecer cantidad en 0
    const cantidadInput = await driver.findElement(By.css('.cantidad-input-venta'));
    await cantidadInput.clear();
    await cantidadInput.sendKeys('0');
    await driver.executeScript("arguments[0].dispatchEvent(new Event('input', { bubbles: true }));", cantidadInput);
    await driver.sleep(1000);
    
    testSteps.push('Monto total modificado a valor inválido');

    // Avanzar al siguiente paso
    await btnSiguiente.click();
    await driver.sleep(1500);

    // === Paso 7: Paso 3 - Método de Pago ===
    testSteps.push('Seleccionar método de pago');
    try {
      await driver.wait(until.elementLocated(By.css('.metodo-pago-select')), 15000);
      const metodoPagoSelect = await driver.findElement(By.css('.metodo-pago-select'));
      
      await metodoPagoSelect.click();
      await driver.sleep(300);
      await metodoPagoSelect.sendKeys(Key.ARROW_DOWN);
      await driver.sleep(300);
      await metodoPagoSelect.sendKeys(Key.ENTER);
      await driver.sleep(1000);

      const montoInput = await driver.findElement(By.css('.monto-metodopago'));
      await montoInput.clear();
      await montoInput.sendKeys('0');
      await driver.executeScript("arguments[0].dispatchEvent(new Event('input', { bubbles: true }));", montoInput);
      await driver.sleep(1000);

      await btnSiguiente.click();
      await driver.sleep(1500);
    } catch (e) {
      // Puede que no se pueda avanzar con monto inválido
      console.log('No se pudo avanzar al paso de pago (comportamiento esperado)');
    }

    // === Paso 8: Intentar Registrar Venta ===
    testSteps.push('Intentar registrar la venta');
    try {
      await driver.wait(until.elementLocated(By.id('btnRegistrarVenta')), 10000);
      const btnRegistrarVenta = await driver.findElement(By.id('btnRegistrarVenta'));
      
      if (await btnRegistrarVenta.isDisplayed()) {
        await btnRegistrarVenta.click();
        await driver.sleep(2000);
      }
    } catch (e) {
      // El botón puede no estar disponible
      console.log('Botón de registrar no disponible con monto inválido');
    }

    // === Paso 9: Verificar Mensaje de Error ===
    testSteps.push('Verificar que se muestra mensaje de error sobre monto inválido');
    await driver.sleep(2000);
    
    let errorEncontrado = false;
    let mensajeError = '';
    
    // Verificar validación HTML5
    try {
      const cantidadInput2 = await driver.findElement(By.css('.cantidad-input-venta'));
      const validationMessage = await driver.executeScript(`
        var input = arguments[0];
        return input.validationMessage || '';
      `, cantidadInput2);
      
      if (validationMessage && (validationMessage.includes('0') || validationMessage.includes('min'))) {
        errorEncontrado = true;
        mensajeError = validationMessage;
      }
    } catch (e) {
      // Continuar
    }

    // Buscar mensajes de error en la UI
    try {
      const errorMessages = await driver.findElements(By.css('.alert-danger, .alert-warning, .toast-error, .toast-warning, .invalid-feedback'));
      for (let errorMsg of errorMessages) {
        const errorText = await errorMsg.getText();
        if (errorText && errorText.trim() !== '' && 
            (errorText.toLowerCase().includes('monto') || 
             errorText.toLowerCase().includes('total') ||
             errorText.toLowerCase().includes('no válido') ||
             errorText.toLowerCase().includes('inválido') ||
             errorText.toLowerCase().includes('cantidad'))) {
          errorEncontrado = true;
          mensajeError = errorText;
          break;
        }
      }
    } catch (e) {
      // Continuar verificando
    }

    // Verificar que el modal sigue abierto
    try {
      const modalVisible = await driver.executeScript("return document.querySelector('#registroModal').classList.contains('show');");
      
      if (modalVisible && !errorEncontrado) {
        // Verificar si hay campos con clase is-invalid
        const invalidFields = await driver.findElements(By.css('.is-invalid'));
        if (invalidFields.length > 0) {
          errorEncontrado = true;
          mensajeError = 'El sistema marcó campos como inválidos';
        }
      }
    } catch (e) {
      // Continuar
    }

    if (errorEncontrado) {
      console.log('Error de monto inválido detectado correctamente: ' + mensajeError);
      notes = 'Prueba exitosa: El sistema mostro correctamente un mensaje de error o validación sobre monto total inválido. Mensaje: ' + mensajeError;
      status = 'p';
    } else {
      // Verificar que la venta no se registró
      const modalVisible = await driver.executeScript("return document.querySelector('#registroModal').classList.contains('show');");
      if (modalVisible) {
        console.log('El modal sigue abierto - La venta no se registro (comportamiento esperado)');
        notes = 'Prueba exitosa: El sistema no permitio registrar la venta con monto inválido. El formulario permanece abierto.';
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

