<?php
include "/opt/bitnami/apache2/htdocs/ZohoCRM/develop/environment.php";
include "/opt/bitnami/apache2/htdocs/ZohoCRM/develop/vendor/autoload.php";
include "/opt/bitnami/apache2/htdocs/ZohoCRM/develop/includes/mongo.php";


function getNewZohoToken(){
  $client = new GuzzleHttp\Client();
  $headers = [
    "Content-Type" => "application/x-www-form-urlencoded",
    "Cookie" => "_zcsr_tmp=4db1245c-c62f-43ba-9de4-8ef0216c9540; b266a5bf57=a7f15cb1555106de5ac96d088b72e7c8; iamcsr=4db1245c-c62f-43ba-9de4-8ef0216c9540"
  ];
  $options = [
    "form_params" => [
      "refresh_token" => "1000.e1dc5d526ecacb5675e12fbfa4afff16.e3f64890f8f5e766951c565f9d131276",
      "client_id" => "1000.MU60VJ3MUFSK19RHE1BB1CM4Y204CW",
      "client_secret" => "a677f065794be7c9e99a7e26350aa15cbee8d6cea3",
      "grant_type" => "refresh_token"
    ]];
  $request = new \GuzzleHttp\Psr7\Request("POST", "https://accounts.zoho.com/oauth/v2/token", $headers);
  $res = $client->sendAsync($request, $options)->wait();
  $token = json_decode($res->getBody());
  $token->expirationDate = date("Y-m-d H:i:s", strtotime("+$token->expires_in sec"));
  return $token;
}

function storeZohoToken($token){
  global $mongoClient;
  global $admin;
  $token->expirationDate = date("Y-m-d H:i:s", strtotime("+$token->expires_in sec"));
  $mongoClient->$admin->Keys->insertOne($token);
}

function getLastValidToken(){
  global $mongoClient;
  global $admin;
  $filter = ['expirationDate' => ['$gte' => date("Y-m-d H:i:s")]];
  $ultima = $mongoClient->$admin->Keys->find($filter,[ 'sort' => [ '_id' => -1 ]]);
  $ultima = $ultima->toArray();
  if(sizeof($ultima)>0){
    $token = $ultima[0];    
  }else{
    $token = getNewZohoToken();
    storeZohoToken($token);
  }
  return $token;
}

function getResponseFromModule($module, $page){
  $client = new GuzzleHttp\Client();
  $token = getLastValidToken();

  $headers = [
    "Authorization" => "Zoho-oauthtoken $token->access_token"
  ];

  $request = new \GuzzleHttp\Psr7\Request("GET", "https://www.zohoapis.com/crm/v2/$module?page=$page", $headers);
  $response = $client->sendAsync($request)->wait();
  $response = json_decode($response->getBody());
  return $response;
}

function downloadModule($module,$database){
  global $mongoClient;
  $mongoClient->$database->$module->drop();
  $moreRecords = true;
  $total=0;
  $page=1;
  while ($moreRecords) {
    $response = getResponseFromModule($module, $page);
    $moreRecords = $response->info->more_records;
    $records = $response->data;
    $total += sizeof($records);
    $collection=$mongoClient->$database->$module->insertMany($records);
    $page++;
  }
  return $total;
}

$admin="0-Admin";

$start = microtime(true);
$dateStart = date('Y-m-d H:i:s');

$collections = $mongoClient->$admin->Modules->find(["enabled"=>true]);  
foreach ($collections as $collection) {
  $collectionName = $collection->name;
  $descargados = downloadModule($collectionName,"ZohoCRM"); 
}

$cron = new stdClass();
$cron->type="Descarga";
$cron->minutes=(microtime(true) - $start)/60;
$cron->startUTC=$dateStart;
$cron->endUTC=date('Y-m-d H:i:s');

$mongoClient->$admin->Cronjobs->insertOne($cron);

?>