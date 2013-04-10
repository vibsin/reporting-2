<?php
include('IndexingAbstract.php');

class Adsindexing extends IndexingAbstract {


    protected $dbTableName = 'babel_topic';
    protected $section = 'ads'; //used in the parent class
    protected $indexingUrl = SOLR_ADS_INDEXING_URL;

    public $allowed_attributes = null;
    protected $isIncrementalIndexing = false;
    protected $isCalledFromOtherScript = false;

    protected static $ausersToUpdate = array();
    protected static $areplyWithAdsToUpdate = array();
    protected $thresholdForIncremental;

    public function  __destruct() {
        parent::__destruct();
    }

    
	    
	    
    public function init($args) {
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
                        $this->isIncrementalIndexing = false;
                        $adId = $args[2];
                        $this->isCalledFromOtherScript = true;
                        $this->sql = 'SELECT tpc_id FROM '.$this->dbTableName.' WHERE tpc_id IN ('.$adId.')';
                        $this->countSql = '';
                        $this->thresholdForIncremental = $args[3];

                    break;
                
                /** This will index data given in the below range. Please dont give very large gap between dates
                 * usage:
                 * /usr/local/php/bin/php Adsindexing.php DATE_RANGE FROM_DATE TO_DATE
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
                
            } else {
                self::$dataToIndex[self::$counter]["id"] = $val['tpc_id'];
            }
            
            self::$dataToIndex[self::$counter]['no_of_visitors'] = $this->getNoOfVisitors($val['tpc_id']); //done
            //when this got indexed
            self::$dataToIndex[self::$counter]['data_indexed_time'] = $this->convertToUTC(time()); //done
            //print_r(self::$dataToIndex);exit;
            self::$counter++;
        }

    }
    
    public function initiateUpdatingOfDepenedantCores() {
        return true;
    }


    


    
}
	
	
	
	//get command line arguments
	$args = $argv;
	
	//start indexing from here:
	$objIndexing = new Adsindexing();
	$objIndexing->init($args);
