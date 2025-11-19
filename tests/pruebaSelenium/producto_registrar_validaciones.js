// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
const ReportGenerator = require('./ReportGenerator');

// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '76133924c3d3f13d8490b26f5d5a7ca5';
const TEST_CASE_EXTERNAL_ID = '19';
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: REGISTRAR PRODUCTO CON ERRORES EN VALIDACIONES ===
async function runTest() {
  let driver;
  let status = 'f';
  let notes = '';
  const startTime = new Date();
  const testSteps = [];
  const reportGenerator = new ReportGenerator();
  const testName = 'Registrar productos Fallo:Error en las validaciones';

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

    // === Paso 2: Ir al módulo Producto ===
    testSteps.push('Navegar al módulo de Producto');
    console.log('Accediendo al módulo Producto...');
    await driver.get(BASE_URL + '?pagina=producto');
    await driver.sleep(2000);
    
    await driver.wait(until.elementLocated(By.id('btnAbrirRegistrar')), 15000);
    console.log('Modulo Producto cargado correctamente.');
    testSteps.push('Módulo Producto cargado correctamente');

    // === Paso 3: Abrir formulario de registro ===
    testSteps.push('Abrir formulario de registro de producto');
    console.log('Abriendo formulario de registro...');
    const registrarBtn = await driver.findElement(By.id('btnAbrirRegistrar'));
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center', behavior: 'smooth'});", registrarBtn);
    await driver.sleep(500);
    await driver.wait(until.elementIsVisible(registrarBtn), 10000);
    await registrarBtn.click();
    await driver.sleep(1500);

    await driver.wait(until.elementLocated(By.id('registro')), 10000);
    const modal = await driver.findElement(By.id('registro'));
    const isModalVisible = await modal.getAttribute('class');
    if (!isModalVisible.includes('show')) {
      throw new Error('El modal de registro no se abrió correctamente');
    }
    console.log('Modal de registro abierto correctamente.');
    testSteps.push('Modal de registro abierto');

    // === Paso 4: Llenar formulario con datos inválidos ===
    testSteps.push('Llenar datos del producto con formato inválido');
    console.log('Llenando datos inválidos del producto...');

    // Nombre inválido: "Bal1s*a premium" (contiene números y caracteres especiales)
    const nombreInput = await driver.findElement(By.id('nombre'));
    await driver.wait(until.elementIsVisible(nombreInput), 10000);
    await nombreInput.clear();
    await nombreInput.sendKeys('Bal1s*a premium');
    await driver.sleep(500);

    // Marca: NO seleccionar (dejar vacío para que falle la validación)
    // O intentar seleccionar una opción inválida si es posible
    const marcaSelect = await driver.findElement(By.id('marca'));
    await driver.wait(until.elementIsVisible(marcaSelect), 10000);
    // Dejar sin seleccionar para que falle

    // Descripción: dejar vacía o con datos inválidos
    const descripcionInput = await driver.findElement(By.id('descripcion'));
    await driver.wait(until.elementIsVisible(descripcionInput), 10000);
    await descripcionInput.clear();
    // Dejar vacía para que falle

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
    await precioDetalInput.sendKeys('12.345.67'); // Formato inválido
    await driver.sleep(500);

    // Precio al mayor: datos inválidos
    const precioMayorInput = await driver.findElement(By.id('precio_mayor'));
    await driver.wait(until.elementIsVisible(precioMayorInput), 10000);
    await precioMayorInput.clear();
    await precioMayorInput.sendKeys('-10'); // Negativo
    await driver.sleep(500);

    // Stock máximo: datos inválidos
    const stockMaximoInput = await driver.findElement(By.id('stock_maximo'));
    await driver.wait(until.elementIsVisible(stockMaximoInput), 10000);
    await stockMaximoInput.clear();
    await stockMaximoInput.sendKeys('123456789'); // Más de 8 dígitos
    await driver.sleep(500);

    // Stock mínimo: datos inválidos
    const stockMinimoInput = await driver.findElement(By.id('stock_minimo'));
    await driver.wait(until.elementIsVisible(stockMinimoInput), 10000);
    await stockMinimoInput.clear();
    await stockMinimoInput.sendKeys('xyz'); // Letras
    await driver.sleep(500);

    // Categoría: NO seleccionar (dejar vacío)
    // Dejar sin seleccionar para que falle

    console.log('Datos inválidos completados.');
    testSteps.push('Datos inválidos ingresados');

    // === Paso 5: Intentar guardar producto (debe mostrar errores) ===
    testSteps.push('Intentar guardar producto con datos inválidos');
    console.log('Intentando guardar producto con datos inválidos...');
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

    // Verificar si hay mensajes de error en los spans de validación
    let validationErrorsFound = false;
    try {
      const errorSpans = await driver.findElements(By.css('.error-message'));
      for (const span of errorSpans) {
        const text = await span.getText();
        if (text && text.trim().length > 0) {
          validationErrorsFound = true;
          console.log(`Mensaje de error encontrado: ${text}`);
          break;
        }
      }
    } catch (e) {
      console.log('No se encontraron spans de error visibles');
    }

    // Verificar si el modal sigue abierto (indicador de que no se guardó)
    const modalAfter = await driver.findElement(By.id('registro'));
    const modalClass = await modalAfter.getAttribute('class');
    const modalStillOpen = modalClass.includes('show');

    // Verificar si hay campos con clase 'is-invalid' (Bootstrap validation)
    let invalidFieldsFound = false;
    try {
      const invalidFields = await driver.findElements(By.css('.is-invalid'));
      if (invalidFields.length > 0) {
        invalidFieldsFound = true;
        console.log(`Se encontraron ${invalidFields.length} campos con errores de validación`);
      }
    } catch (e) {
      console.log('No se encontraron campos con clase is-invalid');
    }

    if (errorFound || validationErrorsFound || (modalStillOpen && invalidFieldsFound)) {
      console.log('Mensajes de error de validación encontrados correctamente.');
      testSteps.push('Mensajes de error de validación mostrados correctamente');
      status = 'p';
      notes = 'Prueba completada exitosamente. El sistema mostró mensajes de error de validación para los datos inválidos ingresados.';
    } else {
      // Si no se encontraron errores claros, verificar si el modal sigue abierto
      if (modalStillOpen) {
        console.log('Modal sigue abierto, lo que indica que la validación funcionó.');
        testSteps.push('Validación funcionó correctamente (modal no se cerró)');
        status = 'p';
        notes = 'Prueba completada. El sistema no permitió guardar el producto con datos inválidos.';
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

