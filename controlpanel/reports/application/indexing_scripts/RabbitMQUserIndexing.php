<?php
/**
 * This script can be used to index ads which come from system generated.(e.g. yellow Page) 
 * It can be used on ad-hoc basis but must come from a queue
 */

include("indexing_config.php");
if(isset($argv[1])) {
    $consumer = new Rabbitmq_Consumer("reporting_users","usertosolr_x",trim($argv[1]));
} else {
    $consumer = new Rabbitmq_Consumer("reporting_users","usertosolr_x");
}
$userId = array();
$i = 1;
while(true) {        
    if (($message = $consumer->get ()) && $message ['count'] >= 0) {
        $i++;
        //echo "\n".unserialize(base64_decode($message ['msg']));
        $userId[] = unserialize(base64_decode($message ['msg']));
        //echo "\n".$i;
        if($i == 100) {
            
            $uniqueU = array_unique($userId);
            $count = count($uniqueU);
            //echo $count;
            $str = trim(implode(",", $uniqueU),",");
            $shellStr =  PHP_EXECUTABLE_PATH.' '.APPLICATION_PATH.'/indexing_scripts/Userindexing.php RUNTIME '.$str.' '.$count;  
            //echo $shellStr;
            shell_exec($shellStr);
            unset($userId);unset($count);unset($str);unset($shellStr);
            $i = 1;
        }
    } else {
        sleep(1);
    }
}