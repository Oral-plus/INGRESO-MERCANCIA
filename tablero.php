<?php
// Conexión a SQL Server
$serverName = "192.168.2.244"; 
$connectionInfo = array("Database"=>"calidad", "UID"=>"sa", "PWD"=>"Sky2022*!");
$conn = sqlsrv_connect($serverName, $connectionInfo);

if (!$conn) {
    die("Conexión fallida: " . print_r(sqlsrv_errors(), true));
}

// Consulta de datos
$sql = "SELECT * FROM Mercancia ORDER BY fecha DESC";
$result = sqlsrv_query($conn, $sql);

$data = [];
while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
    $row['fecha'] = $row['fecha']->format('Y-m-d');
    $data[] = $row;
}

sqlsrv_close($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>📊 Dashboard Control de Mercancía</title>
<!-- Librerías -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.dataTables.min.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />

<style>
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  min-height: 100vh;
  padding: 15px;
}

.container {
  max-width: 1400px;
  margin: 0 auto;
}

h1 {
  text-align: center;
  color: white;
  margin-bottom: 25px;
  font-size: 2.2em;
  text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

/* Filtros compactos */
#filters {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 15px;
  padding: 20px;
  margin-bottom: 25px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.1);
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 15px;
  align-items: end;
}

.filter-group {
  display: flex;
  flex-direction: column;
}

.filter-group label {
  font-weight: 600;
  color: #2c3e50;
  margin-bottom: 5px;
  font-size: 0.9em;
}

.filter-group input, .filter-group select {
  padding: 10px;
  border-radius: 8px;
  border: 2px solid #e1e8ed;
  background: white;
  transition: all 0.3s ease;
  font-size: 14px;
}

.filter-group input:focus, .filter-group select:focus {
  border-color: #667eea;
  outline: none;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

#aplicarFiltros {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 12px 25px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  transition: all 0.3s ease;
  height: fit-content;
}

#aplicarFiltros:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

/* Métricas compactas */
#metrics {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 20px;
  margin-bottom: 25px;
}

.metric {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  padding: 20px;
  border-radius: 15px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.1);
  text-align: center;
  position: relative;
  overflow: hidden;
  transition: all 0.3s ease;
}

.metric:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 40px rgba(0,0,0,0.15);
}

.metric::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.metric-icon {
  font-size: 2.5em;
  margin-bottom: 10px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.metric h2 {
  font-size: 2.2em;
  color: #2c3e50;
  margin-bottom: 5px;
  font-weight: 700;
}

.metric p {
  color: #7f8c8d;
  font-weight: 500;
  font-size: 0.95em;
}

.metric-change {
  font-size: 0.8em;
  margin-top: 8px;
  padding: 4px 8px;
  border-radius: 12px;
  display: inline-block;
}

.metric-change.positive {
  background: #d4edda;
  color: #155724;
}

.metric-change.negative {
  background: #f8d7da;
  color: #721c24;
}

/* Dashboard de gráficos */
.dashboard {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
  gap: 20px;
  margin-bottom: 25px;
}

.chart-container {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  padding: 25px;
  border-radius: 15px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.1);
  transition: all 0.3s ease;
}

.chart-container:hover {
  transform: translateY(-3px);
  box-shadow: 0 12px 40px rgba(0,0,0,0.15);
}

.chart-container h3 {
  color: #2c3e50;
  margin-bottom: 20px;
  font-size: 1.3em;
  font-weight: 600;
  text-align: center;
  position: relative;
  padding-bottom: 10px;
}

.chart-container h3::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 50%;
  transform: translateX(-50%);
  width: 50px;
  height: 3px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 2px;
}

/* Tabla mejorada */
.table-container {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 15px;
  padding: 25px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.1);
  overflow: hidden;
}

.table-container h3 {
  color: #2c3e50;
  margin-bottom: 20px;
  font-size: 1.4em;
  font-weight: 600;
}

/* DataTable personalizado */
.dataTables_wrapper {
  font-size: 0.9em;
}

table.dataTable thead th {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
  color: white !important;
  border: none !important;
  padding: 15px 10px !important;
  font-weight: 600;
}

table.dataTable tbody td {
  padding: 12px 10px;
  border-bottom: 1px solid #ecf0f1;
  vertical-align: middle;
}

table.dataTable tbody tr:hover {
  background-color: rgba(102, 126, 234, 0.05) !important;
}

/* Estadísticas adicionales */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
  margin-bottom: 25px;
}

.stat-card {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 15px;
  padding: 20px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.1);
}

.stat-card h4 {
  color: #2c3e50;
  margin-bottom: 15px;
  font-size: 1.1em;
  font-weight: 600;
}

.stat-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid #ecf0f1;
}

.stat-item:last-child {
  border-bottom: none;
}

.stat-label {
  color: #7f8c8d;
  font-size: 0.9em;
}

.stat-value {
  font-weight: 600;
  color: #2c3e50;
}

/* Responsive */
@media (max-width: 768px) {
  body {
    padding: 10px;
  }
  
  h1 {
    font-size: 1.8em;
  }
  
  #filters {
    grid-template-columns: 1fr;
  }
  
  #metrics {
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
  }
  
  .dashboard {
    grid-template-columns: 1fr;
    gap: 15px;
  }
  
  .chart-container {
    padding: 20px;
  }
}

/* Animaciones */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.metric, .chart-container, .table-container {
  animation: fadeInUp 0.6s ease-out;
}

/* Indicadores de estado */
.status-received {
  background: #d4edda;
  color: #155724;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 0.8em;
  font-weight: 600;
}

.status-pending {
  background: #f8d7da;
  color: #721c24;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 0.8em;
  font-weight: 600;
}
</style>
</head>
<body>

<div class="container">
  <h1>📦 Dashboard Control de Mercancía</h1>

  <!-- Filtros -->
  <div id="filters">
    <div class="filter-group">
      <label for="fechaInicio"><i class="fas fa-calendar-alt"></i> Fecha Inicio</label>
      <input type="date" id="fechaInicio" />
    </div>
    <div class="filter-group">
      <label for="fechaFin"><i class="fas fa-calendar-alt"></i> Fecha Fin</label>
      <input type="date" id="fechaFin" />
    </div>
    <div class="filter-group">
      <label for="empresaFiltro"><i class="fas fa-building"></i> Empresa</label>
      <select id="empresaFiltro">
        <option value="">Todas las empresas</option>
      </select>
    </div>
    <div class="filter-group">
      <label for="fabricaFiltro"><i class="fas fa-industry"></i> Fábrica</label>
      <select id="fabricaFiltro">
        <option value="">Todas las fábricas</option>
      </select>
    </div>
    <div class="filter-group">
      <label for="productoFiltro"><i class="fas fa-box"></i> Producto</label>
      <select id="productoFiltro">
        <option value="">Todos los productos</option>
      </select>
    </div>
    <button id="aplicarFiltros"><i class="fas fa-search"></i> Aplicar Filtros</button>
  </div>

  <!-- Métricas Principales -->
  <div id="metrics">
    <div class="metric">
      <div class="metric-icon"><i class="fas fa-boxes"></i></div>
      <h2 id="totalItems">0</h2>
      <p>Total Registros</p>
      <div class="metric-change positive" id="itemsChange">+0%</div>
    </div>
    <div class="metric">
      <div class="metric-icon"><i class="fas fa-dollar-sign"></i></div>
      <h2 id="totalValor">$0</h2>
      <p>Valor Total</p>
      <div class="metric-change positive" id="valorChange">+0%</div>
    </div>
    <div class="metric">
      <div class="metric-icon"><i class="fas fa-check-circle"></i></div>
      <h2 id="porcentajeRecibido">0%</h2>
      <p>Recibidos</p>
      <div class="metric-change" id="recibidoChange">0%</div>
    </div>
    <div class="metric">
      <div class="metric-icon"><i class="fas fa-chart-line"></i></div>
      <h2 id="valorPromedio">$0</h2>
      <p>Valor Promedio</p>
      <div class="metric-change" id="promedioChange">0%</div>
    </div>
    <div class="metric">
      <div class="metric-icon"><i class="fas fa-exclamation-triangle"></i></div>
      <h2 id="inconsistenciasTotal">0</h2>
      <p>Inconsistencias</p>
      <div class="metric-change negative" id="inconsistenciasChange">0</div>
    </div>
    <div class="metric">
      <div class="metric-icon"><i class="fas fa-calendar-day"></i></div>
      <h2 id="registrosHoy">0</h2>
      <p>Hoy</p>
      <div class="metric-change" id="hoyChange">+0</div>
    </div>
  </div>

  <!-- Estadísticas Adicionales -->
  <div class="stats-grid">
    <div class="stat-card">
      <h4><i class="fas fa-chart-bar"></i> Top Productos por Valor</h4>
      <div id="topProductos"></div>
    </div>
    <div class="stat-card">
      <h4><i class="fas fa-building"></i> Empresas Activas</h4>
      <div id="topEmpresas"></div>
    </div>
    <div class="stat-card">
      <h4><i class="fas fa-clock"></i> Actividad Reciente</h4>
      <div id="actividadReciente"></div>
    </div>
  </div>

  <!-- Gráficos -->
  <div class="dashboard">
    <div class="chart-container">
      <h3><i class="fas fa-chart-bar"></i> Valor por Producto</h3>
      <canvas id="barChart"></canvas>
    </div>
    <div class="chart-container">
      <h3><i class="fas fa-chart-pie"></i> Estado de Recepción</h3>
      <canvas id="pieChart"></canvas>
    </div>
    <div class="chart-container">
      <h3><i class="fas fa-chart-line"></i> Tendencia Temporal</h3>
      <canvas id="lineChart"></canvas>
    </div>
    <div class="chart-container">
      <h3><i class="fas fa-chart-area"></i> Distribución por Empresa</h3>
      <canvas id="empresaChart"></canvas>
    </div>
  </div>

  <!-- Tabla de registros -->
  <div class="table-container">
    <h3><i class="fas fa-table"></i> Registro Detallado de Mercancía</h3>
    <table id="mercanciaTable" class="display nowrap" style="width:100%">
      <thead>
        <tr>
          <th>Empresa</th>
          <th>Fecha</th>
          <th>Factura</th>
          <th>Producto</th>
          <th>Fábrica</th>
          <th>Unidad</th>
          <th>Cantidad</th>
          <th>V. Unitario</th>
          <th>V. Total</th>
          <th>Estado</th>
          <th>Recibe</th>
          <th>Inconsistencias</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>

<script>
$(document).ready(function() {
  const rawData = <?php echo json_encode($data); ?>;
  let filteredData = [...rawData];
  let charts = {};

  // Poblar filtros
  const empresas = new Set();
  const fabricas = new Set();
  const productos = new Set();

  rawData.forEach(item => {
    if (item['empresa']) empresas.add(item['empresa']);
    if (item['fabrica']) fabricas.add(item['fabrica']);
    if (item['producto']) productos.add(item['producto']);
  });

  empresas.forEach(e => $('#empresaFiltro').append(`<option value="${e}">${e}</option>`));
  fabricas.forEach(f => $('#fabricaFiltro').append(`<option value="${f}">${f}</option>`));
  productos.forEach(p => $('#productoFiltro').append(`<option value="${p}">${p}</option>`));

  // Función para formatear números
  function formatNumber(num) {
    return new Intl.NumberFormat('es-CO', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2
    }).format(num);
  }

  function formatCurrency(num) {
    return new Intl.NumberFormat('es-CO', {
      style: 'currency',
      currency: 'COP',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(num);
  }

  // Función principal para actualizar dashboard
  function actualizarDashboard() {
    // Aplicar filtros
    const fechaInicio = $('#fechaInicio').val();
    const fechaFin = $('#fechaFin').val();
    const empresaSel = $('#empresaFiltro').val();
    const fabricaSel = $('#fabricaFiltro').val();
    const productoSel = $('#productoFiltro').val();

    filteredData = rawData.filter(item => {
      const fecha = new Date(item['fecha']);
      const start = fechaInicio ? new Date(fechaInicio) : null;
      const end = fechaFin ? new Date(fechaFin) : null;
      let valido = true;
      if (start && fecha < start) valido = false;
      if (end && fecha > end) valido = false;
      if (empresaSel && item['empresa'] !== empresaSel) valido = false;
      if (fabricaSel && item['fabrica'] !== fabricaSel) valido = false;
      if (productoSel && item['producto'] !== productoSel) valido = false;
      return valido;
    });

    actualizarTabla();
    actualizarKPIs();
    actualizarEstadisticasAdicionales();
    actualizarGraficos();
  }

  // Actualizar tabla
  function actualizarTabla() {
    if ($.fn.DataTable.isDataTable('#mercanciaTable')) {
      $('#mercanciaTable').DataTable().clear().destroy();
    }
    
    $('#mercanciaTable tbody').empty();
    filteredData.forEach(item => {
      const estado = (item['recibido_completo'] || '').toLowerCase() === 'si' 
        ? '<span class="status-received">Recibido</span>' 
        : '<span class="status-pending">Pendiente</span>';
      
      $('#mercanciaTable tbody').append(`
        <tr>
          <td>${item['empresa'] || ''}</td>
          <td>${item['fecha']}</td>
          <td>${item['numero_factura'] || ''}</td>
          <td>${item['producto'] || ''}</td>
          <td>${item['fabrica'] || ''}</td>
          <td>${item['unidad_peso'] || ''}</td>
          <td>${formatNumber(item['cantidad'] || 0)}</td>
          <td>${formatCurrency(item['valor_unitario'] || 0)}</td>
          <td>${formatCurrency(item['valor_total'] || 0)}</td>
          <td>${estado}</td>
          <td>${item['nombre_quien_recibe'] || ''}</td>
          <td>${item['inconsistencias'] || ''}</td>
        </tr>
      `);
    });

    $('#mercanciaTable').DataTable({
      responsive: true,
      dom: 'Bfrtip',
      buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
      language: {
        url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
      },
      pageLength: 25,
      order: [[1, 'desc']]
    });
  }

  // Actualizar KPIs
  function actualizarKPIs() {
    const totalItems = filteredData.length;
    const totalValor = filteredData.reduce((sum, item) => sum + parseFloat(item['valor_total'] || 0), 0);
    const recibidosCount = filteredData.filter(i => (i['recibido_completo'] || '').toLowerCase() === 'si').length;
    const porcentajeRecibido = totalItems ? ((recibidosCount / totalItems) * 100).toFixed(1) : 0;
    const valorPromedio = totalItems ? totalValor / totalItems : 0;
    const inconsistenciasTotal = filteredData.filter(i => i['inconsistencias'] && i['inconsistencias'].trim() !== '').length;
    
    // Registros de hoy
    const hoy = new Date().toISOString().split('T')[0];
    const registrosHoy = filteredData.filter(i => i['fecha'] === hoy).length;

    $('#totalItems').text(formatNumber(totalItems));
    $('#totalValor').text(formatCurrency(totalValor));
    $('#porcentajeRecibido').text(`${porcentajeRecibido}%`);
    $('#valorPromedio').text(formatCurrency(valorPromedio));
    $('#inconsistenciasTotal').text(inconsistenciasTotal);
    $('#registrosHoy').text(registrosHoy);

    // Actualizar indicadores de cambio (simplificado)
    $('#recibidoChange').text(`${porcentajeRecibido}%`).removeClass('positive negative')
      .addClass(porcentajeRecibido >= 80 ? 'positive' : 'negative');
  }

  // Actualizar estadísticas adicionales
  function actualizarEstadisticasAdicionales() {
    // Top productos por valor
    const productosValor = {};
    filteredData.forEach(item => {
      const prod = item['producto'] || 'Sin especificar';
      productosValor[prod] = (productosValor[prod] || 0) + parseFloat(item['valor_total'] || 0);
    });
    
    const topProductos = Object.entries(productosValor)
      .sort(([,a], [,b]) => b - a)
      .slice(0, 5);

    $('#topProductos').empty();
    topProductos.forEach(([producto, valor]) => {
      $('#topProductos').append(`
        <div class="stat-item">
          <span class="stat-label">${producto}</span>
          <span class="stat-value">${formatCurrency(valor)}</span>
        </div>
      `);
    });

    // Top empresas
    const empresasCount = {};
    filteredData.forEach(item => {
      const emp = item['empresa'] || 'Sin especificar';
      empresasCount[emp] = (empresasCount[emp] || 0) + 1;
    });

    const topEmpresas = Object.entries(empresasCount)
      .sort(([,a], [,b]) => b - a)
      .slice(0, 5);

    $('#topEmpresas').empty();
    topEmpresas.forEach(([empresa, count]) => {
      $('#topEmpresas').append(`
        <div class="stat-item">
          <span class="stat-label">${empresa}</span>
          <span class="stat-value">${count} registros</span>
        </div>
      `);
    });

    // Actividad reciente
    const recientes = filteredData
      .sort((a, b) => new Date(b.fecha) - new Date(a.fecha))
      .slice(0, 5);

    $('#actividadReciente').empty();
    recientes.forEach(item => {
      $('#actividadReciente').append(`
        <div class="stat-item">
          <span class="stat-label">${item['producto'] || 'N/A'}</span>
          <span class="stat-value">${item['fecha']}</span>
        </div>
      `);
    });
  }

  // Actualizar gráficos
  function actualizarGraficos() {
    // Gráfico de barras - Productos
    const productosMap = {};
    filteredData.forEach(item => {
      const prod = item['producto'] || 'Sin especificar';
      productosMap[prod] = (productosMap[prod] || 0) + parseFloat(item['valor_total'] || 0);
    });

    const topProductosChart = Object.entries(productosMap)
      .sort(([,a], [,b]) => b - a)
      .slice(0, 8);

    actualizarGraficoBarras(
      topProductosChart.map(([prod]) => prod),
      topProductosChart.map(([,valor]) => valor)
    );

    // Gráfico pie - Estado recepción
    const recibidos = filteredData.filter(i => (i['recibido_completo'] || '').toLowerCase() === 'si').length;
    const noRecibidos = filteredData.length - recibidos;
    actualizarGraficoPie(recibidos, noRecibidos);

    // Gráfico línea - Tendencia temporal
    const tendencia = {};
    filteredData.forEach(item => {
      const fecha = item['fecha'];
      tendencia[fecha] = (tendencia[fecha] || 0) + parseFloat(item['cantidad'] || 0);
    });

    const fechasOrdenadas = Object.keys(tendencia).sort();
    actualizarGraficoLinea(fechasOrdenadas, fechasOrdenadas.map(f => tendencia[f]));

    // Gráfico empresas
    const empresasValor = {};
    filteredData.forEach(item => {
      const emp = item['empresa'] || 'Sin especificar';
      empresasValor[emp] = (empresasValor[emp] || 0) + parseFloat(item['valor_total'] || 0);
    });

    actualizarGraficoEmpresas(Object.keys(empresasValor), Object.values(empresasValor));
  }

  // Funciones de gráficos
  function actualizarGraficoBarras(labels, data) {
    if (charts.barChart) charts.barChart.destroy();
    const ctx = document.getElementById('barChart').getContext('2d');
    charts.barChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Valor Total',
          data: data,
          backgroundColor: 'rgba(102, 126, 234, 0.8)',
          borderColor: 'rgba(102, 126, 234, 1)',
          borderWidth: 2,
          borderRadius: 8
        }]      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(value) {
                return formatCurrency(value);
              }
            }
          }
        }
      }
    });
  }

  function actualizarGraficoPie(recibidos, noRecibidos) {
    if (charts.pieChart) charts.pieChart.destroy();
    const ctx = document.getElementById('pieChart').getContext('2d');
    charts.pieChart = new Chart(ctx, {
      type: 'pie',
      data: {
        labels: ['Recibidos', 'Pendientes'],
        datasets: [{
          data: [recibidos, noRecibidos],
          backgroundColor: ['#28a745', '#dc3545'],
          borderColor: '#fff',
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            position: 'bottom'
          }
        }
      }
    });
  }

  function actualizarGraficoLinea(labels, data) {
    if (charts.lineChart) charts.lineChart.destroy();
    const ctx = document.getElementById('lineChart').getContext('2d');
    charts.lineChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: 'Cantidad',
          data: data,
          borderColor: '#764ba2',
          backgroundColor: 'rgba(118, 75, 162, 0.2)',
          fill: true,
          tension: 0.3,
          pointRadius: 4,
          pointHoverRadius: 6
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          x: {
            type: 'time',
            time: {
              unit: 'day',
              tooltipFormat: 'yyyy-MM-dd',
              displayFormats: {
                day: 'yyyy-MM-dd'
              }
            }
          },
          y: {
            beginAtZero: true
          }
        }
      }
    });
  }

  function actualizarGraficoEmpresas(labels, data) {
    if (charts.empresaChart) charts.empresaChart.destroy();
    const ctx = document.getElementById('empresaChart').getContext('2d');
    charts.empresaChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: 'Valor por Empresa',
          data: data,
          backgroundColor: '#667eea',
          borderColor: '#5a67d8',
          borderWidth: 2,
          borderRadius: 10
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              callback: function(value) {
                return formatCurrency(value);
              }
            }
          }
        }
      }
    });
  }

  // Evento botón filtros
  $('#aplicarFiltros').on('click', function () {
    actualizarDashboard();
  });

  // Cargar dashboard inicialmente
  actualizarDashboard();
});
</script>
</body>
</html>
