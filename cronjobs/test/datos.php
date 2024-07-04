<?php

if($_SERVER["DOCUMENT_ROOT"]){
  $path = $_SERVER["DOCUMENT_ROOT"];
}else{ 
  $name = basename($_SERVER["PHP_SELF"]);
  $path = str_replace($name, "", $_SERVER["PHP_SELF"]); 
  $path = str_replace("/cronjobs/test/", "", $path);
}

include "$path/environment.php";
include "$path/vendor/autoload.php";
include "$path/includes/mongo.php";


$databases = $mongoClient->listDatabases();
foreach ($databases as $database) {
  $db=$database["name"];
  if(!in_array($db, array("local","admin"))){
    $collections = $mongoClient->$db->listCollections();
    echo "$db<br>";
    foreach ($collections as $collection) {
      $col=$collection["name"];
      echo "----$col<br>";
      $elements = $mongoClient->$db->$col->aggregate([['$sample' => ['size' => 100]]]);
      $element = new stdClass();
      foreach ($elements as $temp) { 
        unset($temp->_id);
        foreach ($temp as $x=>$y) { 
          if(!is_null($y)){
            $element->$x=$y;
          }
        }
      }

      foreach ($element as $key=>$val) {        
        $type = gettype($val);
        echo "--------$key => $type<br>";
        if(is_object($val)){
          foreach ($val as $keyx=>$valx) { 
            $typex = gettype($valx);
            echo "------------$keyx => $typex<br>"; 
            if(is_object($valx)){
              foreach ($valx as $keyxx=>$valxx) { 
                $typexx = gettype($valxx);
                echo "----------------$keyxx => $typexx<br>"; 
              }
            }
          }          
        }        
      }
    }
  }
}


?>