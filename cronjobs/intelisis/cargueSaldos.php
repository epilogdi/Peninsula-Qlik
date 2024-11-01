<?php

if($_SERVER["DOCUMENT_ROOT"]){
  $path = $_SERVER["DOCUMENT_ROOT"];
}else{ 
  $name = basename($_SERVER["PHP_SELF"]);
  $path = str_replace($name, "", $_SERVER["PHP_SELF"]); 
  $path = str_replace("/cronjobs/intelisis/", "", $path);
}

include "$path/environment.php";
include "$path/vendor/autoload.php";
include "$path/includes/mongo.php";

use PhpOffice\PhpSpreadsheet\IOFactory;

$filename = '../../../saldos.xlsx';
$spreadsheet = IOFactory::load($filename);
$worksheet = $spreadsheet->getActiveSheet();
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

$database = "Cobranzas";
$collection = "Saldos";
$mongoClient->$database->$collection->insertMany($objetos);


?>