<?php

include('IndexingAbstract.php');

class Bgsindexing extends IndexingAbstract {
    
    protected $dbTableName = 'smb_notifications';
    protected $section = 'bgs'; 
    protected $indexingUrl = SOLR_BGS_INDEXING_URL;
    protected $isIncrementalIndexing = false;
    protected $isCalledFromOtherScript = false;
    
    
    public function  __destruct() {
        parent::__destruct();
    }
    
    protected function initiateUpdatingOfDepenedantCores() {
        return;
    }
    
    public function init($args) {
        $this->isFetchFromSolrEnabled = true;
        if(!empty($args)) {
            $this->commandArgs = $args;
            $runIndexingFor=$args[1];
            if(isset($args[2])) {
                $runInterval = $args[2];
            }

            switch($runIndexingFor) {
                
                
                case 'ALL':
                    $this->sql = 'SELECT * FROM '.$this->dbTableName;
                    $this->countSql = 'SELECT count(id) as "count" FROM '.$this->dbTableName;
                break;
            
            
                case "NEWEST":
                    $past = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j")-$runInterval,date("Y"))));
                    $now = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j"),date("Y"))));
                    $this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE created_time BETWEEN '.$past.' AND '.$now;
                    $this->countSql = 'SELECT count(id) as "count" FROM '.$this->dbTableName.' WHERE created_time BETWEEN '.$past.' AND '.$now;
                    $this->isMasterIndexingScript = true;
                    break;
                
                 case "ID":
                    
                    $this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE id IN ('.$args[2].')';
                    $this->countSql = '';
                    $this->thresholdForIncremental = $args[3];
                    //$this->isMasterIndexingScript = true;
                    $this->isCalledFromOtherScript = true;
                    break;
                
                case "DATE_RANGE":
                    
                    $this->isIncrementalIndexing = false;
                    $past = strtotime($args[2]);
                    $now = strtotime($args[3]);
                    
                    $this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE created_time BETWEEN '.$past.' AND '.$now;
                    $this->countSql = 'SELECT count(id) as "count" FROM '.$this->dbTableName.' WHERE created_time BETWEEN '.$past.' AND '.$now;
                    
                    break;

                case 'DROP':
                    $this->dropsearchindexesAction();
                break;
                default:
                    echo "NOTHING TO DO!";
                break;
            }
            //set the threshhold
            if($this->isCalledFromOtherScript) self::$threshold = $this->thresholdForIncremental;
            else self::$threshold = $this->getMaxRecordsFromDB();
                    
            
            $this->indexAction();
        } else {
            echo 'Please enter valid arguments'; die();
        }
    }
    
    protected function initBuildingData() {
        
        foreach(self::$data as $val) {
            //print_r($val);exit;
            //echo "\n".$val["id"];
            //if($val['lead_type'] == "Lead") {//alert
                
                //first we fill bgs data
                
                $objBgs = new Model_BgsSolr(array());
                $bgsData= $objBgs->getFieldsFromBgs('id,lead_type,lead_id,lead_date,lead_poster_email,lead_poster_id,lead_response_id,is_star,is_read,is_called,is_smsed,is_replied,app_version', $val['id']);
   
                if(!empty($bgsData) && $bgsData->response->numFound > 0) {
                    $bgsStories = $bgsData->response->docs;
                    foreach ($bgsStories as $story) {
                        foreach ($story as $k => $v) {
                            $name = $k; 
                            $value = $v; 
                            self::$dataToIndex[self::$counter][$name] = $value;
                        }
                    }
                
                    //now we take mobile alert data

                    $objAlert = new Model_AlertsSolr(array());
                    $objAlert->solrUrl="http://192.168.2.7:8983/solr/mobilealert/";
                    $alertData= $objAlert->getSingleFieldFromAlert(self::$dataToIndex[self::$counter]['lead_response_id'],'*');
                   

                    if(!empty($alertData) && $alertData->response->numFound > 0) {
                        $alertStories = $alertData->response->docs;
                        foreach ($alertStories as $story) {
                            foreach ($story as $k => $v) {
                                if($k == "subcatid") {
                                    $num = extract($this->getGlobalData($v));
                                    if($num == 0 || $v == 0) { //error
                                        $writer = new Zend_Log_Writer_Stream(INDEXING_LOG."/".date("d-m-Y_H")."_BGS_alert_no_cat.text");
                                        $logger = new Zend_Log($writer);
                                        $logger->log(self::$dataToIndex[self::$counter]['lead_response_id'],Zend_Log::INFO);
                                    } else {
                                        self::$dataToIndex[self::$counter]['lead_response_category_id'] = $metacatId;
                                        self::$dataToIndex[self::$counter]['lead_response_category_name'] =  $metacatName;
                                        self::$dataToIndex[self::$counter]['lead_response_subcategory_id'] = $subcatId;
                                        self::$dataToIndex[self::$counter]['lead_response_subcategory_name'] = $subcatName; 
                                        self::$dataToIndex[self::$counter]['lead_response_global_metacategory_id'] = $globalMetacatId;
                                        self::$dataToIndex[self::$counter]['lead_response_global_subcategory_id'] = $globalSubcatId;

                                    }  
                                }
                                
                                if($k=="category") {
                                    preg_match('/ct:(\d+)/',$v,$match);
                                    self::$dataToIndex[self::$counter]['lead_response_city_id'] = $match[1]; //done
                                    self::$dataToIndex[self::$counter]['lead_response_city_name'] = $this->getCityName($match[1]); //done
                                        
                                }
                                
                            }
                        }


                        $objAds = new Model_AdsSolr(array());
                        $adsData = $objAds->getSingleFieldFromAds('poster_email,free_premium_type,premium_ad_type', self::$dataToIndex[self::$counter]['lead_id']);
                        $adsArr = array();

                        if (!empty($adsData) && $adsData->response->numFound > 0) {
                            $adStories = $adsData->response->docs;
                            foreach ($adStories as $story) {
                                foreach ($story as $k => $v) {
                                    $name = $k;
                                    $value = $v;
                                    $adsArr[$name] = $value;
                                }
                            }
                            self::$dataToIndex[self::$counter]['lead_response_email'] = $adsArr["poster_email"];
                            
                            if($adsArr['free_premium_type'] == "Free") {
                                self::$dataToIndex[self::$counter]['lead_response_style'] = trim($adsArr['free_premium_type']); //done
                            } else {
                                self::$dataToIndex[self::$counter]['lead_response_style'] = trim($adsArr['premium_ad_type']); //done
                            }
                        }
                    }
                    self::$dataToIndex[self::$counter]['data_indexed_time_tdt'] = $this->convertToUTC(time()); //this is dynamic field in Solr
                    
                    
                }
                self::$counter++;
            //} 
            //print_r(self::$dataToIndex);
            
        }
        //print_r(self::$dataToIndex[self::$counter]);
    }
    
   
    
    
}

$args = $argv;

//start indexing from here:
$objIndexing = new Bgsindexing();
$objIndexing->init($args);
