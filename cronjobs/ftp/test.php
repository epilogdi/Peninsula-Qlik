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
  $entries = scandir($directory);
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
        $xx=$rotulos[$key];
        $obj->$xx=$columna;      
      }
      array_push($objetos, $obj);
    }
  }

  $database = $file->database;
  $collection = $file->collection;
  $mongoClient->$database->$collection->insertMany($objetos);
}





if($_SERVER["DOCUMENT_ROOT"]){
  $path = $_SERVER["DOCUMENT_ROOT"];
}else{ 
  $name = basename($_SERVER["PHP_SELF"]);
  $path = str_replace($name, "", $_SERVER["PHP_SELF"]); 
  $path = str_replace("/cronjobs/ftp/", "", $path);
}

include "$path/environment.php";
include "$path/vendor/autoload.php";
include "$path/includes/mongo.php";
use PhpOffice\PhpSpreadsheet\IOFactory;

$directory = '/home/peninsulaftp';

/*$folders = loopFolders($directory);
foreach ($folders as $folder) {
  $files = loopFiles($folder->path);
  foreach ($files as $file) {


  }
}*/

/*$files = loopFiles('/home/peninsulaftp/ingresos');
foreach ($files as $file) {
  $spreadsheet = IOFactory::load($file->path);
  excelToMongo($file,$spreadsheet);
}*/
//echo 'Current script owner: ' . get_current_user() . "<br>";

$databases = loopFolders('/home/peninsulaftp');
foreach ($databases as $database) {
  echo "-".json_encode($database)."<br>";
  $collections = loopFolders($database->path);
  foreach ($collections as $collection) {
    echo "--".json_encode($collection)."<br>";
    $files = loopFiles($collection->path);
    foreach ($files as $file) {
      echo "---".json_encode($file)."<br>";
      $obj = new stdClass();
      $obj->database = $database->folder;
      $obj->collection = $collection->folder;
      $obj->path = $file->path;
      $obj->content = IOFactory::load($obj->path);
      excelToMongo($obj);
    }
  }
}

?>