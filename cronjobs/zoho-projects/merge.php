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
include "$path/includes/zoho-projects.php";

function getPropertyArrayString($array,$property){
  $array = json_encode($array);
  $array = json_decode($array);
  $hola = array_column($array,$property);
  return implode(",",$hola);
}

function getTasksKeys($tasks,$dependencyDetails){
  $keys = array();
  foreach ($tasks as $task) {
    array_push($keys, $dependencyDetails[$task]->KEY);
  }
  return implode(",",$keys);
}

function getMilestoneName($id,$project){
  global $mongoClient; 
  global $database;
  $collectionName = "$project - milestones";
  $element = $mongoClient->$database->$collectionName->find();
  $element = iterator_to_array($element);
  return $element[0]->name;
}

function array_size($arr) {
  $byte = 0;
  foreach ($arr as $key => $val) {
    $byte += is_array($val) ? array_size($val) : mb_strlen($val);
  }
  $kb = number_format($byte / 1024, 4);
  $mb = number_format($byte / 1048576, 4);
  $gb = number_format($byte / 1073741824, 4);
  $result = array('Bytes: ' => $byte, 'Kilobytes: ' => $kb, 'Megabytes: ' => $mb, 'Gigabytes: ' => $gb);
  return $result;
}

function consolidar($type){
  global $mongoClient;
  global $database;
  $objetos = [];
  $collection = "$type - tasks";
  $actividadesTasks =  iterator_to_array($mongoClient->$database->$collection->find());
  foreach ($actividadesTasks as $actividadesTask) {
    $obj = new stdClass();
    $obj->tipo = $type;
    //$obj->tasklistName = $actividadesTask->tasklist->name;
    //$obj->name = $actividadesTask->name;
    //$obj->milestone_id = $actividadesTask->milestone_id;
    $obj->milestone = isset($actividadesTask->milestone_id)?getMilestoneName($actividadesTask->milestone_id,$type):null;
    //$obj->created_time = $actividadesTask->created_time;
    //$obj->end_date = $actividadesTask->end_date;
    $obj->completed_time = isset($actividadesTask->completed_time)?$actividadesTask->completed_time:null;
    //$obj->priority = $actividadesTask->priority;
    //$obj->duration = $actividadesTask->duration;
    //$obj->duration_type = $actividadesTask->duration_type;
    //$obj->percent_complete = $actividadesTask->percent_complete;
    //$obj->start_date = $actividadesTask->start_date;
    //$obj->key = $actividadesTask->key;
    //$obj->statusName = $actividadesTask->status->name;  
    //$obj->tasklistId = $actividadesTask->tasklist->id;
    //$obj->completed = $actividadesTask->completed;
    //$obj->ownersName = getPropertyArrayString($actividadesTask->details->owners,'full_name');
    //$obj->ownersId = getPropertyArrayString($actividadesTask->details->owners,'id');


    $obj->actividadesPredecesorasKey = isset($actividadesTask->dependency->predecessor)?getTasksKeys($actividadesTask->dependency->predecessor,$actividadesTask->dependency->dependencyDetails):null;
    $obj->actividadesSucesorasKey = isset($actividadesTask->dependency->successor)?getTasksKeys($actividadesTask->dependency->successor,$actividadesTask->dependency->dependencyDetails):null;

    foreach ($actividadesTask->custom_fields as $customField) {
      $field = "_".$customField->label_name;
      $obj->$field = $customField->value;
    }
    array_push($objetos,$obj);
  }
  return $objetos;
}


$database = "ZohoProjects";
$start = microtime(true);
$dateStart = date('Y-m-d H:i:s');

$module= "Estimaciones - tasks";
$mongoClient->$database->$module->aggregate(
  [['$addFields' => ['tipo' => 'Estimaciones']],['$merge' => ['into' => 'tasks']]]
);

$module= "Actividades - tasks";
$mongoClient->$database->$module->aggregate(
  [['$addFields' => ['tipo' => 'Actividades']],['$merge' => ['into' => 'tasks']]]
);

$module= "Estimaciones - milestones";
$mongoClient->$database->$module->aggregate(
  [['$merge' => ['into' => 'milestones']]]
);

$module= "Actividades - milestones";
$mongoClient->$database->$module->aggregate(
  [['$merge' => ['into' => 'milestones']]]
);


/*$mongoClient->$database->$module->aggregate(
  [['$project' => [
    'tasklistName' => '$tasklist.name',
    'name' => '$name',
    'milestone_id' => '$milestone_id',
    'created_time' => '$created_time',
    'end_date' => '$end_date',
    'priority' => '$priority',
    'duration' => '$duration',
    'duration_type' => '$duration_type',
    'percent_complete' => '$percent_complete',
    'start_date' => '$start_date',
    'key' => '$key',
    'statusName' => '$status.name',
    'tasklistId' => '$tasklist.id',
    'completed' => '$completed',
    'ownersName' => ['$arrayElemAt' =>['$details.owners.full_name', 0]],
    'ownersId' => ['$arrayElemAt' =>['$details.owners.id', 0]],
    '_En tiempo de programa' => '$custom_fields.xxxxx',
    '_Codigo de Obra' => '$custom_fields.xxxxx',
    '_Partida Presupuestal' => '$custom_fields.xxxxx',
    '_Prioridad' => '$custom_fields.xxxxx',
    '_Falta de seguimiento por Propietario' => '$custom_fields.xxxxx',
    '_Area Responsable' => '$custom_fields.xxxxx',
    '_Especialidad' => '$custom_fields.xxxxx',
    '_Direccion Proyectos' => '$custom_fields.xxxxx',
    '_Subpartida Presupuestal' => '$custom_fields.xxxxx',
    '_Requerimiento' => '$custom_fields.xxxxx',
    '_Monto Pedido' => '$custom_fields.xxxxx',
    '_Pagaré SI / NO' => '$custom_fields.xxxxx',
    '_Obstrucción' => '$custom_fields.xxxxx',
    '_Contratistas' => '$custom_fields.xxxxx',
    '_Autorizado Control Presupuestal' => '$custom_fields.xxxxx',
    '_Estatus de Compra' => '$custom_fields.xxxxx',
    '_Estatus Costos' => '$custom_fields.xxxxx',
    '_Monto Autorizado' => '$custom_fields.xxxxx',
    '_Codigo de Pedido' => '$custom_fields.xxxxx',
    '_Fecha Compromiso' => '$custom_fields.xxxxx',


  ]], ['$merge' => ['into' => 'salida']]]
);

$mongoClient->$database->$module->aggregate(
  [
    ['$lookup' => ['from' => 'Actividades - milestones', 'localField' => 'milestone_id', 'foreignField' => 'id', 'as' => 'name']],
    ['$merge' => ['into' => 'salida', 'whenMatched' => 'replace', 'whenNotMatched' => 'discard']],
  ]
);*/

/*$module= "Estimaciones - tasks";
$mongoClient->$database->$module->aggregate(
  [['$project' => [
    'tasklistName' => '$tasklist.name',
    'name' => '$name',
    'milestone_id' => '$milestone_id',
    'created_time' => '$created_time',
    'end_date' => '$end_date',
    'priority' => '$priority',
    'duration' => '$duration',
    'duration_type' => '$duration_type',
    'percent_complete' => '$percent_complete',
    'start_date' => '$start_date',
    'key' => '$key',
    'statusName' => '$status.name',
    'tasklistId' => '$tasklist.id',
    'completed' => '$completed'

  ]], ['$merge' => ['into' => 'salida']]]
);*/

/*$module= "Estimaciones - tasks";
$mongoClient->$database->$module->aggregate(
  [['$project' => ['field1' => '$details.owners', 'field2' => '$custom_fields']], ['$merge' => ['into' => 'salida']]]
);*/


//$mongoClient->$database->Tareas->drop();
//$records = consolidar("Actividades");
//$mongoClient->$database->Tareas->insertMany($records);
//$records = consolidar("Estimaciones");
//$mongoClient->$database->Tareas->insertMany($records); 

$cron = new stdClass(); 
$cron->type="x";
$cron->minutes=(microtime(true) - $start)/60;
$cron->startUTC=$dateStart;
$cron->endUTC=date('Y-m-d H:i:s');

$mongoClient->$database->Cronjobs->insertOne($cron);

?>