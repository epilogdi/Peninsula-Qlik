<?php

if($_SERVER["DOCUMENT_ROOT"]){
  $path = $_SERVER["DOCUMENT_ROOT"];
}else{ 
  $name = basename($_SERVER["PHP_SELF"]);
  $path = str_replace($name, "", $_SERVER["PHP_SELF"]); 
  $path = str_replace("/cronjobs/zoho-projects/", "", $path);
}

include "$path/environment.php";
include "$path/vendor/autoload.php";
include "$path/includes/mongo.php";


function getRecords($type){
  global $mongoClient;
  global $database;
  $objetos = [];
  $collection = "$type - tasks";
  $actividadesTasks =  iterator_to_array($mongoClient->$database->$collection->find());
  foreach ($actividadesTasks as $actividadesTask) {  
    $obj = new stdClass();
    $obj->tipo = $type;
    $obj->tasklistName = $actividadesTask->tasklist->name;
    $obj->tasklistName = $actividadesTask->tasklist->name;
    $obj->name = $actividadesTask->name;
    $obj->duration = $actividadesTask->duration;
    $obj->duration_type = $actividadesTask->duration_type;
    $obj->percent_complete = $actividadesTask->percent_complete;
    $obj->start_date = $actividadesTask->start_date;
    $obj->end_date = $actividadesTask->end_date;
    $obj->key = $actividadesTask->key;  
    $obj->statusName = $actividadesTask->status->name;  
    $obj->tasklistId = $actividadesTask->tasklist->id;
    $obj->completed = $actividadesTask->completed;
    foreach ($actividadesTask->custom_fields as $customField) {
      $field = "_".$customField->label_name;
      $obj->$field = $customField->value;
    }
    array_push($objetos,$obj);
  }
  return $objetos;
}

$database = "ZohoProjects-Consolidados";

$records = getRecords("Actividades");
$mongoClient->$database->Tareas->insertMany($records); 
$records = getRecords("Estimaciones");
$mongoClient->$database->Tareas->insertMany($records); 

?>