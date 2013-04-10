<?php

class Quikr_SolrIndex {

    public $dataToIndex;
    public $indexingUrl;
    private $idsToDelete;
    public $coreName;
    public $solrPostReponse;
    
    /**
     * To distinguish between various post request. Set this in indexing script to notify is 
     * Commit is to be done
     * @var type 
     */
    public $isCommitFlagRaised = false;
    
    /**
     * To set whether the commit is called for commiting the main request .e.g. only ads
     * @var type 
     */
    public $isCommitForMasterRequest = false;
    //premium ads integer/string problem resolution.
    public static $intFields = array("premiumads_ad_order_created_date",
	"premiumads_ad_order_updated_date",
	"premiumads_ad_order_activated_date",
	"premiumads_ad_order_accounting_date",
	"premiumads_ad_order_refund_accounting_date",
	"premiumads_ad_order_expiry_date",
	"premiumads_attempts",
	"premiumads_amount",
	"premiumads_net_amount",
	"premiumads_tax",
	"premiumads_refund_amount",
	"premiumads_refund_reason",
	"premiumads_refund_date",
	"premiumads_remark",
	"premiumads_user_billing_address",
	"premiumads_pack_id",
	"premiumads_parent_pack_id",
	"premiumads_auto_renew_on_date",
	"premiumads_auto_renew_off_date",
	"premiumads_order_convert_to_free_date",
	"premiumads_ad_order_delete_date",
	"premiumads_order_smb",
	"premiumads_actual_accounting",
	"premiumads_actual_refund_accounting",
	"premiumads_ad_id",
	"premiumads_ad_title",
	"premiumads_ad_description",
	"premiumads_reply_count",
	"premiumads_city_id",
	"premiumads_metacategory_id",
	"premiumads_global_metacategory_id",
	"premiumads_subcategory_id",
	"premiumads_global_subcategory_id",
	"premiumads_visitor_count",
	"premiumads_ad_expiry_date",
	"premiumads_ad_first_created_date",
	"premiumads_ad_localities",
	"premiumads_ad_locality",
	"premiumads_user_id",
	"premiumads_vd_id",
	"premiumads_vd_category",
	"premiumads_vd_discount",
	"premiumads_vd_amount",
	"premiumads_vd_total_credit",
	"premiumads_vd_validity",
	"premiumads_usedcredits_pack_activated_date",
	"premiumads_vdu_id",
	"premiumads_vdu_vd_id",
	"premiumads_vdu_uid_id",
	"premiumads_vdu_remaining_credit",
	"premiumads_vdu_current_credits_used",
	"premiumads_vdu_current_credits_remaining",
	"premiumads_pausestarttime",
	"premiumads_zerocredit_status_date",
	"premiumads_vdu_total_credits_used",
	"premiumads_vdu_created_date",
	"premiumads_vdu_expiry_date",
	"premiumads_vdu_last_updated_date",
	"premiumads_vdu_area_id",
	"data_indexed_time");
    
    public function  __construct($dataToIndex='') {
        if(isset ($dataToIndex)) {
            $this->dataToIndex = $dataToIndex;
        }
    }

    public function setIndexingUrl($url) {
        $this->indexingUrl = $url;
        //echo $this->indexingUrl; exit;
    }

    public function init() {

        $xmlString = $this->createDOMString();
        //print_r($xmlString); exit;
        //first check if solr is running
//        if($this->checkSolr()) {
            $status = $this->post($xmlString);
            //$this->commit();
            return $status;
 //       }

        
    }
    
    protected function cleanHtml($input) {
        //$input= preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F\0xa4\0xa0\0xcb]/', '', $input);
        $str = preg_replace( array('/\x00/', '/\x01/', '/\x02/', '/\x03/', '/\x04/', '/\x05/', '/\x06/', '/\x07/', '/\x08/', '/\x09/', '/\x0A/', '/\x0B/','/\x0C/','/\x0D/', '/\x0E/', '/\x0F/', '/\x10/', '/\x11/', '/\x12/','/\x13/','/\x14/','/\x15/', '/\x16/', '/\x17/', '/\x18/', '/\x19/','/\x1A/','/\x1B/','/\x1C/','/\x1D/', '/\x1E/', '/\x1F/'), array(""), $input);
        return strip_tags(utf8_encode($str));
    }
    

    public function createDOMString() {
        $dom = new DomDocument('1.0','UTF-8');
        $rootElement = $dom->createElement('add');
        $root = $dom->appendChild($rootElement);
        
        //print_r($this->dataToIndex); exit;

        foreach ($this->dataToIndex as $k=>$v){
            $docElement = $dom->createElement('doc');
            $doc = $root->appendChild($docElement);
            foreach ($v as $key=>$x){

                $val = trim($x);
                //for premium ads only.
                if(in_array($key, self::$intFields) && empty($val)){
			continue;
		}
                //if (!is_null($val) && strlen($val) > 0 ) {
                        $element = $dom->createElement('field');
                        $element->setAttribute('name', $key);
                        $elementFinal = $dom->createTextNode($this->cleanHtml(trim($val)));
                        $element->appendChild($elementFinal);
                        $doc->appendChild($element);
                //}
            }
        }

        $str = $dom->saveXML();
        unset($dom);
        //echo $str;
        return $str;
    }


    public function post($domString) {
    	
        //echo SOLR_INDEXING_URL; exit;
        //echo ' posting to solr now';
        $adapter = new Zend_Http_Client_Adapter_Curl();
        $client = new Zend_Http_Client();
        $client->setAdapter($adapter);
        $client->setConfig(array('timeout' => 300));
        $client->setUri($this->indexingUrl.'update');
        $client->setMethod(Zend_Http_Client::POST);
        $client->setHeaders('Content-Type','text/xml; charset=utf-8');
        $client->setHeaders('Content-Length',strlen($domString));
        $client->setRawData($domString, 'text/xml');
        try {
            $responseXml = $client->request()->getBody();
            $this->solrPostReponse = $responseXml;
            $adapter->close();
            //if error the reponse is
            /*
             * <response>
                <lst name="responseHeader">
             *      <int name="status">400</int>
             *      <int name="QTime">1</int>
             *  </lst>
                * <lst name="error">
                *   <str name="msg">ERROR: [doc=9171805] Error adding field 'no_of_alerts'='' msg=For input string: ""</str>
                *   <int name="code">400</int>
                * </lst>
                </response>
             * 
             */
            
            
            $resConfig = simplexml_load_string($responseXml);
            if(!empty($resConfig->lst[1])) {
                    $errorStr =  (string) $resConfig->lst[1]->str;  //fetch the error and send mail with core name
                    $subject = "[Reporting Tool] - ".  APPLICATION_ENV." - Error response from Solr - ".$this->coreName;
                    $message = array();
                    $message[] = "Indexing Url: ".$this->indexingUrl.'update';
                    $message[] = "Solr Message: ".$errorStr;
                    $message[] = "Script name:".$_SERVER["SCRIPT_NAME"];
                    $message[] = "Indexing Time:".date("D,d-M-Y_H:i:s");
                    

                    //mail
                    $mail = new Zend_Mail();
                    $at1 = $mail->createAttachment($domString);
                    $at1->type        = 'text/xml';
                    $at1->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
                    $at1->encoding    = Zend_Mime::TYPE_TEXT;
                    $at1->charset     = "UTF-8";
                    $at1->filename    = "Solr_Indexing_Error_".date('d-M-Y_H:i:s')."_".$this->coreName.".xml";

                    $mail->setBodyHtml(implode($message,"<br />"));
                    $mail->setFrom('vsingh@quikr.com', 'System');
                    $mail->addTo('vsingh@quikr.com', 'Vibhor');
                    $mail->setSubject($subject);
                    $mail->send();
                    
                    $writer = new Zend_Log_Writer_Stream(INDEXING_LOG."/Solr_Indexing_Error_".date('d-M-Y_H:i:s')."_".$this->coreName.".html");
                    $logger = new Zend_Log($writer);
                    $logger->info("<br />".implode($message,"<br />"));
        
            }

        } catch (Exception $e) {
            $subject = "[Reporting Tool] - ".  APPLICATION_ENV." - Solr Exception thrown while indexing - ".$this->coreName;
            $message = array();
            $message[] = "Indexing Url: ".$this->indexingUrl.'update';
            $message[] = "Exception thrown: ".$e->getMessage();
            $message[] = "Headers returned:".$client->request()->getHeadersAsString();
            $message[] = "Script name:".$_SERVER["SCRIPT_NAME"];
            $message[] = "Indexing Time:".date("D,d-M-Y_H:i:s");
            $message[] = "Stack Trace:<pre>".$e->getTraceAsString()."</pre>";
            
            //mail
            $mail = new Zend_Mail();
            $at1 = $mail->createAttachment($domString);
            $at1->type        = 'text/xml';
            $at1->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
            $at1->encoding    = Zend_Mime::TYPE_TEXT;
            $at1->charset     = "UTF-8";
            $at1->filename    = "Solr_Exception_Failed_XML_".date('d-M-Y_H:i:s')."_".$this->coreName.".xml";
            
            $mail->setBodyHtml(implode($message,"<br />"));
            $mail->setFrom('vsingh@quikr.com', 'System');
            $mail->addTo('vsingh@quikr.com', 'Vibhor');
            
            //mail will only be sent to others if commit was called & if it was a master script
            if( APPLICATION_ENV == 'production') {
                $mail->addCc("ppatel@quikr.com", "Purvish Patel");
                //$mail->addTo('gishorek@quikr.com', 'Gishore Kallarackal');
                $mail->addTo('dsuryavanshi@quikr.com', 'Dinesh Suryavanshi');
                $mail->addCc("stiwari@quikr.com","Sudhir Tiwari");
                //$mail->addCc("sumeer@quikr.com","Sumeer Goyal");
                
                $subject .= " - Master script failed";
            } else $subject .= " - Slave script failed";
            
            
            $mail->setSubject($subject);
            $mail->send();
            
            //create physical files
            
            //email message
            $writer = new Zend_Log_Writer_Stream(INDEXING_LOG."/Solr_Failure_Mail_".date('d-M-Y_H:i:s')."_".$this->coreName.".html");
            $logger = new Zend_Log($writer);
            $logger->info("<br />".implode($message,"<br />"));
            
            //failed xml
            $writer2 = new Zend_Log_Writer_Stream(INDEXING_LOG."/Solr_Exception_Failed_XML_".date('d-M-Y_H:i:s')."_".$this->coreName.".xml");
            $logger2 = new Zend_Log($writer2);
            $logger2->info("<br />".$domString);
            
        }

    
        unset($client);
        //$status = $this->parseSolrResponse($responseXml);
        
        return true;

    }

    public function parseSolrResponse($response) {
//        $xml = simplexml_load_file($response);
//        print_r($xml);
//        exit;
        return true;
    }

    public function commit(){
            $comXml = "<commit/>";
            return $this->post($comXml);
    }
    
    public function dropIndex() {
        /**
         * For safety purpose comment this when on production
         */
    	//$status = $this->post('<delete><query>*:*</query></delete>');
        //$this->commit();
    }

    public function checkSolr() {
        try {
            $obj = new Utility_SolrQueryAnalyzer(SOLR_META_QUERY_BASE,__FILE__.' at line '.__LINE__);
            $data = $obj->init();
            if(!empty($data)) {
                return true;
            } else return false;
            
        } catch (Exception $e) {
            trigger_error($e->getMessage());
        }
        
    }
    
    /**
     * Options:
     * 
     *  maxSegments --Default is 1. Optimizes the index to include no more than this number of segments.

        waitFlush --Default is true. Blocks until index changes are flushed to disk.

        waitSearcher -- Default is true. Blocks until a new searcher is opened and registered as the main query searcher, making the changes visible.

        expungeDeletes -- Default is false. Merges segments and removes deleted documents.
     * @return type 
     */
    public function optimizeIndexes() {
        $comXml = '<optimize waitFlush="false" waitSearcher="false" expungeDeletes="true" />';
        return $this->post($comXml);
        
        
    }
    
    /**
     *This function will be used to delete records from solr cores
     * Must supply core name 
     * @return boolean 
     */
    public function delete() {
        //$c = count($this->idsToDelete);
        
        //if($c > 0 && is_array($this->idsToDelete) && !empty($this->coreName)) {
            
            $dom = new DomDocument('1.0','UTF-8');
            $rootElement = $dom->createElement('delete');
            $root = $dom->appendChild($rootElement);
           // for($i =0; $i < $c; $i++) {
                $idElement = $dom->createElement('id');
                $elementFinal = $dom->createTextNode($this->idsToDelete);
                $idElement->appendChild($elementFinal);
                $root->appendChild($idElement);
                
                //unset($idElement);unset($elementFinal);
            //}
            $domString = $dom->saveXML();
            
            
            $response = $this->post($domString);
            //echo $response;
            
            $resConfig = simplexml_load_string($response);
            if(!empty($resConfig->lst[1])) {
                    $errorStr =  (string) $resConfig->lst[1]->str;  //fetch the error and send mail with core name
                    $subject = "[Reporting Tool] - ".  APPLICATION_ENV." - Alerts delete - Error response from Solr while deleting- ".$this->coreName;
                    $message = array();
                    $message[] = "Indexing Url: ".$this->indexingUrl.'update';
                    $message[] = "Solr Message: ".$errorStr;
                    $message[] = "Script name:".$_SERVER["SCRIPT_NAME"];
                    $message[] = "Indexing Time:".date("D,d-M-Y_H:i:s");
                    

                    //mail
                    $mail = new Zend_Mail();
                    $at1 = $mail->createAttachment($domString);
                    $at1->type        = 'text/xml';
                    $at1->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
                    $at1->encoding    = Zend_Mime::TYPE_TEXT;
                    $at1->charset     = "UTF-8";
                    $at1->filename    = "Alerts_delete_Solr_Indexing_Error_".date('d-M-Y_H:i:s')."_".$this->coreName.".xml";

                    $mail->setBodyHtml(implode($message,"<br />"));
                    $mail->setFrom('vsingh@quikr.com', 'System');
                    $mail->addTo('vsingh@quikr.com', 'Vibhor');
                    if(APPLICATION_ENV == 'production') {
                        $mail->addCc("stiwari@quikr.com","Sudhir Tiwari");
                        $mail->addCc("sumeer@quikr.com","Sumeer Goyal");
                    }
                    $mail->setSubject($subject);
                    $mail->send();
                    
                    $writer = new Zend_Log_Writer_Stream(INDEXING_LOG."/Alerts_delete_Solr_Indexing_Error_".date('d-M-Y_H:i:s')."_".$this->coreName.".html");
                    $logger = new Zend_Log($writer);
                    $logger->info("<br />".implode($message,"<br />"));
        
            }
            
            
            //$this->commit();
            $log = $this->idsToDelete;
            $writer = new Zend_Log_Writer_Stream(INDEXING_LOG."/".date("d-m-Y_H")."_RMQ_".$this->coreName.".text");
            $logger = new Zend_Log($writer);
            $logger->log($log,Zend_Log::INFO);
            unset($this->idsToDelete);
        //} else return false;
    }
    
    public function setIdsToDelete($ids) {
        if(!empty($ids) && isset($ids)) {
            $this->idsToDelete = $ids;
        }
    }
    
    
    

}