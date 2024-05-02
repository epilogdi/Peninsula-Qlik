<?php
function getNewZohoToken(){
  
  global $ENV_ZOHO_CRM_REFRESH_TOKEN;
  global $ENV_ZOHO_CRM_CLIENT_ID;
  global $ENV_ZOHO_CRM_CLIENT_SECRET;

  $client = new GuzzleHttp\Client();
  $headers = [
    "Content-Type" => "application/x-www-form-urlencoded",
    "Cookie" => "_zcsr_tmp=4db1245c-c62f-43ba-9de4-8ef0216c9540; b266a5bf57=a7f15cb1555106de5ac96d088b72e7c8; iamcsr=4db1245c-c62f-43ba-9de4-8ef0216c9540"
  ];
  $options = [
    "form_params" => [
      "refresh_token" => "$ENV_ZOHO_CRM_REFRESH_TOKEN",
      "client_id" => "$ENV_ZOHO_CRM_CLIENT_ID",
      "client_secret" => "$ENV_ZOHO_CRM_CLIENT_SECRET", 
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
  global $database;
  $token->expirationDate = date("Y-m-d H:i:s", strtotime("+$token->expires_in sec"));
  $mongoClient->$database->Keys->insertOne($token);
}

function getLastValidToken(){
  global $mongoClient;
  global $database;
  $filter = ['expirationDate' => ['$gte' => date("Y-m-d H:i:s")]];
  $ultima = $mongoClient->$database->Keys->find($filter,[ 'sort' => [ '_id' => -1 ]]);
  $ultima = $ultima->toArray();
  if(sizeof($ultima)>0){
    $token = $ultima[0];    
  }else{
    $token = getNewZohoToken();
    storeZohoToken($token);
  }
  return $token;
}
?>