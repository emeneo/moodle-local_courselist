<?php
require_once(dirname(__FILE__) . '/../../config.php');

$data = optional_param('d', '', PARAM_TEXT);
$row = json_decode(base64_decode($data),true);

header('Content-Type: '.$row['mime']);
$file = substr($row['hash'],0,2)."/".substr($row['hash'],2,2)."/".$row['hash'];
echo file_get_contents($CFG->dataroot."/filedir/".$file);