<?php
if($_SERVER["DOCUMENT_ROOT"]){
  $path = $_SERVER["DOCUMENT_ROOT"];
}else{ 
  $name = basename($_SERVER["PHP_SELF"]);
  $path = str_replace($name, "", $_SERVER["PHP_SELF"]); 
  $path = str_replace("/cronjobs/", "", $path);
}

include "$path/environment.php";
include "$path/vendor/autoload.php";
include "$path/includes/mongo.php";
include "$path/includes/zoho.php";

function getStatusTimeline($records){
  $filtered = [];
  if($records){
    foreach ($records as $record) {   
      if(in_array($record->action, array("updated", "transition")) ){
        if($record->field_history){
          foreach ($record->field_history as $field) { 
            if($field->api_name){
              if($field->api_name == 'Stage'){
                $objx = new stdClass();
                $objx->auditedTime = $record->audited_time;
                $objx->oldValue = $field->_value->old;
                $objx->newValue = $field->_value->new;
                $objx->auditedBy_name = $record->done_by->name;
                $objx->auditedBy_id = $record->done_by->id;
                array_push( $filtered,$objx);
              }
            }
          }
        }
      }        
    }
  }
  return $filtered;
}

function getTimeline($element){ 

  global $mongoClient;
  $client = new GuzzleHttp\Client();
  $token = getLastValidToken();
  $headers = [
    "Authorization" => "Zoho-oauthtoken $token->access_token"
  ];
  $request = new \GuzzleHttp\Psr7\Request("GET", "https://www.zohoapis.com/crm/v5/Deals/$element->id/__timeline", $headers);

  try {
    $response = $client->sendAsync($request)->wait();
    $response = json_decode($response->getBody());

    $obj = new stdClass();
    $obj->dealId = $element->id;
    $obj->lastStatus = $element->Stage;
    $obj->cerrado = (str_contains(strtolower($element->Stage), 'cerrado') || str_contains(strtolower($element->Stage), 'cancelado')) ? true : false;

    $obj->respuesta = ($response) ? true : false;
    if($response){
      if($response->__timeline){
        $obj->timeline = getStatusTimeline($response->__timeline);
      }else{
        $obj->timeline = [];
      }
    }else{
      $obj->timeline = [];
    }

    return $obj;

  } catch (Exception $e) {
    error_log("Exception ERROR");
    return false;
  }

}

$admin="0-Admin";
$destination = "ZohoCRM";

$start = microtime(true);
$dateStart = date('Y-m-d H:i:s');

$filter = [];
$options = [['sort' => ['_id' => 1]],['batchSize' => 500]];
$elements = $mongoClient->$destination->Deals->find($filter, $options);
$i=0;


foreach ($elements as $element) { 
  $encontrado = $mongoClient->$destination->DealStatusTimeline->findOne(['$and' => [['dealId' => $element->id], ['$or' => [['cerrado' => true], ['respuesta' => false]]]]]);
  if(!$encontrado){
    if($record = getTimeline($element)){
      $mongoClient->$destination->DealStatusTimeline->deleteOne(['dealId' => $element->id]);
      $mongoClient->$destination->DealStatusTimeline->insertOne($record);
      error_log(++$i." ".$element->id." ---- TIMELINE: ".sizeof($record->timeline));      
    }else{
      error_log(++$i." $element->id ---- ERROR TRY CATCH");
    }
  }else{
    error_log(++$i." $element->id ---- YA ESTABA");
  }
}

$cron = new stdClass();
$cron->type="DealStatusTimeline";
$cron->minutes=(microtime(true) - $start)/60;
$cron->startUTC=$dateStart;
$cron->endUTC=date('Y-m-d H:i:s');

$mongoClient->$destination->Cronjobs->insertOne($cron);

?>