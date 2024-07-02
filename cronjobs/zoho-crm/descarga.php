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

function storeCollection($obj){ 
  global $mongoClient;
  global $database;

  $moduloZoho = $obj->name;
  $moduloMongo = $obj->temp;
  $client = new GuzzleHttp\Client();
  $token = getLastValidToken();
  $headers = [
    "Authorization" => "Zoho-oauthtoken $token->access_token"
  ];
  $request = new \GuzzleHttp\Psr7\Request("GET", "https://www.zohoapis.com/crm/v2/$moduloZoho?page=$obj->page", $headers);

  try {
    $response = $client->sendAsync($request)->wait();
    $response = json_decode($response->getBody());
    $records = (property_exists($response, 'users'))?$response->users:$response->data;
    $obj->records += count($records);
    $mongoClient->$database->$moduloMongo->insertMany($records);
    if($response->info->more_records==true){   
      $obj->page ++;
      storeCollection($obj);
    }
  } catch (Exception $e) {
    error_log("Caught exception:". $e->getMessage());
  }

}

$database = "ZohoCRM";
$start = microtime(true);
$dateStart = date('Y-m-d H:i:s');

if(isset($_GET["modulo"]) && !empty($_GET["modulo"])){
  $modulo = $_GET["modulo"];
}else{
  $modulo = explode("=", $_SERVER['argv'][1])[1];
} 

$moduloOficial = $modulo;
$moduloTemporal = "$modulo-TEMP";

$obj = new stdClass();
$obj->name = $moduloOficial;
$obj->temp = $moduloTemporal;
$obj->page = 0;
$obj->records = 0;

$mongoClient->$database->$moduloOficial->drop();

storeCollection($obj);//se almacena en temporal

$conteoTemp = $mongoClient->$database->$moduloTemporal->countDocuments();
$conteoOficial = $mongoClient->$database->$moduloOficial->countDocuments();


if($conteoTemp < $conteoOficial * 0.9){ //si los registros que se descargan son menores a los ya existentes
  $mongoClient->$database->$moduloTemporal->drop();//se borra la colección temporal y se deja la ejecucion anterior
}else{
   $mongoClient->$database->$moduloOficial->drop();
  $mongoClient->$database->$moduloTemporal->rename($modulo);
 
}

$cron = new stdClass();
$cron->type = "Descarga $modulo";
$cron->records = $obj->records;
$cron->minutes = (microtime(true) - $start)/60;
$cron->startUTC = $dateStart;
$cron->endUTC = date('Y-m-d H:i:s');

$mongoClient->$database->Cronjobs->insertOne($cron);

?>