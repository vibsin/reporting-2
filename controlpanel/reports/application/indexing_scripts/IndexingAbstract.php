<?php
/* 
 * Some of the properties and functions used will be found in the subclass extending this class
 */

include("indexing_config.php");

abstract class IndexingAbstract {
    
    protected static $start = 0;
    protected static $limit = REPORTING_LIMIT_FOR_INDEXING;
    protected static $data = array();
    protected static $dataToIndex = array();
    protected static $reports;
    protected static $threshold;
    protected $isIncrementalIndexingDone = false;
    protected $sql;
    protected $countSql;
    protected $procedureName = "proc_getGlobalDataUsingSubcatId";

    protected static $counter = 0;
    protected $isIncrementalIndexing = false;
    
    protected $dbConnection = "default";
    public $writer;
    public $logger;
    /**
     *Notify that indexing script is run for main e.g. only ads and is not the dependant indexing request
     * @var type 
     */
    protected $isMasterIndexingScript = false;
    
    protected $commandArgs = array();
    protected $lastInsertIdForDataHistory;
    protected $logFileName;
    
    protected $isFetchFromSolrEnabled=false;
    /**
     * these methods will be implemented in the subclass. This method will create an array of data fields depending upon the section
     */
    abstract protected function initBuildingData();
    abstract protected function initiateUpdatingOfDepenedantCores();
    //abstract protected function getMaxRecordsFromDB();
    
    public function __construct() {
        return TRUE;
        $this->logFileName = $this->section."_".date("d-M-Y_H").".log";
        $fileName = INDEXING_LOG."/".$this->logFileName;
        $this->writer = new Zend_Log_Writer_Stream($fileName);
        
        $format = "%timestamp% %priorityName% (%priority%):%message%". PHP_EOL;
        $formatter = new Zend_Log_Formatter_Simple($format);
        $this->writer->setFormatter($formatter);
        
        
        $this->logger = new Zend_Log($this->writer);
    }
    

    protected function getMaxRecordsFromDB() {
        //$this->logger->info("\n Max Records Sql: ".$this->countSql);
        echo "\n Max Records Sql: ".$this->countSql;
        $sql = $this->countSql;
        
        $conn = null;
        try {
            if($this->dbConnection == "default") {
                //echo "old connection count";
                $conn = Zend_Registry::get("dbconnection");
                $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get("dbconnection"), $sql);
                
            } else {
                //echo "new connection count ";
                 $conn = $this->dbConnection;
                $objStmt = new Zend_Db_Statement_Pdo($this->dbConnection, $sql); 
               
            }
            $str1 = "\n Max Records Fetch Start time: date=".date("d-M-Y_H:i:s")."\t Microtime=".microtime(true);
            echo $str1;
            //$this->logger->info($str1);
            
            $objStmt->execute();
            $count = $objStmt->fetchAll();
            
            $str2 = "\n Max Records Fetch End time: date=".date("d-M-Y_H:i:s")."\t Microtime=".microtime(true);
            echo $str2;
            //$this->logger->info($str2);
            
            //now log this data in history table
           
            if($this->isMasterIndexingScript == true) { 
                $table = new Zend_Db_Table(array("name"=>"data_history","db"=> Zend_Registry::get("authDbConnection")));
                $this->lastInsertIdForDataHistory = $table->insert(array(
                    "core" => $this->section,
                    "indexing_script" => $_SERVER["SCRIPT_NAME"],
                    "sql_query" => $sql,
                    "indexing_flag" => $this->commandArgs[1],
                    "for_no_of_days" => $this->commandArgs[2],
                    "db_count" => $count[0]["count"],
                    "indexing_day" => date("Y-m-d"),
                    "insert_time_db" => date("Y-m-d H:i:s"),
                    "log_file" => $this->logFileName
                ));     
                
            }
            
            return $count[0]["count"];
        } catch (Exception $e) {
            $dbConfig = $conn->getConfig();
            $message = array();
            $subject = "[Reporting Tool] - ".  APPLICATION_ENV." - Indexing Failure for ".$this->section;
            $message[] = "Indexing failed for ".$this->section." on ".date("D,d M Y");
            $message[] = "Exception thrown:".$e->getMessage();
            $message[] = "Connecting Server:".$_SERVER['SERVER_ADDR'];
            $message[] = "Script name:".$_SERVER["SCRIPT_NAME"];
            if($_SERVER["argc"] > 0) {
                $st = "<ul>";
                foreach($_SERVER["argv"] as $k => $v) {
                    $st .= "<li>".$v."</li>";
                }
                $st .="</ul>";
            }
            $message[] = "Script arguments:".$st;
            $message[] = "Indexing Time:".date("D,d-M-Y_H:i:s");
            $message[] = "DB credentials<br><ul><li>Host:".$dbConfig["host"]."</li><li>Username:".$dbConfig["username"]."</li><li>Password:".$dbConfig["password"]."</li><li>DBname:".$dbConfig["dbname"]."</li></ul>";
            $message[] = "Sql run:".$sql;
            $message[] = "Stack Trace:<pre>".$e->getTraceAsString()."</pre>";
            
            $mail = new Zend_Mail();
            $mail->setBodyHtml(implode($message,"<br />"));
            $mail->setFrom('vsingh@quikr.com', 'System');
            $mail->addTo('vsingh@quikr.com', 'Vibhor');
            
            //mail will only be sent to others  if it was a master script
            if($this->isMasterIndexingScript == true && APPLICATION_ENV == 'production') {
                $mail->addTo("ppatel@quikr.com", "Purvish Patel");
                //$mail->addCc('gishorek@quikr.com', 'Gishore Kallarackal');
                $mail->addCc('dsuryavanshi@quikr.com', 'Dinesh Suryavanshi');
                $mail->addCc("apatodia@quikr.com","Abhishek Patodia");
                $mail->addCc("rkhara@quikr.com","Rohan Khara");
                $mail->addCc("stiwari@quikr.com","Sudhir Tiwari");
                //$mail->addCc("sumeer@quikr.com","Sumeer Goyal");
                
                $subject .= " - Master script failed";
            } else $subject .= " - Slave script failed";
            $mail->setSubject($subject);
            $mail->send();
            
            
            $writer = new Zend_Log_Writer_Stream(INDEXING_LOG."/".date("d-m-Y")."_Indexing_Failure_".$this->section.".html");
            $logger = new Zend_Log($writer);
            $logger->log(implode($message,"<br />"),Zend_Log::INFO);
        }
        

    }

    /**
     *
     * @param <type> $sql
     * This will fetch the records from DB, limit is 1000
     */
    private function _fetchFromDb($sql) {
        //$this->logger->info("\n Data Sql: ".$sql);
        echo "\n Data Sql: ".$sql;
        
        $conn = null;
        try {
            if($this->dbConnection == "default") {
                //echo "old connection data";
                $conn = Zend_Registry::get("dbconnection");
                $objStmt = new Zend_Db_Statement_Pdo($conn, $sql);
                
            } else {
            //echo "new connection data";
                $conn = $this->dbConnection;
                $objStmt = new Zend_Db_Statement_Pdo($this->dbConnection, $sql); 
            }
            $str1 = "\n Data Sql Fetch Start time: date=".date("d-M-Y_H:i:s")."\t Microtime=".microtime(true);
            echo $str1; 
            //$this->logger->info($str1);
            
            $objStmt->execute();
            //reset the data array
            self::$data = null;
            self::$data = $objStmt->fetchAll();//exit;
            
            $str2 = "\n Data Sql Fetch End time: date=".date("d-M-Y_H:i:s")."\t Microtime=".microtime(true);
            echo $str2;
            //$this->logger->info($str2);
            
            unset($objStmt);
        } catch (Exception $e) {
            $dbConfig = $conn->getConfig();
            $message = array();
            $subject = "[Reporting Tool] - ".  APPLICATION_ENV." - Intermediate SQL query Failure for ".$this->section;
            $message[] = "Indexing failed for ".$this->section;
            //$message[] = "Reason: DB connection lost during indexing of ".$this->section." on ".date("D,d M Y");
            $message[] = "Exception thrown:".$e->getMessage();
            //$message[] = "Server:".  gethostname();
            $message[] = "Script name:".$_SERVER["SCRIPT_NAME"];
            $message[] = "Indexing Time:".date("D,d-M-Y_H:i:s");
            $message[] = "DB credentials<br><ul><li>Host:".$dbConfig["host"]."</li><li>Username:".$dbConfig["username"]."</li><li>Password:".$dbConfig["password"]."</li><li>DBname:".$dbConfig["dbname"]."</li></ul>";
            $message[] = "Sql run:".$sql;
            $message[] = "Stack Trace:<pre>".$e->getTraceAsString()."</pre>";
            
            $mail = new Zend_Mail();
            $mail->setBodyHtml(implode($message,"<br />"));
            $mail->setFrom('vsingh@quikr.com', 'System');
            $mail->addTo('vsingh@quikr.com', 'Quikr');
            if(APPLICATION_ENV == 'production') {
                //$mail->addTo('gishorek@quikr.com', 'Gishore Kallarackal');
                //$mail->addTo('dsuryavanshi@quikr.com', 'Dinesh Suryavanshi');
                //$mail->addCc("stiwari@quikr.com","Sudhir Tiwari");
                //$mail->addCc("sumeer@quikr.com","Sumeer Goyal");
            }
            
            $mail->setSubject($subject);
            $mail->send();
            
            $writer = new Zend_Log_Writer_Stream(INDEXING_LOG."/".date("d-m-Y")."_Intermediate_SQL_Query_Failure_".$this->section.".html");
            $logger = new Zend_Log($writer);
            $logger->log(implode($message,"<br />"),Zend_Log::INFO);
            
            
            $writer = new Zend_Log_Writer_Stream(INDEXING_LOG."/".date("d-m-Y")."_Intermediate_SQL_Query_Failure_".$this->section.".txt");
            $logger = new Zend_Log($writer);
            $logger->log($this->commandArgs[2],Zend_Log::INFO);
            
            
            //now republish 
            //$this->republish($this->commandArgs[2]); //id
            
            
            
        }
    }


    /**
     *
     * @param <type> $s
     * this is used to increment the start,limit parameters of sql query since getRecords() is called recursively
     */
    private function _incrementStart($s) {
        self::$start = $s + self::$limit;

        if(self::$start >= self::$threshold) {
            
            //self::$reports .= "\n reached max records to fetch \n";
            
            echo "\n reached max records to fetch";
            //$this->logger->info("\n reached max records to fetch");
            
            //echo "\n commiting now to solr";
            //$this->logger->info("\n commiting now to solr");
            $objSolr = new Quikr_SolrIndex();
            $objSolr->setIndexingUrl($this->indexingUrl);
            
            //$str1 = "\n Solr Commit Start time: date=".date("d-M-Y_H:i:s")."\t Microtime=".microtime(true);
            //echo $str1;
            //$this->logger->info($str1);
            
            //$objSolr->isCommitFlagRaised = true;
            //$objSolr->isCommitForMasterRequest = $this->isMasterIndexingScript;
            
            //$status = $objSolr->commit();
            
            //$str2 = "\n Solr Commit End time: date=".date("d-M-Y_H:i:s")."\t Microtime=".microtime(true);
            //echo $str2;
            //$this->logger->info($str2);
            
            //free resource
            unset($objSolr);
            //echo "\n commiting finished";
            //$this->logger->info("\n commiting finished");
            if($this->isMasterIndexingScript == true) { 
                $this->insertSolrCount();
            }

            return;
        }
        
        
        /*$objSolr = new Quikr_SolrIndex();
        $objSolr->setIndexingUrl($this->indexingUrl);
        $status = $objSolr->commit();
*/
        $this->getRecords(self::$start, self::$limit);

        
    }

    /**
     * This function will pass an array of data to index to Solr class
     */
    protected function _postToSolr() {
        //index this many data in solr & commit
        $objSolr = new Quikr_SolrIndex(self::$dataToIndex);
        $objSolr->setIndexingUrl($this->indexingUrl);
        $objSolr->coreName = $this->section;
        
        $str1 = "\n Solr Post Start time: date=".date("d-M-Y_H:i:s")."\t Microtime=".microtime(true);
        echo $str1;
        //$this->logger->info($str1);
        
        
        $status = $objSolr->init();
        
        $str2 = "\n Solr Post End time: date=".date("d-M-Y_H:i:s")."\t Microtime=".microtime(true);
        echo $str2;
        //$this->logger->info($str2);
        //self::$reports .= "\n Solr response: ".$objSolr->solrPostReponse." \n";
        //echo "\n Solr response: ".$objSolr->solrPostReponse;
        //$this->logger->info("\n Solr response: ".$objSolr->solrPostReponse);
        //free resource
        unset($objSolr);
        self::$dataToIndex = null;
    }

    /**
     * any garbage collection
     */
    protected function  __destruct() {
        ;
        $vars = get_class_vars(get_class($this));

        foreach ($vars as $key) {
            unset($key);
        }

        //print_r(get_class_vars(get_class($this)));
    }


    /**
     * Cron process begins from here
     */
    protected function indexAction() {

        //self::$threshold = self::$start + REPORTING_MAX_RECORDS_FOR_INDEXING;

        //self::$reports .= "\n Indexing started at ".date("D, d M Y H:i:s")." \n";
        //$this->logger->info("\n Indexing started at ".date("D, d M Y H:i:s"));
        echo "\n Indexing started at ".date("D, d M Y H:i:s");

        //self::$reports .= "\n Total records to index:".self::$threshold;
        //$this->logger->info("\n Total records to index:".self::$threshold);
        echo "\n Total records to index:".self::$threshold;

        //self::$reports .= "\n Indexing started from ".(self::$start)." \n";
        //$this->logger->info("\n Indexing started from ".(self::$start));
        echo "\n Indexing started from ".(self::$start);
        

        $this->getRecords();



        //self::$reports .= "\n finished fetching all records \n";
        //$this->logger->info("\n finished fetching all records");
        echo "\n finished fetching all records";

        //self::$reports .= "\n Indexing finished at ".date("D, d M Y H:i:s")." \n";
        //$this->logger->info("\n Indexing finished at ".date("D, d M Y H:i:s"));
        echo "\n Indexing finished at ".date("D, d M Y H:i:s");


        //$this->generateReport();



        //if any dependant solr is to be updated
        if($this->isIncrementalIndexing) {
            $this->initiateUpdatingOfDepenedantCores();
        }

        exit;

    }

    //will be called recursive
    protected function getRecords($start="", $limit="") {
        flush();

        if(empty($start)) $s = self::$start;
        else $s = $start;

        if(empty($limit)) $l = self::$limit;
        else $l = $limit;

        if($this->isCalledFromOtherScript) $sql = $this->sql;
        else $sql = $this->sql." LIMIT ".$s.", ".$l;

        
        //echo $sql; 
        if($this->isFetchFromSolrEnabled) {
            $m = new Model_BgsSolr(array());
            self::$data = $m->queryForIndexing($s,$l);
            //print_r(self::$data);exit;
            
        } else {
            $this->_fetchFromDb($sql);
            echo " \n fetched data from DB";
        }
        //$this->logger->info("\n fetched data from DB");
        if(!empty(self::$data) && count(self::$data) > 0) {
            //init data bulding
            //print_r(self::$data); exit;
            $this->initBuildingData(); //this function is implemented in the subclass
            echo " \n finished building array to post";
            //$this->logger->info("\n finished building array to post");
            $this->_postToSolr();
            echo " \n finished posting on solr";
            //$this->logger->info("\n finished posting on solr");
            //if any dependant solr is to be updated
//            if($this->isIncrementalIndexing) {
//                $this->initiateUpdatingOfDepenedantCores();
//            }
            
           
            //now reset new start & limit

            //self::$reports .= "\n Indexing finished till ".($s + self::$limit)." \n";
            //$this->logger->info("\n Indexing finished till ".($s + self::$limit)." \n");
            echo "\n Indexing finished till ".($s + self::$limit)." \n";

        } else {
            //this is one time, remove after done
            if($this->isRunTimeIndexing) {
                switch($this->section) {
                    case "ads":                        
                        $shellStr =  PHP_EXECUTABLE_PATH.' '.APPLICATION_PATH.'/indexing_scripts/DeletedAdsindexing.php DEL_ADID '.$this->commandArgs[2].' 1';  
                        shell_exec($shellStr); $shellStr =null;
                        exit;
                            
                        break;
                    
                    case "deleted_ads":
                        $shellStr =  PHP_EXECUTABLE_PATH.' '.APPLICATION_PATH.'/indexing_scripts/DeletedAdsindexing.php ARCHIVE_ADID '.$this->commandArgs[2].' 1';  
                        shell_exec($shellStr); $shellStr =null;
                        exit;
                        break;
                    
                    case "archive_ads":
                        $writer = new Zend_Log_Writer_Stream(INDEXING_LOG."/".date("Y-m-d")."_MISSING_ADS_FROM_ARCHIVE.txt");
                        $format = "%timestamp%|%message%".PHP_EOL;
                        $formatter = new Zend_Log_Formatter_Simple($format);
                        $writer->setFormatter($formatter);
                        $logger = new Zend_Log($writer);
                        $logger->info($this->commandArgs[2]);
                        $writer->shutdown();
                        exit;
                        break;
                    
                    default:
                        break;
                } 
            }
            //if records not found in first set of limits check for another set
            echo "\n No records found between ".self::$start." to ".($s + self::$limit);
        }
        
        //increment limit and fetch records
        $this->_incrementStart($s);

    }

            


    /**
     * Will fetch global data of metacat and subcat, should provide the city specific id of subcategory
     * We have created a procedure for this in the DB
     */
    protected function getGlobalData($subcatId)  {
        //$this->procedureName is to be implemented in subclass
        $key = new Rediska_Key('SUBCAT_ID|'.$subcatId);
        $value = $key->getValue();
        if($value == null) {
            $sql = "CALL ".$this->procedureName."(".$subcatId.")";
            $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get("procdbconnection"), $sql);
            $objStmt->execute();
            $items = $objStmt->fetchAll();
            unset($objStmt);
            //print_r($items);exit;
            
            //set redis key
            $key->setValue($items[0]);
            return $items[0];
        } else {
            return $value;
        }
    }

    /**
     * Get city name
     * 
     * @param unknown_type $cityId
     */
    protected function getCityName($cityId) {
        $key = new Rediska_Key('CITY_ID|'.$cityId);
        $value = $key->getValue();
        if($value == null) {
        
            $sql ='SELECT area_name FROM babel_area WHERE area_id = '.$cityId.' AND area_title != ""';
            $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get("writedb"), $sql);
            $objStmt->execute();
            $items = $objStmt->fetchAll();
            unset($objStmt);
            
            //set redis key
            $key->setValue(strip_tags($items[0]["area_name"]));
            
            return strip_tags($items[0]["area_name"]);
        } else {
            return $value;
        }

    }


    /**
     * Sanitize our text type values
     *
     * @param unknown_type $input
     * @return unknown
     */
    protected function cleanHtml($input) {
        //$input= preg_replace("/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F\0xa4\0xa0\0xcb]/", "", $input);
        $str = preg_replace( array("/\x00/", "/\x01/", "/\x02/", "/\x03/", "/\x04/", "/\x05/", "/\x06/", "/\x07/", "/\x08/", "/\x09/", "/\x0A/", "/\x0B/","/\x0C/","/\x0D/", "/\x0E/", "/\x0F/", "/\x10/", "/\x11/", "/\x12/","/\x13/","/\x14/","/\x15/", "/\x16/", "/\x17/", "/\x18/", "/\x19/","/\x1A/","/\x1B/","/\x1C/","/\x1D/", "/\x1E/", "/\x1F/"), array("\u0000", "\u0001", "\u0002", "\u0003", "\u0004", "\u0005", "\u0006", "\u0007", "\u0008", "\u0009", "\u000A", "\u000B", "\u000C", "\u000D", "\u000E", "\u000F", "\u0010", "\u0011", "\u0012", "\u0013", "\u0014", "\u0015", "\u0016", "\u0017", "\u0018", "\u0019", "\u001A", "\u001B", "\u001C", "\u001D", "\u001E", "\u001F"), $input);
        return strip_tags(utf8_encode($str));
    }
    
    
    
    protected function cleanHtml2($input) {
        //$input= preg_replace("/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F\0xa4\0xa0\0xcb]/", "", $input);
        $str = str_ireplace(array("\u0000", "\u0001", "\u0002", "\u0003", "\u0004", "\u0005", "\u0006", "\u0007", "\u0008", "\u0009", "\u000A", "\u000A", "\u000B", "\u000C", "\u000D", "\u000E", "\u000F", "\u0010", "\u0011", "\u0012", "\u0013", "\u0014", "\u0015", "\u0016", "\u0017", "\u0018", "\u0019", "\u001A", "\u001B", "\u001C", "\u001D", "\u001E", "\u001F"), array(""), $input);
        return trim($str);
    }
    
    

    /**
     * To drop the indexes--refer to extending class for usage
     */
    protected function dropsearchindexesAction() {
        $objSolr = new Quikr_SolrIndex();
        $objSolr->setIndexingUrl($this->indexingUrl);
        $objSolr->dropIndex();
        unset($objSolr);
        exit;
    }
    
    /**
     *
     * @param type $errorDetailArray = array("error_no","section_name")
     * @return type 
     */
    protected function displayError($errorDetailArray) {
        if($errNo) {
            switch($errNo) {
                
                
            }
        } else return false;
    }
    
    /**
     * To get no of hits for an ad
     */
    protected function getNoOfVisitors($adId) {
        $sql = "SELECT tpc_hits FROM babel_hits WHERE tpc_id = ".$adId;
        $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get("dbconnection"), $sql);
        $objStmt->execute();
        $items = $objStmt->fetchAll();
        if(empty($items[0]["tpc_hits"])) return "0";
        else return $items[0]["tpc_hits"];
    }
    
    
    /**
     * will convert any timestamp in format 2011-12-26T14:40:16Z
     * @param type $epoch
     * @return type 
     */
    protected function convertToUTC($epoch) {
        return date("Y-m-d\TH:i:s\Z",$epoch);
    }
    
    public static function getNumbers($value){
    	$val = preg_replace('/[^\d.]/','', trim((string)$value));
    	
    	return ($val?$val:0);
    }
    
    /**
     * Will use the WURFL library to distinguish between Mobile|Web
     * @param type $str
     * @return string 
     */
    protected function parseUserAgent($str) {
        $wurflManager = Zend_Registry::get("WURFL_MNGR");
        $requestingDevice = $wurflManager->getDeviceForUserAgent($str);
        $is_wireless = ($requestingDevice->getCapability('is_wireless_device') == 'true');
        return $is_wireless ? "Mobile" : "Web";
    }
    
    protected function logIndexingEvents($logMsg) {
        $this->logger->log($logMsg, Zend_Log::INFO);
    }
    
    protected function insertSolrCount() {
        $params = "select?wt=json&rows=0";
        $url = $this->indexingUrl.$params."&q=";
        $past1 = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j")-$this->commandArgs[2],date("Y"))));
        $now = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j"),date("Y"))));
        $past1url = "";
        $objSolr = new Quikr_SolrIndex();
        $objSolr->setIndexingUrl($this->indexingUrl);
        $objSolr->commit();
        switch($this->section) {
            case "ads":
                    $past1url = urlencode("remapped:[".$past1." TO ".$now."]");
                break;
            
            case "deleted_ads":
                    $past1url = urlencode("remapped:[".$past1." TO ".$now."] AND ad_status:Admin deleted");
                break;

            case "reply":                        
                    $past1url = urlencode("rpl_createdTime:[".$past1." TO ".$now."]");
                    
                break;
            case "alert":
                switch($this->commandArgs[1]) {
                    case "NEWEST":
                            $past1url = urlencode("creation_date:[".$past1." TO ".$now."]");
                        break;
                    case "UNSUBSCRIBE":
                            $past1url = urlencode("unsubscribe_date:[".$past1." TO ".$now."] AND status:2");
                        break;
                }
                break;
            case "search":
                    $past1url = urlencode("search_date:[".$past1." TO ".$now."]");
                    
                break;
            case "users":
                    $past1url = urlencode("last_updated_date:[".$past1." TO ".$now."]");
                    
                break;
            case "reply_with_ads":
                    $past1url = urlencode("rpl_createdTime:[".$past1." TO ".$now."]");
                break;
            case "premiumads":
                    switch($this->commandArgs[1]) {
                        case "REMAPPED":
                                // minus 2 days
                                $past1url = urlencode("premiumads_ad_order_remapped_date:[".$past1." TO ".$now."] AND ((premiumads_product_type:Ad) OR (premiumads_product_type:VolumeDiscount))");
                            break;
                        case "NEWEST":
                                $past1url = urlencode("premiumads_ad_order_created_date:[".$past1." TO ".$now."] AND ((premiumads_product_type:Ad) OR (premiumads_product_type:VolumeDiscount))");
                            break;
                        case "NEWESTUPDATE":
                                $past1url = urlencode("premiumads_ad_order_updated_date:[".$past1." TO ".$now."] AND ((premiumads_product_type:Ad) OR (premiumads_product_type:VolumeDiscount))");
                            break;
                        case "REFUNDED":
                                $past1url = urlencode("premiumads_refund_date:[".$past1." TO ".$now."] AND ((premiumads_product_type:Ad) OR (premiumads_product_type:VolumeDiscount))");
                            break;
                        case "PAIDTOFREE":
                                $past1url = urlencode("premiumads_order_convert_to_free_date:[".$past1." TO ".$now."] AND ((premiumads_product_type:Ad) OR (premiumads_product_type:VolumeDiscount))");
                            break;
                        case "CHECKAUTORENEW":
                                $past1url = urlencode("premiumads_auto_renew_on_date:[".$past1." TO ".$now."] AND ((premiumads_product_type:Ad) OR (premiumads_product_type:VolumeDiscount))");
                            break;
                        case "UNCHECKAUTORENEW":
                                $past1url = urlencode("premiumads_auto_renew_off_date:[".$past1." TO ".$now."] AND ((premiumads_product_type:Ad) OR (premiumads_product_type:VolumeDiscount))");
                            break;
                        case "EXPIREDPACKAD":
                                $past1url = urlencode("premiumads_vdu_last_updated_date:[".$past1." TO ".$now."] AND (premiumads_product_type:Ad)");
                            break;
                        case "EXPIREDPACK":
                                $past1url = urlencode("premiumads_vdu_last_updated_date:[".$past1." TO ".$now."] AND (premiumads_product_type:VolumeDiscount)");
                            break;
                        default:
                            break;
                    }
                break;
            
                case "bgs":
                        $past1url = urlencode("lead_date:[".date("Y-m-d\TH:i:s\Z",$past1)." TO ".date("Y-m-d\TH:i:s\Z",$now)."]");
                    break;
            default:
                break;
        }
        
        try {
            
            $obj2 = new Utility_SolrQueryAnalyzer($url.$past1url,__FILE__.' at line '.__LINE__);

            $data2 = $obj2->init();

            //if(!empty($data2)) {
                $xmlData2 = json_decode($data2);
                $count = (int) $xmlData2->response->numFound;
  
                $sql = 'UPDATE 
                            data_history 
                        SET 
                            solr_query="'.$url.$past1url.'", 
                            solr_count="'.$count.'", 
                            insert_time_solr="'.date("Y-m-d H:i:s").'" 
                        WHERE 
                            core="'.$this->section.'" AND 
                            indexing_flag="'.$this->commandArgs[1].'" AND 
                            indexing_day="'.date("Y-m-d").'" AND 
                            id='.$this->lastInsertIdForDataHistory;
                //echo $sql;
                $stmt = new Zend_Db_Statement_Pdo(Zend_Registry::get("authDbConnection"), $sql); 
                $stmt->execute();
                unset($url); unset($past1url);
            //}
        } catch(Exception $e) {
            trigger_error($e->getMessage());
        }
        
    }
    
    protected function republish($id) {
        if ($this->isRunTimeIndexing) {
            switch ($this->section) {
                case "ads":
                case "deleted_ads":
                    $obj = new Rabbitmq_Publisher_Ad(STRING_RABBITMQ_HOST,"adtosolr_x");
                    $obj->publish(serialize($id));
                    unset($obj);
                    break;
                case "users":
                    $obj = new Rabbitmq_Publisher_User(STRING_RABBITMQ_HOST,"usertosolr_x");
                    $obj->publish(serialize($id));
                    unset($obj);
                    break;
                default:
                    break;
            }
        }
        
    }
    

}
