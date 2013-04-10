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
        foreach(self::$data as $key => $val) {
            
            $smbDetails = $this->getSmbDetails($val['email']);
            self::$dataToIndex[self::$counter]['id'] = trim($val['id']); 
            self::$dataToIndex[self::$counter]['lead_type'] = $this->parseItemType(trim($val['item_type'])); //done Reply/Lead
            self::$dataToIndex[self::$counter]['lead_id'] = trim($val['item_id']); //done reply id/ad id
            self::$dataToIndex[self::$counter]['lead_date'] = $this->convertToUTC(trim($val['created_time'])); //done
            self::$dataToIndex[self::$counter]['lead_poster_email'] = trim($val['email']); //BGS user email
            self::$dataToIndex[self::$counter]['lead_poster_id'] = trim($val['user_id']); //BGS user id
            self::$dataToIndex[self::$counter]['lead_poster_mobile_s'] = $smbDetails['mobile']; //BGS user id
            self::$dataToIndex[self::$counter]['lead_response_id'] = trim($val['action_id']); //if lead type is reply then ad else alert
            self::$dataToIndex[self::$counter]['is_star'] = (trim($val['is_star']) == "1") ? "Yes" : "No"; //done
            self::$dataToIndex[self::$counter]['is_read'] = (trim($val['is_read']) == "1") ? "Yes" : "No"; //done
            self::$dataToIndex[self::$counter]['is_called'] = (trim($val['is_called']) == "1") ? "Yes" : "No"; //done
            self::$dataToIndex[self::$counter]['is_smsed'] = (trim($val['is_smsed']) == "1") ? "Yes" : "No"; //done
            self::$dataToIndex[self::$counter]['is_replied'] = (trim($val['is_replied']) == "1") ? "Yes" : "No"; //done
            self::$dataToIndex[self::$counter]['app_version'] = $smbDetails["version"]; //done
            
            
            
            if(trim($val['item_type']) == "1") { //reply
                //fetch ad data
                $objAds = new Model_AdsSolr(array());
                $adsData= $objAds->getSingleFieldFromAds('*', $val['action_id']);
                $adsArr = array();

                    if(!empty($adsData) && $adsData->response->numFound > 0) {
                        $adStories = $adsData->response->docs;
                        foreach ($adStories as $story) {
                            foreach ($story as $k => $v) {
                                $name = $k;
                                $value = $v;
                                $adsArr[$name] = $value;
                            }
                        }

                    self::$dataToIndex[self::$counter]['lead_response_city_id'] = trim($adsArr['city_id']); //done
                    self::$dataToIndex[self::$counter]['lead_response_city_name'] = trim($adsArr['city_name']); //done
                    self::$dataToIndex[self::$counter]['lead_response_category_id'] = trim($adsArr['metacategory_id']); //done
                    self::$dataToIndex[self::$counter]['lead_response_category_name'] = trim($adsArr['metacategory_name']); //done
                    self::$dataToIndex[self::$counter]['lead_response_subcategory_id'] = trim($adsArr['subcategory_id']); //done
                    self::$dataToIndex[self::$counter]['lead_response_subcategory_name'] = substr_replace(trim($adsArr['subcategory_name']),'',0,strpos(trim($adsArr['subcategory_name']), ',')+1); //done
                    if($adsArr['free_premium_type'] == "Free") {
                        self::$dataToIndex[self::$counter]['lead_response_style'] = trim($adsArr['free_premium_type']); //done
                    } else {
                        self::$dataToIndex[self::$counter]['lead_response_style'] = trim($adsArr['premium_ad_type']); //done
                    }
                    self::$dataToIndex[self::$counter]['lead_response_global_metacategory_id'] = trim($adsArr['global_metacategory_id']); //done
                    self::$dataToIndex[self::$counter]['lead_response_global_subcategory_id'] = trim($adsArr['global_subcategory_id']); //done
                    
                    self::$dataToIndex[self::$counter]['lead_response_title_t'] = trim($adsArr['ad_title']); //done
                    self::$dataToIndex[self::$counter]['lead_response_desc_t'] = trim($adsArr['ad_description']); //done
                    //self::$dataToIndex[self::$counter]['lead_response_ad_mobile_s'] = trim($adsArr['poster_mobile']); //done
                }
                //fetch replier email
                $objReply = new Model_ReplySolr(array());
                $replyData= $objReply->getSingleFieldFromReply('rpl_email,rpl_mobile,rpl_content', $val['item_id']);
                $replyArr = array();

                    if(!empty($replyData) && $replyData->response->numFound > 0) {
                        $replyStories = $replyData->response->docs;
                        foreach ($replyStories as $story) {
                            foreach ($story as $k => $v) {
                                $name = $k;
                                $value = $v;
                                $replyArr[$name] = $value;
                            }
                        }

                    self::$dataToIndex[self::$counter]['lead_response_email'] = $replyArr["rpl_email"]; 
                    self::$dataToIndex[self::$counter]['lead_response_mobile_s'] = $replyArr["rpl_mobile"]; 
                    //self::$dataToIndex[self::$counter]['lead_response_reply_mobile_s'] = $replyArr["rpl_mobile"]; 
                    self::$dataToIndex[self::$counter]['lead_response_desc_t'] = $replyArr["rpl_content"]; 
                }
                
                
                //premium ads
            $objPrem = new Model_PremiumAdsSolr(array());
            $premData = $objPrem->getSingleFieldFromPremiumAds($val['action_id'], '*');
            $premArr = array();

            if (!empty($premData) && $premData->response->numFound > 0) {
                $premStories = $premData->response->docs;
                foreach ($premStories as $story) {
                    foreach ($story as $k => $v) {
                        $name = $k;
                        $value = $v;
                        $premArr[$name] = $value;
                    }
                }

                if(isset($premArr['premiumads_pack_id']) && !empty($premArr['premiumads_pack_id']))  {
                	self::$dataToIndex[self::$counter]['pack_id'] = trim($premArr['premiumads_pack_id']); //done
		}
		
		if(isset($premArr['premiumads_vd_status']) && !empty($premArr['premiumads_vd_status']))  {
                	self::$dataToIndex[self::$counter]['pack_status'] = trim($premArr['premiumads_vd_status']); //done
		}
                if(isset($premArr['premiumads_vd_ro_name']) && !empty($premArr['premiumads_vd_ro_name']))  {
                    self::$dataToIndex[self::$counter]['ro_name_s'] = trim($premArr['premiumads_vd_ro_name']); //done
		}
                if(isset($premArr['premiumads_order_id']) && !empty($premArr['premiumads_order_id']))  {
                    self::$dataToIndex[self::$counter]['pack_order_id_s'] = trim($premArr['premiumads_order_id']); //done
		}
                if(isset($premArr['premiumads_vdu_area_id']) && !empty($premArr['premiumads_vdu_area_id']))  {
                    self::$dataToIndex[self::$counter]['pack_city_id_s'] = trim($premArr['premiumads_vdu_area_id']); //done
                    self::$dataToIndex[self::$counter]['pack_city_name_s'] = $this->getCityName($premArr['premiumads_vdu_area_id']); //done
		}
                
                
            }
                
                
               
                
            } elseif (trim($val['item_type']) == "2") { //lead
                // fetch alert data
                
                //now we take mobile alert data

                $objAlert = new Model_AlertsSolr(array());
                $objAlert->solrUrl="http://172.16.1.51:8983/solr/mobilealert/";
                $alertData= $objAlert->getSingleFieldFromAlert($val['action_id'],'*');


                if(!empty($alertData) && $alertData->response->numFound > 0) {
                    $alertStories = $alertData->response->docs;
                    foreach ($alertStories as $story) {
                        foreach ($story as $k => $v) {
                            if($k == "subcatid") {
                                $num = extract($this->getGlobalData($v));
                                if($num == 0 || $v == 0) { //error
                                    $writer = new Zend_Log_Writer_Stream(INDEXING_LOG."/".date("d-m-Y_H")."_BGS_alert_no_cat.text");
                                    $logger = new Zend_Log($writer);
                                    $logger->log($val['action_id'],Zend_Log::INFO);
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
                            //mail
                            if($k=="userinfo") {
                                preg_match('/mobile\:(\d+)/', $value, $match); 
                                self::$dataToIndex[self::$counter]['lead_response_mobile_s'] = $match[1]; //done
                            }

                        }
                    }
                    
                    
                    //premium alert data from DB
                    $premAlert = $this->getPremiumAlertDetails($val['action_id']);
                    if(is_array($premAlert)) {
                        
                        self::$dataToIndex[self::$counter]['pack_id'] = trim($premAlert['pack_id']); //done
                        self::$dataToIndex[self::$counter]['pack_status'] = $this->parseVduStatus(trim($premAlert['pack_status'])); //done
                        self::$dataToIndex[self::$counter]['ro_name_s'] = trim($premAlert['ro_name']); //done
                        self::$dataToIndex[self::$counter]['pack_order_id_s'] = trim($premAlert['order_id']); //done
                        self::$dataToIndex[self::$counter]['pack_city_id_s'] = $this->getCityName(trim($premAlert['pack_city_id'])); //done
		
                    }
                    
                    

                    $objAds = new Model_AdsSolr(array());
                    $adsData = $objAds->getSingleFieldFromAds('ad_title,ad_description,poster_email,poster_mobile,free_premium_type,premium_ad_type', $val['item_id']);
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
                        //self::$dataToIndex[self::$counter]['lead_response_mobile_s'] = $adsArr["poster_mobile"];
                        self::$dataToIndex[self::$counter]['lead_response_title_t'] = trim($adsArr['ad_title']); //done
                        self::$dataToIndex[self::$counter]['lead_response_desc_t'] = trim($adsArr['ad_description']); //done
                        //self::$dataToIndex[self::$counter]['lead_response_ad_mobile_s'] = trim($adsArr['poster_mobile']); //done
                        
                        if($adsArr['free_premium_type'] == "Free") {
                            self::$dataToIndex[self::$counter]['lead_response_style'] = trim($adsArr['free_premium_type']); //done
                        } else {
                            self::$dataToIndex[self::$counter]['lead_response_style'] = trim($adsArr['premium_ad_type']); //done
                        }
                    }
                    
                    
                }
            }
            
            
            
            
            
            //meta
            self::$dataToIndex[self::$counter]['data_indexed_time_tdt'] = $this->convertToUTC(time()); //this is dynamic field in Solr
            //print_r(self::$dataToIndex[self::$counter]);
            //exit;
            
            self::$counter++;
            
        }
    }
    
    private function getPremiumAlertDetails($productId) {
        $sql = "SELECT bvdu.status as \"pack_status\",
                        bpo.orderid as \"order_id\", 
                        bpo.packid as \"pack_id\",
                        bvd.ro_name as \"ro_name\",
                        bvdu.areaid as \"pack_city_id\" 
                FROM 
                    babel_product_order AS bpo, babel_volume_discount_user AS bvdu, babel_volume_discount AS bvd  
                WHERE 
                    bpo.producttype=\"intMobileAlert\" AND 
                    bpo.productid=".$productId." AND 
                    bpo.packid = bvdu.id AND 
                    bvd.id=bvdu.volume_discount_id";
        $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
        $objStmt->execute();
        $items = $objStmt->fetch();
        if (!empty($items)) {
            return $items;
        } 
        return false;; 
    }
    
    private function parseItemType($type) {
        if($type == "1") return "Reply"; else return "Lead"; 
    }
    
    private function getSmbDetails($email) {

        echo $sql = 'select * from smb_details where email = "' . $email . '" LIMIT 1';
        $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
        $objStmt->execute();
        $items = $objStmt->fetch();

        if (!empty($items)) {
            return $items;
        } 
        return "NA";
    }
    
    protected function parseVduStatus($state) {
            if($state == '' || $state == null) {
                    return '';
            } else {
                switch(trim($state)) {
                    case "1":
                            return "Enabled";
                            break;

                    case "0":
                            return "Disabled";
                            break;

                    default:
                            return $state;
                            break;
                }
            }
    }
    
    
    
}

//$str = "Real Estate,Land - Plot For Sale";
//echo $pos = strpos(trim($str),",")+1;
//echo substr_replace(trim($str),'',0,strpos(trim($str),",")+1); exit;
//get command line arguments
$args = $argv;

//start indexing from here:
$objIndexing = new Bgsindexing();
$objIndexing->init($args);
