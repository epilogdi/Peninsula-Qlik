<?php
function getNewZohoToken(){
   
  global $ENV_ZOHO_ANALYTICS_REFRESH_TOKEN;
  global $ENV_ZOHO_ANALYTICS_CLIENT_ID;
  global $ENV_ZOHO_ANALYTICS_CLIENT_SECRET;

  $client = new GuzzleHttp\Client();
  $headers = [
    "Content-Type" => "application/x-www-form-urlencoded",
    "Cookie" => "_zcsr_tmp=5b50846e-1d9d-4c94-b07d-fcf21da43d91; iamcsr=5b50846e-1d9d-4c94-b07d-fcf21da43d91; zalb_b266a5bf57=a711b6da0e6cbadb5e254290f114a026"
  ];
  $options = [
    "form_params" => [
      "refresh_token" => "$ENV_ZOHO_ANALYTICS_REFRESH_TOKEN",
      "client_id" => "$ENV_ZOHO_ANALYTICS_CLIENT_ID",
      "client_secret" => "$ENV_ZOHO_ANALYTICS_CLIENT_SECRET", 
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