<?php

//
// Try to send a notification that image data is going to be send to the UCSD.
// Currently copy the scp_SUID.json file in the quaretine folder to timestammped
// 

$suid = "1.3.46.670589.11.34223.5.0.9892.2023080308322040000";
if (isset($_GET['suid'])) {
  $suid = $_GET['suid'];
} else {
  echo "Error: no suid specified";
  return;
}
$project = "";
if (isset($_GET['project'])) {
   $project = $_GET['project'];
}
if ($project == "HBCD") {
   $project = "";
}

// get the token from the config file
if (! file_exists("config.json")) {
   echo ("{ \"message\": \"Error: could not read the config file\", \"ok\": \"0\" }");
   return;
}
$configs = json_decode(file_get_contents('config.json'), TRUE);
$proxy = "";
$proxyport = 3128;
if (isset($configs['WEBPROXY'])) {
  $proxy=$configs['WEBPROXY'];
  $proxyport=$configs['WEBPROXYPORT'];
}
if (isset($configs['LOCALTIMEZONE'])) {
  date_default_timezone_set($configs['LOCALTIMEZONE']);
}

// $configs = json_decode(file_get_contents("config.json"), TRUE);
if (!isset($configs['CONNECTION'])) {
   echo ("{ \"message\": \"Error: could not find CONNECTION setting in the config file\", \"ok\": \"0\" }");
   return;
}
$token = $configs['CONNECTION'];
if ($token == "") {
   echo ("{ \"message\": \"Error: no token found in config file\", \"ok\": \"0\" }");
   return;
}

//
// Identify scp_SUID.json file in the /data/quarantine/ and copy it.
//

$now = date('m-d-Y-H-i');
echo "SUID = ${suid} timestamp = ${now}";
if (file_exists("/data/quarantine/scp_${suid}.json")) {

   echo ("{ \"message\": \"/data/quarantine/scp_${suid}.json Found\", \"ok\": \"0\" }");
   if (copy("/data/quarantine/scp_${suid}.json", "/data/quarantine/scp_${suid}_toUCSDat_${now}.json")) {
     echo ("{ \"message\": \"/data/quarantine/scp_${suid}.json is marked as sent\", \"ok\": \"0\" }");
   }   
}



?>
