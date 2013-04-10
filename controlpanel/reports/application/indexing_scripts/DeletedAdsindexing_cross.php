<?php
/**
 * log: 9-12-2011---> tpc_lastupdated index was replaced with remapped index, hence using remapped field in where clause
 */
include('IndexingAbstract.php');

class DeletedAdsindexing extends IndexingAbstract {


    protected $dbTableName = 'babel_topic_admin_deleted';
    protected $section = 'deleted_ads'; //used in the parent class
    protected $indexingUrl = SOLR_ADS_INDEXING_URL;

    public $allowed_attributes = null;
    protected $isIncrementalIndexing = false;
    protected $isCalledFromOtherScript = false;

    protected static $dusersToUpdate = array();
    protected static $dreplyWithAdsToUpdate = array();
    protected $thresholdForIncremental;

    public function  __destruct() {
        parent::__destruct();
    }




    public function init($args) {
        //echo strtotime('-5 minutes');
        //print_r($args); exit;
            $this->allowed_attributes = Zend_Registry::get('ALLOWED_ATTRIBUTES');

            if(!empty($args)) {
                $runIndexingFor=$args[1];
                if(isset($args[2])) {
                    $runInterval = $args[2];
                    $this->isIncrementalIndexing = true;
                }

                switch($runIndexingFor) {
                    case 'ALL':
                        $this->sql = 'SELECT * FROM '.$this->dbTableName;
                        $this->countSql = 'SELECT count(tpc_id ) as "count" FROM '.$this->dbTableName;
                    break;
                    case 'NEWEST':
                        $past = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j")-$runInterval,date("Y"))));
                        $now = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j"),date("Y"))));

                        $this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE remapped BETWEEN '.$past.' AND '.$now;
                        $this->countSql = 'SELECT count(tpc_id) as "count" FROM '.$this->dbTableName.' WHERE remapped BETWEEN '.$past.' AND '.$now;
                    break;

                     case 'ADID':
                        $adId = $args[2];
                        $this->isCalledFromOtherScript = true;
                        $this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE tpc_id IN ('.$adId.')';
                        $this->countSql = '';
                    $this->thresholdForIncremental = $args[3];

                    break;
                    
                    /** This will index data given in the below range. Please dont give very large gap between dates
                 * usage:
                 * /usr/local/php/bin/php DeletedAdsindexing.php DATE_RANGE FROM_DATE TO_DATE
                 * 
                 * FROM_DATE and TO_DATE should be of the format dd-mm-yyyy
                 * 
                 */
                case 'DATE_RANGE':
                    $this->isIncrementalIndexing = false;
                    $past = strtotime($args[2]);
                    $now = strtotime($args[3]);

                    $this->sql = 'SELECT tpc_id FROM '.$this->dbTableName.' WHERE remapped BETWEEN '.$past.' AND '.$now;
                    $this->countSql = 'SELECT count(tpc_id) as "count" FROM '.$this->dbTableName.' WHERE remapped BETWEEN '.$past.' AND '.$now;

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


    protected function getPosterDetails($userId) {
        $sql = 'SELECT usr_email,usr_mobile FROM babel_user WHERE usr_id = '.$userId;
        $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
        $objStmt->execute();
        $items = $objStmt->fetchAll();
        return $items[0];
    }

    protected function initBuildingData() {
        
        foreach(self::$data as $key => $val) {
            
            
            $objAds = new Model_AdsSolr(array());
            $adsData= $objAds->getSingleFieldFromAds('*', $val['tpc_id']);
   
            if(!empty($adsData) && $adsData->response->numFound > 0) {
                $adStories = $adsData->response->docs;
                foreach ($adStories as $story) {
                    foreach ($story as $k => $v) {
                        $name = $k; 
                        $value = $v; 
                        self::$dataToIndex[self::$counter][$name] = $value;
                    }
                }
                self::$dataToIndex[self::$counter]['no_of_replies'] = $this->getReplyCountForAd($val['tpc_id']);
                //when this got indexed
                self::$dataToIndex[self::$counter]['data_indexed_time'] = $this->convertToUTC(time()); //done
            }
            self::$counter++;
        }

    }


    protected function initiateUpdatingOfDepenedantCores() {
        if(!empty(self::$dusersToUpdate)) {
            $uniqueU = array_unique(self::$dusersToUpdate);
            $counter = 0;
            $str = '';
            $chunk = array();
            foreach($uniqueU as $key => $val) {
                if(!empty($val) && !is_null($val)) {
                    $str .= $val.',';
                    $counter++;
                    //$this->updateCountOfAdsForUser($val);
                }

                if(($counter % self::$limit) == 0) {
                    $fstr = trim($str, ',');
                    $chunk = explode(',', $fstr);
                    $this->updateCountOfAdsForUser($fstr, count($chunk));
                    $str = '';
                    $chunk = array();
                }
            }

            $fstr = trim($str, ',');
            $chunk = explode(',', $fstr);
            $this->updateCountOfAdsForUser($fstr,count($chunk));
        }

        if(!empty(self::$dreplyWithAdsToUpdate)) {
            $uniqueR = array_unique(self::$dreplyWithAdsToUpdate);
            $counter = 0;
            $str = '';
            $chunk = array();
            //print_r($uniqueR); exit;
            foreach($uniqueR as $key => $val) {
                if(!empty($val) && !is_null($val)) {
                    $str .= $val.',';
                    $counter++;
                    //$this->updateReplyWithAdsCore($val);
                }

                if(($counter % self::$limit) == 0) {
                    $fstr = trim($str, ',');
                    $chunk = explode(',', $fstr);
                    $this->updateReplyWithAdsCore($fstr, count($chunk));
                    $str = '';
                    $chunk = array();
                }
            }

            $fstr = trim($str, ',');
            $chunk = explode(',', $fstr);
            $this->updateReplyWithAdsCore($fstr,count($chunk));
        }
    }

    //for every updated/new ad update the ads data in reply section
    protected function updateReplyWithAdsCore($adId,$count) {
        shell_exec(PHP_EXECUTABLE_PATH.' '.APPLICATION_PATH.'/indexing_scripts/ReplyWithAdsindexing.php ADID '.$adId.' '.$count);
    }


    //just index the user data using the user id
    protected function updateCountOfAdsForUser($userId,$count) {
         shell_exec(PHP_EXECUTABLE_PATH.' '.APPLICATION_PATH.'/indexing_scripts/Userindexing.php USERID '.$userId.' '.$count);

     }


    protected function getReplyCountForAd($adId) {
	//return '0';
        if($adId != '' || !empty($adId)) {
            $obj = new Model_ReplySolr(array());
            $obj->solrUrl = SOLR_META_QUERY_REPLIES;
            $count = $obj->getReplyCountForAd($adId);
            return $count;
        }
    }


    protected function parseAdStatus($val) {
        //        Active = 0;
        //        EXPIRED_BY_SELF AD = 1;
        //        EXPIRED_BY_MASTER AD= 2;
        //        User deleted = 3;
        //        Admin deleted = 4;
        //        Flag and Delay = 11;
        //        PENDING AD = 20;
        if($val == '0') {
            return 'Active';
        } else if($val == '1' || $val == '2') {
            return 'Expired';
        } else if($val == '3') {
            return 'User deleted';
        } else if($val == '4') {
            return 'Admin deleted';
        } else if($val == '20') {
            return 'Flag and Delay';
        } else if($val == '11') {
            return 'Pending';
        }
    }

    protected function parseUseragent($val) {

    	if(empty($val) || is_null($val)) return 'Web';

    	$obj = new Quikr_WAPDeviceDetect($val);
        $status = $obj->mobile_device_detect();

        if($status) return 'Mobile';
        else return 'Web';
    }

    protected function parsePrice($val) {
        preg_match('/Price\:(.*?)[\r\n]/',trim($val),$match1);
        if(@$match1[1]!='')
        {
           $pattern[0] = "/,/";    // comma coming in price like 30,000
           $pattern[1] = "/\/-/";  //  /- coming in price 30000/-
           $replacement = '';
           $match = preg_replace($pattern,$replacement,$match1[1]);
        }
        if(!empty($match) && $match > 0) {
            return trim($match);
        }
        else
        {
            return '0';
        }
    }

    protected function parseLocations($val) {
        $locs = explode('|',$val);
        return trim(implode(', ',$locs));

    }

    protected function parseAttributes($val) {

        preg_match_all('/(.*?)\:(.*?)([\r\n]+|$)/',$val,$attributes);
        $attrString = '';
        $allowedAttr = $this->allowed_attributes;
        //print_r($allowedAttr); exit;
        for($i=0; $i < count($attributes[0]); $i++) {

            //if(in_array($attributes[1][$i], $allowedAttr)) {
                $attrString .= $attributes[0][$i].'|';
            //}
        }
        return rtrim($attrString, '|');

    }


    protected function parseAdType($val) {

        preg_match('/Ad_Type:offer/',$val,$match1);
        if(!empty($match1[0])) {
            return 'Offer';
        }

        preg_match('/Ad_Type:want/',$val,$match2);
        if(!empty($match2[0])) {
            return 'Want';
        }
    }

    protected function parseFlagReason($flag) {
        //Flag reason will contain value if the ad is in Flag and delay i.e tpc_status =20

//        BANNEDWORD = 1;
//         PAIDAD = 2;
//         PAYMENTPENDING = 4;
//         DUPLICATEAD = 8;

        if($flag == '1') {
            return 'Banned word';
        } else if($flag == '2') {
            return 'Paid Ad';
        } else if($flag == '4' || $flag == '6') {
            return 'Payment pending';
        } else if($flag == '8') {
            return 'Duplicate Ad';
        }

    }

    protected function getPosterEmail($userId) {
        $sql = 'SELECT usr_email FROM babel_user WHERE usr_id = '.$userId;
        $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
        $objStmt->execute();
        $items = $objStmt->fetchAll();
        return strip_tags($items[0]['usr_email']);
    }

    protected function getPosterMobile($userId) {
        $sql = 'SELECT usr_mobile FROM babel_user WHERE usr_id = '.$userId;
        $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
        $objStmt->execute();
        $items = $objStmt->fetchAll();
        return strip_tags($items[0]['usr_mobile']);
    }




    protected function parseFreePremium($type) {
        if($type == 'B') {
            return 'Free';
        } else if($type == 'T' || $type == 'H' || $type == 'HT') {
            return 'Premium';
        }
    }

    protected function parsePremiumAdType($type) {
//        B -basic ad/ free ad
//        T - Top ad
//        H - Urgent ad
//        HT - TOp and urgent ad
        if($type == 'T') {
            return 'TOP';
        } else if($type == 'H') {
            return 'URGENT';
        } else if($type == 'HT') {
            return 'ALL';
        }

    }



    protected function parseRegularNoclick($val) {
        if($val == '0') return 'Regular';
        else if($val == '1') return 'No-Click';

    }

    protected function parseNoOfImages($data) {
        $totalImages = 0;
        for($i=1; $i<=4; $i++) {
            if(!empty($data['tpc_img'.$i])) {
                $totalImages++;
            }
        }
        
        /**
         * new fields for addtional 4 images implemented in W36
         *  START_DATE
            SCHEDULED_END_DATE
            ACTUAL_END_DATE
            GUID
         * 
         * 
         * 
         * applying the same logic as above
         * 
         */
        
        if(!empty($data['START_DATE'])) $totalImages++;
        if(!empty($data['SCHEDULED_END_DATE'])) $totalImages++;
        if(!empty($data['ACTUAL_END_DATE'])) $totalImages++;
        if(!empty($data['GUID'])) $totalImages++;

        return $totalImages;
    }

    protected function parseNoOfReplies($adId) {
        $sql = 'SELECT count(rpl_id) as "count" FROM babel_reply WHERE rpl_tpc_id = '.$adId;
        $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
        $objStmt->execute();
        $items = $objStmt->fetchAll();

        return $items[0]['count'];
    }

    protected function parsePaymentType($adId) {

        //this has multiple records for single ad, confirm with shrutika
        //take this ad id and fetch result from babel_product_order
        $sql = 'SELECT paymenttype FROM babel_product_order WHERE productid = '.$adId;
        $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
        $objStmt->execute();
        $items = $objStmt->fetchAll();

    }


    protected function getMetacatName($metaId) {
    	$sql = 'SELECT nod_name FROM babel_node WHERE node_id = '.$metaId.' AND nod_title != ""';
    	$objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
        $objStmt->execute();
        $items = $objStmt->fetchAll();
        return strip_tags($items[0]['nod_name']);
    }



}



	//get command line arguments
	$args = $argv;

	//start indexing from here:
	$objIndexing = new DeletedAdsindexing();
	$objIndexing->init($args);
