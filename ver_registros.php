<?php
require_once 'config/database.php';
$database = new Database();
$conn = $database->getConnection();

// Verificar si la conexión fue exitosa
if ($conn === false) {
    // El mensaje de error ya se maneja en getConnection(), pero es bueno tener un fallback
    die("No se pudo conectar a la base de datos. Por favor, revise la configuración.");
}

// Obtener facturas con empresa
$sql_facturas = "
    SELECT f.*, e.nombre as empresa_nombre, r.numero_factura as remision_numero
    FROM facturas f
    JOIN empresas e ON f.empresa_id = e.id
    LEFT JOIN remisiones r ON f.remision_id = r.id
    ORDER BY f.created_at DESC";
$stmt_facturas = $database->query($sql_facturas); // Aquí se llama al método query()
$facturas = $database->fetchAll($stmt_facturas); // Aquí se llama al método fetchAll()

// Obtener remisiones con empresa
$sql_remisiones = "
    SELECT r.*, e.nombre as empresa_nombre
    FROM remisiones r
    JOIN empresas e ON r.empresa_id = e.id
    ORDER BY r.estado ASC, r.created_at DESC";
$stmt_remisiones = $database->query($sql_remisiones); // Aquí se llama al método query()
$remisiones = $database->fetchAll($stmt_remisiones); // Aquí se llama al método fetchAll()

$database->closeConnection();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Registros - Sistema de Mercancia</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f8faff 0%, #e8f2ff 100%);
            min-height: 100vh;
            padding: 40px 20px;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 76, 151, 0.08);
            overflow: hidden;
            border-top: 4px solid #004C97;
        }
        .header {
            padding: 40px 40px 20px;
            background: linear-gradient(135deg, #004C97 0%, #0056a8 100%);
            color: white;
        }
        .header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .header p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }
        .content {
            padding: 40px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #004C97;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-bottom: 20px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #003875;
        }
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        .tab {
            padding: 12px 24px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
        }
        .tab.active {
            color: #004C97;
            border-bottom-color: #004C97;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .record-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: #f8f9fa;
        }
        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 10px;
        }
        .record-title {
            font-size: 18px;
            font-weight: 600;
            color: #004C97;
        }
        .record-type {
            background: #004C97;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .record-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        .info-item {
            font-size: 14px;
        }
        .info-label {
            font-weight: 600;
            color: #333;
        }
        .productos-list {
            background: white;
            border-radius: 6px;
            padding: 15px;
            margin-top: 15px;
        }
        .productos-title {
            font-weight: 600;
            margin-bottom: 10px;
            color: #004C97;
        }
        .producto-item {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
        }
        .producto-item:last-child {
            border-bottom: none;
        }
        .no-records {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
        @media (max-width: 768px) {
            .record-info {
                grid-template-columns: 1fr;
            }
            .tabs {
                flex-direction: column;
            }
            .record-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Registros del Sistema</h1>
            <p>Facturas y Remisiones registradas</p>
        </div>
        <div class="content">
            <a href="index.php" class="btn">← Volver al formulario</a>
            <div style="margin: 20px 0;">
                <label for="busquedaEmpresa"><strong>Buscar por Empresa:</strong></label>
                <input list="listaEmpresas" id="busquedaEmpresa" placeholder="Escribe o selecciona una empresa..." oninput="filtrarPorEmpresa()" style="padding: 5px; width: 300px;">
                <datalist id="listaEmpresas">
                    <?php
                    // Reunir empresas únicas de facturas y remisiones
                    $empresasUnicas = array_unique(array_merge(
                        array_column($facturas, 'empresa_nombre'),
                        array_column($remisiones, 'empresa_nombre')
                    ));
                    sort($empresasUnicas);
                    foreach ($empresasUnicas as $empresa): ?>
                        <option value="<?php echo htmlspecialchars($empresa); ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            <div class="tabs">
                <div class="tab active" onclick="showTab('facturas')">Facturas (<?php echo count($facturas); ?>)</div>
                <div class="tab" onclick="showTab('remisiones')">Remisiones (<?php echo count($remisiones); ?>)</div>
            </div>
            <!-- Facturas -->
            <div id="facturas" class="tab-content active">
                <?php if (count($facturas) > 0): ?>
                    <?php foreach ($facturas as $factura): ?>
                    <div class="record-card" data-empresa="<?php echo htmlspecialchars($factura['empresa_nombre']); ?>">
                        <div class="record-header">
                            <div class="record-title">📄 Factura #<?php echo htmlspecialchars($factura['numero_factura']); ?></div>
                            <div class="record-type">FACTURA</div>
                        </div>
                        <div class="record-info">
                            <div class="info-item">
                                <div class="info-label">Empresa:</div>
                                <?php echo htmlspecialchars($factura['empresa_nombre']); ?>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Fecha:</div>
                                <?php echo $factura['fecha']->format('d/m/Y'); ?>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Fabricante:</div>
                                <?php echo htmlspecialchars($factura['fabricante']); ?>
                            </div>
                            <?php if ($factura['remision_numero']): ?>
                                <div class="info-item">
                                    <div class="info-label">Remisión Relacionada:</div>
                                    <span style="color: #28a745; font-weight: 600;"><?php echo htmlspecialchars($factura['remision_numero']); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <div class="info-label">Subtotal:</div>
                                $<?php echo number_format($factura['valor_unitario'], 2); ?>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Valor Total:</div>
                                $<?php echo number_format($factura['valor_total'], 2); ?>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Recibido:</div>
                                <?php echo $factura['recibido_completo']; ?>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Quien Recibe:</div>
                                <?php echo htmlspecialchars($factura['nombre_quien_recibe']); ?>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Cantidad Total:</div>
                                <?php echo $factura['cantidad_total']; ?>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Registrado:</div>
                                <?php echo $factura['created_at']->format('d/m/Y H:i'); ?>
                            </div>
                        </div>
                        <?php if ($factura['inconsistencias']): ?>
                            <div class="info-item">
                                <div class="info-label">Inconsistencias:</div>
                                <span style="color: #dc3545;"><?php echo htmlspecialchars($factura['inconsistencias']); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="productos-list">
                            <div class="productos-title">Productos:</div>
                            <?php
                            $productos = json_decode($factura['productos'], true);
                            if ($productos && is_array($productos)) {
                                foreach ($productos as $producto):
                            ?>
                                <div class="producto-item">
                                    <strong><?php echo htmlspecialchars($producto['producto']); ?></strong> -
                                    <?php echo $producto['cantidad']; ?> <?php echo $producto['unidad']; ?>
                                </div>
                            <?php
                                endforeach;
                            } else {
                                echo '<div class="producto-item">No se pudieron cargar los productos</div>';
                            }
                            ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-records">No hay facturas registradas</div>
                <?php endif; ?>
            </div>
            <!-- Remisiones -->
            <div id="remisiones" class="tab-content">
                <?php if (count($remisiones) > 0): ?>
                    <?php foreach ($remisiones as $remision): ?>
                    <div class="record-card" data-empresa="<?php echo htmlspecialchars($remision['empresa_nombre']); ?>">
                        <div class="record-header">
                            <div class="record-title">📋 Remisión #<?php echo htmlspecialchars($remision['numero_factura']); ?></div>
                            <div class="record-type" style="background: <?php echo $remision['estado'] === 'CERRADA' ? '#dc3545' : '#28a745'; ?>;">
                                REMISIÓN - <?php echo $remision['estado']; ?>
                            </div>
                        </div>
                        <div class="record-info">
                            <div class="info-item">
                                <div class="info-label">Empresa:</div>
                                <?php echo htmlspecialchars($remision['empresa_nombre']); ?>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Fecha:</div>
                                <?php echo $remision['fecha']->format('d/m/Y'); ?>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Recibido:</div>
                                <?php echo $remision['recibido_completo']; ?>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Quien Recibe:</div>
                                <?php echo htmlspecialchars($remision['nombre_quien_recibe']); ?>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Cantidad Total:</div>
                                <?php echo $remision['cantidad_total']; ?>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Registrado:</div>
                                <?php echo $remision['created_at']->format('d/m/Y H:i'); ?>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Estado:</div>
                                <span style="color: <?php echo $remision['estado'] === 'CERRADA' ? '#dc3545' : '#28a745'; ?>; font-weight: 600;">
                                    <?php echo $remision['estado']; ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($remision['inconsistencias']): ?>
                            <div class="info-item">
                                <div class="info-label">Inconsistencias:</div>
                                <span style="color: #dc3545;"><?php echo htmlspecialchars($remision['inconsistencias']); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="productos-list">
                            <div class="productos-title">Productos:</div>
                            <?php
                            $productos = json_decode($remision['productos'], true);
                            if ($productos && is_array($productos)) {
                                foreach ($productos as $producto):
                            ?>
                                <div class="producto-item">
                                    <strong><?php echo htmlspecialchars($producto['producto']); ?></strong> -
                                    <?php echo $producto['cantidad']; ?> <?php echo $producto['unidad']; ?>
                                </div>
                            <?php
                                endforeach;
                            } else {
                                echo '<div class="producto-item">No se pudieron cargar los productos</div>';
                            }
                            ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-records">No hay remisiones registradas</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        function showTab(tabName) {
            // Ocultar todos los contenidos
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.remove('active'));

            // Remover clase active de todas las pestañas
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));

            // Mostrar el contenido seleccionado
            document.getElementById(tabName).classList.add('active');

            // Activar la pestaña seleccionada
            event.target.classList.add('active');
            filtrarPorEmpresa(); // Aplica filtro al cambiar de pestaña
        }

        function filtrarPorEmpresa() {
            const filtro = document.getElementById('busquedaEmpresa').value.toLowerCase().trim();
            document.querySelectorAll('.tab-content.active .record-card').forEach(card => {
                const empresa = card.getAttribute('data-empresa').toLowerCase();
                if (!filtro || empresa.includes(filtro)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
