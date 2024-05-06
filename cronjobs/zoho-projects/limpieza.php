<?php

if($_SERVER["DOCUMENT_ROOT"]){
  $path = $_SERVER["DOCUMENT_ROOT"];
}else{ 
  $name = basename($_SERVER["PHP_SELF"]);
  $path = str_replace($name, "", $_SERVER["PHP_SELF"]); 
  $path = str_replace("/cronjobs/zoho-projects/", "", $path);
}

include "$path/environment.php";
include "$path/vendor/autoload.php";
include "$path/includes/mongo.php";

$remove = [
  '$approval' =>  [],
  '$approval_state' =>  [],
  '$approved' =>  [],
  '$currency_symbol' =>  [],
  '$editable' =>  [],
  '$converted' =>  [],
  '$taxable' =>  [],
  '$field_states' =>  [],
  '$in_merge' =>  [],
  '$locked_for_me' =>  [],
  '$orchestration' =>  [],
  '$process_flow' =>  [],
  '$review' =>  [],
  '$review_process' =>  [],
  '$state' =>  [], 
  'Tag' =>  [],
  '$zia_owner_assignment' =>  [],
  'Locked__s' =>  [],
  'Unsubscribed_Mode' =>  [],
  'Unsubscribed_Time' =>  [],
  'Record_Image' =>  [],
  '$followed' =>  [],
  '$followers' =>  [],
  '$converted_detail' =>  [],

];

$database = "ZohoProjects";

$collection = "Actividades - tasklists";
$items = $mongoClient->$database->$collection->find(); 
echo "Actividades <br>";
foreach ($items as $item) {
  echo "$item->name <br>"; 
}
echo "<hr>";
$collection = "Estimaciones - tasklists";
$items = $mongoClient->$database->$collection->find(); 
echo "Estimaciones <br>";
foreach ($items as $item) {
  echo "$item->name <br>"; 
}




?>