<?php

$myfile = fopen("/opt/bitnami/apache2/htdocs/ZohoCRM/master/cronjobs/newfile.txt", "a") or die("Unable to open file!");
fwrite($myfile, "1 : ". $_SERVER["DOCUMENT_ROOT"]. PHP_EOL);
fwrite($myfile, "2 : ". realpath($_SERVER["DOCUMENT_ROOT"]). PHP_EOL);
fwrite($myfile, "3 : ". $_SERVER["PHP_SELF"]. PHP_EOL);
fwrite($myfile, "4 : ". basename($_SERVER["PHP_SELF"]). PHP_EOL);
fwrite($myfile, "5 : ". $_SERVER["HTTP_HOST"]. PHP_EOL);
fwrite($myfile, "6 : ". basename($_SERVER["HTTP_HOST"]). PHP_EOL);
fwrite($myfile, "7 : ". $_SERVER["REQUEST_URI"]. PHP_EOL);
fwrite($myfile, "8 : ". basename($_SERVER["REQUEST_URI"]). PHP_EOL);
fwrite($myfile, "9 : ". getcwd());
fclose($myfile);


?>