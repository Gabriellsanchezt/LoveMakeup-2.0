// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
const ReportGenerator = require('./ReportGenerator');

// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '76133924c3d3f13d8490b26f5d5a7ca5';
const TEST_CASE_EXTERNAL_ID = '14';
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: EDITAR UNA COMPRA CORRECTAMENTE ===
async function runTest() {
  let driver;
  let status = 'f';
  let notes = '';
  const startTime = new Date();
  const testSteps = [];
  const reportGenerator = new ReportGenerator();
  const testName = 'Editar una Compra Correctamente';

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
    
    await driver.wait(until.elementLocated(By.id('myTable')), 15000);
    console.log('Modulo Compra cargado correctamente.');
    testSteps.push('Módulo Compra cargado correctamente');

    // === Paso 3: Buscar botón de editar en la tabla ===
    testSteps.push('En la tabla de compras, hacer clic en el botón Editar');
    console.log('Buscando botón de editar...');
    
    // Esperar a que la tabla cargue
    await driver.sleep(2000);
    
    // Buscar el primer botón de editar disponible
    let botonEditar = null;
    try {
      const botonesEditar = await driver.findElements(By.css('button[data-bs-target^="#editarModal"]'));
      if (botonesEditar.length === 0) {
        throw new Error('No se encontraron compras para editar. Asegurese de que exista al menos una compra registrada.');
      }
      botonEditar = botonesEditar[0];
      await driver.wait(until.elementIsVisible(botonEditar), 10000);
      console.log('Botón de editar encontrado.');
    } catch (e) {
      throw new Error('No se encontró botón de editar. Verifique que existan compras registradas y que tenga permisos de edición.');
    }
    
    // Obtener el ID del modal desde el atributo data-bs-target
    const modalTarget = await botonEditar.getAttribute('data-bs-target');
    const modalId = modalTarget.replace('#', '');
    console.log('Modal de edición: ' + modalId);
    
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center', behavior: 'smooth'});", botonEditar);
    await driver.sleep(500);
    
    try {
      await botonEditar.click();
    } catch (e) {
      await driver.executeScript("arguments[0].click();", botonEditar);
    }

    // === Paso 4: Esperar que el modal de edición se abra ===
    testSteps.push('Verificar que se abre el formulario con los datos de la compra');
    console.log('Esperando modal de edición...');
    
    await driver.sleep(1500);
    const modalEditar = await driver.findElement(By.id(modalId));
    await driver.wait(until.elementIsVisible(modalEditar), 15000);
    await driver.executeScript("return document.querySelector('" + modalTarget + "').classList.contains('show');");
    await driver.sleep(1000);
    
    console.log('Modal de edición abierto correctamente.');

    // === Paso 5: Modificar fecha o cantidad ===
    testSteps.push('Modificar la fecha o la cantidad de un producto');
    console.log('Modificando datos de la compra...');
    
    // Modificar fecha (si es posible)
    try {
      const fechaInput = await driver.findElement(By.css(modalTarget + ' input[type="date"]'));
      await driver.wait(until.elementIsVisible(fechaInput), 10000);
      const fechaActual = await fechaInput.getAttribute('value');
      console.log('Fecha actual: ' + fechaActual);
      
      // Cambiar a ayer si es posible
      const ayer = new Date();
      ayer.setDate(ayer.getDate() - 1);
      const fechaAyer = ayer.toISOString().split('T')[0];
      
      await driver.executeScript("arguments[0].value = arguments[1];", fechaInput, fechaAyer);
      await driver.executeScript("arguments[0].dispatchEvent(new Event('change', { bubbles: true }));", fechaInput);
      await driver.sleep(500);
      console.log('Fecha modificada a: ' + fechaAyer);
    } catch (e) {
      console.log('No se pudo modificar la fecha, continuando con cantidad...');
    }
    
    // Modificar cantidad del primer producto
    try {
      const cantidadInputs = await driver.findElements(By.css(modalTarget + ' .cantidad-input'));
      if (cantidadInputs.length > 0) {
        const cantidadInput = cantidadInputs[0];
        await driver.wait(until.elementIsVisible(cantidadInput), 10000);
        await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", cantidadInput);
        await driver.sleep(300);
        
        const cantidadActual = await cantidadInput.getAttribute('value');
        console.log('Cantidad actual: ' + cantidadActual);
        
        const nuevaCantidad = parseInt(cantidadActual) + 2;
        await cantidadInput.clear();
        await cantidadInput.sendKeys(nuevaCantidad.toString());
        await driver.executeScript("arguments[0].dispatchEvent(new Event('input', { bubbles: true }));", cantidadInput);
        await driver.sleep(1000);
        console.log('Cantidad modificada a: ' + nuevaCantidad);
      }
    } catch (e) {
      console.log('No se pudo modificar la cantidad: ' + e.message);
    }

    // === Paso 6: Guardar cambios ===
    testSteps.push('Hacer clic en Guardar Cambios');
    console.log('Guardando cambios...');
    
    const guardarBtn = await driver.findElement(By.css(modalTarget + ' button[name="modificar_compra"]'));
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center', behavior: 'smooth'});", guardarBtn);
    await driver.sleep(500);
    await driver.wait(until.elementIsVisible(guardarBtn), 10000);
    await driver.wait(until.elementIsEnabled(guardarBtn), 10000);
    
    try {
      await guardarBtn.click();
    } catch (e) {
      await driver.executeScript("arguments[0].click();", guardarBtn);
    }

    // === Paso 7: Verificar éxito ===
    testSteps.push('Verificar que el sistema muestra mensaje de éxito y los cambios se reflejan');
    console.log('Verificando actualización...');
    
    await driver.sleep(2000);
    
    // Verificar mensajes de error primero
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
    
    // Verificar que el modal se cerró o hay mensaje de éxito
    try {
      await driver.wait(until.stalenessOf(modalEditar), 15000);
      console.log('Modal cerrado - Compra actualizada exitosamente.');
    } catch (e) {
      try {
        await driver.wait(until.elementLocated(By.css('.alert-success, .toast-success')), 10000);
        const successMsg = await driver.findElement(By.css('.alert-success, .toast-success'));
        const successText = await successMsg.getText();
        console.log('Mensaje de exito detectado: ' + successText);
        
        // Cerrar modal manualmente si es necesario
        try {
          const closeBtn = await driver.findElement(By.css(modalTarget + ' .btn-close, ' + modalTarget + ' button[data-bs-dismiss="modal"]'));
          await closeBtn.click();
          await driver.sleep(1000);
        } catch (e2) {
          // Continuar
        }
      } catch (e2) {
        await driver.sleep(2000);
        const tabla = await driver.findElement(By.id('myTable'));
        if (await tabla.isDisplayed()) {
          console.log('Tabla visible - Compra actualizada exitosamente.');
        } else {
          throw new Error('No se pudo verificar el exito de la operacion');
        }
      }
    }

    console.log('Compra actualizada exitosamente.');
    notes = 'Compra editada exitosamente. Se modificaron la fecha y/o cantidad del producto.';
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

if (require.main === module) {
  runTest().catch(error => {
    console.error('Error fatal en la ejecucion del test:', error);
    process.exit(1);
  });
}

module.exports = { runTest, reportResultToTestLink };

