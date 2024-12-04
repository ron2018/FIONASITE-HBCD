<?php

$filename = "";
$id_redcap = "";
$redcap_event_name = "";
$run = "";
$log = '/var/www/html/server/logs/sendToDAIC.log';
$project = "";

$config = json_decode(file_get_contents('config.json'), TRUE);
if (isset($config['LOCALTIMEZONE'])) {
   date_default_timezone_set($config['LOCALTIMEZONE']);
}

if (isset($_GET['project'])) {
   $project = $_GET['project'];
}  
if ($project == "HBCD") {
   $project = "";
}

// find out if we have anonymization enabled
$fn="/data/config/enabled";
if ($project !== "") {
   $fn = '/data'.$project.'/config/enabled';
}
$enable = file_get_contents($fn);
$ar = str_split($enable);
$val = array(0,0,0);
if (count($ar) > 2) {
  $val[2] = ($ar[2] == "0"?"0":"1");
}
$need_to_anonymize = ($val[2]==1);

if (!file_exists($log)) {
   // try to create empty log file
   file_put_contents($log, "");
}

if (isset($_GET['filename'])) {
    $filename = $_GET['filename'];
} else {
    echo ("{ \"ok\": 0, \"message\": \"filename not set\" }");
    return;
}
if (isset($_GET['id_redcap']) && $_GET['id_redcap'] != "") {
    $id_redcap = $_GET['id_redcap'];
} else {
    echo ("{ \"ok\": 0, \"message\": \"id_redcap not set\" }");
    return;
}

if (isset($_GET['run']) && $_GET['run'] != "") {
    $run = $_GET['run'];
} else {
    echo ("{ \"ok\": 0, \"message\": \"run not set\" }");
    return;
}

if (isset($_GET['modify_participant_name']) && $_GET['modify_participant_name'] != "") {
    $modify_participant_name = $_GET['modify_participant_name'];
} else {
    echo ("{ \"ok\": 0, \"message\": \"modify_participant_name not set\" }");
    return;
}

if (isset($_GET['sex']) && $_GET['sex'] != "") {
    $sex = $_GET['sex'];
} else {
    echo ("{ \"ok\": 0, \"message\": \"sex not set\" }");
    return;
}

if (isset($_GET['age']) && $_GET['age'] != "") {
    $age = $_GET['age'];
} else {
    echo ("{ \"ok\": 0, \"message\": \"age not set\" }");
    return;
}

if (isset($_GET['dob']) && $_GET['dob'] != "") {
    $dob = $_GET['dob'];
} else {
    echo ("{ \"ok\": 0, \"message\": \"dob not set\" }");
    return;
}


$path_info = pathinfo($filename);
file_put_contents($log, date(DATE_ATOM)." Sending this file(s) to UCSD:  " .$path_info['filename']." \n", FILE_APPEND);
file_put_contents($log, date(DATE_ATOM)." Check if we need to modify the dicom  " . $modify_participant_name . " with Sex: ".$sex. "  and Age :".$age." and DOB ".$dob." \n", FILE_APPEND);

$lock = '/var/www/html/php/.lock/'.$path_info['filename'];
file_put_contents($log, "lock file name: ".$lock, FILE_APPEND);
if (file_exists($lock)) {
   // lock file exists exit sendToDAIC
    file_put_contents($log, "lock file exists exit sendToDAIC for ".$filename." \n",FILE_APPEND);
    return;
}
file_put_contents($lock, "");

$f = glob('/data'.$project.'/quarantine/'.$path_info['filename'].'*');
$oksessions = array();
$failedsessions = array();
$modify_participant_name = "1";
foreach($f as $fi) {
   $path_parts = pathinfo($fi);
   $destination = '/data'.$project.'/outbox';
   if ( $modify_participant_name == "1" ) {
      if ( $path_parts['extension'] == "tgz") {

         echo (" Start modifying $filename");
         file_put_contents($log, date(DATE_ATOM)." We need to modify the dicom for " . $id_redcap . " \n", FILE_APPEND);
         $command=escapeshellcmd("python /var/www/html/php/modifyDicoms.py --tripleId=".$id_redcap." --run=".$run." --sex=".$sex." --age=".$age." --dob=".$dob." --filename=".$path_parts['filename']);
         $output = exec($command);
         # need handle the exception if modify dicoms are not ok
         file_put_contents($log, date(DATE_ATOM).$command."\n", FILE_APPEND);
         file_put_contents($log, date(DATE_ATOM)." modifyDicoms.py run results: ".$output." \n", FILE_APPEND);
      }
       
   } else {
      file_put_contents($log, date(DATE_ATOM)." Move file to " . $destination . " now ".$fi." (header: ".$id_redcap."_".$run.")\n", FILE_APPEND); 
      $prefix = $id_redcap."_".$run;
      $ok=rename($fi, $destination.DIRECTORY_SEPARATOR.$prefix."_".$path_parts['filename'].'.'.$path_parts['extension']);
   }
   if (!$ok) {
       $failedsessions[] = $prefix. " " . $fi;
   } else {
         $oksessions[] = $prefix. " " . $fi;
   }
}


if (file_exists($lock)) {
        file_put_contents($log, "Delete Lock File: ".$lock." \n", FILE_APPEND);
        unlink($lock);
}
$output="{ \"ok\": 1, \"ok_series\": \"".implode(",",$oksessions)."\", \"failed_series\": \"".implode(",", $failedsessions)."\"}";
echo ($output);

return;

?>
