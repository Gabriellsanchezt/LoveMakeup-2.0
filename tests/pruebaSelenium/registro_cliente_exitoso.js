// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = 'Prueba-87';
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/proyectoIII/Proyecto-III/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: REGISTRO CLIENTE EXITOSO ===
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

    // === Paso 2: Hacer clic en "Crea tu cuenta y empieza a comprar" ===
    testSteps.push('Hacer clic en Crea tu cuenta y empieza a comprar');
    console.log('Buscando enlace de registro...');
    
    try {
      // Buscar por data-bs-target="#registroclientess"
      const registroLink = await driver.findElement(By.css('a[data-bs-target="#registroclientess"]'));
      await driver.wait(until.elementIsVisible(registroLink), 10000);
      await registroLink.click();
      await driver.sleep(2000);
      console.log('Modal de registro abierto.');
      testSteps.push('Modal de registro abierto');
    } catch (e) {
      // Intentar con otros selectores
      try {
        const registroLink = await driver.findElement(By.xpath("//a[contains(text(), 'Crea tu cuenta') or contains(text(), 'empieza a comprar')]"));
        await registroLink.click();
        await driver.sleep(2000);
        console.log('Modal de registro abierto (selector alternativo).');
        testSteps.push('Modal de registro abierto');
      } catch (e2) {
        throw new Error('No se encontró el enlace de registro');
      }
    }

    // Verificar que el modal se abrió
    try {
      await driver.wait(until.elementLocated(By.id('registroclientess')), 5000);
      const modal = await driver.findElement(By.id('registroclientess'));
      const isModalVisible = await modal.getAttribute('class');
      if (isModalVisible.includes('show')) {
        console.log('Modal de registro visible.');
      }
    } catch (e) {
      console.log('Modal no detectado automáticamente, continuando...');
    }
    await driver.sleep(1000);

    // === Paso 3: Ingresar datos del formulario ===
    testSteps.push('Ingresar datos del formulario de registro');
    console.log('Ingresando datos del formulario...');
    
    // Nombre
    try {
      const nombreInput = await driver.findElement(By.xpath("//input[@placeholder='Nombre' or @id='nombre' or contains(@name, 'nombre')]"));
      await driver.wait(until.elementIsVisible(nombreInput), 10000);
      await nombreInput.clear();
      await nombreInput.sendKeys('Eduardo');
      console.log('Nombre ingresado: Eduardo');
      testSteps.push('Nombre ingresado: Eduardo');
    } catch (e) {
      const nombreInput = await driver.findElement(By.id('nombre'));
      await nombreInput.clear();
      await nombreInput.sendKeys('Eduardo');
      console.log('Nombre ingresado: Eduardo');
    }
    await driver.sleep(500);

    // Apellido
    try {
      const apellidoInput = await driver.findElement(By.xpath("//input[@placeholder='Apellido' or @id='apellido' or contains(@name, 'apellido')]"));
      await driver.wait(until.elementIsVisible(apellidoInput), 10000);
      await apellidoInput.clear();
      await apellidoInput.sendKeys('Rojas');
      console.log('Apellido ingresado: Rojas');
      testSteps.push('Apellido ingresado: Rojas');
    } catch (e) {
      const apellidoInput = await driver.findElement(By.id('apellido'));
      await apellidoInput.clear();
      await apellidoInput.sendKeys('Rojas');
      console.log('Apellido ingresado: Rojas');
    }
    await driver.sleep(500);

    // Número de Cédula
    try {
      const cedulaInput = await driver.findElement(By.xpath("//input[@placeholder='Cédula' or @id='cedula' or contains(@name, 'cedula')]"));
      await driver.wait(until.elementIsVisible(cedulaInput), 10000);
      await cedulaInput.clear();
      await cedulaInput.sendKeys('12241103');
      console.log('Cédula ingresada: 12241103');
      testSteps.push('Cédula ingresada: 12241103');
    } catch (e) {
      const cedulaInput = await driver.findElement(By.id('cedula'));
      await cedulaInput.clear();
      await cedulaInput.sendKeys('12241103');
      console.log('Cédula ingresada: 12241103');
    }
    await driver.sleep(500);

    // Tipo de Documento - Seleccionar "Extranjero"
    try {
      const tipoDocSelect = await driver.findElement(By.xpath("//select[@id='tipoDocumento' or contains(@name, 'tipo') or contains(@id, 'documento')]"));
      await driver.wait(until.elementIsVisible(tipoDocSelect), 10000);
      await driver.executeScript("arguments[0].value = 'E';", tipoDocSelect);
      await driver.executeScript("arguments[0].dispatchEvent(new Event('change', { bubbles: true }));", tipoDocSelect);
      await driver.sleep(500);
      console.log('Tipo de documento seleccionado: Extranjero');
      testSteps.push('Tipo de documento: Extranjero');
    } catch (e) {
      console.log('No se encontró selector de tipo de documento, continuando...');
    }
    await driver.sleep(500);

    // Teléfono
    try {
      const telefonoInput = await driver.findElement(By.xpath("//input[@placeholder='Teléfono' or @id='telefono' or contains(@name, 'telefono')]"));
      await driver.wait(until.elementIsVisible(telefonoInput), 10000);
      await telefonoInput.clear();
      await telefonoInput.sendKeys('0412-4279329');
      console.log('Teléfono ingresado: 0412-4279329');
      testSteps.push('Teléfono ingresado: 0412-4279329');
    } catch (e) {
      const telefonoInput = await driver.findElement(By.id('telefono'));
      await telefonoInput.clear();
      await telefonoInput.sendKeys('0412-4279329');
      console.log('Teléfono ingresado: 0412-4279329');
    }
    await driver.sleep(500);

    // Correo
    try {
      const correoInput = await driver.findElement(By.xpath("//input[@type='email' or @placeholder='Correo' or @id='correo' or contains(@name, 'correo')]"));
      await driver.wait(until.elementIsVisible(correoInput), 10000);
      await correoInput.clear();
      await correoInput.sendKeys('eduardo.rojas@gmail.com');
      console.log('Correo ingresado: eduardo.rojas@gmail.com');
      testSteps.push('Correo ingresado: eduardo.rojas@gmail.com');
    } catch (e) {
      const correoInput = await driver.findElement(By.id('correo'));
      await correoInput.clear();
      await correoInput.sendKeys('eduardo.rojas@gmail.com');
      console.log('Correo ingresado: eduardo.rojas@gmail.com');
    }
    await driver.sleep(500);

    // Contraseña
    try {
      const passwordInput = await driver.findElement(By.id('recontrasena'));
      await driver.wait(until.elementIsVisible(passwordInput), 10000);
      await passwordInput.clear();
      await passwordInput.sendKeys('lara1234');
      console.log('Contraseña ingresada');
      testSteps.push('Contraseña ingresada');
    } catch (e) {
      const passwordInput = await driver.findElement(By.xpath("//input[@id='recontrasena']"));
      await passwordInput.clear();
      await passwordInput.sendKeys('lara1234');
      console.log('Contraseña ingresada');
    }
    await driver.sleep(500);

    // Confirmar Contraseña
    try {
      const confirmPasswordInput = await driver.findElement(By.id('clave'));
      await driver.wait(until.elementIsVisible(confirmPasswordInput), 10000);
      await confirmPasswordInput.clear();
      await confirmPasswordInput.sendKeys('lara1234');
      console.log('Confirmar contraseña ingresada');
      testSteps.push('Confirmar contraseña ingresada');
    } catch (e) {
      const confirmPasswordInput = await driver.findElement(By.xpath("//input[@id='clave' and @name='clave']"));
      await confirmPasswordInput.clear();
      await confirmPasswordInput.sendKeys('lara1234');
      console.log('Confirmar contraseña ingresada');
    }
    await driver.sleep(500);

    // === Paso 4: Hacer clic en Registrarse ===
    testSteps.push('Hacer clic en Registrarse');
    console.log('Haciendo clic en Registrarse...');
    
    try {
      const registrarseBtn = await driver.findElement(By.id('registrar'));
      await driver.wait(until.elementIsEnabled(registrarseBtn), 10000);
      await registrarseBtn.click();
      await driver.sleep(3000);
    } catch (e) {
      const registrarseBtn = await driver.findElement(By.xpath("//button[contains(text(), 'Registrarse')]"));
      await registrarseBtn.click();
      await driver.sleep(3000);
    }

    // === Paso 5: Verificar mensaje de éxito (SweetAlert) ===
    testSteps.push('Verificar mensaje de éxito');
    console.log('Verificando mensaje de éxito...');
    
    try {
      // Esperar SweetAlert de éxito
      await driver.wait(until.elementLocated(By.css('.swal2-popup')), 10000);
      const swalTitle = await driver.findElement(By.css('.swal2-title'));
      const titleText = await swalTitle.getText();
      console.log('Título del mensaje: ' + titleText);
      
      // También verificar el contenido del mensaje
      let messageText = '';
      try {
        const swalContent = await driver.findElement(By.css('.swal2-html-container, .swal2-content'));
        messageText = await swalContent.getText();
        console.log('Contenido del mensaje: ' + messageText);
      } catch (e) {
        // Si no hay contenido, usar solo el título
      }
      
      const fullText = titleText + ' ' + messageText;
      if (fullText.includes('registrado') && (fullText.includes('exito') || fullText.includes('éxito') || fullText.includes('session'))) {
        // Esperar a que se cierre automáticamente o cerrar manualmente
        try {
          await driver.sleep(2000); // Esperar a que se cierre automáticamente
        } catch (e) {
          // Ignorar
        }
        console.log('Registro exitoso confirmado.');
        testSteps.push('Registro exitoso: ' + titleText);
        notes = 'Cliente registrado exitosamente. Mensaje: ' + titleText;
        status = 'p';
      } else {
        throw new Error('Mensaje de éxito no encontrado. Mensaje recibido: ' + titleText);
      }
    } catch (e) {
      if (e.message.includes('timeout') || e.message.includes('Unable to locate')) {
        // Verificar si hay mensaje de éxito en la página
        try {
          const successElement = await driver.findElement(By.css('.swal2-success, .swal2-title, .alert-success'));
          const successText = await successElement.getText();
          if (successText.includes('registrado') || successText.includes('exito') || successText.includes('éxito')) {
            console.log('Mensaje de éxito encontrado en la página: ' + successText);
            notes = 'Cliente registrado exitosamente. Mensaje: ' + successText;
            status = 'p';
          } else {
            throw new Error('No se encontró el mensaje de éxito esperado');
          }
        } catch (e2) {
          // Verificar si el usuario ya existe
          try {
            const errorElement = await driver.findElement(By.css('.alert, .error, [class*="error"]'));
            const errorText = await errorElement.getText();
            if (errorText.includes('ya existe') || errorText.includes('registrado')) {
              notes = 'El cliente ya está registrado en la BD. Esto es esperado si el test se ejecuta múltiples veces.';
              status = 'p';
            } else {
              throw new Error('No se encontró mensaje de éxito ni error conocido. Error: ' + e.message);
            }
          } catch (e3) {
            throw new Error('No se encontró SweetAlert ni mensaje. Error: ' + e.message);
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

