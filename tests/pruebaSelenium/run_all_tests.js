// === SCRIPT PARA EJECUTAR TODAS LAS PRUEBAS DEL SUITE ===
const { spawn } = require('child_process');
const path = require('path');

// Lista de todos los tests del suite
const tests = [
  // Pruebas del módulo Compra
  { name: 'Registrar compra válida con un producto', file: 'compra_valida.js', id: 9 },
  { name: 'Registrar compra válida con varios productos', file: 'compra_varios_productos.js', id: 10 },
  { name: 'Registrar compra dejando campos vacíos', file: 'compra_campos_vacios.js', id: 11 },
  { name: 'Registrar compra con producto sin stock', file: 'compra_sin_stock.js', id: 12 },
  { name: 'Registrar compra que supera stock máximo', file: 'compra_stock_maximo.js', id: 13 },
  { name: 'Editar una compra correctamente', file: 'compra_editar.js', id: 14 },
  { name: 'Editar una compra con datos inválidos', file: 'compra_editar_invalido.js', id: 15 },
  { name: 'Ver detalles de una compra', file: 'compra_ver_detalles.js', id: 16 },
  // Pruebas del módulo Venta
  { name: 'Registrar venta válida con un solo producto', file: 'venta_valida.js', id: 1 },
  { name: 'Registrar venta válida con varios productos y métodos de pago', file: 'venta_varios_productos.js', id: 2 },
  { name: 'Registrar venta sin productos (inválido)', file: 'venta_sin_productos.js', id: 3 },
  { name: 'Registrar venta con cliente inexistente o inactivo (inválido)', file: 'venta_cliente_inexistente.js', id: 4 },
  { name: 'Registrar venta con producto sin stock suficiente (inválido)', file: 'venta_sin_stock.js', id: 5 },
  { name: 'Registrar venta con monto total inválido (0 o negativo)', file: 'venta_monto_invalido.js', id: 6 },
  { name: 'Registrar venta con método de pago inválido o incompleto', file: 'venta_metodo_pago_invalido.js', id: 7 },
  { name: 'Ver detalles completos de una venta', file: 'venta_ver_detalles.js', id: 8 }
];

// Resultados
const resultados = {
  total: tests.length,
  exitosos: 0,
  fallidos: 0,
  detalles: []
};

// Función para ejecutar un test
function ejecutarTest(test, index) {
  return new Promise((resolve) => {
    console.log(`\n[${index + 1}/${tests.length}] Ejecutando: ${test.name} (ID: ${test.id})`);
    console.log('='.repeat(60));
    
    const proceso = spawn('node', [test.file], {
      cwd: __dirname,
      stdio: 'inherit',
      shell: true
    });
    
    proceso.on('close', (code) => {
      const resultado = {
        test: test.name,
        id: test.id,
        archivo: test.file,
        exitCode: code,
        estado: code === 0 ? 'EXITOSO' : 'FALLIDO'
      };
      
      resultados.detalles.push(resultado);
      
      if (code === 0) {
        resultados.exitosos++;
        console.log(`\n✓ Test ${test.name} completado exitosamente`);
      } else {
        resultados.fallidos++;
        console.log(`\n✗ Test ${test.name} falló (código: ${code})`);
      }
      
      resolve(resultado);
    });
    
    proceso.on('error', (error) => {
      console.error(`Error al ejecutar ${test.name}:`, error.message);
      resultados.fallidos++;
      resultados.detalles.push({
        test: test.name,
        id: test.id,
        archivo: test.file,
        exitCode: -1,
        estado: 'ERROR',
        error: error.message
      });
      resolve();
    });
  });
}

// Función principal para ejecutar todos los tests
async function ejecutarTodosLosTests() {
  console.log('========================================');
  console.log('EJECUTANDO SUITE COMPLETA DE PRUEBAS');
  console.log('Módulos Compra y Venta - Pruebas Detalladas');
  console.log('========================================');
  console.log(`Total de pruebas: ${tests.length}`);
  console.log('========================================\n');
  
  const inicio = new Date();
  
  // Ejecutar tests secuencialmente
  for (let i = 0; i < tests.length; i++) {
    await ejecutarTest(tests[i], i);
    
    // Pausa entre tests para evitar sobrecarga
    if (i < tests.length - 1) {
      console.log('\nEsperando 3 segundos antes del siguiente test...');
      await new Promise(resolve => setTimeout(resolve, 3000));
    }
  }
  
  const fin = new Date();
  const duracion = ((fin - inicio) / 1000).toFixed(2);
  
  // Mostrar resumen
  console.log('\n\n========================================');
  console.log('RESUMEN DE EJECUCIÓN');
  console.log('========================================');
  console.log(`Total de pruebas: ${resultados.total}`);
  console.log(`Exitosas: ${resultados.exitosos}`);
  console.log(`Fallidas: ${resultados.fallidos}`);
  console.log(`Duración total: ${duracion} segundos`);
  console.log('========================================\n');
  
  console.log('Detalles por prueba:');
  resultados.detalles.forEach((detalle, index) => {
    const icono = detalle.estado === 'EXITOSO' ? '✓' : '✗';
    console.log(`${icono} [${index + 1}] ${detalle.test} (ID: ${detalle.id}) - ${detalle.estado}`);
  });
  
  console.log('\n========================================\n');
  
  // Salir con código de error si hay fallos
  process.exit(resultados.fallidos > 0 ? 1 : 0);
}

// Ejecutar si se llama directamente
if (require.main === module) {
  ejecutarTodosLosTests().catch(error => {
    console.error('Error fatal al ejecutar suite de pruebas:', error);
    process.exit(1);
  });
}

module.exports = { ejecutarTodosLosTests, tests };

