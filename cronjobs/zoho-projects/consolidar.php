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

$database = "ZohoProjects";
$start = microtime(true);
$dateStart = date('Y-m-d H:i:s');

$module= "tasks";

$mongoClient->$database->$module->aggregate(
  [
    ['$project' => [
      'id' => '$id',
      'tasklistName' => '$tasklist.name',
      'projectName' => '$projectName',
      'projectId' => '$projectId',
      'name' => '$name',
      'milestone_id' => '$milestone_id',
      'created_time' => '$created_time',
      'end_date' => '$end_date',
      'completed_time' => '$completed_time',
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
      'duration' => '$duration',
      'custom_fields' => '$custom_fields',
      'dependency' => '$dependency'
    ]],
    ['$lookup' => ['from' => 'milestones', 'localField' => 'milestone_id', 'foreignField' => 'id_string', 'as' => 'milestone']],
    ['$unwind' => '$milestone'], 
    ['$addFields' => ['milestone' => '$milestone.name']],
    ['$addFields' => ['customFields' => ['$arrayToObject' => ['$map' => ['input' => '$custom_fields', 'as' => 'field', 'in' => ['k' => ['$concat' => ['_', '$$field.label_name']], 'v' => '$$field.value']]]]]],
    ['$unset' => 'custom_fields'],
    ['$addFields' => ['predecesoras' => ['$reduce' => ['input' => '$dependency.predecessor', 'initialValue' => '', 'in' => ['$concat' => ['$$value', ['$cond' => ['if' => ['$eq' => ['$$value', '']], 'then' => '', 'else' => ',']], '$$this']]]]]],
    ['$addFields' => ['sucesoras' => ['$reduce' => ['input' => '$dependency.successor', 'initialValue' => '', 'in' => ['$concat' => ['$$value', ['$cond' => ['if' => ['$eq' => ['$$value', '']], 'then' => '', 'else' => ',']], '$$this']]]]]],
    ['$unset' => 'dependency'],
    ['$merge' => ['into' => 'Tareas']]
  ]
);

$mongoClient->$database->tasks->drop();

$cron = new stdClass(); 
$cron->type="Consolidar Tareas";
$cron->minutes=(microtime(true) - $start)/60;
$cron->startUTC=$dateStart;
$cron->endUTC=date('Y-m-d H:i:s');

$mongoClient->$database->Cronjobs->insertOne($cron);

?>