<?php

function loopFolders($directory){
  $entries = scandir($directory);
  $folders = array();
  foreach ($entries as $entry) {
    if ($entry !== '.' && $entry !== '..') {
      $path = $directory . '/' . $entry;
      if (is_dir($path)) {
        $obj = new stdClass();
        $obj->path = $path;
        $obj->folder = $entry;
        array_push($folders, $obj);
      }
    }
  }
  return $folders;
}

function loopFiles($directory){
  $entries = preg_grep('/^([^.])/', scandir($directory));
  $files = array();
  foreach ($entries as $entry) {
    if ($entry !== '.' && $entry !== '..') {
      $path = $directory . '/' . $entry;
      if (is_file($path)) {
        $obj = new stdClass();
        $obj->path = $path;
        $obj->file = $entry;
        $obj->folder = explode(".", $entry)[0];
        array_push($files, $obj);
      }
    }
  }
  return $files;
}

function excelToMongo($file){
  global $mongoClient;
  $database = $file->database;
  $collection = $file->collection;
  $mongoClient->$database->$collection->drop();
  $worksheet = $file->content->getActiveSheet();
  $rows = $worksheet->toArray();

  $rotulos = array();
  $objetos = array();
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
        if($rotulos[$key]!=""){
          $xx=$rotulos[$key];
          $obj->$xx=$columna;  
        }

      }
      array_push($objetos, $obj);
    }
    if(sizeof($objetos) == 500){
      $mongoClient->$database->$collection->insertMany($objetos);
      $objetos = array();
    }
  }
  $mongoClient->$database->$collection->insertMany($objetos);
}


if($_SERVER["DOCUMENT_ROOT"]){
  $path = $_SERVER["DOCUMENT_ROOT"];
}else{ 
  $name = basename($_SERVER["PHP_SELF"]);
  $path = str_replace($name, "", $_SERVER["PHP_SELF"]); 
  $path = str_replace("/cronjobs/drive/", "", $path);
}

include "$path/environment.php";
include "$path/vendor/autoload.php";
include "$path/includes/mongo.php";
use PhpOffice\PhpSpreadsheet\IOFactory;

$directory = '/home/rodrigo2/CARGUES'; 
$databases = loopFolders($directory);
foreach ($databases as $database) {
  echo "-".json_encode($database)."<br>";
  $collections = loopFiles($database->path);
  foreach ($collections as $collection) {
    echo "--".json_encode($collection)."<br>";
    $obj = new stdClass();
    $obj->database = $database->folder;
    $obj->collection = explode(".", $collection->file)[0];
    $obj->path = $collection->path;
    echo "---".json_encode($obj)."<br>";
    $obj->content = IOFactory::load($collection->path);
    if(excelToMongo($obj)){
      //unlink($file->path);
    }
  }

}

?>