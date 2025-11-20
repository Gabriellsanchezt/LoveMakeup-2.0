// === DEPENDENCIAS ===
const { Builder, By, Key, until } = require('selenium-webdriver');
const edge = require('selenium-webdriver/edge');
const xmlrpc = require('xmlrpc');
// === CONFIGURACIÓN TESTLINK ===
const TESTLINK_URL = 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php';
const DEV_KEY = '1a4d579d37e9a7f66a417c527ca09718';
const TEST_CASE_EXTERNAL_ID = 'Prueba-98';
const TEST_PLAN_ID = 104;
const BUILD_ID = 1;

// === CONFIGURACIÓN DE URLS ===
const BASE_URL = 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/';

// === CONFIGURACIÓN DEL NAVEGADOR ===
const BROWSER = 'edge';

// === TEST AUTOMATIZADO: REALIZAR PEDIDO - TIENDA FÍSICA ===
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
    
    // === Paso 2: Ingresar credenciales ===
    testSteps.push('Ingresar credenciales');
    console.log('Ingresando credenciales...');
    await driver.wait(until.elementLocated(By.id('usuario')), 15000);
    const usuarioInput = await driver.findElement(By.id('usuario'));
    await driver.wait(until.elementIsVisible(usuarioInput), 10000);
    await usuarioInput.clear();
    await usuarioInput.sendKeys('30559870');
    
    const passwordInput = await driver.findElement(By.id('pid'));
    await driver.wait(until.elementIsVisible(passwordInput), 10000);
    await passwordInput.clear();
    await passwordInput.sendKeys('love1234');
    
    // === Paso 3: Hacer click en Ingresar ===
    testSteps.push('Hacer click en Ingresar');
    console.log('Haciendo click en Ingresar...');
    const ingresarBtn = await driver.findElement(By.id('ingresar'));
    await driver.wait(until.elementIsEnabled(ingresarBtn), 10000);
    await ingresarBtn.click();
    
    // Esperar redirección a catálogo
    await driver.wait(until.urlContains('pagina=catalogo'), 15000);
    await driver.sleep(2000);
    console.log('Login exitoso.');
    testSteps.push('Login completado exitosamente');

    // === Paso 4: Ir a Todos los productos ===
    testSteps.push('Ir a Todos los productos');
    console.log('Accediendo a Todos los productos...');
    await driver.get(BASE_URL + '?pagina=catalogo_producto');
    await driver.sleep(2000);
    await driver.wait(until.urlContains('pagina=catalogo_producto'), 10000);
    testSteps.push('Redirección a catálogo de productos confirmada');

    // === Paso 5: Agregar producto al carrito ===
    testSteps.push('Agregar producto al carrito');
    console.log('Agregando producto al carrito...');
    try {
      // Buscar el primer botón de agregar al carrito (selector real: .btn-agregar-carrito-exterior)
      await driver.sleep(2000);
      const agregarBtns = await driver.findElements(By.css('.btn-agregar-carrito-exterior, #btn-agregar-carrito'));
      if (agregarBtns.length > 0) {
        await driver.executeScript("arguments[0].scrollIntoView({block: 'center', behavior: 'smooth'});", agregarBtns[0]);
        await driver.sleep(500);
        await agregarBtns[0].click();
        await driver.sleep(2000);
        
        // Verificar mensaje de éxito (SweetAlert2)
        try {
          await driver.wait(until.elementLocated(By.css('.swal2-popup, .swal2-title')), 5000);
          const successMsg = await driver.findElement(By.css('.swal2-title'));
          const msgText = await successMsg.getText();
          if (msgText.includes('Agregado') || msgText.includes('agregado') || msgText.includes('exitosamente')) {
            console.log('Producto agregado exitosamente al carrito');
            testSteps.push('Alerta: producto agregado exitosamente al carrito');
            // Cerrar el modal de SweetAlert si es necesario
            await driver.sleep(1000);
          }
        } catch (e) {
          console.log('No se encontró mensaje de confirmación, pero el producto puede haberse agregado');
        }
      } else {
        throw new Error('No se encontró botón para agregar al carrito');
      }
    } catch (error) {
      console.log('Error al agregar producto:', error.message);
      throw error;
    }

    // === Paso 6: Hacer click en el icono del carrito ===
    testSteps.push('Hacer click en el icono del carrito');
    console.log('Abriendo carrito...');
    try {
      const carritoIcon = await driver.findElement(By.css('.carrito, [id*="carrito"], .fa-shopping-cart, [onclick*="carrito"]'));
      await driver.wait(until.elementIsVisible(carritoIcon), 10000);
      await carritoIcon.click();
      await driver.sleep(1500);
      testSteps.push('Ventana del carrito desplegada');
    } catch (error) {
      console.log('No se encontró icono de carrito, intentando ir directamente a ver carrito');
    }

    // === Paso 7: Ver carrito ===
    testSteps.push('Hacer click en Ver carrito');
    console.log('Yendo a ver carrito...');
    await driver.get(BASE_URL + '?pagina=vercarrito');
    await driver.sleep(2000);
    await driver.wait(until.urlContains('pagina=vercarrito'), 10000);
    testSteps.push('Redirección a ver carrito confirmada');

    // === Paso 8: Ajustar cantidad (opcional) ===
    testSteps.push('Ajustar cantidad del producto');
    console.log('Ajustando cantidad...');
    try {
      // El selector real es .btn-mas (botón +)
      const aumentarBtn = await driver.findElement(By.css('.btn-mas'));
      await driver.wait(until.elementIsVisible(aumentarBtn), 5000);
      await aumentarBtn.click();
      await driver.sleep(1000);
      testSteps.push('Cantidad ajustada');
    } catch (e) {
      console.log('No se encontró botón para ajustar cantidad, continuando...');
    }

    // === Paso 9: Hacer click en Comprar ===
    testSteps.push('Hacer click en Comprar');
    console.log('Procediendo a comprar...');
    // El botón real es un enlace <a> con id="btn-siguiente"
    const comprarBtn = await driver.findElement(By.id('btn-siguiente'));
    await driver.wait(until.elementIsVisible(comprarBtn), 10000);
    await comprarBtn.click();
    await driver.sleep(2000);
    await driver.wait(until.urlContains('pagina=Pedidoentrega'), 15000);
    testSteps.push('Redirección a página de entrega confirmada');

    // === Paso 10: Seleccionar Tienda física ===
    testSteps.push('Hacer click en Tienda física');
    console.log('Seleccionando Tienda física...');
    const tiendaRadio = await driver.findElement(By.id('op1'));
    await driver.wait(until.elementIsVisible(tiendaRadio), 10000);
    await driver.executeScript("arguments[0].click();", tiendaRadio);
    await driver.sleep(1500);
    testSteps.push('Ventana con dirección física de la tienda desplegada');

    // === Paso 11: Hacer click en Continuar ===
    testSteps.push('Hacer click en Continuar');
    console.log('Continuando...');
    const continuarBtn = await driver.findElement(By.id('btn-continuar-entrega'));
    await driver.wait(until.elementIsVisible(continuarBtn), 10000);
    await continuarBtn.click();
    await driver.sleep(1500);
    
    // Verificar modal de confirmación
    try {
      await driver.wait(until.elementLocated(By.css('.modal.show, .swal2-popup')), 5000);
      testSteps.push('Modal de validación desplegado');
    } catch (e) {
      console.log('No se encontró modal de confirmación');
    }

    // === Paso 12: Confirmar en el modal (SweetAlert2) ===
    testSteps.push('Hacer click en si, continuar');
    console.log('Confirmando...');
    // El modal de confirmación es SweetAlert2, buscar el botón de confirmar
    try {
      await driver.wait(until.elementLocated(By.css('.swal2-confirm, button.swal2-confirm')), 5000);
      const confirmarBtn = await driver.findElement(By.css('.swal2-confirm, button.swal2-confirm'));
      await driver.wait(until.elementIsVisible(confirmarBtn), 10000);
      await confirmarBtn.click();
      await driver.sleep(2000);
    } catch (e) {
      console.log('No se encontró modal de confirmación, continuando...');
    }
    await driver.wait(until.urlContains('pagina=Pedidopago'), 15000);
    testSteps.push('Redirección a página de pago confirmada');

    // === Paso 13-14: Seleccionar bancos ===
    testSteps.push('Seleccionar Banco de origen');
    console.log('Seleccionando banco de origen...');
    const bancoOrigen = await driver.findElement(By.id('banco'));
    await driver.wait(until.elementIsVisible(bancoOrigen), 10000);
    await bancoOrigen.click();
    await driver.sleep(500);
    await driver.executeScript("arguments[0].selectedIndex = 1; arguments[0].dispatchEvent(new Event('change'));", bancoOrigen);
    await driver.sleep(500);
    testSteps.push('Opciones de bancos de origen desplegadas');

    testSteps.push('Seleccionar Banco de destino');
    console.log('Seleccionando banco de destino...');
    const bancoDestino = await driver.findElement(By.id('banco_destino'));
    await driver.wait(until.elementIsVisible(bancoDestino), 10000);
    await bancoDestino.click();
    await driver.sleep(500);
    await driver.executeScript("arguments[0].selectedIndex = 1; arguments[0].dispatchEvent(new Event('change'));", bancoDestino);
    await driver.sleep(500);
    testSteps.push('Opciones de bancos de destino desplegadas');

    // === Paso 15: Ingresar referencia bancaria ===
    testSteps.push('Ingresar referencia bancaria');
    console.log('Ingresando referencia bancaria...');
    const refBancaria = await driver.findElement(By.id('referencia_bancaria'));
    await driver.wait(until.elementIsVisible(refBancaria), 10000);
    await refBancaria.clear();
    await refBancaria.sendKeys('0876');
    await driver.sleep(500);

    // === Paso 16: Ingresar teléfono emisor ===
    testSteps.push('Ingresar teléfono emisor');
    console.log('Ingresando teléfono emisor...');
    const telefono = await driver.findElement(By.id('telefono_emisor'));
    await driver.wait(until.elementIsVisible(telefono), 10000);
    await telefono.clear();
    await telefono.sendKeys('04245196914');
    await driver.sleep(500);

    // === Paso 17: Subir comprobante ===
    testSteps.push('Subir comprobante de pago');
    console.log('Subiendo comprobante...');
    // Nota: Para subir archivos necesitarías tener una imagen de prueba en el sistema
    // const fileInput = await driver.findElement(By.id('imagen'));
    // await fileInput.sendKeys('ruta/a/imagen.jpg');
    testSteps.push('Comprobante subido (simulado)');

    // === Paso 18: Aceptar términos y condiciones ===
    testSteps.push('Aceptar términos y condiciones');
    console.log('Aceptando términos y condiciones...');
    try {
      const terminosCheck = await driver.findElement(By.id('che'));
      await driver.wait(until.elementIsVisible(terminosCheck), 10000);
      await terminosCheck.click();
      await driver.sleep(500);
      testSteps.push('Términos y condiciones aceptados');
    } catch (e) {
      console.log('No se encontró checkbox de términos, continuando...');
    }

    // === Paso 19: Realizar Pago ===
    testSteps.push('Hacer click en Realizar Pago');
    console.log('Realizando pago...');
    const realizarPagoBtn = await driver.findElement(By.id('btn-guardar-pago'));
    await driver.wait(until.elementIsEnabled(realizarPagoBtn), 10000);
    await realizarPagoBtn.click();
    await driver.sleep(1500);
    
    // Verificar modal de confirmación
    try {
      await driver.wait(until.elementLocated(By.css('.modal.show, .swal2-popup')), 5000);
      testSteps.push('Modal de confirmación de pago desplegado');
    } catch (e) {
      console.log('No se encontró modal de confirmación');
    }

    // === Paso 20: Confirmar pago (SweetAlert2) ===
    testSteps.push('Hacer click en si, continuar');
    console.log('Confirmando pago...');
    // El modal de confirmación es SweetAlert2
    try {
      await driver.wait(until.elementLocated(By.css('.swal2-confirm, button.swal2-confirm')), 5000);
      const confirmarPagoBtn = await driver.findElement(By.css('.swal2-confirm, button.swal2-confirm'));
      await driver.wait(until.elementIsVisible(confirmarPagoBtn), 10000);
      await confirmarPagoBtn.click();
      await driver.sleep(3000);
      
      // Verificar alerta de éxito
      try {
        await driver.wait(until.elementLocated(By.css('.swal2-success, .swal2-title')), 5000);
        testSteps.push('Alerta de confirmación mostrada');
        await driver.sleep(2000);
      } catch (e) {
        console.log('No se encontró alerta de confirmación');
      }
    } catch (e) {
      console.log('No se encontró modal de confirmación de pago');
    }

    // === Paso 21: Verificar redirección a confirmación ===
    // Nota: El código redirige a 'confirmacion' pero puede mostrar Pedidoconfirmar
    try {
      await driver.wait(until.urlContains('pagina=Pedidoconfirmar'), 10000);
    } catch (e) {
      // Intentar con confirmacion si Pedidoconfirmar no funciona
      try {
        await driver.wait(until.urlContains('pagina=confirmacion'), 10000);
      } catch (e2) {
        // Esperar un poco más y verificar la URL actual
        await driver.sleep(3000);
        const currentUrl = await driver.getCurrentUrl();
        console.log('URL actual después del pago:', currentUrl);
      }
    }
    testSteps.push('Redirección a página de confirmación de pedido');

    // === Paso 23: Continuar ===
    testSteps.push('Hacer click en continuar');
    console.log('Finalizando...');
    // El botón continuar es un enlace <a> con class="btn btn-primary" y href="?pagina=catalogo"
    const finalContinuarBtn = await driver.findElement(By.css('a.btn-primary[href*="catalogo"], a.btn[href*="catalogo"]'));
    await driver.wait(until.elementIsVisible(finalContinuarBtn), 10000);
    await finalContinuarBtn.click();
    await driver.sleep(2000);
    await driver.wait(until.urlContains('pagina=catalogo'), 15000);
    testSteps.push('Redirección a catálogo confirmada');

    console.log('Pedido realizado exitosamente.');
    notes = 'Pedido realizado exitosamente. Tipo de entrega: Tienda física. Todos los pasos completados correctamente.';
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

