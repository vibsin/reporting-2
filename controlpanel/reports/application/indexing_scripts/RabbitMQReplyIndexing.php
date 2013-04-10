<?php
/**
 * This script can be used to index reply which come from system generated.(e.g. yellow Page) 
 * It can be used on ad-hoc basis but must come from a queue
 */

include("indexing_config.php");
$consumer = new Rabbitmq_Consumer("reporting_reply","reportingtool_exchange");
$writer = new Zend_Log_Writer_Stream(INDEXING_LOG."/".date("d-m-Y")."_RMQ_reply.text");
$logger = new Zend_Log($writer);

$replyCounter = 0;
define("LIMIT",1000);

while(true) {        
    if (($message = $consumer->get ()) && $message ['count'] >= 0) {
        $replyCounter++;
        $a = unserialize(base64_decode($message ['msg']));
        
        $replyIds[] = $a["repId"];
	unset($a);
        //echo $a["repId"]."\n";
        if($replyCounter == LIMIT) {
            index($replyIds);
            unset($replyIds);
            $replyCounter = 0;
        } 
        
    } else {
        sleep(1);
    }
}


function index($replyIds) {
    $uReplyIds = array_unique($replyIds);
    $str = implode(",", $uReplyIds);
    $shellStr =  PHP_EXECUTABLE_PATH.' '.APPLICATION_PATH.'/indexing_scripts/Replyindexing.php REPLYID '.$str.' '.count($uReplyIds);
    shell_exec($shellStr);
    
    global $logger;
    $logger->log($shellStr,Zend_Log::INFO);
    $logger->log($str, Zend_Log::INFO);
}