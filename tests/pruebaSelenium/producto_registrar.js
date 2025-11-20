// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
const ReportGenerator = require('./ReportGenerator');

// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = '17';
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: REGISTRAR PRODUCTOS ===
async function runTest() {
  let driver;
  let status = 'f';
  let notes = '';
  const startTime = new Date();
  const testSteps = [];
  const reportGenerator = new ReportGenerator();
  const testName = 'Registrar productos';

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

    // === Paso 4: Llenar formulario ===
    testSteps.push('Llenar datos del producto');
    console.log('Llenando datos del producto...');

    // Generar nombre único para evitar duplicados
    const timestamp = Date.now();
    const nombreProducto = `Balsamo premium ${timestamp}`;

    // Nombre del producto
    const nombreInput = await driver.findElement(By.id('nombre'));
    await driver.wait(until.elementIsVisible(nombreInput), 10000);
    await nombreInput.clear();
    await nombreInput.sendKeys(nombreProducto);
    await driver.sleep(500);

    // Marca
    const marcaSelect = await driver.findElement(By.id('marca'));
    await driver.wait(until.elementIsVisible(marcaSelect), 10000);
    await marcaSelect.click();
    await driver.sleep(500);
    // Seleccionar la primera opción disponible (no la opción disabled)
    await driver.executeScript(`
      var select = arguments[0];
      var options = select.options;
      for (var i = 1; i < options.length; i++) {
        if (options[i].value && options[i].value !== '') {
          select.value = options[i].value;
          select.dispatchEvent(new Event('change', { bubbles: true }));
          break;
        }
      }
    `, marcaSelect);
    await driver.sleep(500);

    // Descripción
    const descripcionInput = await driver.findElement(By.id('descripcion'));
    await driver.wait(until.elementIsVisible(descripcionInput), 10000);
    await descripcionInput.clear();
    await descripcionInput.sendKeys('Balsamo premium de alta calidad para cuidado de la piel');
    await driver.sleep(500);

    // Cantidad al mayor
    const cantidadMayorInput = await driver.findElement(By.id('cantidad_mayor'));
    await driver.wait(until.elementIsVisible(cantidadMayorInput), 10000);
    await cantidadMayorInput.clear();
    await cantidadMayorInput.sendKeys('10');
    await driver.sleep(500);

    // Precio al detal
    const precioDetalInput = await driver.findElement(By.id('precio_detal'));
    await driver.wait(until.elementIsVisible(precioDetalInput), 10000);
    await precioDetalInput.clear();
    await precioDetalInput.sendKeys('15.50');
    await driver.sleep(500);

    // Precio al mayor
    const precioMayorInput = await driver.findElement(By.id('precio_mayor'));
    await driver.wait(until.elementIsVisible(precioMayorInput), 10000);
    await precioMayorInput.clear();
    await precioMayorInput.sendKeys('12.00');
    await driver.sleep(500);

    // Stock máximo
    const stockMaximoInput = await driver.findElement(By.id('stock_maximo'));
    await driver.wait(until.elementIsVisible(stockMaximoInput), 10000);
    await stockMaximoInput.clear();
    await stockMaximoInput.sendKeys('100');
    await driver.sleep(500);

    // Stock mínimo
    const stockMinimoInput = await driver.findElement(By.id('stock_minimo'));
    await driver.wait(until.elementIsVisible(stockMinimoInput), 10000);
    await stockMinimoInput.clear();
    await stockMinimoInput.sendKeys('10');
    await driver.sleep(500);

    // Categoría
    const categoriaSelect = await driver.findElement(By.id('categoria'));
    await driver.wait(until.elementIsVisible(categoriaSelect), 10000);
    await categoriaSelect.click();
    await driver.sleep(500);
    // Seleccionar la primera opción disponible
    await driver.executeScript(`
      var select = arguments[0];
      var options = select.options;
      for (var i = 1; i < options.length; i++) {
        if (options[i].value && options[i].value !== '') {
          select.value = options[i].value;
          select.dispatchEvent(new Event('change', { bubbles: true }));
          break;
        }
      }
    `, categoriaSelect);
    await driver.sleep(500);

    console.log('Datos del producto completados.');
    testSteps.push('Datos del producto completados');

    // === Paso 5: Guardar producto ===
    testSteps.push('Guardar producto');
    console.log('Guardando producto...');
    const btnGuardar = await driver.findElement(By.id('btnEnviar'));
    await driver.wait(until.elementIsVisible(btnGuardar), 10000);
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", btnGuardar);
    await driver.sleep(500);
    await btnGuardar.click();
    await driver.sleep(3000);

    // Verificar mensaje de éxito
    const pageSource = await driver.getPageSource();
    const successMessages = [
      'Producto registrado exitosamente',
      'registrado exitosamente',
      'exitosamente',
      'Producto registrado'
    ];

    let successFound = false;
    for (const msg of successMessages) {
      if (pageSource.toLowerCase().includes(msg.toLowerCase())) {
        successFound = true;
        break;
      }
    }

    // También verificar si el modal se cerró (indicador de éxito)
    const modalAfter = await driver.findElement(By.id('registro'));
    const modalClass = await modalAfter.getAttribute('class');
    const modalClosed = !modalClass.includes('show');

    if (successFound || modalClosed) {
      console.log('Producto registrado exitosamente.');
      testSteps.push('Producto registrado exitosamente');
      status = 'p';
      notes = `Prueba completada exitosamente. Producto registrado: ${nombreProducto}`;
    } else {
      // Verificar si hay mensaje de error
      const errorMessages = [
        'Ya existe un producto',
        'producto existente',
        'error',
        'fallo'
      ];
      let errorFound = false;
      for (const msg of errorMessages) {
        if (pageSource.toLowerCase().includes(msg.toLowerCase())) {
          errorFound = true;
          break;
        }
      }

      if (errorFound) {
        throw new Error('El producto ya existe o hubo un error al registrar');
      } else {
        // Si no hay mensaje claro, asumir éxito si el modal se cerró
        if (modalClosed) {
          console.log('Producto registrado (modal cerrado).');
          testSteps.push('Producto registrado exitosamente');
          status = 'p';
          notes = `Prueba completada. Producto registrado: ${nombreProducto}`;
        } else {
          throw new Error('No se pudo confirmar el registro del producto');
        }
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

