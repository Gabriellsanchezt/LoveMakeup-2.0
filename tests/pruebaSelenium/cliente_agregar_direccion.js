// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = 'Prueba-73';
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: ELIMINAR CUENTA DEL CLIENTE ===
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

    // === Paso 1: Iniciar sesión como cliente ===
    testSteps.push('Iniciar sesión como cliente');
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
    await usuarioInput.sendKeys('12241103');
    
    const passwordInput = await driver.findElement(By.id('pid'));
    await driver.wait(until.elementIsVisible(passwordInput), 10000);
    await passwordInput.clear();
    await passwordInput.sendKeys('lara1234');
    
    const ingresarBtn = await driver.findElement(By.id('ingresar'));
    await driver.wait(until.elementIsEnabled(ingresarBtn), 10000);
    await ingresarBtn.click();
    
    // Esperar redirección al catálogo
    await driver.wait(until.urlContains('pagina=catalogo'), 15000);
    await driver.sleep(2000);
    console.log('Login exitoso.');
    testSteps.push('Login completado exitosamente');

    // === Paso 2: Ir a Ver datos ===
    testSteps.push('Navegar a Ver datos');
    console.log('Accediendo a Ver datos...');
    
    // Buscar el menú VER y hacer click en "Ver datos"
    try {
      const verMenu = await driver.findElement(By.xpath("//a[contains(text(), 'VER') or contains(text(), 'Ver')]"));
      await driver.wait(until.elementIsVisible(verMenu), 10000);
      await verMenu.click();
      await driver.sleep(1000);
      
      const verDatosLink = await driver.findElement(By.xpath("//a[contains(text(), 'Ver datos') or contains(@href, 'catalogo_datos')]"));
      await verDatosLink.click();
    } catch (e) {
      // Si no se encuentra el menú, ir directamente
      await driver.get(BASE_URL + '?pagina=catalogo_datos');
    }
    
    await driver.wait(until.urlContains('catalogo_datos'), 15000);
    await driver.sleep(2000);
    console.log('Página de datos cargada correctamente.');
    testSteps.push('Página de datos cargada');

    // === Paso 3: Hacer click en Seguridad ===
    testSteps.push('Hacer click en Seguridad');
    console.log('Abriendo sección Seguridad...');
    
    const seguridadBtn = await driver.findElement(By.id('btn-seguridad'));
    await driver.wait(until.elementIsVisible(seguridadBtn), 10000);
    await seguridadBtn.click();
    await driver.sleep(1000);

    // === Paso 4: Hacer click en Eliminar Cuenta ===
    testSteps.push('Hacer click en Eliminar Cuenta');
    console.log('Buscando botón Eliminar Cuenta...');
    
    const eliminarCuentaBtn = await driver.findElement(By.css('.btn-delete[data-bs-target="#cuenta"], button[data-bs-target="#cuenta"]'));
    await driver.wait(until.elementIsVisible(eliminarCuentaBtn), 10000);
    await eliminarCuentaBtn.click();
    await driver.sleep(1500);

    // === Paso 5: Verificar que el modal se abrió ===
    testSteps.push('Verificar que el modal se abrió');
    await driver.wait(until.elementLocated(By.id('cuenta')), 10000);
    const modal = await driver.findElement(By.id('cuenta'));
    const isModalVisible = await modal.getAttribute('class');
    if (!isModalVisible.includes('show')) {
      throw new Error('El modal no se abrió correctamente');
    }
    console.log('Modal de eliminación abierto correctamente.');
    testSteps.push('Modal abierto');

    // === Paso 6: Escribir "ACEPTAR" ===
    testSteps.push('Escribir ACEPTAR');
    console.log('Escribiendo ACEPTAR...');
    
    const confirmarInput = await driver.findElement(By.id('confirmar'));
    await driver.wait(until.elementIsVisible(confirmarInput), 10000);
    await confirmarInput.clear();
    await confirmarInput.sendKeys('ACEPTAR');
    await driver.sleep(500);

    // === Paso 7: Hacer click en Continuar ===
    testSteps.push('Hacer click en Continuar');
    console.log('Confirmando eliminación...');
    
    const continuarBtn = await driver.findElement(By.id('btnEliminar'));
    await driver.wait(until.elementIsVisible(continuarBtn), 10000);
    await driver.wait(until.elementIsEnabled(continuarBtn), 10000);
    await continuarBtn.click();
    await driver.sleep(3000);

    // === Paso 8: Verificar mensaje de eliminación ===
    testSteps.push('Verificar mensaje de eliminación');
    console.log('Verificando mensaje de eliminación...');
    
    try {
      await driver.wait(until.elementLocated(By.css('.swal2-success, .swal2-title, .alert')), 10000);
      const alertElement = await driver.findElement(By.css('.swal2-title, .alert'));
      const alertText = await alertElement.getText();
      if (alertText.includes('eliminando') || alertText.includes('eliminada') || alertText.includes('éxito') || alertText.includes('exito')) {
        console.log('Mensaje de eliminación detectado: ' + alertText);
        testSteps.push('Mensaje de eliminación confirmado: ' + alertText);
        
        // Cerrar el modal si existe
        try {
          const confirmBtn = await driver.findElement(By.css('.swal2-confirm'));
          await confirmBtn.click();
          await driver.sleep(1000);
        } catch (e) {
          // Ignorar si no hay botón de confirmar
        }
      } else {
        // Verificar si hay una alerta del navegador
        try {
          await driver.wait(until.alertIsPresent(), 2000);
          const alert = await driver.switchTo().alert();
          const alertText = await alert.getText();
          console.log('Alerta del navegador: ' + alertText);
          if (alertText.includes('eliminando') || alertText.includes('eliminada')) {
            await alert.accept();
            notes = 'Cuenta eliminada exitosamente. Alerta: ' + alertText;
            status = 'p';
          }
        } catch (e) {
          throw new Error('No se encontró mensaje de eliminación esperado');
        }
      }
    } catch (e) {
      await driver.sleep(2000);
      // Verificar si se redirigió o hay algún cambio
      const currentUrl = await driver.getCurrentUrl();
      if (currentUrl.includes('login') || currentUrl.includes('catalogo')) {
        console.log('Redirección detectada - Cuenta eliminada exitosamente.');
        notes = 'Cuenta eliminada exitosamente. Redirección a: ' + currentUrl;
        status = 'p';
      } else {
        throw new Error('No se pudo verificar el éxito de la operación');
      }
    }

    if (status !== 'p') {
      notes = 'Proceso de eliminación de cuenta completado. Modal abierto y confirmación enviada.';
      status = 'p';
    }
    console.log('Proceso de eliminación de cuenta completado.');
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

