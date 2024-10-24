<?php

if($_SERVER["DOCUMENT_ROOT"]){
  $path = $_SERVER["DOCUMENT_ROOT"];
}else{ 
  $name = basename($_SERVER["PHP_SELF"]);
  $path = str_replace($name, "", $_SERVER["PHP_SELF"]); 
  $path = str_replace("/cronjobs/zoho-analytics/", "", $path);
}

include "$path/environment.php";
include "$path/vendor/autoload.php";
include "$path/includes/mongo.php";
include "$path/includes/zoho-analytics.php";

function extractCollection($obj){ 
  error_log($obj->workspace);

  $client = new GuzzleHttp\Client();
  $token = getLastValidToken();
  $headers = [
    "Authorization" => "Zoho-oauthtoken $token->access_token",
    "ZANALYTICS-ORGID" => '761378538'
  ];
  $request = new \GuzzleHttp\Psr7\Request("GET", "https://analyticsapi.zoho.com/restapi/v2/workspaces/$obj->workspace/views/$obj->view/data?responseFormat=csv", $headers);

  try {
    $response = $client->sendAsync($request)->wait();
    $content = $response->getBody();
    return $content;

  } catch (Exception $e) {
    error_log("Caught exception:". $e->getMessage());
  }

}

function storeCollection($content,$moduloTemporal){
    global $mongoClient;
  global $database;
  $row = 0;
  $labels = array();
  $batch = array();
  foreach(preg_split("/((\r?\n)|(\r\n?))/", $content) as $line){
    $array = explode(",",$line); 
    if($row == 0){
      $labels = $array;
    }else{
      if(sizeof($array) == sizeof($labels)){
        $element = new stdClass();
        foreach($labels as $key=>$label){
          $element->$label = $array[$key];
        }
        array_push($batch, $element);
        if(sizeof($batch) == 500){
          $mongoClient->$database->$moduloTemporal->insertMany($batch);
          $batch = array();
        }
      }
    }
    $row++;
  }
  $mongoClient->$database->$moduloTemporal->insertMany($batch);
  return $row;
}

$database = "ZohoAnalytics";
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
$moduloOficial;
$moduloTemporal;
$obj->workspace = 2464941000001334001;
if($modulo == "Leads"){
  $obj->view = 2464941000001334012; 
}
if($modulo == "LeadTimeline"){
  $obj->view = 2464941000001336253; 
}
if($modulo == "Deals"){
  $obj->view = 2464941000001334015; 
}
if($modulo == "DealTimeline"){
  $obj->view = 2464941000002555003; 
}



$mongoClient->$database->$moduloOficial->drop();
$content = extractCollection($obj);
$records = storeCollection($content,$moduloTemporal);

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
$cron->records = $records;
$cron->minutes = (microtime(true) - $start)/60;
$cron->startUTC = $dateStart;
$cron->endUTC = date('Y-m-d H:i:s');

$mongoClient->$database->Cronjobs->insertOne($cron);

?>