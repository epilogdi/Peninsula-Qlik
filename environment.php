<?php
$pathx = explode('/', $path  );
array_pop($pathx);
$pathx = implode('/', $pathx); 
$env = parse_ini_file("$pathx/.env");

$ENV_MYSQL_HOST = $env["MYSQL_HOST"];
$ENV_MYSQL_USER = $env['MYSQL_USER'];
$ENV_MYSQL_PASSWORD = $env['MYSQL_PASSWORD'];
$ENV_MYSQL_DATABASE = $env['MYSQL_DATABASE'];

$ENV_MONGO_HOST = $env["MONGO_HOST"];
$ENV_MONGO_USER = $env['MONGO_USER'];
$ENV_MONGO_PASSWORD = $env['MONGO_PASSWORD'];

$ENV_ZOHO_REFRESH_TOKEN = $env["ZOHO_REFRESH_TOKEN"];
$ENV_ZOHO_CLIENT_ID = $env['ZOHO_CLIENT_ID'];
$ENV_ZOHO_CLIENT_SECRET = $env['ZOHO_CLIENT_SECRET'];

?>
