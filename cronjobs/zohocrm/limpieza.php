<?php

if($_SERVER["DOCUMENT_ROOT"]){
  $path = $_SERVER["DOCUMENT_ROOT"];
}else{ 
  $name = basename($_SERVER["PHP_SELF"]);
  $path = str_replace($name, "", $_SERVER["PHP_SELF"]); 
  $path = str_replace("/cronjobs/", "", $path);
}

include "$path/environment.php";
include "$path/vendor/autoload.php";
include "$path/includes/mongo.php";

$remove = [
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

];

$database = "ZohoCRM";
$start = microtime(true);
$dateStart = date('Y-m-d H:i:s');

$collections = $mongoClient->$database->Modules->find(["enabled"=>true]); 


foreach ($collections as $collection) {
  $module =  $collection["name"];

  $mongoClient->$database->$module->updateMany(
    [],
    [['$addFields' => ['createdDateParts' => ['$dateToParts' => ['date' => ['$dateFromString' => ['dateString' => '$Created_Time']]]], 'createdFullDate' => ['$toDate' => ['$dateFromString' => ['dateString' => '$Created_Time']]], 'createdDate' => ['$dateToString' => ['format' => '%Y-%m-%d', 'date' => ['$dateFromString' => ['dateString' => '$Created_Time']]]]]]],
    ['multiple' => true]
  );

  $mongoClient->$database->$module->updateMany(
    [],
    ['$unset' => $remove],
    ['multiple' => true]
  );

  $mongoClient->$database->$module->aggregate(
    [['$out' => ['db' => 'ZohoCRM-Consolidados', 'coll' => $module]]]
  );
}

$cron = new stdClass();
$cron->type="Limpieza";
$cron->minutes=(microtime(true) - $start)/60;
$cron->startUTC=$dateStart;
$cron->endUTC=date('Y-m-d H:i:s');
$mongoClient->$database->Cronjobs->insertOne($cron);


?>