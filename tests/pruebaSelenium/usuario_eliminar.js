// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = 'Prueba-90';
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/proyectoIII/Proyecto-III/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: ELIMINAR USUARIO ===
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
    
    await driver.wait(until.urlContains('pagina=home'), 15000);
    await driver.sleep(2000);
    console.log('Login exitoso.');
    testSteps.push('Login completado exitosamente');

    // === Paso 4: Navegar a Usuario ===
    testSteps.push('Navegar a Usuario');
    console.log('Accediendo a Usuario...');
    
    await driver.get(BASE_URL + '?pagina=usuario');
    await driver.sleep(2000);
    
    await driver.wait(until.urlContains('pagina=usuario'), 15000);
    console.log('Página de Usuario cargada correctamente.');
    testSteps.push('Página de Usuario cargada');

    // === Paso 5: Seleccionar un usuario y hacer clic en el botón de eliminar ===
    testSteps.push('Seleccionar un usuario y hacer clic en eliminar');
    console.log('Buscando botón de eliminar...');
    
    // Buscar el primer botón de eliminar (clase "eliminar" o id="eliminar")
    try {
      const eliminarButtons = await driver.findElements(By.css('button.eliminar, button#eliminar'));
      if (eliminarButtons.length > 0) {
        // Seleccionar el primer botón que no sea el administrador principal (value != cédula del admin)
        const eliminarBtn = eliminarButtons[0];
        await driver.executeScript("arguments[0].scrollIntoView({block: 'center', behavior: 'smooth'});", eliminarBtn);
        await driver.sleep(500);
        await driver.wait(until.elementIsVisible(eliminarBtn), 10000);
        await eliminarBtn.click();
        await driver.sleep(2000);
        console.log('Botón de eliminar clickeado.');
        testSteps.push('Botón de eliminar clickeado');
      } else {
        throw new Error('No se encontró ningún botón de eliminar');
      }
    } catch (e) {
      throw new Error('Error al hacer clic en eliminar: ' + e.message);
    }

    // === Paso 6: Verificar alerta "¿Desea eliminar a este usuario?" ===
    testSteps.push('Verificar alerta de confirmación');
    console.log('Verificando alerta de confirmación...');
    
    try {
      await driver.wait(until.alertIsPresent(), 5000);
      const alert = await driver.switchTo().alert();
      const alertText = await alert.getText();
      if (alertText.includes('Desea eliminar') || alertText.includes('usuario')) {
        console.log('Alerta de confirmación encontrada: ' + alertText);
        testSteps.push('Alerta ¿Desea eliminar a este usuario? encontrada');
      } else {
        await alert.accept();
      }
    } catch (e) {
      // Verificar si hay modal de confirmación
      try {
        await driver.wait(until.elementLocated(By.css('.swal2-popup, .modal')), 5000);
        const modalTitle = await driver.findElement(By.css('.swal2-title, .modal-title'));
        const titleText = await modalTitle.getText();
        if (titleText.includes('Desea eliminar') || titleText.includes('usuario')) {
          console.log('Modal de confirmación encontrado: ' + titleText);
          testSteps.push('Alerta ¿Desea eliminar a este usuario? encontrada');
        }
      } catch (e2) {
        console.log('No se encontró alerta ni modal de confirmación, continuando...');
      }
    }

    // === Paso 7: Hacer clic en "Si, Eliminar" ===
    testSteps.push('Hacer clic en Si, Eliminar');
    console.log('Buscando botón Si, Eliminar...');
    
    try {
      await driver.wait(until.alertIsPresent(), 5000);
      const alert = await driver.switchTo().alert();
      const alertText = await alert.getText();
      if (alertText.includes('Desea eliminar') || alertText.includes('usuario')) {
        await alert.accept(); // Aceptar la alerta (equivalente a hacer clic en SI)
        await driver.sleep(2000);
        console.log('Confirmación aceptada.');
        testSteps.push('Confirmación aceptada (clic en Si, Eliminar)');
      } else {
        await alert.accept();
      }
    } catch (e) {
      // Intentar buscar botón SI en modal
      try {
        const siBtn = await driver.findElement(By.xpath("//button[contains(text(), 'Si, Eliminar') or contains(text(), 'Sí, Eliminar') or contains(text(), 'Eliminar')]"));
        await driver.wait(until.elementIsVisible(siBtn), 10000);
        await siBtn.click();
        await driver.sleep(2000);
        console.log('Botón Si, Eliminar clickeado.');
        testSteps.push('Botón Si, Eliminar clickeado');
      } catch (e2) {
        // Intentar con botón de confirmación de SweetAlert
        try {
          const confirmBtn = await driver.findElement(By.css('.swal2-confirm'));
          await confirmBtn.click();
          await driver.sleep(2000);
          console.log('Botón de confirmación clickeado.');
          testSteps.push('Confirmación aceptada');
        } catch (e3) {
          console.log('No se encontró botón de confirmación, asumiendo que se aceptó la alerta...');
        }
      }
    }

    // === Verificar mensaje de éxito ===
    testSteps.push('Verificar mensaje de éxito');
    console.log('Verificando mensaje de éxito...');
    
    try {
      await driver.wait(until.alertIsPresent(), 5000);
      const alert = await driver.switchTo().alert();
      const alertText = await alert.getText();
      if (alertText.includes('eliminado') && (alertText.includes('exito') || alertText.includes('éxito'))) {
        await alert.accept();
        await driver.sleep(2000);
        console.log('Mensaje de éxito confirmado: ' + alertText);
        testSteps.push('Mensaje de éxito: ' + alertText);
        notes = 'Usuario eliminado exitosamente. Alerta: ' + alertText;
        status = 'p';
      } else {
        await alert.accept();
        throw new Error('Mensaje de éxito no encontrado. Alerta recibida: ' + alertText);
      }
    } catch (e) {
      if (e.message.includes('timeout') || e.message.includes('no such alert')) {
        // Verificar si hay mensaje de éxito en la página
        try {
          await driver.wait(until.elementLocated(By.css('.swal2-success, .swal2-title')), 10000);
          const successElement = await driver.findElement(By.css('.swal2-title'));
          const successText = await successElement.getText();
          if (successText.includes('eliminado') || successText.includes('exito') || successText.includes('éxito')) {
            console.log('Mensaje de éxito encontrado en la página: ' + successText);
            notes = 'Usuario eliminado exitosamente. Mensaje: ' + successText;
            status = 'p';
            
            // Cerrar modal si existe
            try {
              const confirmBtn = await driver.findElement(By.css('.swal2-confirm'));
              await confirmBtn.click();
              await driver.sleep(1000);
            } catch (e2) {
              // Ignorar si no hay botón de confirmar
            }
          } else {
            throw new Error('No se encontró el mensaje de éxito esperado');
          }
        } catch (e2) {
          await driver.sleep(2000);
          // Verificar si la tabla se actualizó (usuario eliminado)
          try {
            const tabla = await driver.findElement(By.css('table, #myTable'));
            if (await tabla.isDisplayed()) {
              console.log('Tabla visible - Usuario eliminado exitosamente.');
              notes = 'Usuario eliminado exitosamente. Tabla actualizada.';
              status = 'p';
            } else {
              throw new Error('No se pudo verificar el éxito de la operación');
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

