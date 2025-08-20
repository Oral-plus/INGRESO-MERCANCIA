
<?php
class Database {
    // Reemplaza con los detalles de tu servidor SQL Server
    private $serverName = "HERCULES"; // Ej: "localhost\SQLEXPRESS", "192.168.1.100,1433"
    private $connectionOptions = array(
        "Database" => "sistema_mercancia", // Reemplaza con el nombre de tu DB
        "Uid" => "sa", // Reemplaza con tu usuario de DB
        "PWD" => "Sky2022*!", // Reemplaza con tu contraseña de DB
        "CharacterSet" => "UTF-8" // Asegura la codificación correcta
    );
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            // Establecer la conexión a SQL Server
            $this->conn = sqlsrv_connect($this->serverName, $this->connectionOptions);

            if ($this->conn === false) {
                echo "Error de conexión a SQL Server.<br />";
                // Muestra los errores detallados de SQL Server
                die(print_r(sqlsrv_errors(), true));
            }
        } catch (Exception $e) {
            // Captura cualquier otra excepción inesperada
            echo "Error inesperado al conectar: " . $e->getMessage();
            die();
        }
        return $this->conn;
    }

    /**
     * Inicia una transacción de base de datos.
     * @return bool True en caso de éxito, false en caso de error.
     */
    public function beginTransaction() {
        if ($this->conn) {
            return sqlsrv_begin_transaction($this->conn);
        }
        return false;
    }

    /**
     * Confirma una transacción de base de datos.
     * @return bool True en caso de éxito, false en caso de error.
     */
    public function commit() {
        if ($this->conn) {
            return sqlsrv_commit($this->conn);
        }
        return false;
    }

    /**
     * Revierte una transacción de base de datos.
     * @return bool True en caso de éxito, false en caso de error.
     */
    public function rollback() {
        if ($this->conn) {
            return sqlsrv_rollback($this->conn);
        }
        return false;
    }

    /**
     * Ejecuta una consulta SQL, opcionalmente con parámetros, y devuelve el statement.
     * @param string $sql La consulta SQL a ejecutar.
     * @param array $params Un array de parámetros para la consulta preparada.
     * @return resource|false El statement de SQL Server o false si hay un error.
     */
    public function query($sql, $params = []) {
        // Si hay parámetros, sqlsrv_query los espera como un array
        if (!empty($params)) {
            $stmt = sqlsrv_query($this->conn, $sql, $params);
        } else {
            $stmt = sqlsrv_query($this->conn, $sql);
        }

        if ($stmt === false) {
            echo "Error al ejecutar la consulta: " . $sql . "<br />";
            die(print_r(sqlsrv_errors(), true));
        }
        return $stmt;
    }

    /**
     * Obtiene una sola fila de resultados de un statement como un array asociativo.
     * Libera el statement después de obtener la fila.
     * @param resource $stmt El statement de SQL Server.
     * @return array|null Un array asociativo de la fila o null si no hay más filas.
     */
    public function fetch($stmt) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        // No liberar el statement aquí si se espera que fetchAll o más fetches se hagan con el mismo stmt
        // Sin embargo, para el uso actual de fetch() en tu código (donde solo se espera 1 resultado),
        // liberarlo aquí es apropiado para evitar fugas de memoria.
        if ($row !== false) { // Solo liberar si se obtuvo una fila
            sqlsrv_free_stmt($stmt);
        }
        return $row;
    }

    /**
     * Obtiene todas las filas de resultados de un statement como un array de arrays asociativos.
     * Libera el statement después de obtener todas las filas.
     * @param resource $stmt El statement de SQL Server.
     * @return array Un array de arrays asociativos.
     */
    public function fetchAll($stmt) {
        $results = [];
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $results[] = $row;
        }
        sqlsrv_free_stmt($stmt); // Liberar los recursos del statement
        return $results;
    }

    public function closeConnection() {
        if ($this->conn) {
            sqlsrv_close($this->conn);
            $this->conn = null;
        }
    }
}
?>
