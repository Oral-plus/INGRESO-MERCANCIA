<?php
// Conexión a SQL Server
$serverName = "192.168.2.244"; 
$connectionInfo = array(
    "Database" => "sistema_mercancia", 
    "UID" => "sa", 
    "PWD" => "Sky2022*!"
);
$conn = sqlsrv_connect($serverName, $connectionInfo);

// Verificar conexión
if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}

// Obtener datos del formulario
$empresa             = $_POST['empresa'];
$fecha               = $_POST['fecha'];
$numero_factura      = $_POST['numero_factura'];
$productos           = $_POST['producto']; // Array de productos
$fabrica             = $_POST['fabrica'];

// UNIDAD
$unidad_peso         = $_POST['unidad_peso'];  // Aquí se organiza la unidad
$cantidad            = $_POST['cantidad'];
$valor_unitario      = $_POST['valor_unitario'];
$valor_total         = $_POST['valor_total'];

$recibido_completo   = $_POST['recibido_completo'];
$nombre_quien_recibe = $_POST['nombre_quien_recibe'];
$inconsistencias     = $_POST['inconsistencias'];

// Consulta de inserción
$sql = "INSERT INTO Mercancia (
    empresa, fecha, numero_factura, producto, fabrica, unidad_peso,
    cantidad, valor_unitario, valor_total, recibido_completo,
    nombre_quien_recibe, inconsistencias
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$insertadoCorrectamente = true;

// Insertar cada producto como una fila individual
foreach ($productos as $producto) {
    $params = array(
        $empresa,
        $fecha,
        $numero_factura,
        $producto,
        $fabrica,
        $unidad_peso,
        $cantidad,
        $valor_unitario,
        $valor_total,
        $recibido_completo,
        $nombre_quien_recibe,
        $inconsistencias
    );

    $stmt = sqlsrv_query($conn, $sql, $params);

    if ($stmt === false) {
        $insertadoCorrectamente = false;
        echo "<pre>";
        print_r(sqlsrv_errors());
        echo "</pre>";
        break;
    }
}

// Cerrar conexión
sqlsrv_close($conn);

// Redirigir o mostrar error
if ($insertadoCorrectamente) {
    echo "<script>
        alert('✅ Registro insertado correctamente');
        window.location.href='gracias.html';
    </script>";
}
?>
