// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = 'Prueba-97';
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/proyectoIII/Proyecto-III/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: REGISTRAR USUARIO ===
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
      // Buscar por clase "registrar" o data-bs-target="#registro"
      const registrarBtn = await driver.findElement(By.css('button.registrar, button[data-bs-target="#registro"]'));
      await driver.wait(until.elementIsVisible(registrarBtn), 10000);
      await driver.wait(until.elementIsEnabled(registrarBtn), 10000);
      await registrarBtn.click();
      await driver.sleep(2000);
      console.log('Botón registrar clickeado.');
      testSteps.push('Botón registrar clickeado');
    } catch (e) {
      // Intentar con otros selectores
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

    // Verificar que se despliega el modal
    try {
      await driver.wait(until.elementLocated(By.css('.modal, [class*="modal"], [role="dialog"]')), 5000);
      console.log('Modal de registro encontrado.');
      testSteps.push('Modal de registro desplegado');
    } catch (e) {
      console.log('Modal no detectado automáticamente, continuando...');
    }
    await driver.sleep(1000);

    // === Paso 6: Ingresar datos del formulario ===
    testSteps.push('Ingresar datos del formulario');
    console.log('Llenando datos del formulario...');
    
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

    // Cédula
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

    // ROL - Seleccionar "Asesora de Venta - Nivel 2"
    try {
      const rolSelect = await driver.findElement(By.id('rolSelect'));
      await driver.wait(until.elementIsVisible(rolSelect), 10000);
      // Buscar la opción que contenga "Asesora de Venta" o "Nivel 2"
      const options = await rolSelect.findElements(By.tagName('option'));
      for (const option of options) {
        const optionText = await option.getText();
        if (optionText.includes('Asesora de Venta') || (optionText.includes('Nivel 2') && !optionText.includes('Nivel 3'))) {
          await option.click();
          await driver.sleep(500);
          console.log('ROL seleccionado: ' + optionText);
          testSteps.push('ROL seleccionado: ' + optionText);
          break;
        }
      }
    } catch (e) {
      console.log('No se pudo seleccionar ROL automáticamente, continuando...');
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
      const passwordInput = await driver.findElement(By.id('clave'));
      await driver.wait(until.elementIsVisible(passwordInput), 10000);
      await passwordInput.clear();
      await passwordInput.sendKeys('lara1234');
      console.log('Contraseña ingresada');
      testSteps.push('Contraseña ingresada');
    } catch (e) {
      const passwordInput = await driver.findElement(By.xpath("//input[@name='clave' or @id='clave']"));
      await passwordInput.clear();
      await passwordInput.sendKeys('lara1234');
      console.log('Contraseña ingresada');
    }
    await driver.sleep(500);

    // Confirmar Contraseña
    try {
      const confirmPasswordInput = await driver.findElement(By.id('confirmar_clave'));
      await driver.wait(until.elementIsVisible(confirmPasswordInput), 10000);
      await confirmPasswordInput.clear();
      await confirmPasswordInput.sendKeys('lara1234');
      console.log('Confirmar contraseña ingresada');
      testSteps.push('Confirmar contraseña ingresada');
    } catch (e) {
      const confirmPasswordInput = await driver.findElement(By.xpath("//input[@id='confirmar_clave']"));
      await confirmPasswordInput.clear();
      await confirmPasswordInput.sendKeys('lara1234');
      console.log('Confirmar contraseña ingresada');
    }
    await driver.sleep(500);

    // === Paso 7: Hacer clic en Registrar ===
    testSteps.push('Hacer clic en Registrar');
    console.log('Haciendo clic en Registrar...');
    
    try {
      const registrarBtn = await driver.findElement(By.id('registrar'));
      await driver.wait(until.elementIsEnabled(registrarBtn), 10000);
      await registrarBtn.click();
      await driver.sleep(3000);
    } catch (e) {
      const registrarBtn = await driver.findElement(By.xpath("//button[@id='registrar' or contains(text(), 'Registrar')]"));
      await registrarBtn.click();
      await driver.sleep(3000);
    }

    // Verificar alerta "¿Deseas Registrar este usuario?"
    try {
      await driver.wait(until.alertIsPresent(), 5000);
      const alert = await driver.switchTo().alert();
      const alertText = await alert.getText();
      if (alertText.includes('Deseas Registrar') || alertText.includes('usuario') || alertText.includes('permisos')) {
        console.log('Alerta de confirmación encontrada: ' + alertText);
        testSteps.push('Alerta ¿Deseas Registrar este usuario? encontrada');
      } else {
        await alert.accept();
      }
    } catch (e) {
      console.log('No se encontró alerta de confirmación, continuando...');
    }

    // === Paso 8: Hacer clic en "Si, registrar" ===
    testSteps.push('Hacer clic en Si, registrar');
    console.log('Buscando botón Si, registrar...');
    
    try {
      await driver.wait(until.alertIsPresent(), 5000);
      const alert = await driver.switchTo().alert();
      const alertText = await alert.getText();
      if (alertText.includes('Deseas Registrar') || alertText.includes('usuario')) {
        await alert.accept(); // Aceptar la alerta (equivalente a hacer clic en SI)
        await driver.sleep(2000);
        console.log('Confirmación aceptada.');
        testSteps.push('Confirmación aceptada (clic en Si, registrar)');
      } else {
        await alert.accept();
      }
    } catch (e) {
      // Intentar buscar botón SI en la página
      try {
        const siBtn = await driver.findElement(By.xpath("//button[contains(text(), 'Si, registrar') or contains(text(), 'Sí, registrar')]"));
        await siBtn.click();
        await driver.sleep(2000);
        console.log('Botón Si, registrar clickeado.');
        testSteps.push('Botón Si, registrar clickeado');
      } catch (e2) {
        console.log('No se encontró botón Si, registrar, asumiendo que la confirmación se hizo con la alerta...');
      }
    }

    // === Verificar mensaje de éxito ===
    testSteps.push('Verificar mensaje de éxito');
    console.log('Verificando mensaje de éxito...');
    
    try {
      await driver.wait(until.alertIsPresent(), 5000);
      const alert = await driver.switchTo().alert();
      const alertText = await alert.getText();
      if (alertText.includes('registrado') && (alertText.includes('exito') || alertText.includes('éxito'))) {
        await alert.accept();
        await driver.sleep(2000);
        console.log('Mensaje de éxito confirmado: ' + alertText);
        testSteps.push('Mensaje de éxito: ' + alertText);
        notes = 'Usuario registrado exitosamente. Alerta: ' + alertText;
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
          if (successText.includes('registrado') || successText.includes('exito') || successText.includes('éxito')) {
            console.log('Mensaje de éxito encontrado en la página: ' + successText);
            notes = 'Usuario registrado exitosamente. Mensaje: ' + successText;
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
          throw new Error('No se encontró alerta ni mensaje de éxito. Error: ' + e.message);
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

