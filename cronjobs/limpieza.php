<?php
include "/opt/bitnami/apache2/htdocs/ZohoCRM/develop/environment.php";
include "/opt/bitnami/apache2/htdocs/ZohoCRM/develop/vendor/autoload.php";
include "/opt/bitnami/apache2/htdocs/ZohoCRM/develop/includes/mongo.php";


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

$dbOrigin="2_limpiados";
$admin="0-Admin";
$start = microtime(true);
$dateStart = date('Y-m-d H:i:s');
$collections = $mongoClient->$admin->Modules->find(["enabled"=>true]);  
foreach ($collections as $collection) {
  $module = $collection->name;  
 
  $mongoClient->$dbOrigin->$module->updateMany(
    [],
    [['$addFields' => ['createdDateParts' => ['$dateToParts' => ['date' => ['$dateFromString' => ['dateString' => '$Created_Time']]]], 'createdFullDate' => ['$toDate' => ['$dateFromString' => ['dateString' => '$Created_Time']]], 'createdDate' => ['$dateToString' => ['format' => '%Y-%m-%d', 'date' => ['$dateFromString' => ['dateString' => '$Created_Time']]]]]]],
    ['multiple' => true]
  );
  
  $mongoClient->$dbOrigin->$module->updateMany(
    [],
    ['$unset' => $remove],
    ['multiple' => true]
  );
  
  $mongoClient->$dbOrigin->$module->aggregate(
    [['$out' => ['db' => '3-Consolidados', 'coll' => $module]]]
  );
}

$cron = new stdClass();
$cron->type="Limpieza";
$cron->minutes=(microtime(true) - $start)/60;
$cron->startUTC=$dateStart;
$cron->endUTC=date('Y-m-d H:i:s');
$mongoClient->$admin->Cronjobs->insertOne($cron);


?>