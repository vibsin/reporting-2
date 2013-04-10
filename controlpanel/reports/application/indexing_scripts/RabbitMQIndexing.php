<?php
/**
 * This script can be used to index ads which come from system generated.(e.g. yellow Page) 
 * It can be used on ad-hoc basis but must come from a queue
 */

include("indexing_config.php");
if(isset($argv[1])) {
    $consumer = new Rabbitmq_Consumer("reporting_ads","adtosolr_x",trim($argv[1]));
} else {
    $consumer = new Rabbitmq_Consumer("reporting_ads","adtosolr_x");
}
while(true) {        
    if (($message = $consumer->get ()) && $message ['count'] >= 0) {
        $adId = unserialize(base64_decode($message ['msg']));
        $shellStr =  PHP_EXECUTABLE_PATH.' '.APPLICATION_PATH.'/indexing_scripts/Adsindexing.php RUNTIME '.$adId.' 1';  
        shell_exec($shellStr);
    } else {
        sleep(1);
    }
}