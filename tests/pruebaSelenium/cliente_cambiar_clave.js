// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = 'Prueba-74';
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: AGREGAR DIRECCIÓN DEL CLIENTE (Cambiar la clave) ===
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

    // === Paso 3: Hacer click en Direcciones ===
    testSteps.push('Hacer click en Direcciones');
    console.log('Abriendo sección Direcciones...');
    
    const direccionesBtn = await driver.findElement(By.id('btn-direcciones'));
    await driver.wait(until.elementIsVisible(direccionesBtn), 10000);
    await direccionesBtn.click();
    await driver.sleep(1000);

    // === Paso 4: Hacer click en Agregar en la parte de delivery ===
    testSteps.push('Hacer click en Agregar en delivery');
    console.log('Buscando botón Agregar...');
    
    const agregarBtn = await driver.findElement(By.css('.btn-registrar[data-bs-target="#modalAgregarDireccion"]'));
    await driver.wait(until.elementIsVisible(agregarBtn), 10000);
    await agregarBtn.click();
    await driver.sleep(1500);

    // === Paso 5: Verificar que el modal se abrió ===
    testSteps.push('Verificar que el modal se abrió');
    await driver.wait(until.elementLocated(By.id('modalAgregarDireccion')), 10000);
    const modal = await driver.findElement(By.id('modalAgregarDireccion'));
    const isModalVisible = await modal.getAttribute('class');
    if (!isModalVisible.includes('show')) {
      throw new Error('El modal no se abrió correctamente');
    }
    console.log('Modal abierto correctamente.');
    testSteps.push('Modal abierto');

    // === Paso 6: Llenar la dirección ===
    testSteps.push('Llenar la dirección');
    console.log('Llenando dirección...');
    
    const direccionInput = await driver.findElement(By.css('#modalAgregarDireccion input[name="direccion_envio"], #modalAgregarDireccion input[type="text"]'));
    await driver.wait(until.elementIsVisible(direccionInput), 10000);
    await direccionInput.clear();
    await direccionInput.sendKeys('Sabana grande zona norte tamaca las casitas');
    await driver.sleep(500);

    // === Paso 7: Hacer click en Registrar ===
    testSteps.push('Hacer click en Registrar');
    console.log('Registrando dirección...');
    
    const registrarBtn = await driver.findElement(By.css('#modalAgregarDireccion button[name="registrardireccion"], #modalAgregarDireccion #direccion'));
    await driver.wait(until.elementIsVisible(registrarBtn), 10000);
    await driver.wait(until.elementIsEnabled(registrarBtn), 10000);
    await registrarBtn.click();
    await driver.sleep(3000);

    // === Paso 8: Confirmar en la alerta ===
    testSteps.push('Confirmar en la alerta');
    console.log('Esperando alerta de confirmación...');
    
    try {
      await driver.wait(until.alertIsPresent(), 5000);
      const alert = await driver.switchTo().alert();
      const alertText = await alert.getText();
      console.log('Texto de la alerta: ' + alertText);
      
      if (alertText.includes('Registrar') || alertText.includes('Deseas')) {
        await alert.accept();
        await driver.sleep(3000);
      } else {
        await alert.dismiss();
        throw new Error('Alerta inesperada: ' + alertText);
      }
    } catch (e) {
      console.log('No se encontró alerta, continuando...');
    }

    // === Paso 9: Verificar mensaje de éxito ===
    testSteps.push('Verificar mensaje de éxito');
    console.log('Verificando mensaje de éxito...');
    
    try {
      await driver.wait(until.elementLocated(By.css('.swal2-success, .swal2-title')), 10000);
      const successElement = await driver.findElement(By.css('.swal2-title'));
      const successText = await successElement.getText();
      if (successText.includes('Registrado') || successText.includes('éxito') || successText.includes('exito')) {
        console.log('Mensaje de éxito detectado: ' + successText);
        testSteps.push('Mensaje de éxito confirmado: ' + successText);
        
        // Cerrar el modal de éxito si existe
        try {
          const confirmBtn = await driver.findElement(By.css('.swal2-confirm'));
          await confirmBtn.click();
          await driver.sleep(1000);
        } catch (e) {
          // Ignorar si no hay botón de confirmar
        }
      } else {
        throw new Error('Mensaje de éxito no encontrado o incorrecto');
      }
    } catch (e) {
      await driver.sleep(2000);
      // Verificar si la dirección aparece en la tabla
      try {
        const direccionText = await driver.findElement(By.xpath("//*[contains(text(), 'Sabana grande')]"));
        if (await direccionText.isDisplayed()) {
          console.log('Dirección registrada exitosamente.');
        } else {
          throw new Error('No se pudo verificar el éxito de la operación');
        }
      } catch (e2) {
        throw new Error('No se pudo verificar el éxito de la operación');
      }
    }

    console.log('Dirección agregada exitosamente.');
    notes = 'Dirección agregada exitosamente. Dirección: Sabana grande zona norte tamaca las casitas';
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

