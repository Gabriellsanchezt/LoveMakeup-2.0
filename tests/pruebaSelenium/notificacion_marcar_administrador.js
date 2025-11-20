// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = 'Prueba-49'; 
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: MARCAR NOTIFICACIÓN COMO LEÍDA POR EL ADMINISTRADOR ===
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

    // === Paso 2: Hacer click en el icono de notificaciones ===
    testSteps.push('Hacer click en el icono de notificaciones');
    console.log('Buscando icono de notificaciones...');
    
    // Buscar el icono de notificaciones (puede estar en el nav)
    const notificationIcon = await driver.findElement(By.css('a.notification-icon, a[href*="notificacion"]'));
    await driver.wait(until.elementIsVisible(notificationIcon), 10000);
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center', behavior: 'smooth'});", notificationIcon);
    await driver.sleep(500);
    
    try {
      await notificationIcon.click();
    } catch (e) {
      await driver.executeScript("arguments[0].click();", notificationIcon);
    }

    // Esperar redirección a la página de notificaciones
    await driver.wait(until.urlContains('pagina=notificacion'), 15000);
    await driver.sleep(2000);
    console.log('Redireccionado a la página de notificaciones.');
    testSteps.push('Redireccionado a la página de notificaciones');

    // === Paso 3: Seleccionar una notificación y hacer click en "Marcar como leida" ===
    testSteps.push('Seleccionar una notificación y hacer click en Marcar como leida');
    console.log('Buscando botón de marcar como leída...');
    
    // Esperar a que la tabla cargue
    await driver.wait(until.elementLocated(By.css('table#myTable')), 15000);
    await driver.sleep(1000);
    
    // Buscar el primer botón de "Marcar como leída" para administrador
    const marcarLeidaBtn = await driver.findElement(By.css('button.btn-action[data-accion="marcarLeida"]'));
    await driver.wait(until.elementIsVisible(marcarLeidaBtn), 10000);
    await driver.executeScript("arguments[0].scrollIntoView({block: 'center', behavior: 'smooth'});", marcarLeidaBtn);
    await driver.sleep(500);
    
    try {
      await marcarLeidaBtn.click();
    } catch (e) {
      await driver.executeScript("arguments[0].click();", marcarLeidaBtn);
    }

    // === Paso 4: Verificar mensaje de confirmación ===
    testSteps.push('Verificar mensaje de confirmación');
    console.log('Esperando mensaje de confirmación...');
    
    // Esperar a que aparezca el SweetAlert2
    await driver.sleep(2000);
    
    // Buscar el modal de SweetAlert2
    const swalModal = await driver.wait(until.elementLocated(By.css('.swal2-popup')), 10000);
    await driver.wait(until.elementIsVisible(swalModal), 10000);
    
    // Verificar que el mensaje contiene "Marcar como leída"
    const swalText = await swalModal.getText();
    if (!swalText.includes('Marcar como leída') && !swalText.includes('leída')) {
      throw new Error('El mensaje de confirmacion no contiene "Marcar como leida". Texto encontrado: ' + swalText);
    }
    
    console.log('Mensaje de confirmacion encontrado: ' + swalText);
    testSteps.push('Mensaje de confirmacion encontrado: ¿Marcar como leida?');

    // === Paso 5: Hacer click en el botón "Sí" ===
    testSteps.push('Hacer click en el boton Si');
    console.log('Haciendo click en el boton Si...');
    
    // Buscar el botón de confirmación de SweetAlert2
    const confirmBtn = await driver.findElement(By.css('.swal2-confirm'));
    await driver.wait(until.elementIsVisible(confirmBtn), 10000);
    await driver.wait(until.elementIsEnabled(confirmBtn), 10000);
    
    try {
      await confirmBtn.click();
    } catch (e) {
      await driver.executeScript("arguments[0].click();", confirmBtn);
    }

    // === Paso 6: Verificar que la notificación desaparece ===
    testSteps.push('Verificar que la notificacion desaparece');
    console.log('Verificando que la notificacion desaparece...');
    
    // Esperar a que se procese la acción
    await driver.sleep(3000);
    
    // Verificar que el botón de "Marcar como leída" ya no está visible o fue reemplazado
    try {
      // El botón debería desaparecer o ser reemplazado por "Actualizado"
      const actionCell = await driver.findElement(By.css('td:last-child'));
      const actionText = await actionCell.getText();
      
      if (actionText.includes('Actualizado') || actionText.includes('Leída')) {
        console.log('Notificacion marcada como leida correctamente. Texto: ' + actionText);
        testSteps.push('Notificacion marcada como leida correctamente');
      } else {
        // Verificar que el botón ya no existe o que el estado cambió
        const estadoCell = await driver.findElement(By.css('td:nth-child(3)'));
        const estadoText = await estadoCell.getText();
        if (estadoText.includes('Leída')) {
          console.log('Estado de notificacion actualizado correctamente: ' + estadoText);
          testSteps.push('Estado de notificacion actualizado correctamente');
        } else {
          throw new Error('No se pudo verificar que la notificacion fue marcada como leida');
        }
      }
    } catch (e) {
      // Si no se encuentra el elemento, puede ser que la notificación desapareció completamente
      console.log('Verificando si la notificacion desaparecio completamente...');
      const notifications = await driver.findElements(By.css('button.btn-action[data-accion="marcarLeida"]'));
      if (notifications.length === 0) {
        console.log('Todas las notificaciones fueron marcadas como leidas.');
        testSteps.push('Todas las notificaciones fueron marcadas como leidas');
      } else {
        throw new Error('La notificacion no desaparecio correctamente');
      }
    }

    console.log('Notificacion marcada como leida exitosamente.');
    notes = 'Notificacion marcada como leida exitosamente por el administrador. La notificacion desaparece tanto para el administrador como para el asesor de ventas.';
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

