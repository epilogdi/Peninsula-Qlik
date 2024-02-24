<?php
$orig='/opt/bitnami/apache2/logs/error_log';
file_put_contents($orig, '');
header("Location: ../../errorLog.php");

?>