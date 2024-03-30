<html>
<body>
  <a href="php/errorLog/eliminar.php">Eliminar</a><br>
</body>
</html>
<?php
$file='../error_log';
$orig=file_get_contents($file);  
$orig=str_replace("\n","<br><br>",$orig);
$orig=str_replace("PHP message","<br>PHP message",$orig);
echo $orig; 
?>