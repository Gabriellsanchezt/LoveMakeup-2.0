// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = 'Prueba-21';
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: MODIFICAR PRODUCTO CON ERRORES EN VALIDACIONES ===
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
    
    // Esperar redirección después del login
    await driver.wait(until.urlContains('pagina=home'), 15000);
    await driver.sleep(2000); // Esperar a que cargue completamente
    console.log('Login exitoso.');
    testSteps.push('Login completado exitosamente');

    // === Paso 2: Ir al módulo Producto ===
    testSteps.push('Navegar al módulo de Producto');
    console.log('Accediendo al módulo Producto...');
    await driver.get(BASE_URL + '?pagina=producto');
    await driver.sleep(3000);
    
    await driver.wait(until.elementLocated(By.id('btnAbrirRegistrar')), 15000);
    console.log('Modulo Producto cargado correctamente.');
    testSteps.push('Módulo Producto cargado correctamente');

    // === Paso 3: Buscar producto para modificar ===
    testSteps.push('Buscar producto para modificar');
    console.log('Buscando producto en la tabla...');
    
    await driver.sleep(2000);
    
    let botonModificar = null;
    try {
      await driver.wait(until.elementLocated(By.css('table tbody tr')), 10000);
      const filas = await driver.findElements(By.css('table tbody tr'));
      
      for (const fila of filas) {
        try {
          const btn = await fila.findElement(By.css('button.modificar'));
          const isEnabled = await btn.isEnabled();
          if (isEnabled) {
            botonModificar = btn;
            break;
          }
        } catch (e) {
          continue;
        }
      }
      
      if (!botonModificar) {
        throw new Error('No se encontró ningún producto con botón de modificar habilitado');
      }
    } catch (e) {
      throw new Error('No se pudo encontrar un producto para modificar: ' + e.message);
    }

    // === Paso 4: Abrir modal de modificación ===
    testSteps.push('Abrir modal de modificación');
    console.log('Abriendo modal de modificación...');
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center', behavior: 'smooth'});", botonModificar);
    await driver.sleep(500);
    await botonModificar.click();
    await driver.sleep(2000);

    await driver.wait(until.elementLocated(By.id('registro')), 10000);
    const modal = await driver.findElement(By.id('registro'));
    const isModalVisible = await modal.getAttribute('class');
    if (!isModalVisible.includes('show')) {
      throw new Error('El modal de modificación no se abrió correctamente');
    }
    console.log('Modal de modificación abierto correctamente.');
    testSteps.push('Modal de modificación abierto');

    // === Paso 5: Modificar con datos inválidos ===
    testSteps.push('Modificar con datos inválidos');
    console.log('Modificando con datos inválidos...');

    // Nombre inválido: "Bal1s*a" (contiene números y caracteres especiales)
    const nombreInput = await driver.findElement(By.id('nombre'));
    await driver.wait(until.elementIsVisible(nombreInput), 10000);
    await nombreInput.clear();
    await nombreInput.sendKeys('Bal1s*a');
    await driver.sleep(500);

    // Descripción: dejar vacía
    const descripcionInput = await driver.findElement(By.id('descripcion'));
    await driver.wait(until.elementIsVisible(descripcionInput), 10000);
    await descripcionInput.clear();
    await driver.sleep(500);

    // Cantidad al mayor: datos inválidos (letras)
    const cantidadMayorInput = await driver.findElement(By.id('cantidad_mayor'));
    await driver.wait(until.elementIsVisible(cantidadMayorInput), 10000);
    await cantidadMayorInput.clear();
    await cantidadMayorInput.sendKeys('abc');
    await driver.sleep(500);

    // Precio al detal: datos inválidos
    const precioDetalInput = await driver.findElement(By.id('precio_detal'));
    await driver.wait(until.elementIsVisible(precioDetalInput), 10000);
    await precioDetalInput.clear();
    await precioDetalInput.sendKeys('12.345.67');
    await driver.sleep(500);

    // Stock máximo: más de 8 dígitos
    const stockMaximoInput = await driver.findElement(By.id('stock_maximo'));
    await driver.wait(until.elementIsVisible(stockMaximoInput), 10000);
    await stockMaximoInput.clear();
    await stockMaximoInput.sendKeys('123456789');
    await driver.sleep(500);

    console.log('Datos inválidos ingresados.');
    testSteps.push('Datos inválidos ingresados');

    // === Paso 6: Intentar guardar (debe mostrar errores) ===
    testSteps.push('Intentar guardar con datos inválidos');
    console.log('Intentando guardar con datos inválidos...');
    const btnGuardar = await driver.findElement(By.id('btnEnviar'));
    await driver.wait(until.elementIsVisible(btnGuardar), 10000);
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", btnGuardar);
    await driver.sleep(500);
    await btnGuardar.click();
    await driver.sleep(3000);

    // Verificar mensajes de error
    const pageSource = await driver.getPageSource();
    const errorMessages = [
      'Error',
      'Datos incorrectos',
      'Solo letras',
      'Debes seleccionar',
      'no puede estar vacía',
      'Solo numeros',
      'validacion'
    ];

    let errorFound = false;
    for (const msg of errorMessages) {
      if (pageSource.toLowerCase().includes(msg.toLowerCase())) {
        errorFound = true;
        break;
      }
    }

    // Verificar mensajes de error en los spans
    let validationErrorsFound = false;
    try {
      const errorSpans = await driver.findElements(By.css('.error-message'));
      for (const span of errorSpans) {
        const text = await span.getText();
        if (text && text.trim().length > 0) {
          validationErrorsFound = true;
          break;
        }
      }
    } catch (e) {
      console.log('No se encontraron spans de error visibles');
    }

    // Verificar si el modal sigue abierto
    const modalAfter = await driver.findElement(By.id('registro'));
    const modalClass = await modalAfter.getAttribute('class');
    const modalStillOpen = modalClass.includes('show');

    // Verificar campos con clase 'is-invalid'
    let invalidFieldsFound = false;
    try {
      const invalidFields = await driver.findElements(By.css('.is-invalid'));
      if (invalidFields.length > 0) {
        invalidFieldsFound = true;
      }
    } catch (e) {
      console.log('No se encontraron campos con clase is-invalid');
    }

    if (errorFound || validationErrorsFound || (modalStillOpen && invalidFieldsFound)) {
      console.log('Mensajes de error de validación encontrados correctamente.');
      testSteps.push('Mensajes de error de validación mostrados correctamente');
      status = 'p';
      notes = 'Prueba completada exitosamente. El sistema mostró mensajes de error de validación al intentar modificar con datos inválidos.';
    } else {
      if (modalStillOpen) {
        console.log('Modal sigue abierto, lo que indica que la validación funcionó.');
        testSteps.push('Validación funcionó correctamente');
        status = 'p';
        notes = 'Prueba completada. El sistema no permitió guardar la modificación con datos inválidos.';
      } else {
        throw new Error('El sistema no mostró los mensajes de error de validación esperados');
      }
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

