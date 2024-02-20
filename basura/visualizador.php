<?php
$server=getcwd();
include $server ."/environment.php";
include $server."/vendor/autoload.php";
include $server."/includes/mysqli.php";
include $server."/includes/mongo.php";

$module = $_GET["module"];

$salida = new stdClass();

visualizar($module);

function visualizar($module){
  global $mongoClient;
  global $database;
  global $loop;
  global $salida;
  $loop = 0;
  $items = $mongoClient->$database->$module->find();
  foreach ($items as $item) {    
    if($loop==0){//se establece el objeto de salida para que tenga todas las propiedades
      $salida = $item;
    }else{
      if(contarCamposNulos($salida)==0){
        echo json_encode($salida);
        break;
      }else{
        buscarCamposNoNulos($item);
      }
    }
    $loop ++;
  }
  echo json_encode($salida);
}


function contarCamposNulos($object){
  $camposNulos=0;
  foreach ($object as $key => $value) {
    if(is_null($value)){
      $camposNulos++;
    }
  }
  return $camposNulos;
}

function buscarCamposNoNulos($object){
  global $salida;
  foreach ($object as $key => $value) {
    if(!is_null($value)){
      $salida->$key=$value;
    }
  }
}


?>
