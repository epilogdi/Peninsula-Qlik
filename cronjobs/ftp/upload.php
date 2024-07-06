<?php

function readExcel($file){
  global $mongoClient;
  $database = $file->database;
  $collection = $file->collection;
  $worksheet = $file->content->getActiveSheet();
  $rows = $worksheet->toArray();

  $rotulos = array();
  $batch = array();
  foreach ($rows as $key=>$row) {
    if($key == 0){
      $rotulos = $row;
    }else{
      $obj = new stdClass();
      $columnas = $row;
      foreach ($columnas as $key=>$columna) {
        if (str_contains($columna, '$')) {
          $columna = str_replace(" ", "", $columna);
          $columna = str_replace("$", "", $columna);
          $columna = str_replace(",", "", $columna);
          $columna = floatval($columna);
        }
        $xx=$rotulos[$key];
        $obj->$xx=$columna;      
      }
      array_push($batch, $obj);
    }
    if(sizeof($batch) == 500){
      $mongoClient->$database->$collection->insertMany($batch);
      $batch = array();
    }
  }
  $mongoClient->$database->$collection->insertMany($batch);
  return true;
}


if($_SERVER["DOCUMENT_ROOT"]){
  $path = $_SERVER["DOCUMENT_ROOT"];
}else{ 
  $name = basename($_SERVER["PHP_SELF"]);
  $path = str_replace($name, "", $_SERVER["PHP_SELF"]); 
  $path = str_replace("/cronjobs/ftp/", "", $path);
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
use PhpOffice\PhpSpreadsheet\IOFactory;

$directory = "/home/peninsulaftp/$database/$collection/$collection.xlsx";
if (file_exists($directory)) {
  $obj = new stdClass();
  $obj->database = $database;
  $obj->collection = $collection;
  $obj->path = $directory;
  $obj->content = IOFactory::load($obj->path);
  if(readExcel($obj)){
    //$mongoClient->$database->$collection->drop();
    //unlink($obj->path);
  }
}


?>