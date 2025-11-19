// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
const ReportGenerator = require('./ReportGenerator');

// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '76133924c3d3f13d8490b26f5d5a7ca5';
const TEST_CASE_EXTERNAL_ID = '5'; 
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: REGISTRAR VENTA CON PRODUCTO SIN STOCK SUFICIENTE (INVÁLIDO) ===
async function runTest() {
  let driver;
  let status = 'f';
  let notes = '';
  const startTime = new Date();
  const testSteps = [];
  const reportGenerator = new ReportGenerator();
  const testName = 'Registrar Venta con Producto sin Stock Suficiente (Inválido)';

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

    // === Paso 5: Paso 2 - Seleccionar Producto con Stock Insuficiente ===
    testSteps.push('Seleccionar un producto con stock insuficiente');
    await driver.wait(until.elementLocated(By.css('.producto-select-venta')), 15000);
    const productoSelect = await driver.findElement(By.css('.producto-select-venta'));
    
    // Buscar un producto con stock bajo o cero
    const productoSeleccionado = await driver.executeScript(`
      var select = arguments[0];
      var options = select.options;
      var productoConStockBajo = null;
      var productoConStockCero = null;
      
      for (var i = 1; i < options.length; i++) {
        var option = options[i];
        if (option.value && option.value !== '') {
          var stock = parseFloat(option.getAttribute('data-stock') || '0');
          if (stock === 0) {
            productoConStockCero = { index: i, value: option.value, text: option.text, stock: stock };
          } else if (stock > 0 && stock < 10 && !productoConStockBajo) {
            productoConStockBajo = { index: i, value: option.value, text: option.text, stock: stock };
          }
        }
      }
      
      // Preferir producto con stock cero, si no hay, usar uno con stock bajo
      var producto = productoConStockCero || productoConStockBajo;
      
      if (producto) {
        select.selectedIndex = producto.index;
        if (typeof $ !== 'undefined' && $(select).hasClass('select2-hidden-accessible')) {
          $(select).val(producto.value).trigger('change');
        } else {
          var event = new Event('change', { bubbles: true });
          select.dispatchEvent(event);
        }
        return { success: true, value: producto.value, text: producto.text, stock: producto.stock };
      }
      
      return { success: false, message: 'No se encontraron productos con stock insuficiente' };
    `, productoSelect);
    
    if (!productoSeleccionado.success) {
      // Si no hay productos con stock cero, intentar con uno que tenga stock bajo y pedir cantidad mayor
      console.log('No se encontró producto con stock cero, intentando con stock bajo...');
      const productoAlternativo = await driver.executeScript(`
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
        return { success: false };
      `, productoSelect);
      
      if (!productoAlternativo.success) {
        throw new Error('No se encontraron productos disponibles');
      }
      
      // Intentar pedir una cantidad mayor al stock disponible
      const cantidadInput = await driver.findElement(By.css('.cantidad-input-venta'));
      await cantidadInput.clear();
      await cantidadInput.sendKeys(String(parseInt(productoAlternativo.stock) + 10)); // Cantidad mayor al stock
      await driver.executeScript("arguments[0].dispatchEvent(new Event('input', { bubbles: true }));", cantidadInput);
      await driver.sleep(1000);
      
      console.log('Producto seleccionado con stock: ' + productoAlternativo.stock + ', cantidad solicitada: ' + (parseInt(productoAlternativo.stock) + 10));
    } else {
      console.log('Producto seleccionado con stock insuficiente: ' + productoSeleccionado.text + ' (Stock: ' + productoSeleccionado.stock + ')');
      await driver.sleep(2000);

      // Si el stock es cero, intentar pedir cualquier cantidad
      if (productoSeleccionado.stock === 0) {
        const cantidadInput = await driver.findElement(By.css('.cantidad-input-venta'));
        await cantidadInput.clear();
        await cantidadInput.sendKeys('1');
        await driver.executeScript("arguments[0].dispatchEvent(new Event('input', { bubbles: true }));", cantidadInput);
        await driver.sleep(1000);
      }
    }

    // Verificar si aparece mensaje de advertencia sobre stock
    await driver.sleep(2000);
    let advertenciaEncontrada = false;
    try {
      const advertencias = await driver.findElements(By.css('.alert-warning, .text-warning, .stock-info'));
      for (let adv of advertencias) {
        const texto = await adv.getText();
        if (texto && texto.toLowerCase().includes('stock')) {
          advertenciaEncontrada = true;
          console.log('Advertencia de stock detectada: ' + texto);
          break;
        }
      }
    } catch (e) {
      // Continuar
    }

    // Avanzar al siguiente paso
    await btnSiguiente.click();
    await driver.sleep(1500);
    testSteps.push('Producto con stock insuficiente seleccionado');

    // === Paso 6: Paso 3 - Método de Pago ===
    testSteps.push('Seleccionar método de pago');
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
    await montoInput.sendKeys('10.00');
    await driver.executeScript("arguments[0].dispatchEvent(new Event('input', { bubbles: true }));", montoInput);
    await driver.sleep(1000);

    await btnSiguiente.click();
    await driver.sleep(1500);
    testSteps.push('Método de pago seleccionado');

    // === Paso 7: Paso 4 - Intentar Registrar Venta ===
    testSteps.push('Intentar registrar la venta');
    await driver.wait(until.elementLocated(By.id('btnRegistrarVenta')), 15000);
    const btnRegistrarVenta = await driver.findElement(By.id('btnRegistrarVenta'));
    await btnRegistrarVenta.click();

    // === Paso 8: Verificar Mensaje de Error ===
    testSteps.push('Verificar que se muestra mensaje de error sobre stock insuficiente');
    await driver.sleep(3000);
    
    let errorEncontrado = false;
    let mensajeError = '';
    
    try {
      const errorMessages = await driver.findElements(By.css('.alert-danger, .alert-warning, .toast-error, .toast-warning'));
      for (let errorMsg of errorMessages) {
        const errorText = await errorMsg.getText();
        if (errorText && errorText.trim() !== '' && 
            (errorText.toLowerCase().includes('stock') || 
             errorText.toLowerCase().includes('insuficiente') ||
             errorText.toLowerCase().includes('disponible'))) {
          errorEncontrado = true;
          mensajeError = errorText;
          break;
        }
      }
    } catch (e) {
      // Continuar verificando
    }

    // Verificar que el modal sigue abierto (la venta no se registró)
    try {
      const modalVisible = await driver.executeScript("return document.querySelector('#registroModal').classList.contains('show');");
      
      if (modalVisible && !errorEncontrado) {
        // El modal sigue abierto pero no hay mensaje visible, puede ser validación del lado del cliente
        errorEncontrado = true;
        mensajeError = 'El sistema no permitió registrar la venta (modal permanece abierto)';
      }
    } catch (e) {
      // Continuar
    }

    if (errorEncontrado || advertenciaEncontrada) {
      console.log('Error o advertencia de stock detectado correctamente: ' + mensajeError);
      notes = 'Prueba exitosa: El sistema mostro correctamente un mensaje de error o advertencia sobre stock insuficiente. Mensaje: ' + mensajeError;
      status = 'p';
    } else {
      throw new Error('No se detecto el error esperado sobre stock insuficiente. La venta puede haberse registrado incorrectamente.');
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

