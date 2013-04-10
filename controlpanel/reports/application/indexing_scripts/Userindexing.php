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
    protected $isRunTimeIndexing = false;

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
                    $this->countSql = 'SELECT count(usr_id ) as "count" FROM '.$this->dbTableName;
                break;

                case 'NEWEST':                    
                    $past = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j")-$runInterval,date("Y"))));
                    $now = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j"),date("Y"))));

                    $this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE usr_lastupdated BETWEEN '.$past.' AND '.$now;
                    $this->countSql = 'SELECT count(usr_id ) as "count" FROM '.$this->dbTableName.' WHERE usr_lastupdated BETWEEN '.$past.' AND '.$now;
                    $this->isMasterIndexingScript = true;
                    break;

                case 'USERID':
                    $userId = $args[2];
                    $this->isCalledFromOtherScript = true;
                    $this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE usr_id IN ('.$userId.')';
                    $this->thresholdForIncremental = $args[3];
                    $this->countSql = '';
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

                    $this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE usr_lastupdated BETWEEN '.$past.' AND '.$now;
                    $this->countSql = 'SELECT count(usr_id) as "count" FROM '.$this->dbTableName.' WHERE usr_lastupdated BETWEEN '.$past.' AND '.$now;

                break;
            
                
                 case 'RUNTIME':
                    $userId = $args[2];
                    $this->dbConnection = Zend_Registry::get("writedb"); 
                    $this->isIncrementalIndexing = false;
                    $this->isCalledFromOtherScript = true;
                   $this->sql = 'SELECT usr_id,usr_email,usr_first,usr_last,usr_nick,usr_mobile,usr_areaname,usr_areaid,usr_password,usr_created,usr_lastlogin,usr_lastupdated,permission_for_bulk_upload FROM '.$this->dbTableName.' WHERE usr_id IN ('.$userId.')';
                    $this->thresholdForIncremental = $args[3];
                    $this->countSql = '';               
                    $this->isRunTimeIndexing = true;
                    //unlink(INDEXING_LOG."/".$this->logFileName);
                    //$this->setRuntimeLog();
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
            self::$dataToIndex[self::$counter]['id'] = trim($val['usr_id']); //done
            self::$dataToIndex[self::$counter]['email'] = (!is_null ($val['usr_email'])) ? trim($val['usr_email']) : 'NA'; //done
            self::$dataToIndex[self::$counter]['firstname'] = (!is_null ($val['usr_first']) && !empty($val['usr_first'])) ? trim($val['usr_first']) : 'NA'; //done
            self::$dataToIndex[self::$counter]['lastname'] = (!is_null ($val['usr_last']) && !empty($val['usr_last'])) ? trim($val['usr_last']) : 'NA'; //done
            self::$dataToIndex[self::$counter]['fullname'] = $val['usr_first'].' '.$val['usr_last'];
            self::$dataToIndex[self::$counter]['nickname'] = (!is_null ($val['usr_nick']) && !empty($val['usr_nick']))? trim($val['usr_nick']) : 'NA'; //done
            self::$dataToIndex[self::$counter]['mobile'] = (!is_null ($val['usr_mobile']) && !empty($val['usr_mobile'])) ? trim($val['usr_mobile']) : 'NA'; //done
            self::$dataToIndex[self::$counter]['city_name'] = (is_null($val['usr_areaname'])) ? '' : trim($val['usr_areaname']);  //done
            self::$dataToIndex[self::$counter]['city_id'] = (is_null($val['usr_areaid'])) ? '' : trim($val['usr_areaid']); //done
            self::$dataToIndex[self::$counter]['is_registered'] = $this->checkUserRegistrationStatus($val['usr_password']); //done
            self::$dataToIndex[self::$counter]['registration_date'] = !empty ($val['usr_created']) ? trim($val['usr_created']) : ''; //done; //done
            self::$dataToIndex[self::$counter]['last_login_date'] = !empty ($val['usr_lastlogin']) ? strtotime(trim($val['usr_lastlogin'])) : ''; //done
            self::$dataToIndex[self::$counter]['last_updated_date'] = !empty ($val['usr_lastupdated']) ? trim($val['usr_lastupdated']) : ''; //done
            self::$dataToIndex[self::$counter]['is_bulk_allowed'] = (trim($val['permission_for_bulk_upload']) == '0') ? 'No' : 'Yes'; //done

            //new fields
            self::$dataToIndex[self::$counter]['no_of_ads'] = 0; //$this->getAdsCountForUser($val['usr_id']);
            self::$dataToIndex[self::$counter]['no_of_alerts'] = 0; //$this->getAlertCountForUser($val['usr_id']);
            self::$dataToIndex[self::$counter]['no_of_reply'] = 0; //$this->getReplyCountForUser($val['usr_id']);
            //when this got indexed
            self::$dataToIndex[self::$counter]['data_indexed_time'] = $this->convertToUTC(time()); //done
            //print_r(self::$dataToIndex);exit;
            
            $this->setRedisKeyForUsers();
            self::$counter++;
        }
    }
    
    protected function setRedisKeyForUsers() {
        $key = new Rediska_Key('USER_ID|'.self::$dataToIndex[self::$counter]['id']);
        $key->setExpire(86400); // 1 day
        $value = $key->getValue();
        if($value == null) {
            $key->setValue(array( 
                'usr_email' => self::$dataToIndex[self::$counter]['email'],
                'usr_mobile' => self::$dataToIndex[self::$counter]['mobile']));
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
        if($userId != '' || !empty($userId)) {
            $obj = new Model_AlertsSolr(array());
            $count = $obj->getAlertCountForUser($userId);
            if($count != "" && !is_null($count)) return (int) $count; else return "0";
        } else return "0";
    }


    //fetch Reply count
    protected function getReplyCountForUser($userId) {
        if($userId != '' || !empty($userId)) {
            $obj = new Model_ReplySolr(array());
            $obj->solrUrl = SOLR_META_QUERY_REPLIES;
            $count = $obj->getReplyCountForUser($userId);
            if($count != "" && !is_null($count)) return (int) $count; else return "0";
        } else return "0";
    }

    //fetch Ads count
    protected function getAdsCountForUser($userId) {
        if($userId != '' || !empty($userId)) {
            $obj = new Model_AdsSolr(array());
            $count = $obj->getAdsCountForUser($userId);
            if($count != "" && !is_null($count)) return (int) $count; else return "0";
        } else return "0";
    }
    
    protected function setRuntimeLog() {
        
        $this->logFileName = $this->section."_RUNTIME_".date("d-M-Y_H").".log";
        $fileName = INDEXING_LOG."/".$this->logFileName;
        $this->writer = new Zend_Log_Writer_Stream($fileName);
        
        $format = "%timestamp% %priorityName% (%priority%):%message%". PHP_EOL;
        $formatter = new Zend_Log_Formatter_Simple($format);
        $this->writer->setFormatter($formatter);
        
        
        $this->logger = new Zend_Log($this->writer);
        
    }

	        
}
	
	
	
	//get command line arguments
	$args = $argv;
	$objIndexing = new Userindexing();
	$objIndexing->init($args);