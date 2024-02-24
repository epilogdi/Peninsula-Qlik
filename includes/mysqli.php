<?php
$mysqli = new mysqli($ENV_MYSQL_HOST, $ENV_MYSQL_USER, $ENV_MYSQL_PASSWORD, $ENV_MYSQL_DATABASE);
mysqli_set_charset($mysqli,"utf8");

if ($mysqli->connect_errno) {
  $resultado=reportarError("001",$path,$query);
  echo json_encode($resultado);
  exit();	
}
?>