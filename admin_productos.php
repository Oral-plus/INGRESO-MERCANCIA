<?php
require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_empresa':
                $nombre = trim($_POST['nombre_empresa']);
                if (!empty($nombre)) {
                    try {
                        $sql = "INSERT INTO empresas (nombre) VALUES (:nombre)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(':nombre', $nombre);
                        $stmt->execute();
                        $success = "Empresa agregada exitosamente";
                    } catch (PDOException $e) {
                        if ($e->getCode() == 23000) {
                            $error = "La empresa ya existe";
                        } else {
                            $error = "Error al agregar empresa: " . $e->getMessage();
                        }
                    }
                }
                break;
                
            case 'add_producto':
                $empresa_id = $_POST['empresa_id'];
                $nombre_producto = trim($_POST['nombre_producto']);
                if (!empty($empresa_id) && !empty($nombre_producto)) {
                    try {
                        $sql = "INSERT INTO productos (empresa_id, nombre) VALUES (:empresa_id, :nombre)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(':empresa_id', $empresa_id);
                        $stmt->bindParam(':nombre', $nombre_producto);
                        $stmt->execute();
                        $success = "Producto agregado exitosamente";
                    } catch (PDOException $e) {
                        $error = "Error al agregar producto: " . $e->getMessage();
                    }
                }
                break;
                
            case 'toggle_producto':
                $producto_id = $_POST['producto_id'];
                $activo = $_POST['activo'] == '1' ? 0 : 1;
                try {
                    $sql = "UPDATE productos SET activo = :activo WHERE id = :id";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':activo', $activo);
                    $stmt->bindParam(':id', $producto_id);
                    $stmt->execute();
                    $success = "Estado del producto actualizado";
                } catch (PDOException $e) {
                    $error = "Error al actualizar producto: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get all companies
$sql = "SELECT id, nombre FROM empresas ORDER BY nombre";
$stmt = $conn->prepare($sql);
$stmt->execute();
$empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get products with company names
$sql = "SELECT p.id, p.nombre, p.activo, e.nombre as empresa_nombre, e.id as empresa_id
        FROM productos p 
        JOIN empresas e ON p.empresa_id = e.id 
        ORDER BY e.nombre, p.nombre";
$stmt = $conn->prepare($sql);
$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$database->closeConnection();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrar Empresas y Productos</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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
            padding: 20px;
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
            padding: 30px;
            background: linear-gradient(135deg, #004C97 0%, #0056a8 100%);
            color: white;
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .content {
            padding: 30px;
        }
        
        .tabs {
            display: flex;
            border-bottom: 2px solid #e0e0e0;
            margin-bottom: 30px;
        }
        
        .tab {
            padding: 12px 24px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            color: #666;
            border-bottom: 3px solid transparent;
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #004C97;
            color: white;
        }
        
        .btn-primary:hover {
            background: #003875;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .status-active {
            color: #28a745;
            font-weight: 600;
        }
        
        .status-inactive {
            color: #dc3545;
            font-weight: 600;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #004C97;
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏢 Administrar Empresas y Productos</h1>
            <p>Gestión completa de empresas y sus productos</p>
        </div>
        
        <div class="content">
            <a href="index.php" class="back-link">← Volver al formulario principal</a>
            
            <?php if (isset($success)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <div class="tabs">
                <button class="tab active" onclick="showTab('empresas')">Empresas</button>
                <button class="tab" onclick="showTab('productos')">Productos</button>
                <button class="tab" onclick="showTab('lista')">Lista Completa</button>
            </div>
            
            <!-- Tab Empresas -->
            <div id="empresas" class="tab-content active">
                <h2>Agregar Nueva Empresa</h2>
                <form method="post">
                    <input type="hidden" name="action" value="add_empresa">
                    <div class="form-group">
                        <label for="nombre_empresa">Nombre de la Empresa</label>
                        <input type="text" id="nombre_empresa" name="nombre_empresa" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Agregar Empresa</button>
                </form>
                
                <h3 style="margin-top: 30px;">Empresas Registradas (<?php echo count($empresas); ?>)</h3>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Productos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empresas as $empresa): ?>
                            <?php
                            $count_productos = array_filter($productos, function($p) use ($empresa) {
                                return $p['empresa_id'] == $empresa['id'] && $p['activo'] == 1;
                            });
                            ?>
                            <tr>
                                <td><?php echo $empresa['id']; ?></td>
                                <td><?php echo htmlspecialchars($empresa['nombre']); ?></td>
                                <td><?php echo count($count_productos); ?> productos activos</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Tab Productos -->
            <div id="productos" class="tab-content">
                <h2>Agregar Nuevo Producto</h2>
                <form method="post">
                    <input type="hidden" name="action" value="add_producto">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="empresa_id">Empresa</label>
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
                            <label for="nombre_producto">Nombre del Producto</label>
                            <input type="text" id="nombre_producto" name="nombre_producto" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Agregar Producto</button>
                </form>
            </div>
            
            <!-- Tab Lista -->
            <div id="lista" class="tab-content">
                <h2>Lista Completa de Productos (<?php echo count($productos); ?>)</h2>
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Empresa</th>
                            <th>Producto</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $producto): ?>
                            <tr>
                                <td><?php echo $producto['id']; ?></td>
                                <td><?php echo htmlspecialchars($producto['empresa_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($producto['nombre']); ?></td>
                                <td>
                                    <span class="<?php echo $producto['activo'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $producto['activo'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_producto">
                                        <input type="hidden" name="producto_id" value="<?php echo $producto['id']; ?>">
                                        <input type="hidden" name="activo" value="<?php echo $producto['activo']; ?>">
                                        <button type="submit" class="btn <?php echo $producto['activo'] ? 'btn-warning' : 'btn-success'; ?>">
                                            <?php echo $producto['activo'] ? 'Desactivar' : 'Activar'; ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }
        
        $(document).ready(function() {
            $('#empresa_id').select2({
                placeholder: "Seleccione una empresa",
                allowClear: true
            });
        });
    </script>
</body>
</html>
