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

$dbOrigin="ZohoProjects-Consolidados";
$start = microtime(true);
$dateStart = date('Y-m-d H:i:s');

//SE BUSCA EL DESARROLLO DE CADA INMUEBLE
$mongoClient->$dbOrigin->Products->aggregate(
  [
    ['$lookup' => ['from' => 'Desarrollos', 'localField' => 'Desarrollo.id', 'foreignField' => 'id', 'as' => 'Desarrollo']],
    ['$merge' => ['into' => 'Products', 'whenMatched' => 'replace', 'whenNotMatched' => 'discard']],
  ]
);
$mongoClient->$dbOrigin->Desarrollos->drop();

//SE BUSCA EL INMUEBLE DE CADA DEAL
$mongoClient->$dbOrigin->Deals->aggregate(
  [
    ['$lookup' => ['from' => 'Products', 'localField' => 'Inmueble.id', 'foreignField' => 'id', 'as' => 'Inmueble']],
    ['$merge' => ['into' => 'Deals', 'whenMatched' => 'replace', 'whenNotMatched' => 'discard']],
  ]
);

$mongoClient->$dbOrigin->Products->drop();

//SE BUSCA EL CONTACTO DE CADA DEAL
$mongoClient->$dbOrigin->Deals->aggregate(
  [
    ['$lookup' => ['from' => 'Contacts', 'localField' => 'Contact_Name.id', 'foreignField' => 'id', 'as' => 'Contacto']],
    ['$merge' => ['into' => 'Deals', 'whenMatched' => 'replace', 'whenNotMatched' => 'discard']],
  ]
);

$mongoClient->$dbOrigin->Contacts->drop();

//SE BUSCA EL BROKER DE CADA DEAL
$mongoClient->$dbOrigin->Leads->aggregate(
  [
    ['$lookup' => ['from' => 'Brokers', 'localField' => 'Broker.id', 'foreignField' => 'id', 'as' => 'Broker']],
    ['$merge' => ['into' => 'Leads', 'whenMatched' => 'replace', 'whenNotMatched' => 'discard']],
  ]
);

$mongoClient->$dbOrigin->Brokers->drop();

//SE BUSCA EL DEAL DE CADA LEAD
$mongoClient->$dbOrigin->Leads->aggregate(
  [
    ['$lookup' => ['from' => 'Deals', 'localField' => 'id', 'foreignField' => 'Lead.id', 'as' => 'Deal']],
    ['$merge' => ['into' => 'Leads', 'whenMatched' => 'replace', 'whenNotMatched' => 'discard']]
  ]
);

$mongoClient->$dbOrigin->Deals->drop();

//SE BUSCA EL DUEÑO DE CADA LEAD
$mongoClient->$dbOrigin->Leads->aggregate(
  [
    ['$lookup' => ['from' => 'users', 'localField' => 'Owner.id', 'foreignField' => 'id', 'as' => 'Owner']],
    ['$merge' => ['into' => 'Leads', 'whenMatched' => 'replace', 'whenNotMatched' => 'discard']]
  ]
);

$mongoClient->$dbOrigin->users->drop();





$cron = new stdClass();
$cron->type="Consolidación";
$cron->minutes=(microtime(true) - $start)/60;
$cron->startUTC=$dateStart;
$cron->endUTC=date('Y-m-d H:i:s');
$mongoClient->$database->Cronjobs->insertOne($cron);
?>