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
$controlHistorico = "Control-Historico";
$controlDiario = "Control-Diario";
$projects = $mongoClient->$database->$controlHistorico->find(["enabled"=>true]);  
foreach ($projects as $project) {  
  $response = extractAnalytics($project,'2020-01-01','yesterday');
  $insert = getData($response,$project);
  if (count($insert) > 0) {
    $mongoClient->$database->Descarga->insertMany($insert);
  }
  unset($a->_id);
  $mongoClient->$database->$controlHistorico->deleteOne(["viewId"=>$project->viewId]); 
  $mongoClient->$database->$controlDiario->insertOne($project);  
}



?>