const fs = require('fs');
const path = require('path');

/**
 * Generador de reportes para pruebas Selenium
 * Genera reportes en formato HTML, JSON y XML
 */
class ReportGenerator {
  constructor() {
    // Ruta base para los reportes (relativa a este archivo)
    this.reportsDir = path.join(__dirname, '..', 'reports', 'testlink');
    
    // Crear directorio si no existe
    if (!fs.existsSync(this.reportsDir)) {
      fs.mkdirSync(this.reportsDir, { recursive: true });
    }
  }

  /**
   * Generar reporte XML
   * @param {Object} data - Datos del test
   * @returns {Promise<string>} Ruta del archivo generado
   */
  async generateReport(data) {
    const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
    const baseName = `test_report_${timestamp}`;

    return this.generateXMLReport(data, baseName);
  }

  /**
   * Generar reporte HTML
   */
  generateHTMLReport(data, baseName) {
    const html = this.getHTMLTemplate(data);
    const filePath = path.join(this.reportsDir, `${baseName}.html`);
    fs.writeFileSync(filePath, html, 'utf8');
    return filePath;
  }

  /**
   * Generar reporte JSON
   */
  generateJSONReport(data, baseName) {
    const jsonData = {
      testName: data.testName || 'Test sin nombre',
      status: data.status,
      notes: data.notes || '',
      startTime: data.startTime ? data.startTime.toISOString() : new Date().toISOString(),
      endTime: data.endTime ? data.endTime.toISOString() : new Date().toISOString(),
      duration: data.startTime && data.endTime 
        ? (data.endTime - data.startTime) / 1000 + ' segundos'
        : 'N/A',
      browser: data.browser || 'N/A',
      baseUrl: data.baseUrl || 'N/A',
      testCaseId: data.testCaseId || 'N/A',
      steps: data.steps || [],
      error: data.error || null
    };

    const filePath = path.join(this.reportsDir, `${baseName}.json`);
    fs.writeFileSync(filePath, JSON.stringify(jsonData, null, 2), 'utf8');
    return filePath;
  }

  /**
   * Generar reporte XML (compatible con TestLink)
   */
  generateXMLReport(data, baseName) {
    const status = this.mapStatusToTestLink(data.status);
    const timestamp = data.startTime ? data.startTime.toISOString() : new Date().toISOString();
    
    let xml = '<?xml version="1.0" encoding="UTF-8"?>\n';
    xml += '<results>\n';
    xml += '  <testcase name="' + this.escapeXml(data.testName || 'Test sin nombre') + '" ';
    xml += 'internalid="' + this.escapeXml(data.testCaseId || 'N/A') + '">\n';
    xml += '    <result status="' + status + '"/>\n';
    
    if (data.notes) {
      xml += '    <notes>' + this.escapeXml(data.notes) + '</notes>\n';
    }
    
    xml += '    <timestamp>' + timestamp + '</timestamp>\n';
    xml += '  </testcase>\n';
    xml += '</results>';

    const filePath = path.join(this.reportsDir, `${baseName}.xml`);
    fs.writeFileSync(filePath, xml, 'utf8');
    return filePath;
  }

  /**
   * Template HTML para el reporte
   */
  getHTMLTemplate(data) {
    const status = data.status === 'p' || data.status === 'passed' ? 'passed' : 'failed';
    const statusText = status === 'passed' ? 'PASADO' : 'FALLIDO';
    const statusColor = status === 'passed' ? '#28a745' : '#dc3545';
    const statusBg = status === 'passed' ? '#d4edda' : '#f8d7da';
    
    const duration = data.startTime && data.endTime 
      ? ((data.endTime - data.startTime) / 1000).toFixed(2) + ' segundos'
      : 'N/A';

    const stepsHtml = data.steps && data.steps.length > 0
      ? data.steps.map((step, index) => `<li>${index + 1}. ${step}</li>`).join('\n')
      : '<li>No hay pasos registrados</li>';

    return `<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Prueba - ${data.testName || 'Test'}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 1.1em;
            margin-top: 10px;
            background: ${statusBg};
            color: ${statusColor};
            border: 2px solid ${statusColor};
        }
        .content {
            padding: 30px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            border-left: 4px solid #667eea;
        }
        .info-card h3 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 0.9em;
            text-transform: uppercase;
        }
        .info-card p {
            font-size: 1.1em;
            font-weight: 600;
            color: #333;
        }
        .section {
            margin-bottom: 30px;
        }
        .section h2 {
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        .steps-list {
            list-style: none;
            padding-left: 0;
        }
        .steps-list li {
            padding: 12px;
            margin-bottom: 8px;
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            border-radius: 4px;
        }
        .error-box {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
        }
        .error-box pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>${this.escapeHtml(data.testName || 'Test sin nombre')}</h1>
            <div class="status-badge">${statusText}</div>
        </div>
        
        <div class="content">
            <div class="info-grid">
                <div class="info-card">
                    <h3>Estado</h3>
                    <p style="color: ${statusColor};">${statusText}</p>
                </div>
                <div class="info-card">
                    <h3>Duración</h3>
                    <p>${duration}</p>
                </div>
                <div class="info-card">
                    <h3>Navegador</h3>
                    <p>${this.escapeHtml(data.browser || 'N/A')}</p>
                </div>
                <div class="info-card">
                    <h3>URL Base</h3>
                    <p>${this.escapeHtml(data.baseUrl || 'N/A')}</p>
                </div>
                <div class="info-card">
                    <h3>ID Caso de Prueba</h3>
                    <p>${this.escapeHtml(data.testCaseId || 'N/A')}</p>
                </div>
                <div class="info-card">
                    <h3>Fecha de Ejecución</h3>
                    <p>${data.startTime ? new Date(data.startTime).toLocaleString('es-ES') : 'N/A'}</p>
                </div>
            </div>

            ${data.steps && data.steps.length > 0 ? `
            <div class="section">
                <h2>Pasos de la Prueba</h2>
                <ul class="steps-list">
                    ${stepsHtml}
                </ul>
            </div>
            ` : ''}

            ${data.notes ? `
            <div class="section">
                <h2>Notas</h2>
                <p>${this.escapeHtml(data.notes)}</p>
            </div>
            ` : ''}

            ${data.error ? `
            <div class="section">
                <h2>Error</h2>
                <div class="error-box">
                    <pre>${this.escapeHtml(data.error)}</pre>
                </div>
            </div>
            ` : ''}
        </div>

        <div class="footer">
            Reporte generado el ${new Date().toLocaleString('es-ES')}
        </div>
    </div>
</body>
</html>`;
  }

  /**
   * Mapear status a formato TestLink
   */
  mapStatusToTestLink(status) {
    if (status === 'p' || status === 'passed') return 'p';
    if (status === 'f' || status === 'failed') return 'f';
    return 'b'; // blocked
  }

  /**
   * Escapar XML
   */
  escapeXml(text) {
    if (!text) return '';
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&apos;');
  }

  /**
   * Escapar HTML
   */
  escapeHtml(text) {
    if (!text) return '';
    const map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, m => map[m]);
  }
}

module.exports = ReportGenerator;

