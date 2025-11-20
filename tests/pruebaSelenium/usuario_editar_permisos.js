// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = 'Prueba-96';
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/proyectoIII/Proyecto-III/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: EDITAR PERMISOS ===
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

    // === Paso 5: Seleccionar un usuario y hacer clic en el botón naranja de editar permisos ===
    testSteps.push('Seleccionar un usuario y hacer clic en editar permisos');
    console.log('Buscando botón de editar permisos...');
    
    try {
      // Buscar el botón de editar permisos (clase "permisotur" o botón naranja/warning)
      const permisoButtons = await driver.findElements(By.css('button.permisotur, button.btn-warning[name="modificar"]'));
      if (permisoButtons.length > 0) {
        // Seleccionar el primer botón que no esté deshabilitado
        let permisoBtn = null;
        for (const btn of permisoButtons) {
          try {
            const isEnabled = await btn.isEnabled();
            if (isEnabled) {
              permisoBtn = btn;
              break;
            }
          } catch (e) {
            // Continuar buscando
          }
        }
        if (!permisoBtn && permisoButtons.length > 0) {
          permisoBtn = permisoButtons[0];
        }
        if (permisoBtn) {
          await driver.executeScript("arguments[0].scrollIntoView({block: 'center', behavior: 'smooth'});", permisoBtn);
          await driver.sleep(500);
          await driver.wait(until.elementIsVisible(permisoBtn), 10000);
          await permisoBtn.click();
          await driver.sleep(3000);
          console.log('Botón de editar permisos clickeado.');
          testSteps.push('Botón de editar permisos clickeado');
        } else {
          throw new Error('No se encontró ningún botón de editar permisos habilitado');
        }
      } else {
        throw new Error('No se encontró ningún botón de editar permisos');
      }
    } catch (e) {
      throw new Error('Error al hacer clic en editar permisos: ' + e.message);
    }

    // Verificar que se redirige a la página de permisos
    try {
      await driver.wait(until.urlContains('seguridad') || until.urlContains('permiso'), 10000);
      console.log('Página de permisos cargada correctamente.');
      testSteps.push('Página de permisos cargada');
    } catch (e) {
      console.log('Redirección no detectada automáticamente, continuando...');
    }
    await driver.sleep(2000);

    // === Paso 6: Seleccionar la columna "ver" con la fila "reporte" y hacer clic en el switch ===
    testSteps.push('Seleccionar switch de ver en la fila reporte');
    console.log('Buscando switch de ver en la fila reporte...');
    
    try {
      // Buscar la tabla de permisos
      const table = await driver.findElement(By.css('table.table-bordered, table.table-m'));
      const rows = await table.findElements(By.tagName('tr'));
      
      let reporteRow = null;
      for (const row of rows) {
        try {
          const rowText = await row.getText();
          if (rowText.toLowerCase().includes('reporte')) {
            reporteRow = row;
            break;
          }
        } catch (e) {
          // Continuar buscando
        }
      }
      
      if (reporteRow) {
        // Buscar el switch de "ver" en la columna correspondiente
        // La columna "ver" es la tercera columna (índice 2)
        const cells = await reporteRow.findElements(By.tagName('td'));
        if (cells.length >= 3) {
          // La celda de "ver" debería ser la tercera (índice 2)
          const verCell = cells[2];
          const switchInput = await verCell.findElement(By.css('input[type="checkbox"], .form-check-input'));
          const isChecked = await switchInput.isSelected();
          
          // Si no está marcado, hacer clic para marcarlo
          if (!isChecked) {
            await switchInput.click();
            await driver.sleep(500);
            console.log('Switch de ver en reporte activado.');
            testSteps.push('Switch de ver en reporte activado');
          } else {
            console.log('Switch de ver en reporte ya estaba activado.');
            testSteps.push('Switch de ver en reporte ya estaba activado');
          }
        } else {
          throw new Error('No se encontró la columna de ver en la fila reporte');
        }
      } else {
        throw new Error('No se encontró la fila de reporte en la tabla');
      }
    } catch (e) {
      throw new Error('Error al seleccionar el switch: ' + e.message);
    }

    // === Paso 7: Hacer clic en Guardar ===
    testSteps.push('Hacer clic en Guardar');
    console.log('Haciendo clic en Guardar...');
    
    try {
      const guardarBtn = await driver.findElement(By.id('actualizar_permisos'));
      await driver.wait(until.elementIsEnabled(guardarBtn), 10000);
      await guardarBtn.click();
      await driver.sleep(3000);
      console.log('Botón Guardar clickeado.');
      testSteps.push('Botón Guardar clickeado');
    } catch (e) {
      try {
        const guardarBtn = await driver.findElement(By.css('button.guardar, button[name="actualizar_permisos"]'));
        await guardarBtn.click();
        await driver.sleep(3000);
        console.log('Botón Guardar clickeado (selector alternativo).');
        testSteps.push('Botón Guardar clickeado');
      } catch (e2) {
        throw new Error('No se encontró el botón Guardar');
      }
    }

    // Verificar SweetAlert "¿Estás seguro? Esto actualizará los permisos del usuario."
    try {
      await driver.wait(until.elementLocated(By.css('.swal2-popup')), 10000);
      const swalTitle = await driver.findElement(By.css('.swal2-title'));
      const titleText = await swalTitle.getText();
      if (titleText.includes('Estás seguro') || titleText.includes('actualizará') || titleText.includes('permisos')) {
        console.log('Alerta de confirmación encontrada: ' + titleText);
        testSteps.push('Alerta ¿Estás seguro? encontrada');
        
        // Hacer clic en "Si, actualizar"
        const confirmBtn = await driver.findElement(By.css('.swal2-confirm'));
        await confirmBtn.click();
        await driver.sleep(3000);
        testSteps.push('Confirmación aceptada (clic en Si, actualizar)');
      }
    } catch (e) {
      console.log('No se encontró SweetAlert de confirmación, continuando...');
    }

    // === Paso 8: Verificar mensaje de éxito ===
    testSteps.push('Verificar mensaje de éxito');
    console.log('Verificando mensaje de éxito...');
    
    try {
      // Esperar SweetAlert de éxito
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
      if (fullText.includes('modificado') && fullText.includes('permiso') && 
          (fullText.includes('exitos') || fullText.includes('éxitos') || fullText.includes('exito') || fullText.includes('éxito'))) {
        // Cerrar el SweetAlert
        try {
          const confirmBtn = await driver.findElement(By.css('.swal2-confirm'));
          await confirmBtn.click();
          await driver.sleep(1000);
        } catch (e) {
          await driver.sleep(2000);
        }
        console.log('Mensaje de éxito confirmado correctamente.');
        testSteps.push('Mensaje de éxito: ' + titleText);
        notes = 'Permisos modificados exitosamente. Mensaje: ' + titleText;
        status = 'p';
      } else {
        throw new Error('Mensaje de éxito no encontrado. Mensaje recibido: ' + titleText);
      }
    } catch (e) {
      if (e.message.includes('timeout') || e.message.includes('Unable to locate')) {
        try {
          const successElement = await driver.findElement(By.css('.swal2-success, .swal2-title, .alert-success'));
          const successText = await successElement.getText();
          if (successText.includes('modificado') && successText.includes('permiso') && 
              (successText.includes('exitos') || successText.includes('éxitos'))) {
            console.log('Mensaje de éxito encontrado en la página: ' + successText);
            notes = 'Permisos modificados exitosamente. Mensaje: ' + successText;
            status = 'p';
          } else {
            throw new Error('No se encontró el mensaje de éxito esperado');
          }
        } catch (e2) {
          throw new Error('No se encontró SweetAlert ni mensaje de éxito. Error: ' + e.message);
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

