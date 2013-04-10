<?php

/**
 * QKR-8105
 * 
 * This script intends to remove the deleted alerts as a result of 
 * 'interface/alert/RemoveDuplicateAndCollapseAlerts.php' script which publishes 
 * such alerts in MQ.
 * This script consumes those alerts at regular intervals and deletes in RT Solr
 *
 */
include("indexing_config.php");
if(isset($argv[1])) {
    $consumer = new Rabbitmq_Consumer("reporting_alerts","reportingtool_exchange",trim($argv[1]));
} else {
    $consumer = new Rabbitmq_Consumer("reporting_alerts", "reportingtool_exchange");
}


while (true) {

    if (($message = $consumer->get()) && $message ['count'] >= 0) {
        $a = explode("|", base64_decode($message ['msg']));
        if (!empty($a)) {
            
//            $writer = new Zend_Log_Writer_Stream(INDEXING_LOG."/".date("d-m-Y_H")."_RMQ_alert_before_processing.text");
//            $logger = new Zend_Log($writer);
//            $logger->log($a[1],Zend_Log::INFO);

            $objSolr = new Quikr_SolrIndex();
            $objSolr->setIndexingUrl(SOLR_ALERTS_INDEXING_URL);

            $objSolr->setIdsToDelete($a[1]);
            $objSolr->coreName = "alerts";
            $objSolr->delete();
            
        }
    } else {
        sleep(1);
    }
    
}