<?php
$server=getcwd();
include $server ."/environment.php";
include $server."/vendor/autoload.php";
include $server."/includes/mysqli.php";
include $server."/includes/mongo.php";

/*

//CONTAR NO NULOS
echo $mongoClient->Database->Products->count(["DesarrolloX" => ['$ne' => null]]);

//CONTAR NULOS
echo $mongoClient->Database->Products->count([DesarrolloX" => null]);

//ELIMINAR COLECCION
$mongoClient->Database->UnidadesConDesarrollo->drop();

//LISTAR COLECCION
$productos = $mongoClient->Database->Products->find();

//INSERTAR EN COLECCION
$mongoClient->Database->UnidadesConDesarrollo->insertOne($producto);

//ACTUALIZAR ELEMENTO EN COLECCION
$mongoClient->Consolidados->Potentials->updateOne(
      [ 'id' => $item->id ],
      [ '$set' => ['Inmueble' => $producto, 'Lead' => $lead, 'Contact_Name' => $contact ]],
      ["upsert" => true, "multiple" => true]
    );

//ENCONTRAR EN COLECCION POR ID
$items = $mongoClient->Database->Potentials->find(['id' => "5153690000031344080"]);

//BUSCAR POR CONDICION 
$condition = [
    '$and' => [
      [
        "Broker" => [$ne => null],
        "LeadX" => null,
        "Contact_NameX" => null
      ]
    ]
  ];

*/
$modulo="Leads";
$admin="0-Admin";
$origin="2-Limpiados";
$destination="3-Consolidados";

$start = microtime(true);
$dateStart = date('Y-m-d H:i:s');
consolidar($modulo);
$end = (microtime(true) - $start);
$dateEnd = date('Y-m-d H:i:s');

$cron = new stdClass();
$cron->type="Consolidación";
$cron->minutes=$end/60;
$cron->startUTC=$dateStart;
$cron->endUTC=$dateEnd;

$mongoClient->$admin->Cronjobs->insertOne($cron);


function consolidar($modulo){
  global $mongoClient;
  global $origin;
  global $destination;
  $num=0;
  $mongoClient->$destination->$modulo->drop();
  $items = $mongoClient->$origin->$modulo->find();  
  foreach ($items as $item) {
    $salida = new stdClass();
    $salida->$modulo = $item;
    ////////////////////////////////////////////////////
    $salida->Broker = daniel($item,"Broker","id","Brokers"); 
    ////////////////////////////////////////////////////
    $deal = getNodeBySubAttribute("Deals","Lead","id",$item->id);
    $salida->Deal = $deal;
    ////////////////////////////////////////////////////
    $contact = daniel($deal,"Contact_Name","id","Contacts");
    $salida->Contact = $contact;
    ////////////////////////////////////////////////////
    $inmueble = daniel($deal,"Inmueble","id","Products");
    $salida->Inmueble = $inmueble;
    ////////////////////////////////////////////////////
    $desarrollo = daniel($inmueble,"Desarrollo","id","Desarrollos");
    $salida->Desarrollo = $desarrollo;
    ////////////////////////////////////////////////////
    $mongoClient->$destination->$modulo->insertOne($salida);

    $num++;
    if($num==1000){
      //break;
    }
  }
}

function daniel($item,$node,$attribute,$collection){
  global $origin;
  if(isset($item->$node)){
    if(isset($item->$node->$attribute)){
      $salida=getNodeByAttribute($collection,$attribute,$item->$node->$attribute);
    }else{
      $salida=null;
    }
  }else{
    $salida=null;
  }
  return $salida;
}

function getNodeByAttribute($collection,$attribute,$value){
  global $mongoClient;
  global $origin;
  $item=$mongoClient->$origin->$collection->findOne([$attribute => $value]);
  return $item;
}

function getNodeBySubAttribute($collection,$attribute,$subattribute,$value){
  global $mongoClient;
  global $origin;
  $item=$mongoClient->$origin->$collection->findOne(["$attribute.$subattribute" => "$value"]);
  return $item;
}



?>
