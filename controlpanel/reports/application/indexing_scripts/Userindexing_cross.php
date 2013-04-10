<?php
include('IndexingAbstract.php');

class Userindexing  extends IndexingAbstract {

    protected $dbTableName = 'babel_user';
    protected $section = 'users'; //used in the parent class
    protected $indexingUrl = SOLR_USER_INDEXING_URL;
    
    //this is used to check the status of users--confirm with shrutika
    public $password = '69809084b06f9563b44a88cad38ed64019abf170';
    protected $isIncrementalIndexing = false;
    protected $isCalledFromOtherScript = false;
    protected $thresholdForIncremental;

    public function  __destruct() {
        parent::__destruct();
    }

    protected function initiateUpdatingOfDepenedantCores() {
        return;
    }
    
    public function init($args) {

        if(!empty($args)) {
            $runIndexingFor=$args[1];
            if(isset($args[2])) {
                $runInterval = $args[2];
            }

            switch($runIndexingFor) {
                case 'ALL':
                    $this->sql = 'SELECT * FROM '.$this->dbTableName;
                    $this->countSql = 'SELECT count(usr_id ) as "count" FROM '.$this->dbTableName;
                break;

                case 'NEWEST':                    
                    $past = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j")-$runInterval,date("Y"))));
                    $now = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j"),date("Y"))));

                    $this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE usr_lastupdated BETWEEN '.$past.' AND '.$now;
                    $this->countSql = 'SELECT count(usr_id ) as "count" FROM '.$this->dbTableName.' WHERE usr_lastupdated BETWEEN '.$past.' AND '.$now;
                
                    break;

                case 'USERID':
                    $userId = $args[2];
                    $this->isCalledFromOtherScript = true;
                    $this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE usr_id IN ('.$userId.')';
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
                    $past = strtotime($args[2]);
                    $now = strtotime($args[3]);

                    $this->sql = 'SELECT usr_id FROM '.$this->dbTableName.' WHERE usr_lastupdated BETWEEN '.$past.' AND '.$now;
                    $this->countSql = 'SELECT count(usr_id) as "count" FROM '.$this->dbTableName.' WHERE usr_lastupdated BETWEEN '.$past.' AND '.$now;

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
	    
	    
	    $objUsers = new Model_UserSolr(array());
	    $userData= $objUsers->getSingleFieldFromUsers('*', $val['usr_id']);
   
	    if(!empty($userData) && $userData->response->numFound > 0) {
	        $userStories = $userData->response->docs;
	        foreach ($userStories as $story) {
	            foreach ($story as $k => $v) {
	                $name = $k; 
	                $value = $v; 
	                self::$dataToIndex[self::$counter][$name] = $value;
	            }
	        }
	        self::$dataToIndex[self::$counter]['no_of_ads'] = $this->getAdsCountForUser($val['usr_id']);
		self::$dataToIndex[self::$counter]['no_of_alerts'] = $this->getAlertCountForUser($val['usr_id']);
		self::$dataToIndex[self::$counter]['no_of_reply'] = $this->getReplyCountForUser($val['usr_id']);
                //when this got indexed
                self::$dataToIndex[self::$counter]['data_indexed_time'] = $this->convertToUTC(time()); //done
	    }
	    self::$counter++;
	}
    }
            
            
    protected  function checkUserRegistrationStatus($password){
        $UserRegisterationStatus = 'No';
        //if(!empty($this->nickname) && !empty($this->password)) {
        if($password != $this->password) {

            $UserRegisterationStatus = 'Yes';
        }
        return $UserRegisterationStatus;
    }


    //fetch Alert count
    protected function getAlertCountForUser($userId) {
//return '0';
        if($userId != '' || !empty($userId)) {
            $obj = new Model_AlertsSolr(array());
            $count = $obj->getAlertCountForUser($userId);
            return $count;
        }
    }


    //fetch Reply count
    protected function getReplyCountForUser($userId) {
//return '0';
        if($userId != '' || !empty($userId)) {
            $obj = new Model_ReplySolr(array());
            $obj->solrUrl = SOLR_META_QUERY_REPLIES;
            $count = $obj->getReplyCountForUser($userId);
            return $count;
        }
    }

    //fetch Ads count
    protected function getAdsCountForUser($userId) {
//return '0';
        if($userId != '' || !empty($userId)) {
            $obj = new Model_AdsSolr(array());
            $count = $obj->getAdsCountForUser($userId);
            return $count;
        }
    }

	        
}
	
	
	
	//get command line arguments
	$args = $argv;
	$objIndexing = new Userindexing();
	$objIndexing->init($args);
