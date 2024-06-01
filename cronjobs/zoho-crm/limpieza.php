<?php

if($_SERVER["DOCUMENT_ROOT"]){
  $path = $_SERVER["DOCUMENT_ROOT"];
}else{ 
  $name = basename($_SERVER["PHP_SELF"]);
  $path = str_replace($name, "", $_SERVER["PHP_SELF"]); 
  $path = str_replace("/cronjobs/zoho-crm/", "", $path);
}

include "$path/environment.php";
include "$path/vendor/autoload.php";
include "$path/includes/mongo.php";

$database = "ZohoCRM";
$start = microtime(true);
$dateStart = date('Y-m-d H:i:s');

if(isset($_GET["modulo"]) && !empty($_GET["modulo"])){
  $modulo = $_GET["modulo"];
}else{
  $modulo = explode("=", $_SERVER['argv'][1])[1];
} 

$obj = new stdClass();
$obj->name = $modulo;


$mongoClient->$database->$modulo->updateMany(
  [],
  [['$addFields' => ['createdDateParts' => ['$dateToParts' => ['date' => ['$dateFromString' => ['dateString' => '$Created_Time']]]], 'createdFullDate' => ['$toDate' => ['$dateFromString' => ['dateString' => '$Created_Time']]], 'createdDate' => ['$dateToString' => ['format' => '%Y-%m-%d', 'date' => ['$dateFromString' => ['dateString' => '$Created_Time']]]]]]],
  ['multiple' => true]
);

$mongoClient->$database->$modulo->updateMany(
  [],
  ['$unset' => [
    '$approval' =>  [],
    '$approval_state' =>  [],
    '$approved' =>  [],
    '$currency_symbol' =>  [],
    '$editable' =>  [],
    '$converted' =>  [],
    '$taxable' =>  [],
    '$field_states' =>  [],
    '$in_merge' =>  [],
    '$locked_for_me' =>  [],
    '$orchestration' =>  [],
    '$process_flow' =>  [],
    '$review' =>  [],
    '$review_process' =>  [],
    '$state' =>  [], 
    'Tag' =>  [],
    '$zia_owner_assignment' =>  [],
    'Locked__s' =>  [],
    'Unsubscribed_Mode' =>  [],
    'Unsubscribed_Time' =>  [],
    'Record_Image' =>  [],
    '$followed' =>  [],
    '$followers' =>  [],
    '$converted_detail' =>  [],

  ]],
  ['multiple' => true]
);

/*$mongoClient->$database->$module->aggregate(
    [['$out' => ['db' => 'ZohoCRM-Consolidados', 'coll' => $module]]]
  );*/


$cron = new stdClass();
$cron->type = "Limpieza $modulo";
$cron->minutes = (microtime(true) - $start)/60;
$cron->startUTC = $dateStart;
$cron->endUTC = date('Y-m-d H:i:s');

$mongoClient->$database->Cronjobs->insertOne($cron);


?>