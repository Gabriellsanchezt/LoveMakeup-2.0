// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = 'Prueba-93';
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/proyectoIII/Proyecto-III/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: REGISTRAR USUARIO - CÉDULA REGISTRADA ===
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

    // === Paso 5: Hacer clic en "registrar" ===
    testSteps.push('Hacer clic en registrar');
    console.log('Buscando botón registrar...');
    
    try {
      const registrarBtn = await driver.findElement(By.css('button.registrar, button[data-bs-target="#registro"]'));
      await driver.wait(until.elementIsVisible(registrarBtn), 10000);
      await driver.wait(until.elementIsEnabled(registrarBtn), 10000);
      await registrarBtn.click();
      await driver.sleep(2000);
      console.log('Botón registrar clickeado.');
      testSteps.push('Botón registrar clickeado');
    } catch (e) {
      try {
        const registrarBtn = await driver.findElement(By.xpath("//button[contains(text(), 'Registrar')]"));
        await registrarBtn.click();
        await driver.sleep(2000);
        console.log('Botón registrar clickeado (selector alternativo).');
        testSteps.push('Botón registrar clickeado');
      } catch (e2) {
        throw new Error('No se encontró el botón registrar');
      }
    }

    await driver.sleep(1000);

    // === Paso 6: Ingresar datos del formulario ===
    testSteps.push('Ingresar datos del formulario');
    console.log('Llenando datos del formulario...');
    
    // Nombre
    const nombreInput = await driver.findElement(By.id('nombre'));
    await nombreInput.clear();
    await nombreInput.sendKeys('Eduardo');
    await driver.sleep(500);

    // Apellido
    const apellidoInput = await driver.findElement(By.id('apellido'));
    await apellidoInput.clear();
    await apellidoInput.sendKeys('Rojas');
    await driver.sleep(500);

    // Cédula (ya registrada: 10200300)
    const cedulaInput = await driver.findElement(By.id('cedula'));
    await cedulaInput.clear();
    await cedulaInput.sendKeys('10200300');
    await driver.sleep(500);

    // ROL - Seleccionar "Administrador - Nivel 3"
    const rolSelect = await driver.findElement(By.id('rolSelect'));
    const options = await rolSelect.findElements(By.tagName('option'));
    for (const option of options) {
      const optionText = await option.getText();
      if (optionText.includes('Administrador') && optionText.includes('Nivel 3')) {
        await option.click();
        await driver.sleep(500);
        break;
      }
    }
    await driver.sleep(500);

    // Teléfono
    const telefonoInput = await driver.findElement(By.id('telefono'));
    await telefonoInput.clear();
    await telefonoInput.sendKeys('0412-4279329');
    await driver.sleep(500);

    // Correo
    const correoInput = await driver.findElement(By.id('correo'));
    await correoInput.clear();
    await correoInput.sendKeys('eduardo.rojas@gmail.com');
    await driver.sleep(500);

    // Contraseña
    const passwordInput2 = await driver.findElement(By.id('clave'));
    await passwordInput2.clear();
    await passwordInput2.sendKeys('lara1234');
    await driver.sleep(500);

    // Confirmar Contraseña
    const confirmPasswordInput = await driver.findElement(By.id('confirmar_clave'));
    await confirmPasswordInput.clear();
    await confirmPasswordInput.sendKeys('lara1234');
    await driver.sleep(500);

    // === Paso 7: Hacer clic en Registrar ===
    testSteps.push('Hacer clic en Registrar');
    console.log('Haciendo clic en Registrar...');
    
    const registrarBtn2 = await driver.findElement(By.id('registrar'));
    await driver.wait(until.elementIsEnabled(registrarBtn2), 10000);
    await registrarBtn2.click();
    await driver.sleep(3000);

    // Verificar SweetAlert "¿Deseas Registrar este usuario?"
    try {
      await driver.wait(until.elementLocated(By.css('.swal2-popup')), 10000);
      const swalTitle = await driver.findElement(By.css('.swal2-title'));
      const titleText = await swalTitle.getText();
      if (titleText.includes('Deseas Registrar') || titleText.includes('usuario')) {
        console.log('Alerta de confirmación encontrada: ' + titleText);
        testSteps.push('Alerta ¿Deseas Registrar este usuario? encontrada');
        
        // Hacer clic en "Si, registrar"
        const confirmBtn = await driver.findElement(By.css('.swal2-confirm'));
        await confirmBtn.click();
        await driver.sleep(3000);
        testSteps.push('Confirmación aceptada (clic en Si, registrar)');
      }
    } catch (e) {
      console.log('No se encontró SweetAlert de confirmación, continuando...');
    }

    // === Paso 8: Verificar mensaje de error "la cedula ya esta registrada" ===
    testSteps.push('Verificar mensaje de error');
    console.log('Verificando mensaje de error...');
    
    try {
      // Esperar SweetAlert de error
      await driver.wait(until.elementLocated(By.css('.swal2-popup')), 10000);
      const swalTitle = await driver.findElement(By.css('.swal2-title'));
      const titleText = await swalTitle.getText();
      console.log('Título del mensaje: ' + titleText);
      
      let messageText = '';
      try {
        const swalContent = await driver.findElement(By.css('.swal2-html-container, .swal2-content'));
        messageText = await swalContent.getText();
        console.log('Contenido del mensaje: ' + messageText);
      } catch (e) {
        // Si no hay contenido, usar solo el título
      }
      
      const fullText = titleText + ' ' + messageText;
      if (fullText.includes('cedula') && (fullText.includes('ya esta registrada') || fullText.includes('ya está registrada'))) {
        // Cerrar el SweetAlert
        try {
          const confirmBtn = await driver.findElement(By.css('.swal2-confirm'));
          await confirmBtn.click();
          await driver.sleep(1000);
        } catch (e) {
          await driver.sleep(2000);
        }
        console.log('Mensaje de error confirmado correctamente.');
        testSteps.push('Mensaje de error: ' + titleText);
        notes = 'Correctamente se detectó que la cédula ya está registrada. Mensaje: ' + titleText;
        status = 'p';
      } else {
        // Verificar también el alert en la página (id="alertcedula")
        try {
          const alertCedula = await driver.findElement(By.id('alertcedula'));
          const alertStyle = await alertCedula.getCssValue('display');
          if (alertStyle !== 'none') {
            const alertText = await alertCedula.getText();
            if (alertText.includes('cedula') && alertText.includes('registrada')) {
              console.log('Alerta de cédula registrada encontrada en la página: ' + alertText);
              notes = 'Correctamente se detectó que la cédula ya está registrada. Alerta: ' + alertText;
              status = 'p';
            }
          }
        } catch (e) {
          throw new Error('Mensaje de error no encontrado. Mensaje recibido: ' + titleText);
        }
      }
    } catch (e) {
      if (e.message.includes('timeout') || e.message.includes('Unable to locate')) {
        // Verificar alerta en la página
        try {
          const alertCedula = await driver.findElement(By.id('alertcedula'));
          const alertStyle = await alertCedula.getCssValue('display');
          if (alertStyle !== 'none') {
            const alertText = await alertCedula.getText();
            if (alertText.includes('cedula') && alertText.includes('registrada')) {
              console.log('Alerta de cédula registrada encontrada: ' + alertText);
              notes = 'Correctamente se detectó que la cédula ya está registrada. Alerta: ' + alertText;
              status = 'p';
            } else {
              throw new Error('No se encontró el mensaje de error esperado');
            }
          } else {
            throw new Error('No se encontró SweetAlert ni alerta en la página. Error: ' + e.message);
          }
        } catch (e2) {
          throw new Error('No se encontró mensaje de error. Error: ' + e.message);
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

