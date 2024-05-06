<?php 
require_once "vendor/autoload.php";

$analytics = initializeAnalytics();
$profileId = getFirstProfileId($analytics);
$viewId = "308641638";
echo json_encode($profileId);
echo "<hr>";
$results = getReport($analytics, $viewId);
echo json_encode($results);


function initializeAnalytics()
{
  $KEY_FILE_LOCATION = '../seismic-iridium-421119-fa5b27d1cef4.json';
  $client = new Google_Client();
  $client->setApplicationName("Hello Analytics Reporting");
  $client->setAuthConfig($KEY_FILE_LOCATION);
  $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
  $analytics = new Google_Service_Analytics($client);
  echo json_encode($analytics);
  return $analytics;
}

function getFirstProfileId($analytics) {
  $accounts = $analytics->management_accounts->listManagementAccounts();
  if (count($accounts->getItems()) > 0) {
    $items = $accounts->getItems();
    $firstAccountId = $items[0]->getId();
    $properties = $analytics->management_webproperties->listManagementWebproperties($firstAccountId);
    if (count($properties->getItems()) > 0) {
      $items = $properties->getItems();
      $firstPropertyId = $items[0]->getId();
      $profiles = $analytics->management_profiles->listManagementProfiles($firstAccountId, $firstPropertyId);
      if (count($profiles->getItems()) > 0) {
        $items = $profiles->getItems();
        return $items[0]->getId();
      } else {
        throw new Exception('No views (profiles) found for this user.');
      }
    } else {
      throw new Exception('No properties found for this user.');
    }
  } else {
    throw new Exception('No accounts found for this user.');
  }
}

function getReport($analytics, $viewId) {
  // Create the DateRange object.
  $dateRange = new Google_Service_AnalyticsReporting_DateRange();
  $dateRange->setStartDate("7daysAgo");
  $dateRange->setEndDate("today");

  // Create the Metrics object.
  $sessions = new Google_Service_AnalyticsReporting_Metric();
  $sessions->setExpression("ga:sessions");
  $sessions->setAlias("sessions");

  // Create the ReportRequest object.
  $request = new Google_Service_AnalyticsReporting_ReportRequest();
  $request->setViewId($viewId);
  $request->setDateRanges($dateRange);
  $request->setMetrics(array($sessions));

  $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
  $body->setReportRequests( array( $request) );
  return $analytics->reports->batchGet( $body );
}


function getResults($analytics, $profileId) {
  // Calls the Core Reporting API and queries for the number of sessions
  // for the last seven days.
   return $analytics->data_ga->get(
       'ga:' . $profileId,
       '7daysAgo',
       'today',
       'ga:sessions');
}







/*require(realpath($_SERVER["DOCUMENT_ROOT"])."/php/config.php");
require($server."/php/funciones.php");
require($server."/php/google.php");
require($server."/php/sessionCuenta.php");
require($server."/vendor/autoload.php");
use Google\Cloud\Storage\StorageClient;
$storage = new StorageClient();

function optimizar($origin,$destination,$quality){
  list($width, $height) = getimagesize($origin);
  $resource = imagecreatefromjpeg($origin);
  imagescale($resource, $width, $height);
  imagejpeg($resource, $destination, $quality);
  if(uploadToStorage("placelings-standard","optimized",$destination)){
    unlink($destination);
    imagedestroy($resource);
    return true;
  }else{
    return false;
  }
}

$image = $_POST['imk'];
$ratio = $_POST['ratio'];
$initialX = $_POST['initialX']*$ratio ;
$initialY = $_POST['initialY']*$ratio; 
$lengthX = $_POST['lengthX']*$ratio;
$lengthY = $_POST['lengthY']*$ratio;
$uniq = RandomString(10,TRUE,TRUE,FALSE);
$imgType = explode(";", $image);
$imgType=$imgType[0];
$imgType = explode("/", $imgType);
$imgType=$imgType[1];
$image = str_replace('data:image/'.$imgType.';base64,', '', $image);	
$data = base64_decode($image);
$file = $server."/php/entidades/publicaciones/fotos/".$uniq.'.'.$imgType;
$original = $server."/php/entidades/publicaciones/originals/".$uniq.'.'.$imgType;
$optimized=$server.'/php/entidades/publicaciones/optimized/'.$uniq.'.'.$imgType;
$wm = $server."/php/entidades/publicaciones/watermarks/wm2.png";
if($wm = imagecreatefrompng($wm)){//watermark
  if(file_put_contents($original, $data)){
    if(chmod($original, 0777)){
      if($img_r = imagecreatefromjpeg($original)){
        if($dst_r = ImageCreateTrueColor( 750, 510 )){
          if(imagecopyresampled($dst_r,$img_r,0,0,$initialX,$initialY,750,510,$lengthX,$lengthY)){
            if(imagecopymerge ( $dst_r ,$wm,  0 , 0 , 0 , 0 , 750 , 510 , 40 )){
              if(imagejpeg($dst_r,$file,$calidadFotos)){            
                optimizar($file,$optimized,$calidadOptimizadas); 
                  if(uploadToStorage("placelings-standard","fotos",$file)){
                    unlink($file);
                     if(uploadToStorage("placelings-standard","originals",$original)){
                        unlink($original);
                        generarPublicaciones($mysqli);
                        $resultado=reportarEvento($path,"107",array("foto"=>$uniq.".".$imgType));
                      }else{          
                        $resultado=reportarError("032",$path,$query);		
                      }    
                  }else{          
                    $resultado=reportarError("032",$path,$query);		
                  }                
              }else{
                $resultado=reportarError("xxx",$path,$file);
              }              
            }else{
              $resultado=reportarError("xxx",$path,$file);
            }            
          }else{
            $resultado=reportarError("xxx",$path,$file);
          }          
        }else{
          $resultado=reportarError("xxx",$path,$file);
        }        
      }else{
        $resultado=reportarError("xxx",$path,$file);
      }
    }else{
      $resultado=reportarError("xxx",$path,$file);
    }
  }else{
    $resultado=reportarError("xxx",$path,$file);
  }
}else{
$resultado=reportarError("xxx",$path,$file);
}

echo json_encode($resultado);*/
?>