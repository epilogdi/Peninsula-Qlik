<?php
$mongoClient = new MongoDB\Client("mongodb+srv://$ENV_MONGO_USER:$ENV_MONGO_PASSWORD@$ENV_MONGO_HOST/?retryWrites=true&w=majority");
try {
  $mongoClient->selectDatabase('admin')->command(['ping' => 1]);
} catch (Exception $e) {
  echo "Error de Conexión con Mongo";
  exit();	
}
?>