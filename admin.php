<?php
require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Manejar acciones POST
$mensaje = '';
$tipo_mensaje = '';

if ($_POST) {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_empresa':
                    $nombre_empresa = trim($_POST['nombre_empresa']);
                    
                    if (empty($nombre_empresa)) {
                        throw new Exception("El nombre de la empresa es requerido");
                    }
                    
                    // Verificar si la empresa ya existe
                    $sql_check = "SELECT COUNT(*) as count FROM empresas WHERE nombre = ?";
                    $stmt_check = $database->prepare($sql_check);
                    $stmt_check->bindValue(1, $nombre_empresa, PDO::PARAM_STR);
                    $stmt_check->execute();
                    $result = $stmt_check->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result['count'] > 0) {
                        throw new Exception("La empresa ya existe en la base de datos");
                    }
                    
                    $sql = "INSERT INTO empresas (nombre) VALUES (?)";
                    $stmt = $database->prepare($sql);
                    $stmt->bindValue(1, $nombre_empresa, PDO::PARAM_STR);
                    
                    if ($stmt->execute()) {
                        $mensaje = "Empresa '{$nombre_empresa}' agregada exitosamente";
                        $tipo_mensaje = "success";
                    } else {
                        throw new Exception("Error al agregar la empresa");
                    }
                    break;
                    
                case 'add_producto':
                    $empresa_id = $_POST['empresa_id'];
                    $nombre_producto = trim($_POST['nombre_producto']);
                    
                    if (empty($empresa_id) || empty($nombre_producto)) {
                        throw new Exception("Todos los campos son requeridos");
                    }
                    
                    // Verificar si el producto ya existe para esta empresa
                    $sql_check = "SELECT COUNT(*) as count FROM productos_empresa WHERE empresa_id = ? AND nombre_producto = ?";
                    $stmt_check = $database->prepare($sql_check);
                    $stmt_check->bindValue(1, $empresa_id, PDO::PARAM_INT);
                    $stmt_check->bindValue(2, $nombre_producto, PDO::PARAM_STR);
                    $stmt_check->execute();
                    $result = $stmt_check->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result['count'] > 0) {
                        throw new Exception("Este producto ya existe para la empresa seleccionada");
                    }
                    
                    $sql = "INSERT INTO productos_empresa (empresa_id, nombre_producto) VALUES (?, ?)";
                    $stmt = $database->prepare($sql);
                    $stmt->bindValue(1, $empresa_id, PDO::PARAM_INT);
                    $stmt->bindValue(2, $nombre_producto, PDO::PARAM_STR);
                    
                    if ($stmt->execute()) {
                        $mensaje = "Producto '{$nombre_producto}' agregado exitosamente";
                        $tipo_mensaje = "success";
                    } else {
                        throw new Exception("Error al agregar el producto");
                    }
                    break;
                    
                case 'delete_producto':
                    $producto_id = $_POST['producto_id'];
                    
                    $sql = "UPDATE productos_empresa SET activo = 0 WHERE id = ?";
                    $stmt = $database->prepare($sql);
                    $stmt->bindValue(1, $producto_id, PDO::PARAM_INT);
                    
                    if ($stmt->execute()) {
                        $mensaje = "Producto desactivado exitosamente";
                        $tipo_mensaje = "success";
                    } else {
                        throw new Exception("Error al desactivar el producto");
                    }
                    break;
                    
                case 'activate_producto':
                    $producto_id = $_POST['producto_id'];
                    
                    $sql = "UPDATE productos_empresa SET activo = 1 WHERE id = ?";
                    $stmt = $database->prepare($sql);
                    $stmt->bindValue(1, $producto_id, PDO::PARAM_INT);
                    
                    if ($stmt->execute()) {
                        $mensaje = "Producto activado exitosamente";
                        $tipo_mensaje = "success";
                    } else {
                        throw new Exception("Error al activar el producto");
                    }
                    break;
            }
        }
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// Obtener empresas
$sql_empresas = "SELECT id, nombre FROM empresas ORDER BY nombre";
$stmt_empresas = $database->query($sql_empresas);
$empresas = $database->fetchAll($stmt_empresas);

// Obtener productos con información de empresa (para la tabla)
$empresa_filtro = isset($_GET['empresa_filtro']) ? $_GET['empresa_filtro'] : '';
$sql_productos = "SELECT pe.id, pe.nombre_producto, e.nombre as empresa_nombre, pe.activo, pe.fecha_creacion
                  FROM productos_empresa pe 
                  INNER JOIN empresas e ON pe.empresa_id = e.id";

if (!empty($empresa_filtro)) {
    $sql_productos .= " WHERE e.id = ?";
}

$sql_productos .= " ORDER BY e.nombre, pe.nombre_producto";

$stmt_productos = $database->prepare($sql_productos);
if (!empty($empresa_filtro)) {
    $stmt_productos->bindValue(1, $empresa_filtro, PDO::PARAM_INT);
}
$stmt_productos->execute();
$productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

$database->closeConnection();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Empresas y Productos</title>
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
            color: #333;
        }
        .header {
            background: linear-gradient(135deg, #004C97 0%, #0056a8 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            font-size: 24px;
            font-weight: 600;
        }
        .nav-links {
            display: flex;
            gap: 20px;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
            transition: background 0.3s;
        }
        .nav-links a:hover {
            background: rgba(255,255,255,0.1);
        }
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .tabs {
            display: flex;
            background: white;
            border-radius: 8px 8px 0 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .tab {
            flex: 1;
            padding: 15px 20px;
            background: #f8f9fa;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
        }
        .tab.active {
            background: white;
            color: #004C97;
            border-bottom: 3px solid #004C97;
        }
        .tab:hover {
            background: #e9ecef;
        }
        .tab-content {
            background: white;
            padding: 30px;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            min-height: 500px;
        }
        .tab-pane {
            display: none;
        }
        .tab-pane.active {
            display: block;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #004C97;
            box-shadow: 0 0 0 3px rgba(0, 76, 151, 0.1);
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-primary {
            background: linear-gradient(135deg, #004C97 0%, #0056a8 100%);
            color: white;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #003875 0%, #004C97 100%);
            transform: translateY(-1px);
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .status-active {
            color: #28a745;
            font-weight: 600;
        }
        .status-inactive {
            color: #dc3545;
            font-weight: 600;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #004C97;
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .tabs {
                flex-direction: column;
            }
            .nav-links {
                flex-direction: column;
                gap: 10px;
            }
            .header-content {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🏢 Administración del Sistema</h1>
            <div class="nav-links">
                <a href="index.php">📄 Sistema Principal</a>
                <a href="ver_registros.php">📋 Ver Registros</a>
                <a href="admin.php">⚙️ Administrar</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($empresas); ?></div>
                <div class="stat-label">Empresas Registradas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($productos, function($p) { return $p['activo'] == 1; })); ?></div>
                <div class="stat-label">Productos Activos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count(array_filter($productos, function($p) { return $p['activo'] == 0; })); ?></div>
                <div class="stat-label">Productos Inactivos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($productos); ?></div>
                <div class="stat-label">Total Productos</div>
            </div>
        </div>

        <div class="tabs">
            <button class="tab active" onclick="showTab('empresas')">🏢 Gestionar Empresas</button>
            <button class="tab" onclick="showTab('productos')">📦 Gestionar Productos</button>
            <button class="tab" onclick="showTab('listado')">📋 Listado Completo</button>
        </div>

        <div class="tab-content">
            <!-- Tab Empresas -->
            <div id="empresas" class="tab-pane active">
                <h2>Agregar Nueva Empresa</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add_empresa">
                    <div class="form-group">
                        <label for="nombre_empresa">Nombre de la Empresa *</label>
                        <input type="text" id="nombre_empresa" name="nombre_empresa" required 
                               placeholder="Ej: PLASTICOS FR, MINIPET, etc.">
                    </div>
                    <button type="submit" class="btn btn-primary">➕ Agregar Empresa</button>
                </form>

                <h3 style="margin-top: 40px;">Empresas Existentes</h3>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre de la Empresa</th>
                                <th>Productos</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($empresas as $empresa): ?>
                                <?php 
                                $productos_empresa = array_filter($productos, function($p) use ($empresa) {
                                    return $p['empresa_nombre'] === $empresa['nombre'];
                                });
                                ?>
                                <tr>
                                    <td><?php echo $empresa['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($empresa['nombre']); ?></strong></td>
                                    <td>
                                        <span class="status-active"><?php echo count(array_filter($productos_empresa, function($p) { return $p['activo'] == 1; })); ?> activos</span> / 
                                        <span class="status-inactive"><?php echo count(array_filter($productos_empresa, function($p) { return $p['activo'] == 0; })); ?> inactivos</span>
                                    </td>
                                    <td>
                                        <a href="?empresa_filtro=<?php echo $empresa['id']; ?>#listado" class="btn btn-primary btn-sm">Ver Productos</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab Productos -->
            <div id="productos" class="tab-pane">
                <h2>Agregar Nuevo Producto</h2>
                <form method="POST">
                    <input type="hidden" name="action" value="add_producto">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="empresa_id">Empresa *</label>
                            <select id="empresa_id" name="empresa_id" required>
                                <option value="">Seleccione una empresa</option>
                                <?php foreach ($empresas as $empresa): ?>
                                    <option value="<?php echo $empresa['id']; ?>">
                                        <?php echo htmlspecialchars($empresa['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="nombre_producto">Nombre del Producto/Referencia *</label>
                            <input type="text" id="nombre_producto" name="nombre_producto" required 
                                   placeholder="Ej: GOTICICA AZUL NACADO, ENVASE DE 1000 ML, etc.">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">➕ Agregar Producto</button>
                </form>

                <div style="margin-top: 30px; padding: 20px; background: #e3f2fd; border-radius: 6px;">
                    <h4>💡 Consejos para agregar productos:</h4>
                    <ul style="margin-top: 10px; padding-left: 20px;">
                        <li>Usa nombres descriptivos y específicos</li>
                        <li>Incluye medidas, colores o características importantes</li>
                        <li>Mantén consistencia en la nomenclatura</li>
                        <li>Evita caracteres especiales innecesarios</li>
                    </ul>
                </div>
            </div>

            <!-- Tab Listado -->
            <div id="listado" class="tab-pane">
                <div class="filter-section">
                    <h3>Filtrar por Empresa</h3>
                    <form method="GET">
                        <div class="form-row">
                            <div class="form-group">
                                <select name="empresa_filtro" onchange="this.form.submit()">
                                    <option value="">Todas las empresas</option>
                                    <?php foreach ($empresas as $empresa): ?>
                                        <option value="<?php echo $empresa['id']; ?>" 
                                                <?php echo ($empresa_filtro == $empresa['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($empresa['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <?php if (!empty($empresa_filtro)): ?>
                                    <a href="admin.php#listado" class="btn btn-primary">Mostrar Todas</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Empresa</th>
                                <th>Producto/Referencia</th>
                                <th>Estado</th>
                                <th>Fecha Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $producto): ?>
                                <tr>
                                    <td><?php echo $producto['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($producto['empresa_nombre']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($producto['nombre_producto']); ?></td>
                                    <td>
                                        <span class="<?php echo $producto['activo'] ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo $producto['activo'] ? 'Activo' : 'Inactivo'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($producto['fecha_creacion'])); ?></td>
                                    <td>
                                        <?php if ($producto['activo']): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_producto">
                                                <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('¿Está seguro de desactivar este producto?')">
                                                    🚫 Desactivar
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="activate_producto">
                                                <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    ✅ Activar
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Ocultar todas las pestañas
            const tabPanes = document.querySelectorAll('.tab-pane');
            tabPanes.forEach(pane => pane.classList.remove('active'));
            
            // Remover clase active de todos los tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Mostrar la pestaña seleccionada
            document.getElementById(tabName).classList.add('active');
            
            // Activar el tab correspondiente
            event.target.classList.add('active');
            
            // Actualizar URL hash
            window.location.hash = tabName;
        }
        
        // Manejar hash en la URL al cargar la página
        window.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash.substring(1);
            if (hash && ['empresas', 'productos', 'listado'].includes(hash)) {
                showTab(hash);
                // Simular click en el tab correspondiente
                const tabButton = document.querySelector(`[onclick="showTab('${hash}')"]`);
                if (tabButton) {
                    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                    tabButton.classList.add('active');
                }
            }
        });
        
        // Auto-submit del filtro cuando cambia
        document.addEventListener('DOMContentLoaded', function() {
            const empresaSelect = document.querySelector('select[name="empresa_filtro"]');
            if (empresaSelect) {
                empresaSelect.addEventListener('change', function() {
                    this.form.submit();
                });
            }
        });
    </script>
</body>
</html>
