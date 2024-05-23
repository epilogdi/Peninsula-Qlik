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

$view_id = $_GET["viewid"];


$database = "GoogleAnalytics";
$start = microtime(true);
$dateStart = date('Y-m-d H:i:s');
$collection = "Control-Diario";

$project = new stdClass();
$project->viewId = $view_id;


$response = extractAnalytics($project,'yesterday','today');
$insert = getData($response,$project);
echo json_encode($insert);



?>