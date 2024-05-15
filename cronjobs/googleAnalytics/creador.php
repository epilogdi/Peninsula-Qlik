<?php

if($_SERVER["DOCUMENT_ROOT"]){
  $path = $_SERVER["DOCUMENT_ROOT"];
}else{ 
  $name = basename($_SERVER["PHP_SELF"]);
  $path = str_replace($name, "", $_SERVER["PHP_SELF"]); 
  $path = str_replace("/cronjobs/googleAnalytics/", "", $path);
}

include "$path/environment.php";
include "$path/vendor/autoload.php";
include "$path/includes/mongo.php";


//$objetos = '[{"nameAlias": "AMANCAY","viewId": 277757220,"enabled": true},{"nameAlias": "BRELIA RESIDENCES","viewId": 397683474,"enabled": true},{"nameAlias": "CIMA PARK","viewId": 366147070,"enabled": true},{"nameAlias": "CITY TOWER","viewId": 365046012,"enabled": true},{"nameAlias": "GARIBALDI","viewId": 362175915,"enabled": true},{"nameAlias": "MARITIMA GOLF","viewId": 370853397,"enabled": true},{"nameAlias": "MARKETPLACE PENINSULA RESIDENCES","viewId": 355934330,"enabled": true},{"nameAlias": "MARKETPLACE PENINSULA RESIDENCES","viewId": 371103145,"enabled": true},{"nameAlias": "NEREA","viewId": 363112990,"enabled": true},{"nameAlias": "OCEAN ONE PUNTA DIAMANTE","viewId": 312344898,"enabled": true},{"nameAlias": "OCEAN ONE PUNTA DIAMANTE","viewId": 313243478,"enabled": true},{"nameAlias": "OCEANONE.MX","viewId": 363851452,"enabled": true},{"nameAlias": "PENINSULA RESIDENCE","viewId": 355932908,"enabled": true},{"nameAlias": "TIZATE RESIDENCES","viewId": 389756712,"enabled": true}]';
$objetos = '[{"nameAlias": "BOLONGO","viewId": 438489908,"enabled": true},{"nameAlias": "VIA RESIDENCES","viewId": 413254519,"enabled": true},{"nameAlias": "VIA INTERLOMAS","viewId": 438557810,"enabled": true}]';
$objetos = json_decode($objetos);
$database = "GoogleAnalytics";
$collection = "Control-Historico";
$mongoClient->$database->$collection->insertMany($objetos);


?>