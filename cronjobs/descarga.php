<?php

$ruta = $_SERVER["PHP_SELF"];
$archivo = "/cronjobs/".basename($_SERVER["PHP_SELF"]);
$ruta = str_replace($archivo, "", $ruta);


include "$ruta/environment.php";
include "$ruta/vendor/autoload.php";
include "$ruta/includes/mongo.php";
include "$ruta/includes/zoho.php";

function storeCollection($collectionName){ 
  global $mongoClient;
  global $destination;
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
    $mongoClient->$destination->$collectionName->insertMany($records);
    if($response->info->more_records==true){   
      $page++;
      storeCollection($collectionName);
    }
  } catch (Exception $e) {
    
    error_log("Caught exception:". $e->getMessage());
  }

}


$admin="0-Admin";
$destination = "ZohoCRM";

$start = microtime(true);
$dateStart = date('Y-m-d H:i:s');

$collections = $mongoClient->$admin->Modules->find(["enabled"=>true]);  
foreach ($collections as $collection) {
  $page=1;
  $collectionName = $collection->name;
  $mongoClient->$destination->$collectionName->drop();
  storeCollection($collectionName);
}

$cron = new stdClass();
$cron->type="Descarga";
$cron->minutes=(microtime(true) - $start)/60;
$cron->startUTC=$dateStart;
$cron->endUTC=date('Y-m-d H:i:s');

$mongoClient->$admin->Cronjobs->insertOne($cron);

?>