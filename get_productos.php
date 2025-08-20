<?php
require_once 'config/database.php';

header('Content-Type: application/json');

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $empresa_id = $_GET['empresa_id'] ?? '';
    
    if (empty($empresa_id)) {
        echo json_encode([]);
        exit;
    }
    
    $sql = "SELECT p.id, p.nombre 
            FROM productos p 
            WHERE p.empresa_id = :empresa_id 
            AND p.activo = 1 
            ORDER BY p.nombre ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':empresa_id', $empresa_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($productos);
    
} catch (Exception $e) {
    error_log("Error en get_productos.php: " . $e->getMessage());
    echo json_encode([]);
} finally {
    if (isset($database)) {
        $database->closeConnection();
    }
}
?>
