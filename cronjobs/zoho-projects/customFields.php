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
include "$path/includes/zoho-projects.php";

$database = "ZohoProjects";

//$filter = [];
//$options = [['sort' => ['_id' => 1]]];
//$elements = $mongoClient->$database->Deals->find($filter, $options);
$elements = $mongoClient->$database->tasks->find();

foreach ($elements as $element) { 
  foreach ($element->custom_fields as $field) { 
    file_put_contents("$path/cronjobs/zoho-projects/customFields.txt", $field->label_name. "\n",FILE_APPEND);

  }

}


?>