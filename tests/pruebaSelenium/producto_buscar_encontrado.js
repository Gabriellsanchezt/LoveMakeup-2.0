// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = '25';
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: BARRA DE BÚSQUEDA - PRODUCTO ENCONTRADO ===
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

    // === Paso 1: Ir al catálogo ===
    testSteps.push('Navegar al catálogo de productos');
    console.log('Navegando al catálogo...');
    await driver.get(BASE_URL + '?pagina=catalogo');
    await driver.sleep(2000);
    
    console.log('Catálogo cargado correctamente.');
    testSteps.push('Catálogo cargado correctamente');

    // === Paso 2: Buscar barra de búsqueda ===
    testSteps.push('Buscar barra de búsqueda');
    console.log('Buscando barra de búsqueda...');
    
    await driver.sleep(2000);
    
    // Buscar el input de búsqueda
    let searchInput = null;
    try {
      // Intentar encontrar el input de búsqueda por name="busqueda"
      searchInput = await driver.findElement(By.css('input[name="busqueda"]'));
      await driver.wait(until.elementIsVisible(searchInput), 10000);
      console.log('Barra de búsqueda encontrada.');
      testSteps.push('Barra de búsqueda encontrada');
    } catch (e) {
      // Intentar buscar por placeholder
      try {
        searchInput = await driver.findElement(By.css('input[placeholder*="Búsqueda"]'));
        await driver.wait(until.elementIsVisible(searchInput), 10000);
        console.log('Barra de búsqueda encontrada por placeholder.');
        testSteps.push('Barra de búsqueda encontrada');
      } catch (e2) {
        throw new Error('No se pudo encontrar la barra de búsqueda: ' + e.message);
      }
    }

    // === Paso 3: Ingresar término de búsqueda ===
    testSteps.push('Ingresar término de búsqueda');
    console.log('Ingresando término de búsqueda: Polvo...');
    
    await searchInput.clear();
    await searchInput.sendKeys('Polvo');
    await driver.sleep(1000);
    testSteps.push('Término de búsqueda ingresado: Polvo');

    // === Paso 4: Hacer clic en icono de lupa ===
    testSteps.push('Hacer clic en icono de lupa');
    console.log('Buscando icono de lupa...');
    
    let searchButton = null;
    try {
      // Buscar el icono de lupa o botón de búsqueda
      const searchIcons = await driver.findElements(By.css('.fa-magnifying-glass, .fa-search, [onclick*="search-form"]'));
      if (searchIcons.length > 0) {
        searchButton = searchIcons[0];
      } else {
        // Buscar el formulario y hacer submit
        const searchForm = await driver.findElement(By.css('form#search-form, form[action*="catalogo_producto"]'));
        await driver.executeScript("arguments[0].submit();", searchForm);
        await driver.sleep(2000);
        testSteps.push('Búsqueda realizada mediante submit del formulario');
      }
    } catch (e) {
      // Intentar hacer submit del formulario directamente
      try {
        const searchForm = await driver.findElement(By.css('form'));
        await driver.executeScript("arguments[0].submit();", searchForm);
        await driver.sleep(2000);
        testSteps.push('Búsqueda realizada mediante submit del formulario');
      } catch (e2) {
        // Si hay un botón, hacer clic
        if (searchButton) {
          await searchButton.click();
          await driver.sleep(2000);
          testSteps.push('Búsqueda realizada mediante clic en icono');
        } else {
          // Presionar Enter en el input
          await searchInput.sendKeys(Key.RETURN);
          await driver.sleep(2000);
          testSteps.push('Búsqueda realizada mediante Enter');
        }
      }
    }

    // === Paso 5: Verificar resultados de búsqueda ===
    testSteps.push('Verificar resultados de búsqueda');
    console.log('Verificando resultados...');
    
    await driver.sleep(2000);
    
    // Verificar que se redirigió a la página de resultados
    const currentUrl = await driver.getCurrentUrl();
    const isResultsPage = currentUrl.includes('catalogo_producto') || currentUrl.includes('busqueda');
    
    if (!isResultsPage) {
      throw new Error('No se redirigió a la página de resultados de búsqueda');
    }
    
    console.log('Redirección a página de resultados confirmada.');
    testSteps.push('Redirección a página de resultados confirmada');

    // Verificar que hay productos en los resultados
    let productosEncontrados = false;
    try {
      await driver.wait(until.elementLocated(By.css('.product-item, .product-grid, [data-id]')), 10000);
      const productos = await driver.findElements(By.css('.product-item, .product-grid .col, [data-id]'));
      
      if (productos.length > 0) {
        productosEncontrados = true;
        console.log(`Se encontraron ${productos.length} productos en los resultados.`);
        testSteps.push(`Productos encontrados: ${productos.length}`);
      }
    } catch (e) {
      // Verificar si hay mensaje de "no se encontraron productos"
      const pageSource = await driver.getPageSource();
      if (pageSource.toLowerCase().includes('no se encontraron') || 
          pageSource.toLowerCase().includes('sin resultados')) {
        throw new Error('No se encontraron productos para la búsqueda');
      }
    }

    if (productosEncontrados) {
      console.log('Búsqueda exitosa. Productos encontrados.');
      testSteps.push('Búsqueda exitosa');
      status = 'p';
      notes = 'Prueba completada exitosamente. La búsqueda encontró productos relacionados con "Polvo" y se mostraron en la página de resultados.';
    } else {
      throw new Error('No se encontraron productos en los resultados de la búsqueda');
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

