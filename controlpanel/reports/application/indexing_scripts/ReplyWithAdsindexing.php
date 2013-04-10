<?php
include('IndexingAbstract.php');

class ReplyWithAdsindexing extends IndexingAbstract {

    protected $dbTableName = 'babel_myquikrreply';
    protected $section = 'ReplyWithAds'; //used in the parent class
    protected $indexingUrl = SOLR_REPLY_WITH_ADS_INDEXING_URL;
    protected $isIncrementalIndexing = false;
    protected $isCalledFromOtherScript = false;
    protected $thresholdForIncremental;

    public function  __destruct() {
        parent::__destruct();
    }


    public function init($args) {
        if(!empty($args)) {
            $this->commandArgs = $args;
            $runAlertFor=$args[1];

            if(isset($args[2])) {
                $runAlertInterval = $args[2];
                //$this->isIncrementalIndexing = true;
            }

            switch($runAlertFor) {
                case 'ALL':
                    $this->sql = 'SELECT rpl_id FROM '.$this->dbTableName;
                    $this->countSql = 'SELECT count(rpl_id ) as "count" FROM '.$this->dbTableName;
                break;

                case 'REPLYID':
                    $replyId = $args[2];
                    $this->isCalledFromOtherScript = true;
                    $this->sql = 'SELECT rpl_id FROM '.$this->dbTableName.' WHERE rpl_id IN ('.$replyId.')';
                    //$this->countSql = '';
                    //$this->sql = 'SELECT rpl_id FROM '.$this->dbTableName.' WHERE rpl_id = '.$replyId;
                    //$this->countSql = 'SELECT count(rpl_id) as "count" FROM '.$this->dbTableName.' WHERE rpl_id IN ('.$replyId.')';

                    $this->countSql = '';
                    $this->thresholdForIncremental = $args[3];
                break;

                case 'ADID':
                    $adId = $args[2];
                    $this->isCalledFromOtherScript = true;
                    $this->sql = 'SELECT rpl_id FROM '.$this->dbTableName.' WHERE rpl_tpc_id IN ('.$adId.')';
                    //$this->countSql = '';
                    //$this->countSql = 'SELECT count(rpl_id) as "count" FROM '.$this->dbTableName.' WHERE rpl_tpc_id IN ('.$adId.')';

                     $this->countSql = '';
                    $this->thresholdForIncremental = $args[3];
                break;
                
                case 'DATE_RANGE':
                    //$this->isIncrementalIndexing = false;
                    $past = strtotime($args[2]);
                    $now = strtotime($args[3]);

                    $this->sql = 'SELECT rpl_id FROM '.$this->dbTableName.' WHERE rpl_created BETWEEN '.$past.' AND '.$now;
                    $this->countSql = 'SELECT count(rpl_id ) as "count" FROM '.$this->dbTableName.' WHERE rpl_created BETWEEN '.$past.' AND '.$now;

                break;
            
            
            
                case 'DROP':
                    $this->dropsearchindexesAction();
                break;
                default:
                //run all alerts
                    $this->sql = 'SELECT rpl_id FROM '.$this->dbTableName;
                    $this->countSql = 'SELECT count(rpl_id ) as "count" FROM '.$this->dbTableName;
                break;
            }

            //set the threshhold
            if($this->isCalledFromOtherScript) self::$threshold = $this->thresholdForIncremental;
            else self::$threshold = $this->getMaxRecordsFromDB();
            //self::$threshold = $this->getMaxRecordsFromDB();

            $this->indexAction();

        } else {
            echo 'Please enter valid arguments'; die();
        }
    }

    protected function initiateUpdatingOfDepenedantCores() {
        return;
    }
    protected function initBuildingData() {

        //print_r(self::$data);exit;
        foreach(self::$data as $key => $val) {

            //extract($this->getGlobalData($val['alert_subcategoryid']));
            if(!empty($val['rpl_id']) && !is_null($val['rpl_id'])) {
                

                //reply
                $objReply = new Model_ReplySolr(array());
                $objReply->solrUrl = SOLR_META_QUERY_REPLIES;
                $replyData= $objReply->getSingleFieldFromReply('*', $val['rpl_id']);

                
                if(!empty($replyData) && $replyData->response->numFound > 0) {
                    
                    
                    $rplStories = $replyData->response->docs;

                    foreach ($rplStories as $story) {
                        //self::$dataToIndex[self::$counter] = get_object_vars($story);
                        foreach ($story as $k => $v) {
                            $name = $k; //$item->attributes()->name;
                            $value = $v; //(string)$item;
                            self::$dataToIndex[self::$counter][$name] = $value;
                        }
                    }

                    //ads
                    if(!empty(self::$dataToIndex[self::$counter]['ad_id'])
                            && !is_null(self::$dataToIndex[self::$counter]['ad_id'])) {
                        $objAds = new Model_AdsSolr(array());
                        $adsData= $objAds->getSingleFieldFromAds('*', self::$dataToIndex[self::$counter]['ad_id']);
                        //print_r($adsData); exit;
                        if(!empty($adsData)) {
                            $adStories = $adsData->response->docs;
                            foreach ($adStories as $story) {
                                //self::$dataToIndex[self::$counter] = get_object_vars($story);
                                
                                
                                foreach ($story as $k => $v) {
                                    $name = $k; //$item->attributes()->name;
                                    if($name != 'id') {

                                    $value = $v; //(string)$item;
                                    
                                    if(is_array($value)) self::$dataToIndex[self::$counter][$name] = $value[0];
                                    else self::$dataToIndex[self::$counter][$name] = $value; //($value == '') ? 'NA': $value;
                                    }
                                }
                            }
                        }
                    }
                    
                    self::$dataToIndex[self::$counter]['id'] = $val['rpl_id']; //done
                    //when this got indexed
                    self::$dataToIndex[self::$counter]['data_indexed_time'] = $this->convertToUTC(time()); //done
                    self::$dataToIndex[self::$counter]['_version_'] = 0; //done
                    
                    self::$counter++;
                }
            }
        }
    }

     
    protected function fetchDetailFromCores($coreName, $fieldToFetch, $itemId) {

        switch($coreName) {
            case 'ads':
                $obj = new Model_AdsSolr(array());
                $value = $obj->getSingleFieldFromAds($fieldToFetch, $itemId);
                return $value;
                break;

            case 'reply':
                $obj = new Model_ReplySolr(array());
                $obj->solrUrl = SOLR_META_QUERY_REPLIES;
                $value = $obj->getSingleFieldFromReply($fieldToFetch, $itemId);
                return $value;
                break;
        }
        
    }


}



	//get command line arguments
	$args = $argv;
	//give example here how it will be called
	/**
	 * to run indexing for all alerts:
	 * /usr/local/php/bin/php /home/data/controlpanel/reports/application/indexing_scripts/Alertsindexing.php ALL
	 *
	 *
	 * to run indexing for updated alerts (updated in last 5 mins):
	 * /usr/local/php/bin/php /home/data/controlpanel/reports/application/indexing_scripts/Alertsindexing.php UNSUBSCRIBED 5
	 */


	//start indexing from here:
	$objIndexing = new ReplyWithAdsindexing();
	$objIndexing->init($args);




