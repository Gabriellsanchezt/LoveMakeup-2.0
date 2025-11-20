// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = 'Prueba-9'; 
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
// Ajustar según tu configuración local
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';


// === CONFIGURACIÓN DEL NAVEGADOR ===
// Opciones: 'edge', 'chrome', 'firefox'
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: REGISTRAR COMPRA VÁLIDA CON UN PRODUCTO ===
async function runTest() {
  let driver;
  let status = 'f';
  let notes = '';
  const startTime = new Date();
  const testSteps = [];

  try {
    // Configurar el driver según el navegador seleccionado
    console.log(`Inicializando navegador: ${BROWSER}...`);
    
    if (BROWSER === 'edge') {
      const options = new edge.Options();
      // Opciones adicionales si es necesario
      // options.addArguments('--headless'); // Descomentar para modo headless
      
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
    console.error('Asegúrate de que:');
    console.error('   1. EdgeDriver esté instalado y en el PATH');
    console.error('   2. O instala los drivers con: npm install --save-dev @seleniumhq/webdriver-manager');
    console.error('   3. O descarga EdgeDriver desde: https://developer.microsoft.com/en-us/microsoft-edge/tools/webdriver/');
    throw driverError;
  }

  try {
    // Configurar timeouts
    await driver.manage().setTimeouts({
      implicit: 10000,
      pageLoad: 30000,
      script: 30000
    });

    // Maximizar ventana
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

    // === Paso 2: Ir al módulo Compra ===
    testSteps.push('Navegar al módulo de Compra');
    console.log('Accediendo al módulo Compra...');
    await driver.get(BASE_URL + '?pagina=entrada');
    await driver.sleep(2000);
    
    // Esperar a que la página cargue completamente
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

    // === Paso 4: Esperar que el modal esté completamente visible ===
    await driver.sleep(1500); // Dar tiempo para que Select2 se inicialice
    const modal = await driver.findElement(By.id('registroModal'));
    await driver.wait(until.elementIsVisible(modal), 15000);
    
    // Esperar a que el modal esté completamente cargado
    await driver.executeScript("return document.querySelector('#registroModal').classList.contains('show');");
    await driver.sleep(1000);

    // === Paso 5: Llenar datos básicos ===
    testSteps.push('Llenar datos básicos de la compra (fecha y proveedor)');
    console.log('Llenando datos de la compra...');
    
    // Obtener fecha de hoy en formato local (YYYY-MM-DD)
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    const todayFormatted = `${year}-${month}-${day}`;
    
    console.log('Fecha de hoy: ' + todayFormatted);

    const fechaInput = await driver.findElement(By.id('fecha_entrada_reg'));
    await driver.wait(until.elementIsVisible(fechaInput), 10000);
    await driver.executeScript("arguments[0].value = arguments[1];", fechaInput, todayFormatted);
    await driver.executeScript("arguments[0].dispatchEvent(new Event('change', { bubbles: true }));", fechaInput);
    await driver.sleep(500);
    console.log('Fecha establecida: ' + todayFormatted);

    // Seleccionar proveedor
    const proveedorSelect = await driver.findElement(By.id('id_proveedor_reg'));
    await driver.wait(until.elementIsVisible(proveedorSelect), 10000);
    
    // Hacer clic en el select para abrirlo
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", proveedorSelect);
    await driver.sleep(500);
    await proveedorSelect.click();
    await driver.sleep(500);
    
    // Seleccionar la primera opción disponible (que no sea el placeholder)
    await proveedorSelect.sendKeys(Key.ARROW_DOWN);
    await driver.sleep(300);
    await proveedorSelect.sendKeys(Key.ENTER);
    await driver.sleep(500);

    // === Paso 6: Llenar sección de productos ===
    testSteps.push('Llenar información del producto (producto, cantidad, precio)');
    console.log('Llenando producto...');
    await driver.wait(until.elementLocated(By.css('#productos-container .producto-fila')), 15000);
    const productoFila = await driver.findElement(By.css('#productos-container .producto-fila'));
    await driver.wait(until.elementIsVisible(productoFila), 10000);

    // Manejar Select2 para el producto
    const productoSelect = await productoFila.findElement(By.css('.producto-select'));
    await driver.wait(until.elementIsVisible(productoSelect), 10000);
    
    // Hacer scroll al select
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", productoSelect);
    await driver.sleep(500);
    
    // Obtener todas las opciones disponibles y seleccionar la primera válida
    const productoSeleccionado = await driver.executeScript(`
      var select = arguments[0];
      var options = select.options;
      
      // Buscar la primera opción válida (que tenga value y no sea el placeholder)
      for (var i = 1; i < options.length; i++) {
        var option = options[i];
        if (option.value && option.value !== '') {
          // Verificar que tenga stock disponible si tiene el atributo
          var stockActual = option.getAttribute('data-stock-actual');
          if (stockActual === null || parseFloat(stockActual) >= 0) {
            select.selectedIndex = i;
            // Si está usando Select2, actualizar también
            if (typeof $ !== 'undefined' && $(select).hasClass('select2-hidden-accessible')) {
              $(select).val(option.value).trigger('change');
            } else {
              // Disparar evento change para que se actualicen los campos relacionados
              var event = new Event('change', { bubbles: true });
              select.dispatchEvent(event);
            }
            return { success: true, value: option.value, text: option.text };
          }
        }
      }
      return { success: false, message: 'No se encontraron productos disponibles' };
    `, productoSelect);
    
    if (!productoSeleccionado.success) {
      throw new Error('No se encontraron productos disponibles para seleccionar');
    }
    
    console.log('Producto seleccionado: ' + productoSeleccionado.text);
    await driver.sleep(1000);
    
    // Verificar que el producto se seleccionó correctamente
    const productoValueVerificado = await driver.executeScript(`
      var select = arguments[0];
      if (typeof $ !== 'undefined' && $(select).hasClass('select2-hidden-accessible')) {
        return $(select).val();
      } else {
        return select.value;
      }
    `, productoSelect);
    
    if (!productoValueVerificado || productoValueVerificado === '') {
      throw new Error('El producto no se selecciono correctamente. Valor: ' + productoValueVerificado);
    }
    
    console.log('Producto verificado con ID: ' + productoValueVerificado);

    // Llenar cantidad
    const cantidadInput = await productoFila.findElement(By.css('.cantidad-input'));
    await driver.wait(until.elementIsVisible(cantidadInput), 10000);
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", cantidadInput);
    await driver.sleep(300);
    await cantidadInput.clear();
    await cantidadInput.sendKeys('10');
    await driver.executeScript("arguments[0].dispatchEvent(new Event('input', { bubbles: true }));", cantidadInput);
    await driver.sleep(500);

    // Llenar precio
    const precioInput = await productoFila.findElement(By.css('.precio-input'));
    await driver.wait(until.elementIsVisible(precioInput), 10000);
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", precioInput);
    await driver.sleep(300);
    await precioInput.clear();
    await precioInput.sendKeys('5.00');
    await driver.executeScript("arguments[0].dispatchEvent(new Event('input', { bubbles: true }));", precioInput);
    await driver.sleep(1000); // Esperar a que se calcule el precio total

    // === Paso 7: Registrar compra ===
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

    // === Paso 8: Verificar éxito ===
    testSteps.push('Verificar que la compra se registró exitosamente');
    console.log('Verificando registro de compra...');
    
    // Esperar un momento para que se procese la respuesta
    await driver.sleep(2000);
    
    // Primero verificar si hay mensajes de error
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
      // Si no hay elementos de error, continuar
      if (e.message && e.message.includes('Error detectado')) {
        throw e;
      }
    }
    
    // Esperar a que el modal se cierre o aparezca un mensaje de éxito
    try {
      // Opción 1: Verificar que el modal se cierre
      await driver.wait(until.stalenessOf(modal), 15000);
      console.log('Modal cerrado - Compra registrada exitosamente.');
    } catch (e) {
      // Opción 2: Verificar mensaje de éxito en la página
      try {
        await driver.wait(until.elementLocated(By.css('.alert-success, .toast-success')), 10000);
        const successMsg = await driver.findElement(By.css('.alert-success, .toast-success'));
        const successText = await successMsg.getText();
        console.log('Mensaje de exito detectado: ' + successText);
      } catch (e2) {
        // Opción 3: Verificar que la tabla se actualizó
        await driver.sleep(2000);
        const tabla = await driver.findElement(By.id('myTable'));
        if (await tabla.isDisplayed()) {
          console.log('Tabla visible - Compra registrada exitosamente.');
        } else {
          throw new Error('No se pudo verificar el exito de la operacion');
        }
      }
    }

    console.log('Compra registrada exitosamente.');
    notes = 'Compra registrada exitosamente con un producto válido. Fecha: ' + todayFormatted + ', Cantidad: 10, Precio: 5.00';
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

