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

function storeModule($project){ 
  global $mongoClient;
  global $database;
  global $ENV_ZOHO_PROJECTS_PORTAL_ID;
  $modulo = $project->modulo;
  $temp = $project->temp;
  
  $client = new GuzzleHttp\Client();
  $token = getLastValidToken();
  $headers = [
    "Authorization" => "Zoho-oauthtoken $token->access_token"
  ];
  $url = "https://projectsapi.zoho.com/restapi/portal/$ENV_ZOHO_PROJECTS_PORTAL_ID/projects/$project->id/$modulo/?index=$project->page&range=200";

  $request = new \GuzzleHttp\Psr7\Request("GET", $url, $headers);

  try {
    $response = $client->sendAsync($request)->wait();
    $response = json_decode($response->getBody(),true);
    if($response){
      $records = $response[$modulo];
      $mongoClient->$database->$temp->insertMany($records);
      $project->page = $project->page + 200;
      storeModule($project);
    }

  } catch (Exception $e) {

    echo "Caught exception:". $e->getMessage();
  }

}

$database = "ZohoProjectsX";
$start = microtime(true);
$dateStart = date('Y-m-d H:i:s');

if(isset($_GET["modulo"]) && !empty($_GET["modulo"])){
  $modulo = $_GET["modulo"];
}else{
  $modulo = explode("=", $_SERVER['argv'][1])[1];
} 

$mongoClient->$database->$modulo->drop();
$projects = $mongoClient->$database->projects->find();  

foreach ($projects as $project) {
  $temp = "$modulo-TEMP";
  $project->page = 0; 
  $project->modulo = $modulo;
  $project->temp = $temp;
  storeModule($project);
  

  $mongoClient->$database->$temp->aggregate([
    ['$addFields' => [
      'projectName' =>  $project->name,
      'projectId' =>  $project->id,
    ]],
    ['$merge' => ['into' => $modulo]]
  ]);
  $mongoClient->$database->$temp->drop();
}



$cron = new stdClass(); 
$cron->type="Descarga $modulo";
$cron->minutes=(microtime(true) - $start)/60;
$cron->startUTC=$dateStart;
$cron->endUTC=date('Y-m-d H:i:s');

$mongoClient->$database->Cronjobs->insertOne($cron);

?>