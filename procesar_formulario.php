<?php
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$database = new Database();
$conn = $database->getConnection();

try {
    $database->beginTransaction();

    // Obtener datos del formulario
    $tipo = $_POST['tipo'];
    $empresa_nombre = $_POST['empresa'];
    $fecha = $_POST['fecha'];
    $numero_factura = $_POST['numero_factura'];
    $recibido_completo = $_POST['recibido_completo'];
    $nombre_quien_recibe = $_POST['nombre_quien_recibe'];
    $inconsistencias = $_POST['inconsistencias'] ?? '';
    $cantidad_total = $_POST['cantidad_total'] ?? 0;

    // Datos específicos de factura
    $fabricante = $_POST['fabrica'] ?? null;
    $valor_unitario = $_POST['valor_unitario'] ?? null;
    $valor_total = $_POST['valor_total'] ?? null;
    // Obtener array de remisiones relacionadas
    $remisiones_relacionadas = isset($_POST['remisiones_relacionadas']) && is_array($_POST['remisiones_relacionadas']) ? $_POST['remisiones_relacionadas'] : [];

    // Productos
    $productos_array = $_POST['producto'] ?? [];
    $unidades = $_POST['unidad'] ?? [];
    $cantidades = $_POST['cantidad'] ?? [];

    // Crear array de productos
    $productos = [];
    for ($i = 0; $i < count($productos_array); $i++) {
        if (!empty($productos_array[$i]) && !empty($unidades[$i]) && !empty($cantidades[$i])) {
            $productos[] = [
                'producto' => $productos_array[$i],
                'unidad'   => $unidades[$i],
                'cantidad' => floatval($cantidades[$i])
            ];
        }
    }

    // Obtener ID de la empresa
    $sql = "SELECT id FROM empresas WHERE nombre = ?";
    $stmt = $database->query($sql, [$empresa_nombre]);
    $empresa = $database->fetch($stmt);

    if (!$empresa) {
        throw new Exception("Empresa no encontrada");
    }

    $empresa_id = $empresa['id'];

    if ($tipo === 'FACTURA') {
        // Validar que las remisiones pertenezcan a la empresa y estén abiertas
        if (!empty($remisiones_relacionadas)) {
            $placeholders = implode(',', array_fill(0, count($remisiones_relacionadas), '?'));
            $sql = "SELECT id, estado FROM remisiones WHERE id IN ($placeholders) AND empresa_id = ? AND estado = 'ABIERTA'";
            $params = array_merge($remisiones_relacionadas, [$empresa_id]);
            $stmt = $database->query($sql, $params);
            $remisiones = $database->fetchAll($stmt);

            if (count($remisiones) !== count($remisiones_relacionadas)) {
                throw new Exception("Alguna de las remisiones seleccionadas no pertenece a la empresa o ya está cerrada");
            }
        }

        // Insertar factura (SOLO UNA remisión_id)
        $sql = "INSERT INTO facturas 
                (empresa_id, fecha, numero_factura, productos, fabricante, valor_unitario, valor_total, recibido_completo, nombre_quien_recibe, inconsistencias, cantidad_total, remision_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [
            $empresa_id,
            $fecha,
            $numero_factura,
            json_encode($productos, JSON_UNESCAPED_UNICODE),
            $fabricante,
            $valor_unitario,
            $valor_total,
            $recibido_completo,
            $nombre_quien_recibe,
            $inconsistencias,
            $cantidad_total,
            !empty($remisiones_relacionadas) ? (int)$remisiones_relacionadas[0] : null // 👈 solo primer ID
        ];
        $database->query($sql, $params);

        // Marcar todas las remisiones seleccionadas como cerradas
        if (!empty($remisiones_relacionadas)) {
            $placeholders = implode(',', array_fill(0, count($remisiones_relacionadas), '?'));
            $sql = "UPDATE remisiones SET estado = 'CERRADA' WHERE id IN ($placeholders)";
            $database->query($sql, $remisiones_relacionadas);
        }

        $mensaje = "Factura procesada correctamente";
        if (!empty($remisiones_relacionadas)) {
            $mensaje .= " y " . count($remisiones_relacionadas) . " remisión(es) marcada(s) como cerrada(s)";
        }

    } else { // REMISION
        $sql = "INSERT INTO remisiones 
                (empresa_id, fecha, numero_factura, productos, recibido_completo, nombre_quien_recibe, inconsistencias, cantidad_total, estado) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'ABIERTA')";
        $params = [
            $empresa_id,
            $fecha,
            $numero_factura,
            json_encode($productos, JSON_UNESCAPED_UNICODE),
            $recibido_completo,
            $nombre_quien_recibe,
            $inconsistencias,
            $cantidad_total
        ];
        $database->query($sql, $params);

        $mensaje = "Remisión procesada correctamente";
    }

    $database->commit();
    $database->closeConnection();
    header('Location: index.php?success=' . urlencode($mensaje));
    exit;

} catch (Exception $e) {
    $database->rollback();
    $database->closeConnection();
    header('Location: index.php?error=' . urlencode('Error al procesar: ' . $e->getMessage()));
    exit;
}
?>
