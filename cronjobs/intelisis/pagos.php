<?php
if($_SERVER["DOCUMENT_ROOT"]){
  $path = $_SERVER["DOCUMENT_ROOT"];
}else{ 
  $name = basename($_SERVER["PHP_SELF"]);
  $path = str_replace($name, "", $_SERVER["PHP_SELF"]); 
  $path = str_replace("/cronjobs/intelisis/", "", $path);
}

include "$path/environment.php";
include "$path/vendor/autoload.php";
include "$path/includes/mongo.php";

$serverName = "tcp:$ENV_INTELISIS_MSSQL_IP, $ENV_INTELISIS_MSSQL_PORT";
$connectionInfo = array( "Database"=>$ENV_INTELISIS_MSSQL_DATABASE, "UID"=>$ENV_INTELISIS_MSSQL_USER, "PWD"=>$ENV_INTELISIS_MSSQL_PASSWORD,"TrustServerCertificate" => true);
$conn = sqlsrv_connect( $serverName, $connectionInfo);

if( $conn ) {
  echo "Successfuly connected.<br />";
}else{
  echo "Connection error.<br />";
  die( print_r( sqlsrv_errors(), true));
}
$database="EGRESOS";
$modulo="Pagos";
$mongoClient->$database->$modulo->drop();

$sql = "SELECT 
	c.FechaEmision, 
	p.Nombre, 
	c.Referencia, 
	c.Observaciones,
	c.Importe, 
	c.concepto,
	ve.concepto,
	ve.contratoID,
	ve.importe
FROM CXP c, PROV p, vic_ContratoEstimacionGasto ve
WHERE 1=1
AND c.MOV = 'PAGO'
AND c.ESTATUS = 'CONCLUIDO'
AND c.ContratoMov = 'Contrato Constr.Prov'
AND p.Proveedor = c.Proveedor
AND ve.EstimacionID = c.vicEstimacionID";

$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
  die(print_r(sqlsrv_errors(), true));
}

$objetos = array();
while ($row = sqlsrv_fetch_object($stmt)) {
  $obj = new stdClass();
  foreach ($row as $key => $value) {     
    $obj->$key = $value;
  }
  array_push($objetos, $obj);
  if(sizeof($objetos) == 500){
    $mongoClient->$database->Pagos->insertMany($objetos);
    $objetos = array();
  }

}
$mongoClient->$database->Pagos->insertMany($objetos);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);




?>