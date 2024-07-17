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
$mongoClient->$database->Tareas->drop();
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
      'created_by' => '$created_by',
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
      '_# Estimación' => [ '$ifNull'  => [ '$customFields._# Estimación', null ]],
      '_Amortizacion' => [ '$ifNull'  => [ '$customFields._Amortizacion', null ]],
      '_Aprobado Admin Obra' => [ '$ifNull'  => [ '$customFields._Aprobado Admin Obra', null ]],
      '_Aprobado Coord  Estimaciones' => [ '$ifNull'  => [ '$customFields._Aprobado Coord  Estimaciones', null ]],
      '_Aprobado Dirección Obra' => [ '$ifNull'  => [ '$customFields._Aprobado Dirección Obra', null ]],
      '_Aprobado Mesa de Control' => [ '$ifNull'  => [ '$customFields._Aprobado Mesa de Control', null ]],
      '_Area Responsable' => [ '$ifNull'  => [ '$customFields._Area Responsable', null ]],
      '_Area Solicitante' => [ '$ifNull'  => [ '$customFields._Area Solicitante', null ]],
      '_Autorizado Control Presupuestal' => [ '$ifNull'  => [ '$customFields._Autorizado Control Presupuestal', null ]],
      '_Cargo a Contratista' => [ '$ifNull'  => [ '$customFields._Cargo a Contratista', null ]],
      '_Codigo de Contrato' => [ '$ifNull'  => [ '$customFields._Codigo de Contrato', null ]],
      '_Codigo de Obra' => [ '$ifNull'  => [ '$customFields._Codigo de Obra', null ]],
      '_Codigo de Pedido' => [ '$ifNull'  => [ '$customFields._Codigo de Pedido', null ]],
      '_Consecutivo de Intelisis' => [ '$ifNull'  => [ '$customFields._Consecutivo de Intelisis', null ]],
      '_Contratistas' => [ '$ifNull'  => [ '$customFields._Contratistas', null ]],
      '_Direccion Proyectos' => [ '$ifNull'  => [ '$customFields._Direccion Proyectos', null ]],
      '_En tiempo de programa' => [ '$ifNull'  => [ '$customFields._En tiempo de programa', null ]],
      '_Especialidad' => [ '$ifNull'  => [ '$customFields._Especialidad', null ]],
      '_Estatus Costos' => [ '$ifNull'  => [ '$customFields._Estatus Costos', null ]],
      '_Estatus de Compra' => [ '$ifNull'  => [ '$customFields._Estatus de Compra', null ]],
      '_Falta de seguimiento por Propietario' => [ '$ifNull'  => [ '$customFields._Falta de seguimiento por Propietario', null ]],
      '_Fecha Carga en Drive (Recepcion)' => [ '$ifNull'  => [ '$customFields._Fecha Carga en Drive (Recepcion)', null ]],
      '_Fecha Compromiso' => [ '$ifNull'  => [ '$customFields._Fecha Compromiso', null ]],
      '_Fecha Corte de Estimacion' => [ '$ifNull'  => [ '$customFields._Fecha Corte de Estimacion', null ]],
      '_Fecha de Ingreso' => [ '$ifNull'  => [ '$customFields._Fecha de Ingreso', null ]],
      '_Fecha de recalibracion' => [ '$ifNull'  => [ '$customFields._Fecha de recalibracion', null ]],
      '_Fecha Revision Admin Obra' => [ '$ifNull'  => [ '$customFields._Fecha Revision Admin Obra', null ]],
      '_Fecha Revision Coord. Estimacion' => [ '$ifNull'  => [ '$customFields._Fecha Revision Coord. Estimacion', null ]],
      '_Fecha Revision Dir. Obra' => [ '$ifNull'  => [ '$customFields._Fecha Revision Dir. Obra', null ]],
      '_Fecha Revision Mesa Control' => [ '$ifNull'  => [ '$customFields._Fecha Revision Mesa Control', null ]],
      '_Fondo de Garantia' => [ '$ifNull'  => [ '$customFields._Fondo de Garantia', null ]],
      '_ID Intelisis' => [ '$ifNull'  => [ '$customFields._ID Intelisis', null ]],
      '_IVA para Pago o Intercambio' => [ '$ifNull'  => [ '$customFields._IVA para Pago o Intercambio', null ]],
      '_Moneda' => [ '$ifNull'  => [ '$customFields._Moneda', null ]],
      '_Monto Autorizado' => [ '$ifNull'  => [ '$customFields._Monto Autorizado', null ]],
      '_Monto Contratado' => [ '$ifNull'  => [ '$customFields._Monto Contratado', null ]],
      '_Monto Pagado' => [ '$ifNull'  => [ '$customFields._Monto Pagado', null ]],
      '_Monto Pedido' => [ '$ifNull'  => [ '$customFields._Monto Pedido', null ]],
      '_Monto Total s/IVA o Retenciones' => [ '$ifNull'  => [ '$customFields._Monto Total s/IVA o Retenciones', null ]],
      '_Numero de Semana' => [ '$ifNull'  => [ '$customFields._Numero de Semana', null ]],
      '_Numero de Semana Pagado' => [ '$ifNull'  => [ '$customFields._Numero de Semana Pagado', null ]],
      '_Numero tarea duplicada (si aplica)' => [ '$ifNull'  => [ '$customFields._Numero tarea duplicada (si aplica)', null ]],
      '_Obstrucción' => [ '$ifNull'  => [ '$customFields._Obstrucción', null ]],
      '_Pagaré SI / NO' => [ '$ifNull'  => [ '$customFields._Pagaré SI / NO', null ]],
      '_Partida Presupuestal INTELISIS' => [ '$ifNull'  => [ '$customFields._Partida Presupuestal INTELISIS', null ]],
      '_Partida Presupuestal ZOHO' => [ '$ifNull'  => [ '$customFields._Partida Presupuestal ZOHO', null ]],
      '_Por Pagar' => [ '$ifNull'  => [ '$customFields._Por Pagar', null ]],
      '_Prioridad' => [ '$ifNull'  => [ '$customFields._Prioridad', null ]],
      '_Proveedor' => [ '$ifNull'  => [ '$customFields._Proveedor', null ]],
      '_Requerimiento' => [ '$ifNull'  => [ '$customFields._Requerimiento', null ]],
      '_Revision Admin Obra' => [ '$ifNull'  => [ '$customFields._Revision Admin Obra', null ]],
      '_Revision Direccion Obra' => [ '$ifNull'  => [ '$customFields._Revision Direccion Obra', null ]],
      '_Subpartida Presupuestal' => [ '$ifNull'  => [ '$customFields._Subpartida Presupuestal', null ]],
      '_Subtotal para Pago o Intercambio' => [ '$ifNull'  => [ '$customFields._Subtotal para Pago o Intercambio', null ]],
      '_Tipo de Estimación' => [ '$ifNull'  => [ '$customFields._Tipo de Estimación', null ]],
      '_Total Pago NETO' => [ '$ifNull'  => [ '$customFields._Total Pago NETO', null ]],
      '_Verificador de Obra' => [ '$ifNull'  => [ '$customFields._Verificador de Obra', null ]],

    ]],
    ['$unset' => 'customFields'],
    ['$merge' => ['into' => 'Tareas']]
  ]
);

//$mongoClient->$database->tasks->drop();

$cron = new stdClass(); 
$cron->type="Consolidar Tareas";
$cron->minutes=(microtime(true) - $start)/60;
$cron->startUTC=$dateStart;
$cron->endUTC=date('Y-m-d H:i:s');

$mongoClient->$database->Cronjobs->insertOne($cron);

?>