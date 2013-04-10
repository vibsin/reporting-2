<?php
include('IndexingAbstract.php');
class Searchindexing extends IndexingAbstract  {


            protected $dbTableName = 'search_keyword_data';
            protected $section = 'search'; //used in the parent class
            protected $indexingUrl = SOLR_SEARCH_INDEXING_URL;
            protected $isIncrementalIndexing = false;
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
                        }
	
                        switch($runIndexingFor) {
                                case 'ALL':
                                    $this->sql = 'SELECT * FROM '.$this->dbTableName;
                                    $this->countSql = 'SELECT count(id) as "count" FROM '.$this->dbTableName;
                                        break;
                                case 'NEWEST':
                                        $past = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j")-$runInterval,date("Y"))));
                                        $now = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j"),date("Y"))));
                                        
                                        $this->sql = 'SELECT * FROM '.$this->dbTableName.'  WHERE fldTime BETWEEN FROM_UNIXTIME('.$past.') AND FROM_UNIXTIME('.$now.')';
                                        
                                        $this->countSql = 'SELECT count(id) as "count" FROM '.$this->dbTableName.' WHERE fldTime BETWEEN FROM_UNIXTIME('.$past.') AND FROM_UNIXTIME('.$now.')';
                                        $this->isMasterIndexingScript = true;
                                        
                                        break;
                                    
                                /** This will index data given in the below range. Please dont give very large gap between dates
                                 * usage:
                                 * /usr/local/php/bin/php Searchindexing.php DATE_RANGE FROM_DATE TO_DATE
                                 * 
                                 * FROM_DATE and TO_DATE should be of the format dd-mm-yyyy
                                 * 
                                 */
                                case 'DATE_RANGE':
                                    $this->isIncrementalIndexing = false;
                                    $past = strtotime($args[2]);
                                    $now = strtotime($args[3]);

                                    $this->sql = 'SELECT * FROM '.$this->dbTableName.'  
                                        WHERE fldTime BETWEEN FROM_UNIXTIME('.$past.') AND FROM_UNIXTIME('.$now.')';
                                        
                                        $this->countSql = 'SELECT count(id) as "count" FROM '.$this->dbTableName.'
                                        WHERE fldTime BETWEEN FROM_UNIXTIME('.$past.') AND FROM_UNIXTIME('.$now.')';

                                break;
                                    
                                    
                                case 'DROP':
                                    $this->dropsearchindexesAction();
                                    break;
                                default:
                                    echo "NOTHING TO DO!";
                                        break;
                        }

                        //set the threshhold
                        self::$threshold = $this->getMaxRecordsFromDB();
                        self::indexAction();
	    	} else {
	    		echo 'Please enter valid arguments'; die();
	    	}
	    }
	    

	    protected function initBuildingData() {
                
	        foreach(self::$data as $key => $val) {
	        	//first we fetch all golobal data for cat and subcat

                    extract($this->getGlobalData($val['fldCateId']));
	        	
	            self::$dataToIndex[self::$counter]['id'] = $val['id']; //done
                    
	            self::$dataToIndex[self::$counter]['search_date'] = strtotime($val['fldTime']); //done
                    
	            self::$dataToIndex[self::$counter]['remote_address'] = $val['fldRemoteAdd']; //done
                    self::$dataToIndex[self::$counter]['request_url'] = $val['fldRequestUrl']; //done
                    self::$dataToIndex[self::$counter]['hostname'] = $val['fldHost']; //done
                    self::$dataToIndex[self::$counter]['user_agent'] = $val['fldUserAgent']; //done
                    self::$dataToIndex[self::$counter]['keyword'] = $val['fldKeyword']; //done
                    
	            self::$dataToIndex[self::$counter]['city_id'] = $val['fldCityId']; //done
	            self::$dataToIndex[self::$counter]['city_name'] = $val['fldCity']; //done

	            self::$dataToIndex[self::$counter]['global_metacategory_id'] = $globalMetacatId; //done
	            self::$dataToIndex[self::$counter]['metacategory_id'] = $metacatId; //done
	            self::$dataToIndex[self::$counter]['metacategory_name'] = $metacatName; //done
                    
	            self::$dataToIndex[self::$counter]['subcategory_id'] = $subcatId; //done
	            self::$dataToIndex[self::$counter]['global_subcategory_id'] = $globalSubcatId; //done
	            self::$dataToIndex[self::$counter]['subcategory_name'] = $subcatName; // done
                    
	            self::$dataToIndex[self::$counter]['no_of_results'] = $val['fldResult'];//done
	            self::$dataToIndex[self::$counter]['user_id'] = $val['fldUserId']; //done
                    //when this got indexed
                    self::$dataToIndex[self::$counter]['data_indexed_time'] = $this->convertToUTC(time()); //done
	    		
                        //print_r(self::$dataToIndex); exit;
	            self::$counter++;
	        }
	
	
	    }


            protected function initiateUpdatingOfDepenedantCores() {
                return;
            }

	}
	
	
	
	//get command line arguments
	$args = $argv;
	$objIndexing = new Searchindexing();
	$objIndexing->init($args);