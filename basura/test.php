<?php
$server=getcwd();
include $server ."/environment.php";
include $server."/vendor/autoload.php";
include $server."/includes/mysqli.php";
include $server."/includes/mongo.php";

$collections = $mongoClient->Descargados->listCollections();
foreach ($collections as $collection) {
  echo $collection["name"];
}

?>