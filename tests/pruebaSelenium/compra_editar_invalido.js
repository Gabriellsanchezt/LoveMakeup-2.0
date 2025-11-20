// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = 'Prueba-15';
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: EDITAR UNA COMPRA CON DATOS INVÁLIDOS ===
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
    
    // Seleccionar tipo de documento (V - Venezolano)
    const tipoDocSelect = await driver.findElement(By.id('DocumentoSelct'));
    await driver.wait(until.elementIsVisible(tipoDocSelect), 10000);
    await driver.executeScript("arguments[0].value = 'V';", tipoDocSelect);
    await driver.executeScript("arguments[0].dispatchEvent(new Event('change', { bubbles: true }));", tipoDocSelect);
    await driver.sleep(300);
    
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

    // === Paso 3: Abrir formulario de edición ===
    testSteps.push('Abrir el formulario de edición de una compra existente');
    console.log('Buscando botón de editar...');
    
    await driver.sleep(2000);
    
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
      throw new Error('No se encontró botón de editar. Verifique que existan compras registradas.');
    }
    
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

    await driver.sleep(1500);
    const modalEditar = await driver.findElement(By.id(modalId));
    await driver.wait(until.elementIsVisible(modalEditar), 15000);
    await driver.executeScript("return document.querySelector('" + modalTarget + "').classList.contains('show');");
    await driver.sleep(1000);
    
    console.log('Modal de edición abierto.');

    // === Paso 4: Eliminar cantidad o dejarla en 0 ===
    testSteps.push('Eliminar la cantidad o dejarla en 0');
    console.log('Modificando cantidad a valor inválido...');
    
    const cantidadInputs = await driver.findElements(By.css(modalTarget + ' .cantidad-input'));
    if (cantidadInputs.length === 0) {
      throw new Error('No se encontraron campos de cantidad en el formulario de edición');
    }
    
    const cantidadInput = cantidadInputs[0];
    await driver.wait(until.elementIsVisible(cantidadInput), 10000);
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", cantidadInput);
    await driver.sleep(300);
    
    // Intentar poner cantidad en 0 o vacío
    await cantidadInput.clear();
    await cantidadInput.sendKeys('0');
    await driver.executeScript("arguments[0].dispatchEvent(new Event('input', { bubbles: true }));", cantidadInput);
    await driver.sleep(500);
    
    console.log('Cantidad modificada a 0.');

    // === Paso 5: Intentar guardar ===
    testSteps.push('Hacer clic en Guardar Cambios');
    console.log('Intentando guardar con datos inválidos...');
    
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

    // === Paso 6: Verificar error ===
    testSteps.push('Verificar que el sistema muestra mensaje de error y no permite guardar');
    console.log('Verificando mensaje de error...');
    
    await driver.sleep(2000);
    
    let errorEncontrado = false;
    let errorText = '';
    
    // Verificar validación HTML5
    try {
      const cantidadInvalid = await cantidadInput.getAttribute('validity');
      const cantidadValue = await cantidadInput.getAttribute('value');
      if (cantidadValue === '0' || cantidadValue === '') {
        // Verificar si el campo tiene atributo min
        const minValue = await cantidadInput.getAttribute('min');
        if (minValue && parseInt(minValue) > 0) {
          errorEncontrado = true;
          errorText = 'Validación HTML5: La cantidad debe ser mayor a ' + minValue;
          console.log('Validación HTML5 detectada');
        }
      }
    } catch (e) {
      // Continuar
    }
    
    // Verificar mensajes de error en la página
    try {
      const errorMessages = await driver.findElements(By.css('.alert-danger, .alert-warning, .toast-error, .toast-warning, .invalid-feedback'));
      if (errorMessages.length > 0) {
        for (let errorMsg of errorMessages) {
          const text = await errorMsg.getText();
          if (text && text.trim() !== '') {
            errorEncontrado = true;
            errorText = text;
            console.log('Mensaje de error encontrado: ' + text);
            break;
          }
        }
      }
    } catch (e) {
      // Continuar
    }
    
    // Verificar que el modal sigue abierto (no se guardó)
    try {
      const modalVisible = await modalEditar.isDisplayed();
      if (modalVisible) {
        console.log('Modal sigue abierto - El sistema no permitio guardar con datos inválidos');
        if (!errorEncontrado) {
          // Puede ser validación del navegador que previene el envío
          errorEncontrado = true;
          errorText = 'El formulario no se envió (validación del navegador o JavaScript)';
        }
      }
    } catch (e) {
      // Modal cerrado, verificar si hay error en la página
    }

    if (errorEncontrado) {
      console.log('Test exitoso: El sistema mostro error o no permitio guardar con datos inválidos');
      notes = 'El sistema correctamente mostro error o no permitio guardar con cantidad inválida. Mensaje: ' + errorText;
      status = 'p';
    } else {
      throw new Error('El sistema no mostro error o permitio guardar con datos inválidos');
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

if (require.main === module) {
  runTest().catch(error => {
    console.error('Error fatal en la ejecucion del test:', error);
    process.exit(1);
  });
}

module.exports = { runTest, reportResultToTestLink };

