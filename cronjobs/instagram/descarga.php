<?php

if($_SERVER["DOCUMENT_ROOT"]){
  $path = $_SERVER["DOCUMENT_ROOT"];
}else{ 
  $name = basename($_SERVER["PHP_SELF"]);
  $path = str_replace($name, "", $_SERVER["PHP_SELF"]); 
  $path = str_replace("/cronjobs/instagram/", "", $path);
}

include "$path/environment.php";
include "$path/vendor/autoload.php";
include "$path/includes/mongo.php";
//include "$path/includes/instagram.php";

use Instagram\User\BusinessDiscovery;

$config = array( // instantiation config params
    'user_id' => '<IG_USER_ID>',
    'username' => '<USERNAME>', // string of the Instagram account username to get data on
    'access_token' => '<ACCESS_TOKEN>',
);

// instantiate business discovery for a user
$businessDiscovery = new BusinessDiscovery( $config );

// initial business discovery
$userBusinessDiscovery = $businessDiscovery->getSelf();

?>