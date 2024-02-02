<?php
$server=getcwd();
include $server ."/environment.php";
include $server."/vendor/autoload.php";
include $server."/includes/mysqli.php";
include $server."/includes/mongo.php";

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
  return json_decode($res->getBody());
}

function storeZohoToken($tokenResponse){
  global $mysqli;
  $token=$tokenResponse->access_token;
  $secondsToExpire=$tokenResponse->expires_in;

  $query="INSERT INTO Tokens (token, Expiration) VALUES ('$token', DATE_ADD(NOW(), INTERVAL $secondsToExpire SECOND));";
  if($result=$mysqli->query($query)){ 
    return $token;
  }else{
    return false;
  }
}

function getLastStoredZohoToken(){
  global $mysqli;
  $token = new stdClass();
  $token->enabled=false;
  $query="SELECT * from Tokens ORDER BY id DESC LIMIT 1;";
  $result=$mysqli->query($query);
  if($token = mysqli_fetch_object($result)){
    $token=checkTokenExpiration($token);
  }
  return $token;
}

function checkTokenExpiration($lastToken){
  $expiration=strtotime($lastToken->Expiration);
  $now=strtotime(date('Y-m-d H:i:s'));
  if($expiration > $now){
    $lastToken->enabled=true;
  }else{
    $lastToken->enabled=false;
  }
  return $lastToken;
}

function getResponseFromModule($token, $module, $page){
  $client = new GuzzleHttp\Client();
  $headers = [
    "Authorization" => "Zoho-oauthtoken $token"
  ];
  $request = new \GuzzleHttp\Psr7\Request("GET", "https://www.zohoapis.com/crm/v2/$module?page=$page", $headers);
  $response = $client->sendAsync($request)->wait();
  $response = json_decode($response->getBody());
  return $response;
}

function getValidTokenFromZoho(){
  $lastToken=getLastStoredZohoToken();
  if($lastToken->enabled==true){
    $token=$lastToken->token;
  }else{
    $tokenResponse=getNewZohoToken();
    $token=storeZohoToken($tokenResponse);
  }
  return $token;
}


$token=getValidTokenFromZoho();
$module=$_GET["module"];

$mongoClient->Descargados->$module->drop();
$moreRecords = true;
$items=0;
$page=1;
while ($moreRecords) {
  $response = getResponseFromModule($token, $module, $page);
  $moreRecords = $response->info->more_records;
  $records = $response->data;
  $items += sizeof($records);
  $collection=$mongoClient->Descargados->$module->insertMany($records);
  $page++;
}
echo "Felicidades, se han descargo y almacenado <b>$items</b> registros para el módulo de <b>$module</b>";




?>
