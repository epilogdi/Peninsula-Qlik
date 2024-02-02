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

consolidar($modulo);

function consolidar($modulo){
  global $mongoClient;
  $num=0;
  $mongoClient->Consolidados->$modulo->drop();
  $items = $mongoClient->Limpiados->$modulo->find();  
  foreach ($items as $item) {
    $salida = new stdClass();
    $salida->$modulo = $item;
    ////////////////////////////////////////////////////
    if(isset($item->Broker)){
      if(isset($item->Broker->id)){
        $broker=getNodeByAttribute("Brokers","id",$item->Broker->id);
      }else{
        $broker=null;
      }
    }else{
      $broker=null;
    }
    $salida->Broker = $broker; 
    ////////////////////////////////////////////////////
    $deal = getNodeBySubAttribute("Deals","Lead","id",$item->id);
    $salida->Deal = $deal;
    ////////////////////////////////////////////////////
    if(isset($deal->Contact_Name)){
      if(isset($deal->Contact_Name->id)){
        $contact=getNodeByAttribute("Contacts","id",$deal->Contact_Name->id);
      }else{
        $contact=null;
      }
    }else{
      $contact=null;
    }
    $salida->Contact = $contact;
    ////////////////////////////////////////////////////
    if(isset($deal->Inmueble)){
      if(isset($deal->Inmueble->id)){
        $inmueble=getNodeByAttribute("Products","id",$deal->Inmueble->id);
      }else{
        $inmueble=null;
      }
    }else{
      $inmueble=null;
    }
    $salida->Inmueble = $inmueble;
    ////////////////////////////////////////////////////
    if(isset($inmueble->Desarrollo)){
      if(isset($inmueble->Desarrollo->id)){
        $desarrollo=getNodeByAttribute("Desarrollos","id",$inmueble->Desarrollo->id);
      }else{
        $desarrollo=null;
      }
    }else{
      $desarrollo=null;
    }
    $salida->Desarrollo = $desarrollo;
    ////////////////////////////////////////////////////
    $mongoClient->Consolidados->$modulo->insertOne($salida);

    $num++;
    if($num==1000){
      //break;
    }
  }
}

/*function consolidar($modulo){
  global $mongoClient;
  $num=0;
  $mongoClient->Consolidados->$modulo->drop();
  $items = $mongoClient->Limpiados->$modulo->find();  
  foreach ($items as $item) {
    $salida = new stdClass();
    $salida->$modulo = $item;

    $contact=($item->Contact_Name)?getNodeByAttribute("Contacts","id",$item->Contact_Name->id):null;
    $salida->Contact = $contact;

    $Inmueble=($item->Inmueble)?getNodeByAttribute("Products","id",$item->Inmueble->id):null;
    $salida->Inmueble = $Inmueble;

    $lead=($item->Lead)?getNodeByAttribute("Leads","id",$item->Lead->id):null;
    $salida->Lead = $lead;  

    //$broker=($item->Contact_Name &&  $item->Contact_Name->Broker)?getNodeByAttribute("Brokers","id",$item->Contact_Name->Broker->id):null;
    //$salida->Broker = $broker;  

    //$account=($item->Contact_Name &&  $item->Contact_Name->Account_Name)?getNodeByAttribute("Accounts","id",$item->Contact_Name->Account_Name->id):null;
    //$salida->Account = $account;  

    $desarrollo=($Inmueble &&  $Inmueble->Desarrollo)?getNodeByAttribute("Desarrollos","id",$Inmueble->Desarrollo->id):null;
    $salida->Desarrollo = $desarrollo;  

    error_log("Broker:".$lead->Broker->id);
    $broker=($lead &&  $lead->Broker)?getNodeByAttribute("Brokers","id",$lead->Broker->id):null;
    $salida->Broker = $broker;  

    error_log("Account_Name:".$contact->Account_Name->id);
    $account=($contact &&  $contact->Account_Name)?getNodeByAttribute("Accounts","id",$contact->Account_Name->id):null;
    $salida->Account = $account;  





    $mongoClient->Consolidados->$modulo->insertOne($salida);

    $num++;
    if($num==100){
      break;
    }
  }
}*/

function getNodeByAttribute($collection,$attribute,$value){
  global $mongoClient;
  $item=$mongoClient->Limpiados->$collection->findOne([$attribute => $value]);
  $item=removeTrash($item);
  return $item;
}

function getNodeBySubAttribute($collection,$attribute,$subattribute,$value){
  global $mongoClient;
  $item=$mongoClient->Limpiados->$collection->findOne(["$attribute.$subattribute" => "$value"]);
  $item=removeTrash($item);
  return $item;
}

/*
$coleccionOrigen=$deal;
$atributoOrigen="Contact_Name";
$valorOrigen="id";
$coleccionDestino="Contacts";
$valorDestino="id";
function xxxxx($coleccionOrigen,$atributoOrigen,$valorOrigen,$coleccionDestino,$valorDestino){  
  $salida=new stdClass();
  if(isset($coleccionOrigen->$atributoOrigen)){
    if(isset($coleccionOrigen->$atributoOrigen->id)){
      $salida=getNodeByAttribute($coleccionDestino,$valorDestino,$coleccionOrigen->$atributoOrigen->$valorOrigen);
    }else{
      $salida=null;
    }
  }else{
    $salida=null;
  }
  return $salida; 
}*/








function removeTrash($item){

  unset($item['$approval']);
  unset($item['$approval_state']);
  unset($item['$approved']);
  unset($item['$currency_symbol']);
  unset($item['$editable']);
  unset($item['$field_states']);
  unset($item['$in_merge']);
  unset($item['$locked_for_me']);
  unset($item['$orchestration']);
  unset($item['$process_flow']);
  unset($item['$review']);
  unset($item['$review_process']);
  unset($item['$state']);
  unset($item['Created_By']);
  unset($item['Created_Time']);
  unset($item['Last_Activity_Time']);
  unset($item['Modified_By']);
  unset($item['Modified_Time']);
  unset($item['Owner']);
  unset($item['Tag']);
  unset($item['_id']);
  unset($item['$zia_owner_assignment']);
  unset($item['Locked__s']);
  unset($item['Unsubscribed_Mode']);
  unset($item['Unsubscribed_Time']);
  unset($item['Record_Image']);
  unset($item['$followed']);
  unset($item['$followers']);
  return $item;
}


?>
