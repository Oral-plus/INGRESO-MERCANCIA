<?php
// Archivo para probar la conexión y ver si los productos se cargan correctamente
require_once 'config/database.php';

echo "<h2>Test de Conexión y Productos</h2>";

try {
    $database = new Database();
    $conn = $database->getConnection();

    if ($conn) {
        echo "✅ Conexión a la base de datos exitosa<br><br>";

        // Consulta de empresas
        $sql_empresas = "SELECT id, nombre FROM empresas ORDER BY nombre";
        $stmt_empresas = sqlsrv_query($conn, $sql_empresas);

        if (!$stmt_empresas) {
            throw new Exception(print_r(sqlsrv_errors(), true));
        }

        $empresas = [];
        while ($row = sqlsrv_fetch_array($stmt_empresas, SQLSRV_FETCH_ASSOC)) {
            $empresas[] = $row;
        }

        echo "<h3>Empresas encontradas (" . count($empresas) . "):</h3>";
        foreach ($empresas as $empresa) {
            echo "- " . htmlspecialchars($empresa['nombre']) . " (ID: " . $empresa['id'] . ")<br>";
        }

        echo "<br><h3>Test de productos por empresa:</h3>";

        // Probar productos para las primeras 3 empresas
        $empresas_test = array_slice($empresas, 0, 3);

        foreach ($empresas_test as $empresa) {
            $sql_productos = "SELECT pe.nombre_producto 
                              FROM productos_empresa pe 
                              WHERE pe.empresa_id = ? AND pe.activo = 1 
                              ORDER BY pe.nombre_producto";

            $params = [$empresa['id']];
            $stmt_productos = sqlsrv_query($conn, $sql_productos, $params);

            if (!$stmt_productos) {
                echo "❌ Error en la consulta de productos para empresa ID " . $empresa['id'] . "<br>";
                continue;
            }

            $productos = [];
            while ($row = sqlsrv_fetch_array($stmt_productos, SQLSRV_FETCH_ASSOC)) {
                $productos[] = $row;
            }

            echo "<strong>" . htmlspecialchars($empresa['nombre']) . "</strong> (" . count($productos) . " productos):<br>";

            if (count($productos) > 0) {
                foreach (array_slice($productos, 0, 5) as $producto) {
                    echo "&nbsp;&nbsp;• " . htmlspecialchars($producto['nombre_producto']) . "<br>";
                }
                if (count($productos) > 5) {
                    echo "&nbsp;&nbsp;... y " . (count($productos) - 5) . " más<br>";
                }
            } else {
                echo "&nbsp;&nbsp;❌ No se encontraron productos<br>";
            }
            echo "<br>";
        }

        $database->closeConnection();
    } else {
        echo "❌ Error de conexión a la base de datos<br>";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>
