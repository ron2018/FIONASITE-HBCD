<?php
$suid = "";
$tripleId = "";
$redcap_event_name = "";
$run = "";
$log = '/var/www/html/server/logs/packRawData.log';
$suidlists = '/data/site/rawdata/suidlists.csv';
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

if (!file_exists($log)) {
   // try to create empty log file
   file_put_contents($log, "");
}

if (!file_exists($suidlists)) {
   // try to create empty log file
   file_put_contents($suidlists, "");
}

if (isset($_GET['suid'])) {
    $suid = $_GET['suid'];
} else {
    echo ("{ \"ok\": 0, \"message\": \"suid not set\" }");
    return;
}
if (isset($_GET['tripleId']) && $_GET['tripleId'] != "") {
    $tripleId = $_GET['tripleId'];
} else {
    echo ("{ \"ok\": 0, \"message\": \"tripleId not set\" }");
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

file_put_contents($log, date(DATE_ATOM)." Pack this suid(s) to UCSD:  " .$suid." \n", FILE_APPEND);
file_put_contents($log, date(DATE_ATOM)." Check if we need to modify the dicom  " . $modify_participant_name . " with Sex: ".$sex. "  and Age :".$age." \n", FILE_APPEND);
echo (" Pack this suid(s) to UCSD:  " .$suid);


file_put_contents($suidlists, $tripleId.",".$suid.",".$run."\n", FILE_APPEND);

echo ($rawSessionLists);

echo ("File is added to rawSessionLists");

return;

?>
