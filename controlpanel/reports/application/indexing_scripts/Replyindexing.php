<?php
include('IndexingAbstract.php');

class Replyindexing  extends IndexingAbstract {

    protected $dbTableName = 'babel_myquikrreply';
    protected $section = 'reply'; //used in the parent class
    protected $indexingUrl = SOLR_REPLY_INDEXING_URL;
    protected static $rusersToUpdate = array();
    protected static $radsToUpdate = array();
    protected static $rreplyWithAdsToUpdate = array();
    protected $isCalledFromOtherScript = false;




    public function  __destruct() {
        parent::__destruct();
    }

    public function init($args) {

        if(!empty($args)) {
            $this->commandArgs = $args;
            $runIndexingFor=$args[1];
            if(isset($args[2])) {
                $runInterval = $args[2];
                $this->isIncrementalIndexing = true;
            }

            switch($runIndexingFor) {
                case 'ALL':
                    $this->sql = 'SELECT * FROM '.$this->dbTableName;
                    $this->countSql = 'SELECT count(rpl_id) as "count" FROM '.$this->dbTableName;
                break;

                case 'NEWEST':
                    $past = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j")-$runInterval,date("Y"))));
                    $now = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j"),date("Y"))));

                    $this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE rpl_created BETWEEN '.$past.' AND '.$now;
                    $this->countSql = 'SELECT count(rpl_id) as "count" FROM '.$this->dbTableName.' WHERE rpl_created BETWEEN '.$past.' AND '.$now;
                    $this->isMasterIndexingScript = true;
                break;
                
                case 'REPLYID':
                    $replyId = $args[2];
                    //$this->isIncrementalIndexing = false;
                    $this->isCalledFromOtherScript = true;
                    $this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE rpl_id IN ('.$replyId.')';
                    $this->countSql = '';
                    $this->thresholdForIncremental = $args[3];
                    
                break;
            
            
                /** This will index data given in the below range. Please dont give very large gap between dates
                 * usage:
                 * /usr/local/php/bin/php Replyindexing.php DATE_RANGE FROM_DATE TO_DATE
                 * 
                 * FROM_DATE and TO_DATE should be of the format dd-mm-yyyy
                 * 
                 */
                case 'DATE_RANGE':
                    $this->isIncrementalIndexing = false;
                    $past = strtotime($args[2]);
                    $now = strtotime($args[3]);

                    $this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE rpl_created BETWEEN '.$past.' AND '.$now;
                    $this->countSql = 'SELECT count(rpl_id) as "count" FROM '.$this->dbTableName.' WHERE rpl_created BETWEEN '.$past.' AND '.$now;

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

    public function initBuildingData() {

        foreach(self::$data as $key => $val) {
            self::$dataToIndex[self::$counter]['id'] = $val['rpl_id']; //done
            self::$dataToIndex[self::$counter]['ad_id'] = $val['rpl_tpc_id']; //done
            self::$dataToIndex[self::$counter]['rpl_user_id'] = $val['rpl_usr_id']; //done
            self::$dataToIndex[self::$counter]['rpl_content'] = $val['rpl_content']; //done
            self::$dataToIndex[self::$counter]['rpl_email'] = $val['rpl_email']; //done
            self::$dataToIndex[self::$counter]['rpl_nick'] = $val['rpl_post_nick']; //done
            self::$dataToIndex[self::$counter]['rpl_mobile'] = $val['rpl_mobile']; //done
            self::$dataToIndex[self::$counter]['rpl_createdTime'] = $val['rpl_created']; //done
            self::$dataToIndex[self::$counter]['rpl_status'] = $val['rpl_status']; //done
            self::$dataToIndex[self::$counter]['rpl_post_nick'] = $val['rpl_post_nick']; //done
            self::$dataToIndex[self::$counter]['rpl_post_usr_id'] = $val['rpl_post_usr_id']; //done
            self::$dataToIndex[self::$counter]['rpl_http_referer'] = $val['rpl_http_referer']; //done
            //this will store the entire user agent string
            self::$dataToIndex[self::$counter]['rpl_user_agent'] = $val['rpl_user_agent']; //done
            self::$dataToIndex[self::$counter]['rpl_bak1'] = $val['rpl_bak1']; //done
            
            //this will store the actual user agent -- mobile or web
            //in Solr we are using the dyncamic field "*_t" for this purpose
            self::$dataToIndex[self::$counter]['user_agent_flag_t'] = $this->parseUserAgent($val['rpl_user_agent']); //done
            //when this got indexed
            self::$dataToIndex[self::$counter]['data_indexed_time'] = $this->convertToUTC(time()); //done
            
            if($this->isIncrementalIndexing == true) {

              //update user count
              //self::$rusersToUpdate[] = $val['rpl_usr_id'];
//                //update reply count in ads
              self::$radsToUpdate[] = $val['rpl_tpc_id'];
//                //update reply_with_ads
              self::$rreplyWithAdsToUpdate[] = $val['rpl_id'];
            }
            self::$counter++; 
        }
	
    }


    protected function initiateUpdatingOfDepenedantCores() {
       
        if(!empty(self::$rusersToUpdate)) {
            $uniqueU = array_unique(self::$rusersToUpdate);
            $counter = 0;
            $str = '';
            $chunk = array();
            foreach($uniqueU as $key => $val) {
                if(!empty($val) && !is_null($val)) {
                    $str .= $val.',';
                    $counter++;
                    //$this->updateCountOfRepliesForUser($val);
                }

                if(($counter % self::$limit) == 0) {
                    $fstr = trim($str, ',');
                    $chunk = explode(',', $fstr);
                    $this->updateCountOfRepliesForUser($fstr, count($chunk));
                    $str = '';
                    $chunk = array();
                }
                
            }
            $fstr = trim($str, ',');
            $chunk = explode(',', $fstr);
            $this->updateCountOfRepliesForUser($fstr, count($chunk));
        }

        if(!empty(self::$radsToUpdate)) {
            $uniqueA = array_unique(self::$radsToUpdate);
            $counter = 0;
            $str = '';
            $chunk = array();
            //print_r($uniqueA); exit;
            foreach($uniqueA as $key => $val) {
                if(!empty($val) && !is_null($val)) {
                     $str .= $val.',';
                    $counter++;
                    //$this->updateCountOfRepliesForAds($val);
                }

                if(($counter % self::$limit) == 0) {
                    $fstr = trim($str, ',');
                    $chunk = explode(',', $fstr);
                    $this->updateCountOfRepliesForAds($fstr, count($chunk));
                    $str = '';
                    $chunk = array();
                }
            }

            $fstr = trim($str, ',');
            $chunk = explode(',', $fstr);
            $this->updateCountOfRepliesForAds($fstr,count($chunk));
        }


        if(!empty(self::$rreplyWithAdsToUpdate)) {
            $uniqueR = array_unique(self::$rreplyWithAdsToUpdate);
            $counter = 0;
            $str = '';
            $chunk = array();
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
            $this->updateReplyWithAdsCore($fstr, count($chunk));
        }

    }


    protected function updateCountOfRepliesForUser($userId, $count) {
        
        shell_exec(PHP_EXECUTABLE_PATH.' '.APPLICATION_PATH.'/indexing_scripts/Userindexing.php USERID '.$userId.' '.$count);
    }

    protected function updateCountOfRepliesForAds($adId,$count) {
        shell_exec(PHP_EXECUTABLE_PATH.' '.APPLICATION_PATH.'/indexing_scripts/Adsindexing.php ADID '.$adId.' '.$count);
    }

    protected function updateReplyWithAdsCore($replyId,$count) {
        shell_exec(PHP_EXECUTABLE_PATH.' '.APPLICATION_PATH.'/indexing_scripts/ReplyWithAdsindexing.php REPLYID '.$replyId.' '.$count);
    }
    

}

  
	
	//get command line arguments
	$args = $argv;

	//start indexing from here:
	$objIndexing = new Replyindexing();
	$objIndexing->init($args);
