

<?php

function connectSQLServer() {
    $serverName = "HERCULES";
    $connectionOptions = [
        "Database" => "sistema_mercancia",
        "Uid" => "sa",
        "PWD" => "Sky2022*!"
    ];
    return sqlsrv_connect($serverName, $connectionOptions);
}

function executeSQLServerQuery($conn, $sql, $params = []) {
    return sqlsrv_query($conn, $sql, $params);
}
?>