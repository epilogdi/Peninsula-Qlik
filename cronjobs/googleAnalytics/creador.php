<?php

if($_SERVER["DOCUMENT_ROOT"]){
  $path = $_SERVER["DOCUMENT_ROOT"];
}else{ 
  $name = basename($_SERVER["PHP_SELF"]);
  $path = str_replace($name, "", $_SERVER["PHP_SELF"]); 
  $path = str_replace("/cronjobs/googleAnalytics/", "", $path);
}

include "$path/environment.php";
include "$path/vendor/autoload.php";
include "$path/includes/mongo.php";

$obj = new stdClass();
$obj->nameAlias = $_GET["nameAlias"];
$obj->viewId = intval($_GET["viewId"]);
$obj->enabled = true;

$collection = "Control-Historico";
$existe = $mongoClient->GoogleAnalytics->$collection->count(["viewId"=>$obj->viewId]);
if($existe == 0){
  $mongoClient->GoogleAnalytics->$collection->insertOne($obj);
  echo "Se inserto el registro con éxito.";
}else{
   echo "El registro ya existe.";
}



?>