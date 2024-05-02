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
include "$path/includes/zoho-projects.php";

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
    $collectionName = "$project->name - $module->name";
    $mongoClient->$database->$collectionName->drop();
    $project->name = str_replace("Seguimiento ", "", $project->name);
    $project->name = str_replace(" Peninsula", "", $project->name);
    storeCollection($project,$module->name);
  }  
}

$cron = new stdClass();
$cron->type="Descarga";
$cron->minutes=(microtime(true) - $start)/60;
$cron->startUTC=$dateStart;
$cron->endUTC=date('Y-m-d H:i:s');

$mongoClient->$database->Cronjobs->insertOne($cron);

?>