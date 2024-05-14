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

function consolidar($type){
  global $mongoClient;
  global $database;
  $objetos = [];
  $collection = "$type - tasks";
  $actividadesTasks =  iterator_to_array($mongoClient->$database->$collection->find());
  foreach ($actividadesTasks as $actividadesTask) {  
    $obj = new stdClass();
    $obj->tipo = $type;
    $obj->tasklistName = $actividadesTask->tasklist->name;
    $obj->name = $actividadesTask->name;
    $obj->milestone_id = $actividadesTask->milestone_id;
    $obj->milestone = isset($actividadesTask->milestone_id)?getMilestoneName($actividadesTask->milestone_id,$type):null;
    $obj->created_time = $actividadesTask->created_time;
    $obj->end_date = $actividadesTask->end_date;
    $obj->completed_time = isset($actividadesTask->completed_time)?$actividadesTask->completed_time:null;
    $obj->priority = $actividadesTask->priority;
    $obj->duration = $actividadesTask->duration;
    $obj->duration_type = $actividadesTask->duration_type;
    $obj->percent_complete = $actividadesTask->percent_complete;
    $obj->start_date = $actividadesTask->start_date;
    $obj->key = $actividadesTask->key;
    $obj->statusName = $actividadesTask->status->name;  
    $obj->tasklistId = $actividadesTask->tasklist->id;
    $obj->completed = $actividadesTask->completed;
    $obj->ownersName = getPropertyArrayString($actividadesTask->details->owners,'full_name');
    $obj->ownersId = getPropertyArrayString($actividadesTask->details->owners,'id');


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

function storeCollection($project,$module){ 
  global $mongoClient;
  global $database;
  global $ENV_ZOHO_PROJECTS_PORTAL_ID;
  global $page;
  $client = new GuzzleHttp\Client();
  $token = getLastValidToken();
  $headers = [
    "Authorization" => "Zoho-oauthtoken $token->access_token"
  ];
  $url = "https://projectsapi.zoho.com/restapi/portal/$ENV_ZOHO_PROJECTS_PORTAL_ID/projects/$project->id/$module/?index=$page&range=200";
  $request = new \GuzzleHttp\Psr7\Request("GET", $url, $headers);

  try {
    $response = $client->sendAsync($request)->wait();
    $response = json_decode($response->getBody(),true);
    if($response){
      $records = $response[$module];
      $collectionName = "$project->name - $module";
      $mongoClient->$database->$collectionName->insertMany($records);
      $page++;
      $page=$page*100;
      storeCollection($project,$module);
    }

  } catch (Exception $e) {

    error_log("Caught exception:". $e->getMessage());
  }

}

$database = "ZohoProjects";
$start = microtime(true);
$dateStart = date('Y-m-d H:i:s');

$projects = $mongoClient->$database->Projects->find();  
foreach ($projects as $project) {
  $modules = $mongoClient->$database->Modules->find(["enabled"=>true]);  
  foreach ($modules as $module) {
    $page=0;   

    $project->name = str_replace("Seguimiento ", "", $project->name);
    $project->name = str_replace(" Peninsula", "", $project->name);
    $collectionName = "$project->name - $module->name";
    $mongoClient->$database->$collectionName->drop();
    storeCollection($project,$module->name);
  }  
}

$mongoClient->$database->Tareas->drop();
$records = consolidar("Actividades");
$mongoClient->$database->Tareas->insertMany($records); 
$records = consolidar("Estimaciones");
$mongoClient->$database->Tareas->insertMany($records); 

$cron = new stdClass(); 
$cron->type="Descarga";
$cron->minutes=(microtime(true) - $start)/60;
$cron->startUTC=$dateStart;
$cron->endUTC=date('Y-m-d H:i:s');

$mongoClient->$database->Cronjobs->insertOne($cron);

?>