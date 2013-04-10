<?php
include('IndexingAbstract.php');

class Alertsindexing extends IndexingAbstract {

    protected $dbTableName = 'babel_alert_master';
    protected $section = 'alert'; //used in the parent class
    protected $indexingUrl = SOLR_ALERTS_INDEXING_URL;
    protected $isIncrementalIndexing = false;
    protected static $alusersToUpdate = array();
    protected $isCalledFromOtherScript = false;

    public function  __destruct() {
        parent::__destruct();
    }

	    
    public function init($args) {
        if(!empty($args)) {
            $this->commandArgs = $args;
            $runAlertFor=$args[1];
            
            if(isset($args[2])) {
                $runInterval = $args[2];
                $this->isIncrementalIndexing = true;
            }

            switch($runAlertFor) {
                case 'ALL':
                    $this->sql = 'SELECT * FROM '.$this->dbTableName;
                    $this->countSql = 'SELECT count(alert_id) as "count" FROM '.$this->dbTableName;
                break;
            
            
                 case 'ONLY_ACTIVE':
                    $this->isIncrementalIndexing = false;
                    $this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE alert_status=0';
                    $this->countSql = 'SELECT count(alert_id) as "count" FROM '.$this->dbTableName.' WHERE alert_status=0';
                break;

                case 'UNSUBSCRIBE':

                    $past = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j")-$runInterval,date("Y"))));
                    $now = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j"),date("Y"))));

                    $this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE alert_status = 2 AND alert_unsubscribe_date BETWEEN '.$past.' AND '.$now;
                    $this->countSql = 'SELECT count(alert_id) as "count" FROM '.$this->dbTableName.' WHERE alert_status = 2 AND alert_unsubscribe_date BETWEEN '.$past.' AND '.$now;
                    $this->isMasterIndexingScript = true;
                break;

                case 'NEWEST':

                    $past = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j")-$runInterval,date("Y"))));
                    $now = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j"),date("Y"))));
                    


                    $this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE alert_createddate BETWEEN '.$past.' AND '.$now;
                    $this->countSql = 'SELECT count(alert_id) as "count" FROM '.$this->dbTableName.' WHERE alert_createddate BETWEEN '.$past.' AND '.$now;
                    $this->isMasterIndexingScript = true;
                break;
                
                /** This will index data given in the below range. Please dont give very large gap between dates
                 * usage:
                 * /usr/local/php/bin/php Alertsindexing.php DATE_RANGE FROM_DATE TO_DATE
                 * 
                 * FROM_DATE and TO_DATE should be of the format dd-mm-yyyy
                 * 
                 */
                case 'DATE_RANGE':
                    $this->isIncrementalIndexing = false;
                    $past = strtotime($args[2]);
                    $now = strtotime($args[3]);


                    $this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE alert_createddate BETWEEN '.$past.' AND '.$now;
                    $this->countSql = 'SELECT count(alert_id) as "count" FROM '.$this->dbTableName.' WHERE alert_createddate BETWEEN '.$past.' AND '.$now;
            
                    break;
                
                case 'DATE_RANGE_ACTIVE':
                    $this->isIncrementalIndexing = false;
                    $past = strtotime($args[2]);
                    $now = strtotime($args[3]);


                    $this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE alert_status=0 AND alert_createddate BETWEEN '.$past.' AND '.$now;
                    $this->countSql = 'SELECT count(alert_id) as "count" FROM '.$this->dbTableName.' WHERE alert_status=0 AND alert_createddate BETWEEN '.$past.' AND '.$now;
            
                    break;
                
                case 'DATE_RANGE_INACTIVE':
                    $this->isIncrementalIndexing = false;
                    $past = strtotime($args[2]);
                    $now = strtotime($args[3]);

                    $this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE alert_status=2 AND alert_unsubscribe_date BETWEEN '.$past.' AND '.$now;
                    $this->countSql = 'SELECT count(alert_id) as "count" FROM '.$this->dbTableName.' WHERE alert_status=2 AND alert_unsubscribe_date  BETWEEN '.$past.' AND '.$now;
            
                    break;
                
                case 'ALERTID':
                    $this->isIncrementalIndexing = false;
                    $alertId = $args[2];
                    $this->isCalledFromOtherScript = true;
                    $this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE alert_id IN ('.$alertId.')';
                    $this->countSql = '';
                    $this->thresholdForIncremental = $args[3];

                break;

		case 'UPDATED':
                $this->isIncrementalIndexing = false;
                $past = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j")-$runInterval,date("Y"))));
                $now = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j"),date("Y"))));


                $this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE alert_updatetime BETWEEN '.$past.' AND '.$now;
                $this->countSql = 'SELECT count(alert_id) as "count" FROM '.$this->dbTableName.' WHERE alert_updatetime BETWEEN '.$past.' AND '.$now;

                break;
                

                case 'DROP':
                    $this->dropsearchindexesAction();
                break;

                default:
                    echo "NOTHING TO DO!";
                break;
            }
            
            
            $config = new Zend_Config_Ini(CONFIG_FILE_PATH, CONFIG_SECTION);
            $params = array(
                'host'           => $config->resources->multidb->db2->host,
                'username'       => $config->resources->multidb->db2->username,
                'password'       => $config->resources->multidb->db2->password,
                'dbname'         => $config->resources->multidb->db2->dbname
            );
            $db = Zend_Db::factory('Pdo_Mysql', $params);
            try {
                //$db->getConnection();
                //Zend_Db_Table_Abstract::setDefaultAdapter($db);
                Zend_Registry::set('dbconnection', $db);

            } catch (Excepetion $e) {
                echo 'Cannot connect to DB now'; exit;
            }
            
            
            
            //set other DB connection;
            
            $this->dbConnection = Zend_Registry::get('alertsdbconnection');

            //set the threshhold
            if($this->isCalledFromOtherScript) self::$threshold = $this->thresholdForIncremental;
            else self::$threshold = $this->getMaxRecordsFromDB();
		echo self::$threshold;
            $this->indexAction();

        } else {
            echo 'Please enter valid arguments'; die();
        }
    }
	    	    
    protected function initBuildingData() {

        foreach(self::$data as $key => $val) {

            $num = extract($this->getGlobalData($val['alert_subcategoryid']));
            //echo "+++".$num."--".$val['alert_id']."---".$val['alert_subcategoryid']."\n";
            if($num == 0 || $val['alert_subcategoryid'] == 0) { //error

		$writer = new Zend_Log_Writer_Stream(INDEXING_LOG."/".date("d-m-Y_H")."_RMQ_alert_no_cat.text");
		$logger = new Zend_Log($writer);
		$logger->log($val['alert_id']." ".$val['alert_status'],Zend_Log::INFO);

                //$this->sendAlertIndexingErrorMail($val); //send the entire array
                
            } else {
            self::$dataToIndex[self::$counter]['id'] = $val['alert_id']; //done
            self::$dataToIndex[self::$counter]['email'] = $val['alert_email']; //done
            self::$dataToIndex[self::$counter]['mobile'] = (!is_null($val['alert_mobile'])) ? trim($val['alert_mobile']) : $this->getPosterMobile($val['alert_userid']); //done
            self::$dataToIndex[self::$counter]['city_id'] = $val['alert_cityid']; //done
            self::$dataToIndex[self::$counter]['city_name'] = $this->getCityName($val['alert_cityid']); //done
            self::$dataToIndex[self::$counter]['localities'] = $this->parseLocations($val['alert_location']); //done
            self::$dataToIndex[self::$counter]['global_metacategory_id'] = $globalMetacatId; //done
            self::$dataToIndex[self::$counter]['metacategory_id'] = $metacatId; //done
            self::$dataToIndex[self::$counter]['metacategory_name'] = $metacatName; //done
            self::$dataToIndex[self::$counter]['subcategory_id'] = $subcatId; //done
            self::$dataToIndex[self::$counter]['global_subcategory_id'] = $globalSubcatId; //done
            self::$dataToIndex[self::$counter]['subcategory_name'] = $subcatName; // done
            self::$dataToIndex[self::$counter]['ad_type'] = ucfirst($val['alert_adtype']);//$this->parseAdType($val['alert_keyword']);//done
            self::$dataToIndex[self::$counter]['status'] = $val['alert_status']; //done
            self::$dataToIndex[self::$counter]['creation_date'] = $val['alert_createddate']; //done
            self::$dataToIndex[self::$counter]['unsubscribe_date'] = $val['alert_unsubscribe_date']; //done
            self::$dataToIndex[self::$counter]['user_id'] = $val['alert_userid']; //done

            self::$dataToIndex[self::$counter]['alert_frequency'] = $val['alert_frequency']; //done
            self::$dataToIndex[self::$counter]['referrer'] = $val['alert_referrer']; //done
            //when this got indexed
            self::$dataToIndex[self::$counter]['data_indexed_time'] = $this->convertToUTC(time()); //done
               // print_r(self::$dataToIndex[self::$counter]); exit;
             //if incremental update then do these steps also
//            if($this->isIncrementalIndexing == true) {                
//                self::$alusersToUpdate[] = $val['alert_userid'];
//            }

            self::$counter++;
		}
        }
        }

    protected function initiateUpdatingOfDepenedantCores() {
        if(!empty(self::$alusersToUpdate)) {
            $uniqueU = array_unique(self::$alusersToUpdate); 
            $counter = 0;
            $str = '';
            $chunk = array();
            foreach($uniqueU as $key => $val) {
                if(!empty($val) && !is_null($val)) {
                    $str .= $val.',';
                    $counter++;
                    //$this->updateUserCount($val);
                }

                if(($counter % self::$limit) == 0) {
                    $fstr = trim($str, ',');
                    $chunk = explode(',', $fstr);
                    $this->updateUserCount($fstr, count($chunk));
                    $str = '';
                    $chunk = array();
                }
            }

            $fstr = trim($str, ',');
            $chunk = explode(',', $fstr);
            $this->updateUserCount($fstr,count($chunk));
        }
        
    }

     protected function updateUserCount($userId,$count) {
         shell_exec(PHP_EXECUTABLE_PATH.' '.APPLICATION_PATH.'/indexing_scripts/Userindexing.php USERID '.$userId.' '.$count);
         
     }
	    
    /**
     * Will use regex to identify the Ad type: Offering/Want
     *
     * @param unknown_type $val
     * @return unknown
     */
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
	    
	    
    /**
     * Fetch email of the user
     *
     * @param unknown_type $userId
     * @return unknown
     */
    protected function getPosterEmail($userId) {
        $sql = 'SELECT usr_email FROM babel_user WHERE usr_id = '.$userId;
        $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
        $objStmt->execute();
        $items = $objStmt->fetchAll();
        return strip_tags($items[0]['usr_email']);
    }


        /**
         * Fetch mobile number of the user
         *
         * @param unknown_type $userId
         * @return unknown
         */
    protected function getPosterMobile($userId) {
        if(!empty($userId)) {
        $sql = 'SELECT usr_mobile FROM babel_user WHERE usr_id = '.$userId;
        $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
        $objStmt->execute();
        $items = $objStmt->fetchAll();
        
        if(!is_null($items[0]['usr_mobile'])) return strip_tags($items[0]['usr_mobile']);
        else return '';
        } else return '';
    }


    protected function parseLocations($val) {
        $locs = explode('|',$val);
        return trim(implode(', ',$locs));

    }
    
    protected function sendAlertIndexingErrorMail($alert) {
        $message = array();
        $subject = "[Reporting Tool] - ".  APPLICATION_ENV." - Cannot extract variables - ".$this->section;
        $message[] = "Alert id:".$alert["alert_id"];
        $message[] = "Subcategory id:".$alert["alert_subcategoryid"];
        
        $mail = new Zend_Mail();
        $mail->setBodyHtml(implode($message,"<br />"));
        $mail->setFrom('vsingh@quikr.com', 'System');
        $mail->addTo('vsingh@quikr.com', 'Vibhor Singh');
	if(APPLICATION_ENV == 'production') {
            $mail->addCc("stiwari@quikr.com","Sudhir Tiwari");
            //$mail->addCc("sumeer@quikr.com","Sumeer Goyal");
            $mail->addCc("bbhalara@quikr.com", "Bhavin Bhalara");
        }
        $mail->setSubject($subject);
        $mail->send();
        
        
    }
    
        
}


	
	//get command line arguments
	$args = $argv;

	//start indexing from here:
	$objIndexing = new Alertsindexing();
	$objIndexing->init($args);


        
