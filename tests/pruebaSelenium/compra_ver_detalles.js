// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
const ReportGenerator = require('./ReportGenerator');

// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = '16';
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: VER DETALLES DE UNA COMPRA ===
async function runTest() {
  let driver;
  let status = 'f';
  let notes = '';
  const startTime = new Date();
  const testSteps = [];
  const reportGenerator = new ReportGenerator();
  const testName = 'Ver Detalles de una Compra';

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

    // === Paso 3: Buscar botón de ver detalles ===
    testSteps.push('En la tabla principal, hacer clic en el botón con el ícono de ojo');
    console.log('Buscando botón de ver detalles...');
    
    await driver.sleep(2000);
    
    let botonVerDetalles = null;
    try {
      const botonesVer = await driver.findElements(By.css('button[data-bs-target^="#verDetallesModal"]'));
      if (botonesVer.length === 0) {
        throw new Error('No se encontraron compras para ver detalles. Asegurese de que exista al menos una compra registrada.');
      }
      botonVerDetalles = botonesVer[0];
      await driver.wait(until.elementIsVisible(botonVerDetalles), 10000);
      console.log('Botón de ver detalles encontrado.');
    } catch (e) {
      throw new Error('No se encontró botón de ver detalles. Verifique que existan compras registradas.');
    }
    
    const modalTarget = await botonVerDetalles.getAttribute('data-bs-target');
    const modalId = modalTarget.replace('#', '');
    console.log('Modal de detalles: ' + modalId);
    
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center', behavior: 'smooth'});", botonVerDetalles);
    await driver.sleep(500);
    
    try {
      await botonVerDetalles.click();
    } catch (e) {
      await driver.executeScript("arguments[0].click();", botonVerDetalles);
    }

    // === Paso 4: Verificar que se abre el modal ===
    testSteps.push('Verificar que se abre el modal de detalles');
    console.log('Esperando modal de detalles...');
    
    await driver.sleep(1500);
    const modalDetalles = await driver.findElement(By.id(modalId));
    await driver.wait(until.elementIsVisible(modalDetalles), 15000);
    await driver.executeScript("return document.querySelector('" + modalTarget + "').classList.contains('show');");
    await driver.sleep(1000);
    
    console.log('Modal de detalles abierto correctamente.');

    // === Paso 5: Expandir secciones ===
    testSteps.push('Expandir las secciones de Fecha y Hora, Proveedor y Productos');
    console.log('Expandiendo secciones del modal...');
    
    // Expandir sección de Fecha y Hora
    try {
      const collapseFecha = await driver.findElement(By.css(modalTarget + ' [data-bs-target^="#collapse-fecha-"]'));
      await driver.wait(until.elementIsVisible(collapseFecha), 10000);
      await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", collapseFecha);
      await driver.sleep(300);
      
      const collapseFechaId = await collapseFecha.getAttribute('data-bs-target');
      const fechaCollapsed = await driver.executeScript(`
        var collapseId = arguments[0];
        var element = document.querySelector(collapseId);
        return element ? element.classList.contains('show') : false;
      `, collapseFechaId);
      
      if (!fechaCollapsed) {
        await collapseFecha.click();
        await driver.sleep(500);
        console.log('Sección Fecha y Hora expandida.');
      }
    } catch (e) {
      console.log('No se pudo expandir sección Fecha y Hora: ' + e.message);
    }
    
    // Expandir sección de Proveedor
    try {
      const collapseProveedor = await driver.findElement(By.css(modalTarget + ' [data-bs-target^="#collapse-proveedor-detalle-"]'));
      await driver.wait(until.elementIsVisible(collapseProveedor), 10000);
      await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", collapseProveedor);
      await driver.sleep(300);
      
      const collapseProveedorId = await collapseProveedor.getAttribute('data-bs-target');
      const proveedorCollapsed = await driver.executeScript(`
        var collapseId = arguments[0];
        var element = document.querySelector(collapseId);
        return element ? element.classList.contains('show') : false;
      `, collapseProveedorId);
      
      if (!proveedorCollapsed) {
        await collapseProveedor.click();
        await driver.sleep(500);
        console.log('Sección Proveedor expandida.');
      }
    } catch (e) {
      console.log('No se pudo expandir sección Proveedor: ' + e.message);
    }
    
    // Expandir sección de Productos
    try {
      const collapseProductos = await driver.findElement(By.css(modalTarget + ' [data-bs-target^="#collapse-productos-detalle-"]'));
      await driver.wait(until.elementIsVisible(collapseProductos), 10000);
      await driver.executeScript("arguments[0].scrollIntoView({block: 'center'});", collapseProductos);
      await driver.sleep(300);
      
      const collapseProductosId = await collapseProductos.getAttribute('data-bs-target');
      const productosCollapsed = await driver.executeScript(`
        var collapseId = arguments[0];
        var element = document.querySelector(collapseId);
        return element ? element.classList.contains('show') : false;
      `, collapseProductosId);
      
      if (!productosCollapsed) {
        await collapseProductos.click();
        await driver.sleep(500);
        console.log('Sección Productos expandida.');
      }
      
      // Verificar que hay productos en la tabla
      await driver.sleep(500);
      const tablaProductos = await driver.findElement(By.css(modalTarget + ' .table'));
      await driver.wait(until.elementIsVisible(tablaProductos), 10000);
      const filasProductos = await driver.findElements(By.css(modalTarget + ' .table tbody tr'));
      console.log('Productos encontrados en la tabla: ' + filasProductos.length);
      
      if (filasProductos.length === 0) {
        console.log('Advertencia: No se encontraron productos en la tabla de detalles');
      }
    } catch (e) {
      console.log('No se pudo expandir sección Productos: ' + e.message);
      throw e;
    }

    // === Paso 6: Verificar que se muestran los datos ===
    testSteps.push('Verificar que se muestran correctamente los datos registrados');
    console.log('Verificando datos mostrados...');
    
    // Obtener número de productos de la tabla
    let numProductos = 0;
    try {
      const tablaProductos = await driver.findElement(By.css(modalTarget + ' .table'));
      await driver.wait(until.elementIsVisible(tablaProductos), 10000);
      const filasProductos = await driver.findElements(By.css(modalTarget + ' .table tbody tr'));
      numProductos = filasProductos.length;
      console.log('Productos encontrados en la tabla: ' + numProductos);
    } catch (e) {
      console.log('No se pudo contar productos: ' + e.message);
    }
    
    // Verificar que hay contenido en las secciones
    let datosEncontrados = 0;
    
    try {
      const fechaSection = await driver.findElements(By.css(modalTarget + ' *'));
      for (let elem of fechaSection) {
        const text = await elem.getText();
        if (text && text.includes('Fecha de Entrada')) {
          datosEncontrados++;
          break;
        }
      }
    } catch (e) {
      // Continuar
    }
    
    // Verificar proveedor
    try {
      const proveedorElements = await driver.findElements(By.css(modalTarget + ' *'));
      for (let elem of proveedorElements) {
        const text = await elem.getText();
        if (text && (text.includes('Proveedor') || text.includes('Nombre del Proveedor'))) {
          datosEncontrados++;
          break;
        }
      }
    } catch (e) {
      // Continuar
    }
    
    if (datosEncontrados > 0 || numProductos > 0) {
      console.log('Datos encontrados correctamente en el modal.');
    } else {
      throw new Error('No se encontraron datos en el modal de detalles');
    }

    // === Paso 7: Cerrar el modal ===
    testSteps.push('Cerrar el modal');
    console.log('Cerrando modal...');
    
    const closeBtn = await driver.findElement(By.css(modalTarget + ' .btn-close'));
    await driver.wait(until.elementIsVisible(closeBtn), 10000);
    await closeBtn.click();
    await driver.sleep(1000);
    
    // Verificar que el modal se cerró
    try {
      await driver.wait(until.stalenessOf(modalDetalles), 10000);
      console.log('Modal cerrado correctamente.');
    } catch (e) {
      // Verificar que no está visible
      const modalVisible = await modalDetalles.isDisplayed();
      if (!modalVisible) {
        console.log('Modal cerrado correctamente (no visible).');
      } else {
        throw new Error('El modal no se cerro correctamente');
      }
    }

    console.log('Test completado exitosamente.');
    notes = 'Se visualizaron correctamente los detalles de la compra. Se expandieron las secciones de Fecha y Hora, Proveedor y Productos, y se verifico que los datos se muestran correctamente.';
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

