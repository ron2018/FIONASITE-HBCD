<?php

$project = "";
if (isset($_GET['project'])) {
   $project = $_GET['project'];
}
if ($project == "HBCD") {
   $project = "";
}


$suid = "";
if (isset($_GET['suid'])) {
   $suid = $_GET['suid'];
}



$records[] = "PHUMN0001_123456_V01";
$records[1] = "M_2022-01-01_010M";

print(json_encode(array_values(array_unique($records))));

?>
