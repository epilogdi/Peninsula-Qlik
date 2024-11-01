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

function getStatusTimeline($records){
  $filtered = [];
  if($records){
    foreach ($records as $record) { 
      if(in_array($record->action, array("updated", "transition"))){
        if(property_exists($record, 'field_history')){
          if($record->field_history){
            foreach ($record->field_history as $field) { 
              if(property_exists($field, 'api_name')){
                if($field->api_name == 'Lead_Status'){
                  $objx = new stdClass();
                  $objx->auditedTime = $record->audited_time;
                  $objx->oldValue = $field->_value->old;
                  $objx->newValue = $field->_value->new;
                  $objx->auditedBy_name = $record->done_by->name;
                  $objx->auditedBy_id = $record->done_by->id;
                  $objx->leadId = $record->record->id;
                  $objx->leadName = $record->record->name;
                  array_push( $filtered,$objx);
                }
              }
            }
          }
        }
      }elseif(in_array($record->action, array("process_entry"))){
        if(property_exists($record, 'automation_details')){
          if($record->automation_details){
            if($record->automation_details->rule->state->field->api_name == "Lead_Status"){
              $objx = new stdClass();
              $objx->auditedTime = $record->audited_time;
              $objx->oldValue = null;
              $objx->newValue = $record->automation_details->rule->state->name;
              $objx->auditedBy_name = $record->done_by->name;
              $objx->auditedBy_id = $record->done_by->id;
              $objx->leadId = $record->record->id;
              $objx->leadName = $record->record->name;
              array_push( $filtered,$objx);
            }
          }
        }
      }       
    }
  }
  return $filtered;
}

function getTimeline($id){ 

  global $mongoClient;
  $client = new GuzzleHttp\Client();
  $token = getLastValidToken();
  $headers = [
    "Authorization" => "Zoho-oauthtoken $token->access_token"
  ];
  $request = new \GuzzleHttp\Psr7\Request("GET", "https://www.zohoapis.com/crm/v5/Leads/$id/__timeline", $headers);

  try {
    $response = $client->sendAsync($request)->wait();
    $response = json_decode($response->getBody());
    $timeline = [];

    if($response){
      if(property_exists($response, '__timeline')){
        $timeline = getStatusTimeline($response->__timeline);
      }
    }
    return $timeline;

  } catch (Exception $e) {
    error_log("Exception ERROR");
    return false;
  }

}

$database = "ZohoCRM";

$start = microtime(true);
$dateStart = date('Y-m-d H:i:s');

//$filter = [];
//$options = [['sort' => ['_id' => 1]]];
$elements = $mongoClient->$database->Leads->aggregate([['$sample' => ['size' => 3000]]]);
//$elements = $mongoClient->$database->Leads->find(['leadId' => 5153690000048130031]);

$i=0;


foreach ($elements as $element) { 
  //$encontrado = $mongoClient->$database->LeadStatusControl->findOne(['$and' => [['leadId' => $element->id], ['$or' => [['cerrado' => true], ['respuesta' => false]]]]]);
  //$encontrado = $mongoClient->$database->LeadStatusControl->findOne(['$and' => [['leadId' => $element->id], ['cerrado' => true]]]);
  //$encontrado = $mongoClient->$database->LeadStatusControl->findOne(['leadId' => $element->id]);
  //if(!$encontrado){
    $timeline = getTimeline($element->id);
    $obj = new stdClass();
    $obj->leadId = $element->id;
    //$obj->lastStatus = $element->Lead_Status;
    //$obj->cerrado = (str_contains(strtolower($element->Lead_Status), 'cerrado') || str_contains(strtolower($element->Lead_Status), 'cancelado')) ? true : false;
    $obj->respuesta = false;
    if(sizeof($timeline)>0){
      $mongoClient->$database->LeadStatusTimeline->deleteMany(['leadId' => $element->id]);
      $mongoClient->$database->LeadStatusTimeline->insertMany($timeline);
      $obj->respuesta = true;
    }
    $mongoClient->$database->LeadStatusControl->deleteOne(['leadId' => $element->id]);
    $mongoClient->$database->LeadStatusControl->insertOne($obj);
  //}
}

$cron = new stdClass();
$cron->type="LeadStatusTimeline";
$cron->minutes=(microtime(true) - $start)/60;
$cron->startUTC=$dateStart;
$cron->endUTC=date('Y-m-d H:i:s');

$mongoClient->$database->Cronjobs->insertOne($cron);

?>