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

function storeTasks($project){ 
  global $mongoClient;
  global $database;
  global $ENV_ZOHO_PROJECTS_PORTAL_ID;
  $client = new GuzzleHttp\Client();
  $token = getLastValidToken();
  $headers = [
    "Authorization" => "Zoho-oauthtoken $token->access_token"
  ];
  $url = "https://projectsapi.zoho.com/restapi/portal/$ENV_ZOHO_PROJECTS_PORTAL_ID/projects/$project->id/tasks/?index=$project->page&range=200";
  error_log($url);
  $request = new \GuzzleHttp\Psr7\Request("GET", $url, $headers);

  try {
    $response = $client->sendAsync($request)->wait();
    $response = json_decode($response->getBody(),true);
    if($response){
      $records = $response["tasks"];
      $records = array_map(function($x) {
        return (object) $x;
      }, $records);

      foreach ($records as $record) {        
        $record->projectName = $project->name;
        $record->projectId = $project->id;
      }

      $mongoClient->$database->tasks->insertMany($records);
      $project->page = $project->page + 200;
      storeTasks($project);
    }

  } catch (Exception $e) {

    echo "Caught exception:". $e->getMessage();
  }

}

$database = "ZohoProjects";
$start = microtime(true);
$dateStart = date('Y-m-d H:i:s');

$projects = $mongoClient->$database->projects->find();  
$mongoClient->$database->tasks->drop();
foreach ($projects as $project) {
  $project->page=0;  
  storeTasks($project);
}


$cron = new stdClass(); 
$cron->type="Descarga Tareas";
$cron->minutes=(microtime(true) - $start)/60;
$cron->startUTC=$dateStart;
$cron->endUTC=date('Y-m-d H:i:s');

$mongoClient->$database->Cronjobs->insertOne($cron);

?>