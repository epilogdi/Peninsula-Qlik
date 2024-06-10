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
    ['$project' => [
      'id' => '$id',
      'tasklistName' => '$tasklistName',
      'projectName' => '$projectName',
      'projectId' => '$projectId',
      'name' => '$name',
      'milestone_id' => '$milestone_id',
      'created_time' => '$created_time',
      'created_by' => '$created_by',
      'end_date' => '$end_date',
      'completed_time' => '$completed_time',
      'priority' => '$priority',
      'duration' => '$duration',
      'duration_type' => '$duration_type',
      'percent_complete' => '$percent_complete',
      'start_date' => '$start_date',
      'key' => '$key',
      'statusName' => '$statusName',
      'tasklistId' => '$tasklistId',
      'completed' => '$completed',
      'ownersName' => '$ownersName',
      'ownersId' => '$ownersId',
      'duration' => '$duration',
      'custom_fields' => '$custom_fields',
      'dependency' => '$dependency',
      '_En tiempo de programa' => [ '$ifNull'  => [ '$customFields._En tiempo de programa', null ]],
      '_Codigo de Obra' => [ '$ifNull'  => [ '$customFields._Codigo de Obra', null ] ],
      '_Partida Presupuestal' => [ '$ifNull'  => [ '$customFields._Partida Presupuestal', null ]],
      '_Prioridad' => [ '$ifNull'  => [ '$customFields._Prioridad', null ]],
      '_Falta de seguimiento por Propietario' => [ '$ifNull'  => [ '$customFields._Falta de seguimiento por Propietario', null ]],
      '_Area Responsable' => [ '$ifNull'  => [ '$customFields._Area Responsable', null ]],
      '_Especialidad' => [ '$ifNull'  => [ '$customFields._Especialidad', null ]],
      '_Direccion Proyectos' => [ '$ifNull'  => [ '$customFields._Direccion Proyectos', null ]],
      '_Subpartida Presupuestal' => [ '$ifNull'  => [ '$customFields._Subpartida Presupuestal', null ]],
      '_Requerimiento' => [ '$ifNull'  => [ '$customFields._Requerimiento', null ]],
      '_Monto Pedido' => [ '$ifNull'  => [ '$customFields._Monto Pedido', null ]],
      '_Pagaré SI / NO' => [ '$ifNull'  => [ '$customFields._Pagaré SI / NO', null ]], 
      '_Obstrucción' => [ '$ifNull'  => [ '$customFields._Obstrucción', null ]],
      '_Contratistas' => [ '$ifNull'  => [ '$customFields._Contratistas', null ]],
      '_Autorizado Control Presupuestal' => [ '$ifNull'  => [ '$customFields._Autorizado Control Presupuestal', null ]],
      '_Estatus de Compra' => [ '$ifNull'  => [ '$customFields._Estatus de Compra', null ]],
      '_Estatus Costos' => [ '$ifNull'  => [ '$customFields._Estatus Costos', null ]],
      '_Monto Autorizado' => [ '$ifNull'  => [ '$customFields._Monto Autorizado', null ]],
      '_Codigo de Pedido' => [ '$ifNull'  => [ '$customFields._Codigo de Pedido', null ]],
      '_Fecha Compromiso' => [ '$ifNull'  => [ '$customFields._Fecha Compromiso', null ]],
    ]],
    ['$unset' => 'customFields'],
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