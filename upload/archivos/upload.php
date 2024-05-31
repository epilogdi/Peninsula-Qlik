<?php 
$filename = $_FILES['file']['name']; 
$id = $_POST["idx"];
error_log($id);
$location = "uploads/".$filename; 
$uploadOk = 1; 
  
if($uploadOk == 0){ 
   echo 0; 
}else{ 
   if(move_uploaded_file($_FILES['file']['tmp_name'], $location)){ 
      echo $location; 
   }else{ 
      echo 0; 
   } 
} 
?> 