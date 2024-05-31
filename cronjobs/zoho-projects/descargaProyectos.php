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


function storeProjects(){ 
  global $mongoClient;
  global $database;
  global $ENV_ZOHO_PROJECTS_PORTAL_ID;
  global $page;
  $client = new GuzzleHttp\Client();
  $token = getLastValidToken();
  $headers = [
    "Authorization" => "Zoho-oauthtoken $token->access_token"
  ];
  $url = "https://projectsapi.zoho.com/restapi/portal/$ENV_ZOHO_PROJECTS_PORTAL_ID/projects/";
  $request = new \GuzzleHttp\Psr7\Request("GET", $url, $headers);

  try {
    $response = $client->sendAsync($request)->wait();
    $response = json_decode($response->getBody(),true);
    if($response){
      $records = $response["projects"];
      $mongoClient->$database->projects->insertMany($records);
    }

  } catch (Exception $e) {

    error_log("Caught exception:". $e->getMessage());
  }

}

$database = "ZohoProjects";
$start = microtime(true);
$dateStart = date('Y-m-d H:i:s');


$mongoClient->$database->projects->drop();
storeProjects();




$cron = new stdClass(); 
$cron->type="Descarga Proyectos";
$cron->minutes=(microtime(true) - $start)/60;
$cron->startUTC=$dateStart;
$cron->endUTC=date('Y-m-d H:i:s');

$mongoClient->$database->Cronjobs->insertOne($cron);

?>