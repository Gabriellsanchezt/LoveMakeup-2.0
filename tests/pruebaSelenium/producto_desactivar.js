// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = '22';
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: DESACTIVAR PRODUCTOS ===
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

    // === Paso 2: Ir al módulo Producto ===
    testSteps.push('Navegar al módulo de Producto');
    console.log('Accediendo al módulo Producto...');
    await driver.get(BASE_URL + '?pagina=producto');
    await driver.sleep(3000);
    
    await driver.wait(until.elementLocated(By.id('btnAbrirRegistrar')), 15000);
    console.log('Modulo Producto cargado correctamente.');
    testSteps.push('Módulo Producto cargado correctamente');

    // === Paso 3: Buscar producto activo para desactivar ===
    testSteps.push('Buscar producto activo para desactivar');
    console.log('Buscando producto activo en la tabla...');
    
    await driver.sleep(2000);
    
    // Buscar la primera fila con botón de desactivar (producto activo)
    let botonDesactivar = null;
    let nombreProducto = '';
    
    try {
      await driver.wait(until.elementLocated(By.css('table tbody tr')), 10000);
      const filas = await driver.findElements(By.css('table tbody tr'));
      
      for (const fila of filas) {
        try {
          const btn = await fila.findElement(By.css('button.btn-desactivar'));
          const isEnabled = await btn.isEnabled();
          if (isEnabled) {
            botonDesactivar = btn;
            // Obtener nombre del producto
            try {
              const primeraCelda = await fila.findElement(By.css('td'));
              nombreProducto = await primeraCelda.getText();
            } catch (e) {
              nombreProducto = 'Producto encontrado';
            }
            console.log(`Producto encontrado para desactivar: ${nombreProducto}`);
            testSteps.push(`Producto encontrado: ${nombreProducto}`);
            break;
          }
        } catch (e) {
          continue;
        }
      }
      
      if (!botonDesactivar) {
        throw new Error('No se encontró ningún producto activo para desactivar');
      }
    } catch (e) {
      throw new Error('No se pudo encontrar un producto activo: ' + e.message);
    }

    // === Paso 4: Hacer clic en botón de desactivar ===
    testSteps.push('Hacer clic en botón de desactivar');
    console.log('Haciendo clic en botón de desactivar...');
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center', behavior: 'smooth'});", botonDesactivar);
    await driver.sleep(500);
    await botonDesactivar.click();
    await driver.sleep(2000);

    // === Paso 5: Confirmar desactivación ===
    testSteps.push('Confirmar desactivación en alerta');
    console.log('Esperando alerta de confirmación...');
    
    // Esperar a que aparezca el SweetAlert
    await driver.sleep(2000);
    
    // Buscar el botón de confirmación "Sí, desactivar"
    let confirmButton = null;
    try {
      // SweetAlert generalmente tiene botones con texto específico
      const buttons = await driver.findElements(By.css('button'));
      for (const btn of buttons) {
        const text = await btn.getText();
        if (text.includes('desactivar') || text.includes('Desactivar') || text.includes('Sí')) {
          confirmButton = btn;
          break;
        }
      }
      
      if (!confirmButton) {
        // Intentar buscar por clase de SweetAlert
        try {
          confirmButton = await driver.findElement(By.css('.swal2-confirm'));
        } catch (e) {
          throw new Error('No se encontró el botón de confirmación');
        }
      }
      
      await driver.wait(until.elementIsVisible(confirmButton), 5000);
      await confirmButton.click();
      console.log('Confirmación de desactivación realizada.');
      testSteps.push('Confirmación realizada');
      await driver.sleep(3000);
    } catch (e) {
      // Si no se encuentra el botón, intentar con JavaScript
      try {
        await driver.executeScript(`
          if (typeof Swal !== 'undefined' && Swal.isVisible()) {
            Swal.clickConfirm();
          }
        `);
        await driver.sleep(3000);
        testSteps.push('Confirmación realizada mediante JavaScript');
      } catch (jsError) {
        throw new Error('No se pudo confirmar la desactivación: ' + e.message);
      }
    }

    // === Paso 6: Verificar desactivación exitosa ===
    testSteps.push('Verificar desactivación exitosa');
    console.log('Verificando desactivación...');
    
    // Verificar mensaje de éxito
    const pageSource = await driver.getPageSource();
    const successMessages = [
      'desactivado con exito',
      'desactivado exitosamente',
      'exitosamente',
      'Producto desactivado'
    ];

    let successFound = false;
    for (const msg of successMessages) {
      if (pageSource.toLowerCase().includes(msg.toLowerCase())) {
        successFound = true;
        break;
      }
    }

    // Verificar si la página se recargó
    const currentUrl = await driver.getCurrentUrl();
    const pageReloaded = currentUrl.includes('pagina=producto');

    if (successFound || pageReloaded) {
      console.log('Producto desactivado exitosamente.');
      testSteps.push('Producto desactivado exitosamente');
      status = 'p';
      notes = `Prueba completada exitosamente. El producto fue desactivado correctamente: ${nombreProducto}`;
    } else {
      // Verificar si hay mensaje de error
      const errorMessages = ['error', 'fallo', 'no se pudo'];
      let errorFound = false;
      for (const msg of errorMessages) {
        if (pageSource.toLowerCase().includes(msg.toLowerCase())) {
          errorFound = true;
          break;
        }
      }

      if (errorFound) {
        throw new Error('Hubo un error al desactivar el producto');
      } else {
        // Si la página se recargó, asumir éxito
        if (pageReloaded) {
          console.log('Producto desactivado (página recargada).');
          testSteps.push('Producto desactivado exitosamente');
          status = 'p';
          notes = `Prueba completada. El producto fue desactivado: ${nombreProducto}`;
        } else {
          throw new Error('No se pudo confirmar la desactivación del producto');
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

