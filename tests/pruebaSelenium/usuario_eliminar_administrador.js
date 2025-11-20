// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = 'Prueba-91';
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/proyectoIII/Proyecto-III/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: ELIMINAR USUARIO - ADMINISTRADOR PRINCIPAL ===
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

    // === Paso 2: Ingresar credenciales del administrador principal ===
    testSteps.push('Ingresar credenciales del administrador principal');
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
    await usuarioInput.sendKeys('00010030');
    
    const passwordInput = await driver.findElement(By.id('pid'));
    await driver.wait(until.elementIsVisible(passwordInput), 10000);
    await passwordInput.clear();
    await passwordInput.sendKeys('Soporte123*');
    
    console.log('Credenciales ingresadas correctamente.');
    testSteps.push('Credenciales ingresadas: Cédula: 00010030');

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

    // === Paso 5: Seleccionar el administrador principal y hacer clic en eliminar ===
    testSteps.push('Seleccionar administrador principal y hacer clic en eliminar');
    console.log('Buscando botón de eliminar del administrador principal...');
    
    // Buscar el botón de eliminar del administrador principal
    // Necesitamos identificar al administrador principal (probablemente el primero o con un ID específico)
    try {
      // Buscar el botón de eliminar del administrador principal (id_usuario = 2 según la vista)
      // El administrador principal tiene un ícono especial según la vista (línea 150-152)
      const eliminarButtons = await driver.findElements(By.css('button.eliminar, button#eliminar'));
      if (eliminarButtons.length > 0) {
        // Buscar el botón que corresponde al administrador principal
        // Según la vista, el administrador principal tiene id_usuario = 2
        // Intentar encontrar el botón que está en la fila con el ícono de verificación
        let eliminarBtn = null;
        for (const btn of eliminarButtons) {
          // Verificar si está cerca de un ícono de verificación (administrador principal)
          try {
            const parentRow = await btn.findElement(By.xpath("./ancestor::tr"));
            const iconos = await parentRow.findElements(By.css('i.fa-circle-check'));
            if (iconos.length > 0) {
              eliminarBtn = btn;
              break;
            }
          } catch (e) {
            // Continuar buscando
          }
        }
        // Si no se encuentra por el ícono, usar el primer botón
        if (!eliminarBtn && eliminarButtons.length > 0) {
          eliminarBtn = eliminarButtons[0];
        }
        if (eliminarBtn) {
          await driver.executeScript("arguments[0].scrollIntoView({block: 'center', behavior: 'smooth'});", eliminarBtn);
          await driver.sleep(500);
          await driver.wait(until.elementIsVisible(eliminarBtn), 10000);
          await eliminarBtn.click();
          await driver.sleep(2000);
          console.log('Botón de eliminar del administrador principal clickeado.');
          testSteps.push('Botón de eliminar clickeado');
        } else {
          throw new Error('No se encontró el botón de eliminar del administrador principal');
        }
      } else {
        throw new Error('No se encontró ningún botón de eliminar');
      }
    } catch (e) {
      throw new Error('Error al hacer clic en eliminar: ' + e.message);
    }

    // Verificar alerta "¿Desea eliminar a este usuario?"
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
      console.log('No se encontró alerta de confirmación, continuando...');
    }

    // === Paso 6: Hacer clic en "Si, Eliminar" ===
    testSteps.push('Hacer clic en Si, Eliminar');
    console.log('Buscando botón Si, Eliminar...');
    
    try {
      await driver.wait(until.alertIsPresent(), 5000);
      const alert = await driver.switchTo().alert();
      const alertText = await alert.getText();
      if (alertText.includes('Desea eliminar') || alertText.includes('usuario')) {
        await alert.accept();
        await driver.sleep(2000);
        console.log('Confirmación aceptada.');
        testSteps.push('Confirmación aceptada');
      } else {
        await alert.accept();
      }
    } catch (e) {
      try {
        const siBtn = await driver.findElement(By.xpath("//button[contains(text(), 'Si, Eliminar') or contains(text(), 'Sí, Eliminar')]"));
        await siBtn.click();
        await driver.sleep(2000);
        console.log('Botón Si, Eliminar clickeado.');
        testSteps.push('Botón Si, Eliminar clickeado');
      } catch (e2) {
        try {
          const confirmBtn = await driver.findElement(By.css('.swal2-confirm'));
          await confirmBtn.click();
          await driver.sleep(2000);
          console.log('Botón de confirmación clickeado.');
          testSteps.push('Confirmación aceptada');
        } catch (e3) {
          console.log('No se encontró botón de confirmación...');
        }
      }
    }

    // === Verificar mensaje de error ===
    testSteps.push('Verificar mensaje de error');
    console.log('Verificando mensaje de error...');
    
    try {
      await driver.wait(until.alertIsPresent(), 5000);
      const alert = await driver.switchTo().alert();
      const alertText = await alert.getText();
      if (alertText.includes('No se puede eliminar') && alertText.includes('administrador')) {
        await alert.accept();
        await driver.sleep(2000);
        console.log('Mensaje de error confirmado: ' + alertText);
        testSteps.push('Mensaje de error: ' + alertText);
        notes = 'Correctamente se denegó la eliminación del administrador principal. Alerta: ' + alertText;
        status = 'p';
      } else {
        await alert.accept();
        throw new Error('Mensaje de error no encontrado. Alerta recibida: ' + alertText);
      }
    } catch (e) {
      if (e.message.includes('timeout') || e.message.includes('no such alert')) {
        try {
          await driver.wait(until.elementLocated(By.css('.swal2-error, .swal2-title, .alert-danger')), 10000);
          const errorElement = await driver.findElement(By.css('.swal2-title, .alert-danger'));
          const errorText = await errorElement.getText();
          if (errorText.includes('No se puede eliminar') || errorText.includes('administrador')) {
            console.log('Mensaje de error encontrado en la página: ' + errorText);
            notes = 'Correctamente se denegó la eliminación del administrador principal. Mensaje: ' + errorText;
            status = 'p';
            
            try {
              const confirmBtn = await driver.findElement(By.css('.swal2-confirm'));
              await confirmBtn.click();
              await driver.sleep(1000);
            } catch (e2) {
              // Ignorar
            }
          } else {
            throw new Error('No se encontró el mensaje de error esperado');
          }
        } catch (e2) {
          throw new Error('No se encontró alerta ni mensaje de error. Error: ' + e.message);
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

