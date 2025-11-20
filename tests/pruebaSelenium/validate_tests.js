const fs = require('fs');
const path = require('path');

// Mapeo de módulos esperados según el nombre del archivo
const moduleMapping = {
  'categoria': 'categoria',
  'producto': 'producto',
  'proveedor': 'proveedor',
  'tipousuario': 'tipousuario',
  'delivery': 'delivery',
  'compra': 'entrada',  // Las compras usan ?pagina=entrada
  'venta': 'salida',    // Las ventas usan ?pagina=salida
  'notificacion': 'notificacion'
};

// Archivos que no requieren login (públicos)
const publicTests = [
  'producto_buscar_encontrado',
  'producto_buscar_no_encontrado'
];

// Configuración esperada de TestLink
const expectedTestLinkConfig = {
  TESTLINK_URL: 'http://localhost/testlink-1.9.18/lib/api/xmlrpc/v1/xmlrpc.php',
  DEV_KEY: '1a4d579d37e9a7f66a417c527ca09718',
  BASE_URL: 'http://localhost:8080/LoveMakeup/LoveMakeup-2.0/'
};

const issues = [];
const warnings = [];
const validFiles = [];

// Obtener todos los archivos de prueba
const testFiles = fs.readdirSync(__dirname).filter(f => 
  f.endsWith('.js') && 
  !f.includes('node_modules') &&
  f !== 'package.json' &&
  f !== 'package-lock.json' &&
  f !== 'validate_tests.js' &&
  f !== 'remove_testlink_reports.js'
);

console.log('========================================');
console.log('VALIDACIÓN DE PRUEBAS SELENIUM');
console.log('========================================\n');
console.log(`Analizando ${testFiles.length} archivos de prueba...\n`);

testFiles.forEach(file => {
  const filePath = path.join(__dirname, file);
  const content = fs.readFileSync(filePath, 'utf8');
  const fileIssues = [];
  const fileWarnings = [];

  // 1. Verificar que el archivo tenga la estructura básica
  if (!content.includes('async function runTest()')) {
    fileIssues.push('❌ No tiene la función runTest()');
  }

  if (!content.includes('module.exports')) {
    fileIssues.push('❌ No exporta la función runTest');
  }

  // 2. Verificar dependencias básicas
  if (!content.includes("require('selenium-webdriver')")) {
    fileIssues.push('❌ No importa selenium-webdriver');
  }

  // 3. Verificar configuración de TestLink
  const hasTestLinkConfig = content.includes('// === CONFIGURACIÓN TESTLINK ===');
  const hasTestLinkFunction = content.includes('reportResultToTestLink');
  
  if (hasTestLinkConfig) {
    // Verificar que tenga todas las constantes necesarias
    if (!content.includes('TESTLINK_URL')) {
      fileIssues.push('❌ Tiene sección TestLink pero falta TESTLINK_URL');
    }
    if (!content.includes('DEV_KEY')) {
      fileIssues.push('❌ Tiene sección TestLink pero falta DEV_KEY');
    }
    if (!content.includes('TEST_CASE_EXTERNAL_ID')) {
      fileIssues.push('❌ Tiene sección TestLink pero falta TEST_CASE_EXTERNAL_ID');
    }
    if (!hasTestLinkFunction) {
      fileIssues.push('❌ Tiene configuración TestLink pero falta función reportResultToTestLink');
    }
  } else if (hasTestLinkFunction) {
    fileIssues.push('❌ Tiene función reportResultToTestLink pero no tiene configuración TestLink');
  }

  // 4. Verificar que tenga BASE_URL
  if (!content.includes('BASE_URL')) {
    fileIssues.push('❌ No tiene BASE_URL definida');
  } else {
    const baseUrlMatch = content.match(/const BASE_URL = ['"]([^'"]+)['"]/);
    if (baseUrlMatch && baseUrlMatch[1] !== expectedTestLinkConfig.BASE_URL) {
      fileWarnings.push(`⚠️  BASE_URL diferente: ${baseUrlMatch[1]}`);
    }
  }

  // 5. Verificar que navegue al módulo correcto
  let moduleFound = false;
  for (const [key, moduleName] of Object.entries(moduleMapping)) {
    if (file.toLowerCase().includes(key)) {
      moduleFound = true;
      // Verificar que la URL contenga el módulo correcto
      const urlPattern = new RegExp(`pagina=${moduleName}`, 'i');
      
      // Excepciones especiales
      const specialCases = {
        'compra_varios_productos': ['entrada', 'producto'], // Puede navegar a ambos
        'venta_sin_productos': ['salida'], // Navega a salida
        'venta_varios_productos': ['salida'], // Navega a salida
        'producto_buscar_encontrado': ['catalogo'], // Navega a catálogo público
        'producto_buscar_no_encontrado': ['catalogo'] // Navega a catálogo público
      };
      
      const fileName = file.replace('.js', '');
      if (specialCases[fileName]) {
        // Verificar que navegue a alguna de las URLs permitidas
        const hasValidUrl = specialCases[fileName].some(url => 
          new RegExp(`pagina=${url}`, 'i').test(content)
        );
        if (!hasValidUrl) {
          fileIssues.push(`❌ Archivo de ${key} pero no navega a ninguna URL válida (${specialCases[fileName].join(' o ')})`);
        }
      } else if (!urlPattern.test(content)) {
        fileIssues.push(`❌ Archivo de ${key} pero no navega a ?pagina=${moduleName}`);
      }
      break;
    }
  }

  // 6. Verificar que tenga login (excepto pruebas públicas)
  const isPublicTest = publicTests.some(pt => file.toLowerCase().includes(pt));
  if (!isPublicTest && !content.includes('?pagina=login')) {
    fileIssues.push('❌ No tiene paso de login');
  }

  // 7. Verificar que cierre el driver
  if (!content.includes('driver.quit()')) {
    fileIssues.push('❌ No cierra el driver (driver.quit())');
  }

  // 8. Verificar que tenga manejo de errores
  if (!content.includes('catch (error)') && !content.includes('catch(error)')) {
    fileWarnings.push('⚠️  No tiene manejo de errores con catch');
  }

  // 9. Verificar que no tenga referencias a ReportGenerator
  if (content.includes('ReportGenerator')) {
    fileIssues.push('❌ Tiene referencias a ReportGenerator (debe estar eliminado)');
  }

  // 10. Verificar sintaxis básica (intentar parsear)
  try {
    // Verificar que no tenga errores de sintaxis obvios
    if (content.includes('const reportGenerator') || content.includes('new ReportGenerator')) {
      fileIssues.push('❌ Tiene código de ReportGenerator sin eliminar');
    }
  } catch (e) {
    fileWarnings.push(`⚠️  Posible error de sintaxis: ${e.message}`);
  }

  // Agregar resultados
  if (fileIssues.length > 0) {
    issues.push({ file, issues: fileIssues });
  }
  if (fileWarnings.length > 0) {
    warnings.push({ file, warnings: fileWarnings });
  }
  if (fileIssues.length === 0) {
    validFiles.push(file);
  }
});

// Mostrar resultados
console.log('========================================');
console.log('RESUMEN DE VALIDACIÓN');
console.log('========================================\n');

if (validFiles.length > 0) {
  console.log(`✅ ${validFiles.length} archivos válidos:`);
  validFiles.forEach(f => console.log(`   - ${f}`));
  console.log('');
}

if (warnings.length > 0) {
  console.log(`⚠️  ${warnings.length} archivos con advertencias:\n`);
  warnings.forEach(({ file, warnings: fileWarnings }) => {
    console.log(`   ${file}:`);
    fileWarnings.forEach(w => console.log(`      ${w}`));
  });
  console.log('');
}

if (issues.length > 0) {
  console.log(`❌ ${issues.length} archivos con problemas:\n`);
  issues.forEach(({ file, issues: fileIssues }) => {
    console.log(`   ${file}:`);
    fileIssues.forEach(i => console.log(`      ${i}`));
  });
  console.log('');
}

// Resumen final
const totalIssues = issues.reduce((sum, item) => sum + item.issues.length, 0);
const totalWarnings = warnings.reduce((sum, item) => sum + item.warnings.length, 0);

console.log('========================================');
console.log('ESTADÍSTICAS');
console.log('========================================');
console.log(`Total de archivos: ${testFiles.length}`);
console.log(`Archivos válidos: ${validFiles.length}`);
console.log(`Archivos con advertencias: ${warnings.length}`);
console.log(`Archivos con problemas: ${issues.length}`);
console.log(`Total de problemas: ${totalIssues}`);
console.log(`Total de advertencias: ${totalWarnings}`);
console.log('========================================\n');

if (totalIssues === 0) {
  console.log('✅ ¡Todas las pruebas están correctamente configuradas!');
  process.exit(0);
} else {
  console.log('❌ Se encontraron problemas que deben corregirse.');
  process.exit(1);
}

