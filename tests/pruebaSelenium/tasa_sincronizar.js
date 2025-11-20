// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = 'Prueba-89';
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/proyectoIII/Proyecto-III/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: SINCRONIZAR TASA ===
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

    // === Paso 1: Entrar a la página de login ===
    testSteps.push('Entrar a la página de login');
    console.log('Navegando al login...');
    await driver.get(BASE_URL + '?pagina=login');
    await driver.sleep(2000);
    
    await driver.wait(until.elementLocated(By.id('usuario')), 15000);
    console.log('Página de login cargada correctamente.');
    testSteps.push('Página de login cargada');

    // === Paso 2: Ingresar credenciales válidas ===
    testSteps.push('Ingresar credenciales válidas');
    console.log('Ingresando credenciales...');
    
    // Seleccionar tipo de documento si existe
    try {
      const tipoDocSelect = await driver.findElement(By.id('DocumentoSelct'));
      await driver.wait(until.elementIsVisible(tipoDocSelect), 10000);
      await driver.executeScript("arguments[0].value = 'V';", tipoDocSelect);
      await driver.executeScript("arguments[0].dispatchEvent(new Event('change', { bubbles: true }));", tipoDocSelect);
      await driver.sleep(300);
    } catch (e) {
      console.log('No se encontró selector de tipo de documento, continuando...');
    }
    
    const usuarioInput = await driver.findElement(By.id('usuario'));
    await driver.wait(until.elementIsVisible(usuarioInput), 10000);
    await usuarioInput.clear();
    await usuarioInput.sendKeys('10200300');
    
    const passwordInput = await driver.findElement(By.id('pid'));
    await driver.wait(until.elementIsVisible(passwordInput), 10000);
    await passwordInput.clear();
    await passwordInput.sendKeys('love1234');
    
    console.log('Credenciales ingresadas correctamente.');
    testSteps.push('Credenciales ingresadas: Cédula: 10200300');

    // === Paso 3: Hacer clic en Ingresar ===
    testSteps.push('Hacer clic en Ingresar');
    console.log('Haciendo clic en Ingresar...');
    
    const ingresarBtn = await driver.findElement(By.id('ingresar'));
    await driver.wait(until.elementIsEnabled(ingresarBtn), 10000);
    await ingresarBtn.click();
    
    // Esperar redirección a home
    await driver.wait(until.urlContains('pagina=home'), 15000);
    await driver.sleep(2000);
    console.log('Login exitoso.');
    testSteps.push('Login completado exitosamente');

    // === Paso 4: Navegar a Tasa de Cambio ===
    testSteps.push('Navegar a Tasa de Cambio');
    console.log('Accediendo a Tasa de Cambio...');
    
    // Intentar navegar directamente a la página
    await driver.get(BASE_URL + '?pagina=tasacambio');
    await driver.sleep(2000);
    
    // Verificar que se cargó la página correctamente
    await driver.wait(until.urlContains('tasacambio'), 15000);
    console.log('Página de Tasa de Cambio cargada correctamente.');
    testSteps.push('Página de Tasa de Cambio cargada');

    // === Paso 5: Hacer clic en "Actualizar Manualmente" ===
    testSteps.push('Hacer clic en Actualizar Manualmente');
    console.log('Buscando botón Actualizar Manualmente...');
    
    try {
      const actualizarBtn = await driver.findElement(By.id('btnActualizarManual'));
      await driver.wait(until.elementIsVisible(actualizarBtn), 10000);
      await driver.wait(until.elementIsEnabled(actualizarBtn), 10000);
      await actualizarBtn.click();
      await driver.sleep(2000);
      console.log('Botón Actualizar Manualmente clickeado.');
      testSteps.push('Botón Actualizar Manualmente clickeado');
    } catch (e) {
      // Intentar con otros selectores
      try {
        const actualizarBtn = await driver.findElement(By.xpath("//button[contains(text(), 'Actualizar Manualmente')]"));
        await actualizarBtn.click();
        await driver.sleep(2000);
        console.log('Botón Actualizar Manualmente clickeado (selector alternativo).');
        testSteps.push('Botón Actualizar Manualmente clickeado');
      } catch (e2) {
        throw new Error('No se encontró el botón Actualizar Manualmente');
      }
    }

    // Verificar que aparece el modal SweetAlert "Actualizar Tasa"
    try {
      await driver.wait(until.elementLocated(By.css('.swal2-popup')), 5000);
      const swalTitle = await driver.findElement(By.css('.swal2-title'));
      const titleText = await swalTitle.getText();
      if (titleText.includes('Actualizar') || titleText.includes('Tasa')) {
        console.log('Modal Actualizar Tasa encontrado: ' + titleText);
        testSteps.push('Modal para introducir tasa encontrado');
      }
    } catch (e) {
      console.log('Modal no detectado automáticamente, continuando...');
    }
    await driver.sleep(1000);

    // === Paso 6: Introducir tasa en el input del modal ===
    testSteps.push('Introducir tasa en el modal');
    console.log('Buscando campo de tasa en el modal...');
    
    try {
      const tasaInput = await driver.findElement(By.id('swal-tasa'));
      await driver.wait(until.elementIsVisible(tasaInput), 10000);
      await tasaInput.clear();
      await tasaInput.sendKeys('400.50');
      console.log('Tasa ingresada en el modal: 400.50');
      testSteps.push('Tasa ingresada: 400.50');
    } catch (e) {
      throw new Error('No se encontró el campo de tasa en el modal');
    }
    await driver.sleep(1000);

    // === Paso 7: Hacer clic en "Guardar" en el modal ===
    testSteps.push('Hacer clic en Guardar en el modal');
    console.log('Buscando botón Guardar en el modal...');
    
    try {
      const guardarBtn = await driver.findElement(By.css('.swal2-confirm'));
      await driver.wait(until.elementIsEnabled(guardarBtn), 10000);
      await guardarBtn.click();
      await driver.sleep(2000);
      console.log('Botón Guardar clickeado en el modal.');
      testSteps.push('Botón Guardar clickeado');
    } catch (e) {
      throw new Error('No se encontró el botón Guardar en el modal');
    }

    // Verificar modal "¿Confirmar cambios?"
    try {
      await driver.wait(until.elementLocated(By.css('.swal2-popup')), 5000);
      const swalTitle = await driver.findElement(By.css('.swal2-title'));
      const titleText = await swalTitle.getText();
      if (titleText.includes('Confirmar') || titleText.includes('cambios')) {
        console.log('Modal de confirmación encontrado: ' + titleText);
        testSteps.push('Modal ¿Confirmar cambios? encontrado');
      }
    } catch (e) {
      console.log('No se encontró modal de confirmación, continuando...');
    }

    // === Paso 8: Hacer clic en "Si" en el modal de confirmación ===
    testSteps.push('Hacer clic en Si en el modal de confirmación');
    console.log('Buscando botón Si en el modal...');
    
    try {
      const siBtn = await driver.findElement(By.css('.swal2-confirm'));
      await driver.wait(until.elementIsEnabled(siBtn), 10000);
      await siBtn.click();
      await driver.sleep(2000);
      console.log('Botón Si clickeado en el modal.');
      testSteps.push('Confirmación aceptada (clic en Si)');
    } catch (e) {
      throw new Error('No se encontró el botón Si en el modal de confirmación');
    }

    // === Verificar mensaje de éxito ===
    testSteps.push('Verificar mensaje de éxito');
    console.log('Verificando mensaje de éxito...');
    
    try {
      await driver.wait(until.alertIsPresent(), 5000);
      const alert = await driver.switchTo().alert();
      const alertText = await alert.getText();
      if (alertText.includes('Actualizado') && (alertText.includes('éxito') || alertText.includes('exito'))) {
        await alert.accept();
        await driver.sleep(2000);
        console.log('Mensaje de éxito confirmado: ' + alertText);
        testSteps.push('Mensaje de éxito: ' + alertText);
        notes = 'Tasa sincronizada exitosamente. Alerta: ' + alertText;
        status = 'p';
      } else {
        await alert.accept();
        throw new Error('Mensaje de éxito no encontrado. Alerta recibida: ' + alertText);
      }
    } catch (e) {
      if (e.message.includes('timeout') || e.message.includes('no such alert')) {
        // Verificar si hay mensaje de éxito en la página
        try {
          const successElement = await driver.findElement(By.css('.swal2-success, .swal2-title, .alert-success, [class*="success"]'));
          const successText = await successElement.getText();
          if (successText.includes('Actualizado') || successText.includes('exito') || successText.includes('éxito')) {
            console.log('Mensaje de éxito encontrado en la página: ' + successText);
            notes = 'Tasa sincronizada exitosamente. Mensaje: ' + successText;
            status = 'p';
            
            // Cerrar modal si existe
            try {
              const confirmBtn = await driver.findElement(By.css('.swal2-confirm, .btn-confirm'));
              await confirmBtn.click();
              await driver.sleep(1000);
            } catch (e2) {
              // Ignorar si no hay botón de confirmar
            }
          } else {
            throw new Error('No se encontró el mensaje de éxito esperado');
          }
        } catch (e2) {
          // Verificar si hay algún mensaje en la página
          try {
            const messageElement = await driver.findElement(By.css('.alert, .message, [class*="alert"]'));
            const messageText = await messageElement.getText();
            if (messageText.includes('Actualizado') || messageText.includes('exito') || messageText.includes('éxito')) {
              console.log('Mensaje encontrado en la página: ' + messageText);
              notes = 'Tasa sincronizada exitosamente. Mensaje: ' + messageText;
              status = 'p';
            } else {
              throw new Error('No se encontró el mensaje de éxito esperado. Mensaje encontrado: ' + messageText);
            }
          } catch (e3) {
            throw new Error('No se encontró alerta ni mensaje de éxito. Error: ' + e.message);
          }
        }
      } else {
        throw e;
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

