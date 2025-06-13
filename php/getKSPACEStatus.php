<?php

$project = "";
if (isset($_GET['project'])) {
   $project = $_GET['project'];
}
if ($project == "HBCD") {
   $project = "";
}

#$val = getopt("i:s:t:");
#if ($val !== false) {
#	echo var_export($val, true);
#}
#else {
#	echo "Could not get value of command line option\n";
#}

#$id_triplet = $val["i"];
#$scann_date = $val["s"];
#$fiona_id = $val["t"];

$id_triplet = "";
if (isset($_GET['i'])) {
   $id_triplet = $_GET['i'];
}
$scann_date = "";
if (isset($_GET['s'])) {
   $scann_date = $_GET['s'];
}


#echo $id_triplet;
#$fiona_id = "OKH";
#echo $fiona_id;
#$scann_date ="2022-11-22";
#echo $scann_date;


$proxy = "";
$proxyport = 3128;
if (isset($configs['WEBPROXY'])) {
  $proxy=$configs['WEBPROXY'];
  $proxyport=$configs['WEBPROXYPORT'];
}

// get the token from the config file
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
if ($project !== "") {
   $token = $configs['SITES'][$project]['CONNECTION'];
} else {
   $token = $configs['CONNECTION'];
}
if ($token == "") {
   echo ("{ \"message\": \"Error: no token found in config file\", \"ok\": \"0\" }");
   return;
}
#echo($token);
$fiona_id = "";
if ($project !== "") {
   $fiona_id = $configs['SITES'][$project]['FIOANID'];
} else {
   $fiona_id = $configs['FIONAID'];
}
if ($fiona_id == "") {
   echo ("{ \"message\": \"Error: no fiona ID found in config file\", \"ok\": \"0\" }");
   return;
}


$command = escapeshellcmd('python /var/www/html/php/getKSPACEStatus.py');
#echo ($command);
$output = exec($command);
echo $output;

#$result = json_decode($output,true);
#(array_values(array_unique($result)));
#print($result);

#$records = array();
#foreach ($result as $res) {
#   if (isset($res['pscId'])) {
        
#       $records[] = $res['pscId']."_".$res['dccId']."_".$res['visit'];
#       $records[1] = $res['gender']."_".$res['anonymizedDOB']."_".$res['age'];
#       #$records[] = $result  
#   } else {
#       if (isset($res['error'])) {
#           $records[] = $res['error'];
#       } else {
#           $records[] = $res['errors'];
#       }
#   }
#}

#$records = array();
#$records[] = "PHUMN0001_123456_V01";
#$records[1] = "M_2022-01-01_010M";

#print(json_encode(array_values(array_unique($records))));
#echo $results;
#print($result);

?>
