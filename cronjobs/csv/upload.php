<?php
if($_SERVER["DOCUMENT_ROOT"]){
  $path = $_SERVER["DOCUMENT_ROOT"];
}else{ 
  $name = basename($_SERVER["PHP_SELF"]);
  $path = str_replace($name, "", $_SERVER["PHP_SELF"]); 
  $path = str_replace("/cronjobs/csv/", "", $path);
}

if(isset($_GET["database"]) && !empty($_GET["database"])){
  $database = $_GET["database"];
}else{
  $database = explode("=", $_SERVER['argv'][1])[1];
} 

if(isset($_GET["collection"]) && !empty($_GET["collection"])){
  $collection = $_GET["collection"];
}else{
  $collection = explode("=", $_SERVER['argv'][2])[1];
} 

include "$path/environment.php";
include "$path/vendor/autoload.php";
include "$path/includes/mongo.php";

$directory = "$path/cronjobs/csv/input/semicolon/$collection.csv";


$handle = fopen($directory, "r");
if ($handle) {
  $contador = 1;
  $rotulos = array();
  $batch = array();
  while (($data = fgetcsv($handle, null, ";")) !== FALSE) {
    if($contador == 1){
      $rotulos = $data;
    }else{
      $registro = $data;
      $obj = new stdClass();
      foreach ($registro as $key=>$columna) {
        if (str_contains($columna, '$')) {
          $columna = str_replace(" ", "", $columna);
          $columna = str_replace("$", "", $columna);
          $columna = str_replace(",", "", $columna);
          $columna = floatval($columna);
        }
        $xx = $rotulos[$key];
        $obj->$xx = $columna;         
      }
      array_push($batch, $obj);      
    }

    if(sizeof($batch) == 500){
      $mongoClient->$database->$collection->insertMany($batch);
      $batch = array();
    }
    $contador ++;
  }
  $mongoClient->$database->$collection->insertMany($batch);
  fclose($handle);
}

?>