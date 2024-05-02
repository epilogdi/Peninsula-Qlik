<?php
$ENV_MSSQL_IP = "200.56.126.246";
$ENV_MSSQL_PORT = "1433";
$ENV_MSSQL_INSTANCE = "SVR-SQLINT";
$ENV_MSSQL_USER = "ConexionBI";
$ENV_MSSQL_PASSWORD = "Ytrewq2024";
$ENV_MSSQL_DATABASE = "Peninsula";

//$serverName = "//".$ENV_MSSQL_IP."\".$ENV_MSSQL_INSTANCE.", ".$ENV_MSSQL_PORT";
$serverName = "tcp:$ENV_MSSQL_IP, $ENV_MSSQL_PORT";
$connectionInfo = array( "Database"=>$ENV_MSSQL_DATABASE, "UID"=>$ENV_MSSQL_USER, "PWD"=>$ENV_MSSQL_PASSWORD);
$conn = sqlsrv_connect( $serverName, $connectionInfo);

if( $conn ) {
     echo "Successfuly connected.<br />";
}else{
     echo "Connection error.<br />";
     die( print_r( sqlsrv_errors(), true));
}




?>