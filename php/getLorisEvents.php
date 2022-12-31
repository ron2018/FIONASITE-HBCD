<?php

// get the token from the config file
$project = "";
if (isset($_GET['project'])) {
   $project = $_GET['project'];
}
if ($project == "HBCD") {
   $project = "";
}

if (! file_exists("config.json")) {
   echo ("{ \"message\": \"Error: could not read the config file\", \"ok\": \"0\" }");
   return;
}
$configs = json_decode(file_get_contents("config.json"), TRUE);
if (!isset($configs['CONNECTION'])) {
   echo ("{ \"message\": \"Error: could not find CONNECTION setting in the config file\", \"ok\": \"0\" }");
   return;
}

$token = "";
if ($project != "") {
   $token = $configs['SITES'][$project]['CONNECTION'];
} else {
  $token = $configs['CONNECTION'];
}
if ($token == "") {
   echo ("{ \"message\": \"Error: no token found in config file\", \"ok\": \"0\" }");
   return;
}

$proxy = "";
$proxyport = 3128;
if (isset($configs['WEBPROXY'])) {
  $proxy=$configs['WEBPROXY'];
  $proxyport=$configs['WEBPROXYPORT'];
}


$command = escapeshellcmd('/var/www/html/php/getParticipantInfoFromLoris.py 2>&1');
$output = exec($command);
#echo $output;

$result = json_decode($output,true);
#echo $result;

$records = array();
foreach ($result as $res) {
 #  echo $res['visit_label'];
   $records[] = $res['visit_label'];
}


print(json_encode(array_values(array_unique($records))));
#echo $results;
#print($results);

?>
