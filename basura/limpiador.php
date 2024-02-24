<?php
$server=getcwd();
include $server ."/environment.php";
include $server."/vendor/autoload.php";
include $server."/includes/mysqli.php";
include $server."/includes/mongo.php";


function cleanModule($module,$origin,$destination){
  global $mongoClient;
  $mongoClient->$destination->$module->drop();
  $items = $mongoClient->$origin->$module->find();
  foreach ($items as $item) {
    $item=removeTrash($item);
    $mongoClient->$destination->$module->insertOne($item);   
  }
}

$admin="0-Admin";
$collections = $mongoClient->$admin->Modules->find(["enabled"=>true]);  
foreach ($collections as $collection) {
  cleanModule($collection->name,"1-Descargas","2-Limpiados");
}



$collections = $mongoClient->Descargados->listCollections();
foreach ($collections as $collection) {
  $module = $collection["name"];
  $mongoClient->Limpiados->$module->drop();
  $items = $mongoClient->Descargados->$module->find();
  foreach ($items as $item) {
    $item=removeTrash($item);
    $mongoClient->Limpiados->$module->insertOne($item);    
  }
}





function getDesarrollo($id){
  global $mongoClient;
  $items = $mongoClient->Descargados->Desarrollos->find(['id' => $id]);
  foreach ($items as $item) {
    return removeTrash($item);
  }
}

function getLead($id){
  global $mongoClient;
  $items = $mongoClient->Descargados->Leads->find(['id' => $id]);
  foreach ($items as $item) {
    return removeTrash($item);
  }
}

function getContact($id){
  global $mongoClient;
  $items = $mongoClient->Descargados->Contacts->find(['id' => $id]);
  foreach ($items as $item) {
    return removeTrash($item);
  }
}

function removeTrash($item){
  unset($item['$approval']);
  unset($item['$approval_state']);
  unset($item['$approved']);
  unset($item['$currency_symbol']);
  unset($item['$editable']);
  unset($item['$field_states']);
  unset($item['$in_merge']);
  unset($item['$locked_for_me']);
  unset($item['$orchestration']);
  unset($item['$process_flow']);
  unset($item['$review']);
  unset($item['$review_process']);
  unset($item['$state']);
  unset($item['Created_By']);
  unset($item['Created_Time']);
  unset($item['Last_Activity_Time']);
  unset($item['Modified_By']);
  unset($item['Modified_Time']);
  unset($item['Owner']);
  unset($item['Tag']);
  unset($item['_id']);
  unset($item['$zia_owner_assignment']);
  unset($item['Locked__s']);
  unset($item['Unsubscribed_Mode']);
  unset($item['Unsubscribed_Time']);
  unset($item['Record_Image']);
  unset($item['$followed']);
  unset($item['$followers']);


  return $item;
}


/*function getUnidad($id){
  global $mongoClient;
  global Descargados;
  $productos = $mongoClient->Descargados->Products->find(['id' => $id]);
  foreach ($productos as $producto) {
    unset($producto->_id);
    $producto->Desarrollo = getDesarrollo($producto->Desarrollo->id);
    return $producto;
  }
}*/



/*$collection = $mongoClient->getCollection('Products');
*/


?>
