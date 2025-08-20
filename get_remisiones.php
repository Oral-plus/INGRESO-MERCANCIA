<?php
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['empresa']) || empty($_GET['empresa'])) {
    echo json_encode([]);
    exit;
}

$database = new Database();
$conn = $database->getConnection();

try {
    $sql = "SELECT id FROM empresas WHERE nombre = ?";
    $stmt = $database->query($sql, [$_GET['empresa']]);
    $empresa = $database->fetch($stmt);

    if (!$empresa) {
        echo json_encode([]);
        exit;
    }

    $sql = "SELECT id, numero_factura, fecha, cantidad_total 
            FROM remisiones 
            WHERE empresa_id = ? AND estado = 'ABIERTA'
            ORDER BY fecha DESC, numero_factura";
    $stmt = $database->query($sql, [$empresa['id']]);
    $remisiones = $database->fetchAll($stmt);

    // Formatear fechas para JSON
    foreach ($remisiones as &$remision) {
        if ($remision['fecha'] instanceof DateTime) {
            $remision['fecha'] = $remision['fecha']->format('Y-m-d');
        }
    }

    echo json_encode($remisiones);

} catch (Exception $e) {
    echo json_encode([]);
} finally {
    $database->closeConnection();
}
?>
