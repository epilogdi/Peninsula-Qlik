<?php

if($_SERVER["DOCUMENT_ROOT"]){
  $path = $_SERVER["DOCUMENT_ROOT"];
}else{ 
  $name = basename($_SERVER["PHP_SELF"]);
  $path = str_replace($name, "", $_SERVER["PHP_SELF"]); 
  $path = str_replace("/cronjobs/googleAnalytics/", "", $path);
}

include "$path/environment.php";
include "$path/vendor/autoload.php";
include "$path/includes/mongo.php";
include "$path/includes/googleAnalytics.php";


$database = "GoogleAnalytics";
$start = microtime(true);
$dateStart = date('Y-m-d H:i:s');

$projects = $mongoClient->$database->Projects->find(["enabled"=>true]);  
$mongoClient->$database->Descarga->drop();
foreach ($projects as $project) {  
  $response = extractAnalytics($project);
  $insert = getData($response,$project);
  if (count($insert) > 0) {
    $mongoClient->$database->Descarga->insertMany($insert);
  }
}

$cron = new stdClass();
$cron->type="Descarga Diaria";
$cron->minutes=(microtime(true) - $start)/60;
$cron->startUTC=$dateStart;
$cron->endUTC=date('Y-m-d H:i:s');

$mongoClient->$database->Cronjobs->insertOne($cron);

?>