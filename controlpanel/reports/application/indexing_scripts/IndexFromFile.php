<?php

include("indexing_config.php");

$fileLocation = "/home/quikr/Documents/Reporting/runtime/MISSING_ADS.csv";

if (($handle = fopen($fileLocation, "r")) !== FALSE) {
    $num = array();
    $counter = 0;
    while (($data = fgetcsv($handle)) !== FALSE) {
        $num[] = $data[0];
        if(count($num) == 1000) {
            $counter += 1000;
            $str = trim(implode(",", $num),",");
            $shellStr =  PHP_EXECUTABLE_PATH.' '.APPLICATION_PATH.'/indexing_scripts/DeletedAdsindexing.php ADID '.$str.' 1000';  
            shell_exec($shellStr);
            echo "\n Data indexed till ".$counter;
            unset($num);unset($str);unset($shellStr);
            $num = array();
        }
    }
}