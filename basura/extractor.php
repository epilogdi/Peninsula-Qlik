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
      "refresh_token" => "1000.47b3e713e2ff261df04994641b857432.718a60352b6beba8a834c3ef87b5ba89",
      "client_id" => "1000.61EDW4DIJT36EM6MLRE034VONOG3IJ",
      "client_secret" => "b0f205e4fd968678cc181c682ec4ee55533f1dcc1e",
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

function getResponseFromModule($module, $page){
  $client = new GuzzleHttp\Client();
  $token = getValidTokenFromZoho();
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

function cleanModule($module,$origin,$destination){
  global $mongoClient;
  $mongoClient->$destination->$module->drop();
  $items = $mongoClient->$origin->$module->find();
  foreach ($items as $item) {
    $item=removeTrash($item);
    $mongoClient->$destination->$module->insertOne($item);   
  }
}

function removeTrash($item){
  unset($item['$approval']);
  unset($item['$approval_state']);
  unset($item['$approved']);
  unset($item['$currency_symbol']);
  unset($item['$editable']);
  unset($item['$field_states']);
  unset($item['$in_merge']);
  unset($item['$locked_for_me']);
  unset($item['$orchestration']);
  unset($item['$process_flow']);
  unset($item['$review']);
  unset($item['$review_process']);
  unset($item['$state']);
  unset($item['Created_By']);
  unset($item['Created_Time']);
  unset($item['Last_Activity_Time']);
  unset($item['Modified_By']);
  unset($item['Modified_Time']);
  unset($item['Owner']);
  unset($item['Tag']);
  unset($item['_id']);
  unset($item['$zia_owner_assignment']);
  unset($item['Locked__s']);
  unset($item['Unsubscribed_Mode']);
  unset($item['Unsubscribed_Time']);
  unset($item['Record_Image']);
  unset($item['$followed']);
  unset($item['$followers']);


  return $item;
}

function downloadModule($module,$database){
  global $mongoClient;
  $mongoClient->$database->$module->drop();
  $moreRecords = true;
  $items=0;
  $page=1;
  while ($moreRecords) {
    $response = getResponseFromModule($module, $page);
    $moreRecords = $response->info->more_records;
    $records = $response->data;
    $items += sizeof($records);
    $collection=$mongoClient->$database->$module->insertMany($records);
    $page++;
  }
  return $items;
}


$admin="0-Admin";
$collections = $mongoClient->$admin->Modules->find(["enabled"=>true]);  
foreach ($collections as $collection) {
  $date = date('Y-m-d H:i:s');
  $time_start = microtime(true); 
  $registros = downloadModule($collection->name,"1-Descargas");
  $tiempoDescarga = (microtime(true) - $time_start);
  $time_start = microtime(true); 
  cleanModule($collection->name,"1-Descargas","2-Limpiados");
  $tiempoLimpieza = (microtime(true) - $time_start);
  $mongoClient->$admin->Modules->updateOne(['name' => $collection->name],[ '$push' => [ "downloading" => ["seconds" => $tiempoDescarga, "time-UTC" => $date], "cleaning" => ["seconds" => $tiempoLimpieza, "time-UTC" => $date] ]]);

}




?>
