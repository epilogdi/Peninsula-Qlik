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
include "$path/includes/zoho-crm.php";

function storeCollection($collectionName){ 
  global $mongoClient;
  global $database;
  global $page;
  $client = new GuzzleHttp\Client();
  $token = getLastValidToken();
  $headers = [
    "Authorization" => "Zoho-oauthtoken $token->access_token"
  ];
  $request = new \GuzzleHttp\Psr7\Request("GET", "https://www.zohoapis.com/crm/v2/$collectionName?page=$page", $headers);

  try {
    $response = $client->sendAsync($request)->wait();
    $response = json_decode($response->getBody());
    $records = (property_exists($response, 'users'))?$response->users:$response->data;
    $mongoClient->$database->$collectionName->insertMany($records);
    if($response->info->more_records==true){   
      $page++;
      storeCollection($collectionName);
    }
  } catch (Exception $e) {
    
    error_log("Caught exception:". $e->getMessage());
  }

}

$database = "ZohoCRM";
$start = microtime(true);
$dateStart = date('Y-m-d H:i:s');

$collections = $mongoClient->$database->Modules->find(["enabled"=>true]);  
foreach ($collections as $collection) {
  $page=1;
  $collectionName = $collection->name;
  $mongoClient->$database->$collectionName->drop();
  storeCollection($collectionName);
}

$cron = new stdClass();
$cron->type="Descarga";
$cron->minutes=(microtime(true) - $start)/60;
$cron->startUTC=$dateStart;
$cron->endUTC=date('Y-m-d H:i:s');

$mongoClient->$database->Cronjobs->insertOne($cron);

?>