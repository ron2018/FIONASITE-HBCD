<?php

// calculate what the status of the current scan is
// series instance uid can identify more than one scan
// return an array of detected scans

$config = json_decode(file_get_contents('config.json'), TRUE);
if (isset($config['LOCALTIMEZONE'])) {
   date_default_timezone_set($config['LOCALTIMEZONE']);
}

$filename = "";

$project = "";

if (isset($_GET['project'])) {
  $project = $_GET['project'];
}

if ($project == "HBCD") {
   $project = "";
}

if (isset($_GET['filename']) && $_GET['filename'] !== "") {
   $filename = $_GET['filename'];
} else {
  echo ("{ \"ok\": 0, \"message\": \"ERROR: filename not set\" }");
  return;
}
#$filename="rawdata_suid_1.3.12.2.1107.5.2.43.166158.30000023062013162853300000030_seuid_1.3.12.2.1107.5.2.43.166158.2023062215492942043238624.0.0.0.dat";
#$filename="XISTL0009_181193_V02_MRS.zip";

$fn = $filename;
$path_info = pathinfo($filename);

// we can find this study/series in three locations
// we will assume that the naming convention ensures that the series instance uid is in the filename
if (!isset($path_info['extension'])) {
   syslog(LOG_EMERG, "Error: did not get an extension on ".$filename);
}
$q = glob('/data'.$project.'/quarantine/'.$path_info['filename'].".".$path_info['extension']);
$o = glob('/data'.$project.'/outbox/*'.$path_info['filename'].".".$path_info['extension']);
$d = glob('/data'.$project.'/DAIC/*'.$path_info['filename'].".".$path_info['extension']);

$suid="";
$tripleid="";
$filestrs = explode('_',$path_info['filename']);
if ($filestrs[0] == 'rawdata') {
	$suid=$filestrs[2];
}	


$e = glob('/data'.$project.'/site/kspace/outbox/'.$suid.'/'.$path_info['filename'].".".$path_info['extension']);
$p = glob('/data'.$project.'/site/kspace/processed/'.$suid.'/'.$path_info['filename'].".".$path_info['extension']);
$g = glob('/data'.$project.'/site/mrs/'.$path_info['filename'].".".$path_info['extension']);
$h = glob('/data'.$project.'/site/mrs/umn/'.$path_info['filename'].".".$path_info['extension']);


// we should check if we have an md5sum file for each one
$qvalid = array();
foreach($q as $f) {
   $path_parts = pathinfo($f);
   // lets see if we have an md5sum file here
   $md5sumfname = $path_parts['dirname'].DIRECTORY_SEPARATOR.$path_parts['filename'].'.md5sum';
   $jsonfname = $path_parts['dirname'].DIRECTORY_SEPARATOR.$path_parts['filename'].'.json';
   if (file_exists($md5sumfname)) {
       $qvalid[] = [$f, date ("F d Y H:i:s.", filemtime($md5sumfname))];
   }
}

$ovalid = array();
foreach($o as $f) {
   $path_parts = pathinfo($f);
   // lets see if we have an md5sum file here
   $md5sumfname = $path_parts['dirname'].DIRECTORY_SEPARATOR.$path_parts['filename'].'.md5sum';
   $jsonfname = $path_parts['dirname'].DIRECTORY_SEPARATOR.$path_parts['filename'].'.json';
   if (file_exists($md5sumfname)) {
       $ovalid[] = [$f, date ("F d Y H:i:s.", filemtime($md5sumfname))];
   }
}

$dvalid = array();
foreach($d as $f) {
   $path_parts = pathinfo($f);
   // lets see if we have an md5sum file here
   $md5sumfname = $path_parts['dirname'].DIRECTORY_SEPARATOR.$path_parts['filename'].'.md5sum';
   $jsonfname = $path_parts['dirname'].DIRECTORY_SEPARATOR.$path_parts['filename'].'.json';
   if (file_exists($md5sumfname)) {
       $dvalid[] = [$f, date ("F d Y H:i:s.", filemtime($md5sumfname))];
   }
}

$evalid = array();
foreach($e as $f) {

   $path_parts = pathinfo($f);
   // lets see if we have an md5sum file here
   $md5sumfname = $path_parts['dirname'].DIRECTORY_SEPARATOR.$path_parts['filename'].'.md5sum';
   if (file_exists($md5sumfname)) {
       $evalid[] = [$f, date ("F d Y H:i:s.", filemtime($md5sumfname))];
   }
}
$fvalid = array();
foreach($p as $f) {
   $path_parts = pathinfo($f);
   // lets see if we have an md5sum file here
   $md5sumfname = $path_parts['dirname'].DIRECTORY_SEPARATOR.$path_parts['filename'].'.md5sum';
   if (file_exists($md5sumfname)) {
       $fvalid[] = [$f, date ("F d Y H:i:s.", filemtime($md5sumfname))];
   }
}

$gvalid = array();
foreach($g as $f) {

   $path_parts = pathinfo($f);
   // lets see if we have an md5sum file here
   $md5sumfname = $path_parts['dirname'].DIRECTORY_SEPARATOR.$path_parts['filename'].'.md5sum';
   if (file_exists($md5sumfname)) {
       $gvalid[] = [$f, date ("F d Y H:i:s.", filemtime($md5sumfname))];
   }
}
$hvalid = array();
foreach($h as $f) {
   $path_parts = pathinfo($f);
   // lets see if we have an md5sum file here
   $md5sumfname = $path_parts['dirname'].DIRECTORY_SEPARATOR.$path_parts['filename'].'.md5sum';
   if (file_exists($md5sumfname)) {
       $hvalid[] = [$f, date ("F d Y H:i:s.", filemtime($md5sumfname))];
   }
}

$val = array();
foreach($qvalid as $qv) {
   $val[] = array( "ok" => 1, "message" => "readyToSend", "filename" => $qv[0], "filemtime" => $qv[1] );
}
foreach($ovalid as $qv) {
   $val[] = array( "ok" => 1, "message" => "transit", "filename" => $qv[0], "filemtime" => $qv[1] );
}
foreach($dvalid as $qv) {
   $val[] = array( "ok" => 1, "message" => "transferred", "filename" => $qv[0], "filemtime" => $qv[1] );
}
foreach($evalid as $qv) {
   $val[] = array( "ok" => 1, "message" => "readyToSend", "filename" => $qv[0], "filemtime" => $qv[1] );
}
foreach($fvalid as $qv) {
   $val[] = array( "ok" => 1, "message" => "transferred", "filename" => $qv[0], "filemtime" => $qv[1] );
}
foreach($gvalid as $qv) {
   $val[] = array( "ok" => 1, "message" => "readyToSend", "filename" => $qv[0], "filemtime" => $qv[1] );
}
foreach($hvalid as $qv) {
   $val[] = array( "ok" => 1, "message" => "transferred", "filename" => $qv[0], "filemtime" => $qv[1] );
}

echo(json_encode($val));

return;

?>
