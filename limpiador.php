<?php
$server=getcwd();
include $server ."/environment.php";
include $server."/vendor/autoload.php";
include $server."/includes/mysqli.php";
include $server."/includes/mongo.php";

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

function procesarPotentials(){
  global $mongoClient;
  global Descargados;

  $condition = [
    '$and' => [
      [
        "InmuebleX" => null,
        "LeadX" => null,
        "Contact_NameX" => null
      ]
    ]
  ];


  $items = $mongoClient->Descargados->Potentials->find();
  //$items = $mongoClient->Descargados->Potentials->find(['id' => "5153690000031344080"]);
  foreach ($items as $item) {
    $producto = getProducto($item->Inmueble->id);
    $lead = getLead($item->Lead->id);
    $contact = getContact($item->Contact_Name->id);
    $mongoClient->Descargados->Potentials->updateOne(
      [ 'id' => $item->id ],
      [ '$set' => ['InmuebleX' => $producto, 'LeadX' => $lead, 'Contact_NameX' => $contact ]],
      ["upsert" => true, "multiple" => true]
    );
    echo $item->Potentials->id."<br>";
  }
  /*$items=$mongoClient->Descargados->Potentials->find(['id' => "5153690000031344080"]);
  foreach ($items as $item) {
    echo json_encode($item);
  }*/


}

function getProducto($id){
  global $mongoClient;
  $items = $mongoClient->Descargados->Products->find(['id' => $id]);
  foreach ($items as $item) {
    return removeTrash($item);
  }
}

function procesarProductos(){
  global $mongoClient;
  $items = $mongoClient->Descargados->Products->find(["DesarrolloX" => null]);
  foreach ($items as $item) {
    $desarrollo = getDesarrollo($item->Desarrollo->id);
    $mongoClient->Descargados->Products->updateOne(
      [ 'id' => $item->id ],
      [ '$set' => [ 'DesarrolloX' => $desarrollo ]]
    );
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
