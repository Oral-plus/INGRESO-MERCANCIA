<?php
require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add_empresa':
                    $nombre_empresa = trim(strtoupper($_POST['nombre_empresa']));

                    if (empty($nombre_empresa)) {
                        throw new Exception("El nombre de la empresa es requerido.");
                    }

                    // Verificar duplicados
                    $sql_check = "SELECT COUNT(*) as count FROM empresas WHERE nombre = ?";
                    $params_check = array(&$nombre_empresa);
                    $stmt_check = $database->query($sql_check, $params_check);
                    $result = $database->fetch($stmt_check);

                    if ($result['count'] > 0) {
                        throw new Exception("La empresa '{$nombre_empresa}' ya existe en la base de datos.");
                    }

                    // Insertar empresa
                    $sql = "INSERT INTO empresas (nombre) VALUES (?)";
                    $params = array(&$nombre_empresa);
                    $database->query($sql, $params);

                    $mensaje = "Empresa '{$nombre_empresa}' agregada exitosamente.";
                    $tipo_mensaje = "success";
                    break;

                case 'add_producto':
                    $empresa_id = (int)$_POST['empresa_id'];
                    $nombre_producto = trim(strtoupper($_POST['nombre_producto']));

                    if (empty($empresa_id) || empty($nombre_producto)) {
                        throw new Exception("Todos los campos son requeridos.");
                    }

                    // Verificar que la empresa existe
                    $sql_empresa = "SELECT nombre FROM empresas WHERE id = ?";
                    $params_empresa = array(&$empresa_id);
                    $stmt_empresa = $database->query($sql_empresa, $params_empresa);
                    $empresa_data = $database->fetch($stmt_empresa);

                    if (!$empresa_data) {
                        throw new Exception("La empresa seleccionada no existe o está inactiva.");
                    }
                    $empresa_nombre_from_db = $empresa_data['nombre'];

                    // Verificar duplicados para la empresa específica
                    $sql_check = "SELECT COUNT(*) as count FROM productos_empresa WHERE empresa_id = ? AND nombre_producto = ?";
                    $params_check = array(&$empresa_id, &$nombre_producto);
                    $stmt_check = $database->query($sql_check, $params_check);
                    $result = $database->fetch($stmt_check);

                    if ($result['count'] > 0) {
                        throw new Exception("Este producto '{$nombre_producto}' ya existe para la empresa '{$empresa_nombre_from_db}'.");
                    }

                    // Insertar producto
                    $sql = "INSERT INTO productos_empresa (empresa_id, nombre_producto, activo) VALUES (?, ?, 1)"; // Assuming 'activo' column
                    $params = array(&$empresa_id, &$nombre_producto);
                    $database->query($sql, $params);

                    $mensaje = "Producto '{$nombre_producto}' agregado exitosamente a '{$empresa_nombre_from_db}'.";
                    $tipo_mensaje = "success";
                    break;
            }
        }
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipo_mensaje = "error";
    }
}

// Obtener empresas para los selectores (siempre se necesita)
$empresas = [];
try {
    $sql_empresas = "SELECT id, nombre FROM empresas ORDER BY nombre";
    $stmt_empresas = $database->query($sql_empresas);
    $empresas = $database->fetchAll($stmt_empresas);
} catch (Exception $e) {
    $mensaje = $e->getMessage();
    $tipo_mensaje = "error";
}

// Obtener estadísticas (siempre se necesita)
$total_empresas = 0;
$total_productos = 0;
try {
    $sql_stats_empresas = "SELECT COUNT(*) as count FROM empresas";
    $stmt_stats_empresas = $database->query($sql_stats_empresas);
    $stats_empresas = $database->fetch($stmt_stats_empresas);
    $total_empresas = $stats_empresas['count'];

    $sql_stats_productos = "SELECT COUNT(*) as count FROM productos_empresa WHERE activo = 1";
    $stmt_stats_productos = $database->query($sql_stats_productos);
    $stats_productos = $database->fetch($stmt_stats_productos);
    $total_productos = $stats_productos['count'];
} catch (Exception $e) {
    error_log("Error al obtener estadísticas: " . $e->getMessage());
    // No es crítico, solo loguear si hay un problema con las estadísticas
} finally {
    $database->closeConnection();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- jQuery debe ir primero -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Select2 CSS (debe ir en <head>) -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Activación de Select2 -->
<script>
    $(document).ready(function () {
        $('#empresa_id').select2({
            placeholder: "Seleccione una empresa",
            allowClear: true
        });
    });
</script>


    <title>Gestionar Empresas y Productos</title>
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
            max-width: 1000px;
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
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
            transition: background 0.3s;
            margin-left: 10px;
        }
        .nav-links a:hover {
            background: rgba(255,255,255,0.1);
        }
        .container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
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
        .form-section {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
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
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
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
        }
        .btn-primary {
            background: linear-gradient(135deg, #004C97 0%, #0056a8 100%);
            color: white;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #003875 0%, #004C97 100%);
            transform: translateY(-1px);
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
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .info-box {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-box h4 {
            color: #1976d2;
            margin-bottom: 10px;
        }
        .required {
            color: #dc3545;
        }
        .section-separator {
            border-top: 1px solid #eee;
            margin: 40px 0;
            padding-top: 40px;
        }
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
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
            <h1>🏢 Gestionar Empresas y Productos</h1>
            <div class="nav-links">
                <a href="index.php">📄 Sistema Principal</a>
                <a href="ver_registros.php">📋 Ver Registros</a>
            </div>
        </div>
    </div>
    <div class="container">
        <!-- Estadísticas -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo htmlspecialchars($total_empresas); ?></div>
                <div class="stat-label">Empresas Activas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo htmlspecialchars($total_productos); ?></div>
                <div class="stat-label">Productos Activos</div>
            </div>
        </div>
        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($tipo_mensaje); ?>">
                <?php echo htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>
        <!-- Formulario para Crear Nueva Empresa -->
        <div class="form-section">
            <h2>Crear Nueva Empresa</h2>
            <div class="info-box">
                <h4>💡 Instrucciones:</h4>
                <ul>
                    <li>Ingresa el nombre completo de la empresa</li>
                    <li>Se convertirá automáticamente a mayúsculas</li>
                    <li>El sistema verificará duplicados</li>
                </ul>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_empresa">
                <div class="form-group">
                    <label for="nombre_empresa">Nombre de la Empresa <span class="required">*</span></label>
                    <input type="text" id="nombre_empresa" name="nombre_empresa" required
                           placeholder="Ej: PLASTICOS FR, MINIPET, SIGMA PLASTICOS..."
                           maxlength="255">
                </div>
                <button type="submit" class="btn btn-primary">➕ Crear Empresa</button>
            </form>
        </div>
        <div class="section-separator"></div>
        <!-- Formulario para Agregar Producto Individual -->
        <div class="form-section">
            <h2>Agregar Producto Individual</h2>
            <div class="info-box">
                <h4>💡 Instrucciones:</h4>
                <ul>
                    <li>Selecciona la empresa a la que pertenece el producto</li>
                    <li>Ingresa el nombre completo del producto/referencia</li>
                    <li>Se verificará que no exista duplicado para esa empresa</li>
                </ul>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_producto">
                <div class="form-row">
                    <div class="form-group">
                        <label for="empresa_id">Empresa <span class="required">*</span></label>
                       <select id="empresa_id" name="empresa_id" required style="width: 100%;">
    <option value="">Seleccione una empresa</option>
    <?php foreach ($empresas as $empresa): ?>
        <option value="<?php echo htmlspecialchars($empresa['id']); ?>">
            <?php echo htmlspecialchars($empresa['nombre']); ?>
        </option>
    <?php endforeach; ?>
</select>

                    </div>
                    <div class="form-group">
                        <label for="nombre_producto">Nombre del Producto <span class="required">*</span></label>
                        <input type="text" id="nombre_producto" name="nombre_producto" required
                               placeholder="Ej: GOTICICA AZUL NACADO">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">➕ Agregar Producto</button>
            </form>
        </div>
    </div>
   <script>
    document.getElementById('nombre_empresa').addEventListener('input', function () {
        this.value = this.value.toUpperCase();
    });

    document.getElementById('nombre_producto').addEventListener('input', function () {
        this.value = this.value.toUpperCase();
    });
</script>



<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>


</body>
</html>