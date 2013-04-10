<?php
include('IndexingAbstract.php');

class Vdindexing extends IndexingAbstract {

    protected $dbTableName = 'babel_volume_discount';
    protected $section = 'vd'; //used in the parent class
    protected $indexingUrl = SOLR_VD_INDEXING_URL;
    protected $isIncrementalIndexing = false;

    protected $isCalledFromOtherScript = false;

    public function  __destruct() {
        parent::__destruct();
    }

	    
    public function init($args) {
        if(!empty($args)) {
            $runAlertFor=$args[1];
            
            if(isset($args[2])) {
                $runInterval = $args[2];
                $this->isIncrementalIndexing = true;
            }

            switch($runAlertFor) {
                case 'ALL':
                    $this->sql = 'SELECT * FROM '.$this->dbTableName;
                    $this->countSql = 'SELECT count(id) as "count" FROM '.$this->dbTableName;
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

            $this->indexAction();

        } else {
            echo 'Please enter valid arguments'; die();
        }
    }
    
    
    
    
    
    protected function parseAdstyle($state) {
        if($state == '' || $state == null) {
            return '';
        } else {
            switch(trim($state)) {
                case "T":
                    return "Top";
                    break;
                
                case "H":
                    return "Highlight";
                    break;
                
                case "HT":
                    return "Top-Highlight";
                    break;
                
                default:
                    return $state;
                    break;
            }
        }
    }
    
    
    /**
     *
     * posttype
        S
        M
     */
    protected function parsePostType($state) {
        if($state == '' || $state == null) {
            return '';
        } else {
            switch(trim($state)) {
                case "S":
                    return "System";
                    break;
                
                case "M":
                    return "Manual";
                    break;

                default:
                    return $state;
                    break;
            }
        }
    }
    
    /**
     *status 1 - package is enable , 0- package is disable
            1
            0
     * 
     */
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
    
    
    
    
    protected function initBuildingData() {

        foreach(self::$data as $key => $val) {
            
            /**** indexing all fields from babel_volume_discount START***/

            self::$dataToIndex[self::$counter]['id'] = $val['id']; //done
            
            self::$dataToIndex[self::$counter]['premiumads_vd_id'] = $val['id']; //done
            self::$dataToIndex[self::$counter]['premiumads_vd_adstyle'] = $this->parseAdstyle($val['adstyle']); //done
            self::$dataToIndex[self::$counter]['premiumads_vd_category'] = $val['category']; //done
            self::$dataToIndex[self::$counter]['premiumads_vd_discount'] = ($val['discount'] > 0) ? $val['discount'] : 0.00; //done
            self::$dataToIndex[self::$counter]['premiumads_vd_amount'] = ($val['amount'] > 0) ? $val['amount'] : 0.00; //done
            self::$dataToIndex[self::$counter]['premiumads_vd_total_credit'] = $val['total_credit']; //done
            self::$dataToIndex[self::$counter]['premiumads_vd_status'] = $this->parseVduStatus($val['status']); //done
            self::$dataToIndex[self::$counter]['premiumads_vd_validity'] = $val['pack_validity']; //done
            self::$dataToIndex[self::$counter]['premiumads_vd_posttype'] = $this->parsePostType($val['posttype']); //done
            self::$dataToIndex[self::$counter]['premiumads_vd_telemarketer_name'] = $val['telemarketer_name']; //done
            self::$dataToIndex[self::$counter]['premiumads_vd_ro_name'] = $val['ro_name']; //done
            self::$dataToIndex[self::$counter]['premiumads_vd_telemarketer_tl_name'] = $val['telemarketer_tl_name']; //done
            self::$dataToIndex[self::$counter]['premiumads_vd_territory_manager_name'] = $val['territory_manager_name']; //done
            self::$dataToIndex[self::$counter]['premiumads_vd_previous_pack'] = $val['previous_pack']; //done
            
            
            //when this got indexed
            self::$dataToIndex[self::$counter]['data_indexed_time'] = $this->convertToUTC(time()); //done
            
            
            //print_r(self::$dataToIndex);exit;
            self::$counter++;
        }
    }

    protected function initiateUpdatingOfDepenedantCores() {
       return true;
        
    }

}
	
	
	
	//get command line arguments
	$args = $argv;

	//start indexing from here:
	$objIndexing = new Vdindexing();
	$objIndexing->init($args);


        
