<?php

class AdsController extends Zend_Controller_Action {


    public $data;
    public $summarizedData;
    public $separator = "|";
    public $sectionName = 'ads';

    public function indexAction() {

        //print_r($this->_request->getPost());
        if($this->getRequest()->isPost()) {
            $posts = $this->getRequest()->getPost(); 
            $isvalidPost = $this->validateDates($posts);
            if($isvalidPost === true) {
                $isSummarize = $this->getRequest()->getParam('show_summarize');
                if($isSummarize == 'on') {
                    $status = $this->validateSummarize($posts);
                    if($status === true) {
                        $this->view->summarizedData = $this->getDateItems($posts['ads_summarize_interval_of'][0],$posts);
                        if($posts['is_export_request'] == 'yes') {
                            $str = "Count".$this->separator."Period\n";
                            foreach($this->view->summarizedData as $key => $val) {
                                    $str .= $val['count'].$this->separator.$val['date']."\n";
                            }
                            $this->downloadCSV($str);
                        }
                    } else {
                        $this->view->summarizeError = $status;
                    }
                } else {
                    
                    if($posts['is_export_request'] == 'yes') {
                        $cacheKey = md5(serialize($posts));
                        
                        //if the file is already present,send it
                        $fileName = $this->sectionName.'_'.date('d-m-Y',strtotime('now')).'_'.$cacheKey.'.csv.zip';
                        $this->downloadExistingCSV($fileName);
                        
                       
                        
                        $obj2 = new Model_AdsSolr($posts);
                        $obj2->solrUrl = SOLR_META_QUERY_SLAVE_ADS;
                        $obj2->start = 0;
                        $obj2->rows = 0;
                        if($_GET["show_matching"] == 1) {
                            $obj2->rows = 100;
                        }
                        
                        $obj2->fl = 'id';
                        $obj2->getResults();
                        $max = $obj2->totalRecordsFound;
                        unset($obj2);

                        for($i=0;$i<=$max;$i=$i+100000) {
                            $obj3 = new Model_AdsSolr($posts);
                            $obj3->solrUrl = SOLR_META_QUERY_SLAVE_ADS;
                            $obj3->rows = $max;
                            $obj3->getResults(true);
                            unset($obj3);
                        }
                        
                        
                        
                    } else {
                        $obj2 = new Model_AdsSolr($posts);
                        $obj2->solrUrl = SOLR_META_QUERY_SLAVE_ADS;
                        $pageNumber = $this->getRequest()->getParam('start_rows');
                        $obj2->start = $pageNumber -1;
                        $obj2->getResults();

                        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Null($obj2->totalRecordsFound));
                        $paginator->setItemCountPerPage(MAX_RESULTS_PER_PAGE);

                        $paginator->setCurrentPageNumber(($pageNumber+(MAX_RESULTS_PER_PAGE - 1)) / MAX_RESULTS_PER_PAGE);
                        $this->view->paginator = $paginator;
                        $this->view->requestUrl = $obj2->finalUrl;
                        //print_r($obj2->columnsToShow);
                        $this->prepareRecordset($obj2->columnsToShow);
                        //unset($obj2);
                    }
                }
            } else {
                $this->view->summarizeError = $isvalidPost;
            }

            
        }
        $this->view->posts = $posts;
        $this->renderScript('ads/index.phtml');
    }


    protected function validateDates($posts) {
    	//from date  should always be lesser than or equal to 'to' date

    	//create date
    	if(!empty($posts['ads_filter_createdate_from']) &&
                !empty($posts['ads_filter_createdate_to'])) {
            $from = strtotime($posts['ads_filter_createdate_from']);
            $to = strtotime($posts['ads_filter_createdate_to']);
            if($from > $to) {
            	return INVALID_DATE_ERROR;
            }
        }

        //last update date
        if(!empty($posts['ads_filter_adlastupdate_from']) &&
                !empty($posts['ads_filter_adlastupdate_to'])) {
            $from = strtotime($posts['ads_filter_adlastupdate_from']);
            $to = strtotime($posts['ads_filter_adlastupdate_to']);
            if($from > $to) {
            	return INVALID_DATE_ERROR;
            }
        }


        //expire date
        if(!empty($posts['ads_filter_expiretime_from']) &&
                !empty($posts['ads_filter_expiretime_to'])) {
            $from = strtotime($posts['ads_filter_expiretime_from']);
            $to = strtotime($posts['ads_filter_expiretime_to']);
            if($from > $to) {
            	return INVALID_DATE_ERROR;
            }
        }

        //delete date
        if(!empty($posts['ads_filter_addeletedate_from']) &&
                !empty($posts['ads_filter_addeletedate_to'])) {
            $from = strtotime($posts['ads_filter_addeletedate_from']);
            $to = strtotime($posts['ads_filter_addeletedate_to']);
            if($from > $to) {
            	return INVALID_DATE_ERROR;
            }
        }

        //repost date
        if(!empty($posts['ads_filter_reposttime_from']) &&
                !empty($posts['ads_filter_reposttime_to'])) {
            $from = strtotime($posts['ads_filter_reposttime_from']);
            $to = strtotime($posts['ads_filter_reposttime_to']);
            if($from > $to) {
            	return INVALID_DATE_ERROR;
            }
        }
        
        
        //first created date
        if(!empty($posts['ads_filter_first_created_from']) &&
                !empty($posts['ads_filter_first_created_to'])) {
            $from = strtotime($posts['ads_filter_first_created_from']);
            $to = strtotime($posts['ads_filter_first_created_to']);
            if($from > $to) {
            	return INVALID_DATE_ERROR;
            }
        }
        

    	return true;
    }


    protected function downloadExistingCSV($fileName) {
        $filePath = BASE_PATH_CSV.'/'.$fileName;
        if(!file_exists($filePath)) {
            return false;
        } else {
            header("Content-type: application/zip");
            header("Content-Disposition: attachment; filename=".$fileName);
            header("Pragma: no-cache");
            header("Expires: 0");
            readfile($filePath); 
            exit;
        }
    }
    
    
    
    //create csv file for download
    protected function downloadCSV($str='',$cacheKey='') {
    	$fileName = $this->sectionName.'_'.date('d-m-Y',strtotime('now')).'_'.$cacheKey.'.csv';
    	$filePath = BASE_PATH_CSV.'/'.$fileName;
    	$handle = fopen($filePath,'w');
    	fwrite($handle,$str);
    	fclose($handle);
        
    	$csvFileLink = BASE_URL.'/assets/csv/'.$fileName;
    	header("Content-type: application/csv");
        header("Content-Disposition: attachment; filename=".$fileName);
        header("Pragma: no-cache");
        header("Expires: 0");
        readfile($filePath);
    	exit;
    }


    //validate if proper summarize options are chosen
    public function validateSummarize($posts) {
        if(!empty($posts['ads_summarize_by_count_of']) &&
        !empty($posts['ads_summarize_for_ad_date']) &&
        !empty($posts['ads_summarize_interval_of'])) {


        //now check if the dates are not blank and if present should be proper dates

            switch($posts['ads_summarize_for_ad_date'][0]) {
                case 'ad_create_date':
                    if(!$this->validateDate($posts['ads_filter_createdate_from']) ||
                    !$this->validateDate($posts['ads_filter_createdate_to'])) {
                        return 'Please select valid date ranges for Create date';
                    }
                break;

                case 'ad_last_update_date':
                    if(!$this->validateDate($posts['ads_filter_adlastupdate_from']) ||
                    !$this->validateDate($posts['ads_filter_adlastupdate_to'])) {
                        return 'Please select valid date ranges for Last update date';
                    }
                break;

                case 'ad_delete_date':
                    if(!$this->validateDate($posts['ads_filter_addeletedate_from']) ||
                    !$this->validateDate($posts['ads_filter_addeletedate_to'])) {
                        return 'Please select valid date ranges for Delete date';
                    }
                break;

                case 'ad_expire_time':
                    if(!$this->validateDate($posts['ads_filter_expiretime_from']) ||
                    !$this->validateDate($posts['ads_filter_expiretime_to'])) {
                        return 'Please select valid date ranges for Expire date';
                    }
                break;

                case 'ad_repost_time':
                    if(!$this->validateDate($posts['ads_filter_reposttime_from']) ||
                    !$this->validateDate($posts['ads_filter_reposttime_to'])) {
                        return 'Please select valid date ranges for Repost date';
                    }
                break;
                
                
                case 'ad_first_created_date':
                    if(!$this->validateDate($posts['ads_filter_first_created_from']) ||
                    !$this->validateDate($posts['ads_filter_first_created_to'])) {
                        return 'Please select valid date ranges for Ad First create date';
                    }
                break;
                
            }


            return true;
        }
        return 'Please select fields for summarize option';
    }


    protected function validateDate($date,$format='dd-mm-yyyy') {
    	$validator = new Zend_Validate_Date(array('format' => $format));
    	return $validator->isValid($date);

    }
    
    public function getDatesForThisPeriod($filterDate,$posts) {
        
        switch($filterDate) {
            case 'ad_create_date':
                return array('from' => $posts['ads_filter_createdate_from'], 
                    'to' => $posts['ads_filter_createdate_to'],
                    'caption' => 'Create Date',
                    'field_to_fetch' => 'ad_created_date');
            break;
            case 'ad_last_update_date':
                return array('from' => $posts['ads_filter_adlastupdate_from'],
                    'to' => $posts['ads_filter_adlastupdate_to'],
                    'caption' => 'Update Date',
                    'field_to_fetch' => 'ad_modified_date');
            break;

            case 'ad_delete_date':
                return array('from' => $posts['ads_filter_addeletedate_from'],
                    'to' => $posts['ads_filter_addeletedate_to'],
                    'caption' => 'Delete Date',
                    'field_to_fetch' => 'ad_delete_date');
            break;

            case 'ad_expire_time':
                return array('from' => $posts['ads_filter_expiretime_from'],
                    'to' => $posts['ads_filter_expiretime_to'],
                    'caption' => 'Expire Date',
                    'field_to_fetch' => 'expired_time');
            break;

            case 'ad_repost_time':
                return array('from' => $posts['ads_filter_reposttime_from'],
                    'to' => $posts['ads_filter_reposttime_to'],
                    'caption' => 'Repost Date',
                    'field_to_fetch' => 'repost_time');
            break;
        
            case 'ad_first_created_date':
                return array('from' => $posts['ads_filter_first_created_from'],
                    'to' => $posts['ads_filter_first_created_to'],
                    'caption' => 'Ad First Created Date',
                    'field_to_fetch' => 'tpc_firstcreated');
            break;
        
        }
    }
    
    /**
     * TODO: implement logic
     * @param type $posts
     * @return type 
     */
    protected function getFacetField($posts) {
        if(in_array("poster_count",$posts['ads_summarize_by_count_of'])){
            return "poster_id_i";
        } else return false;
    }
    
    
    public function getDateItems($interval,$posts) {
        
        $dates = $this->getDatesForThisPeriod($posts['ads_summarize_for_ad_date'][0], $posts);
        $caption = $dates['caption'];
        $fieldToFetch = $dates['field_to_fetch'];
        $facetField = $this->getFacetField($posts);
        
        
        switch($interval) {
            case 'daily':
                $fromInMicro = $dates['from'];
                $toInMicro = strtotime($dates['to']);

                $current = strtotime($dates['from']);

                $i = 0;
                while($current <= $toInMicro) {
                    //echo date('d-m-Y',$current);
                    $obj3 = new Model_AdsSolr($posts);
                    $obj3->solrUrl = SOLR_META_QUERY_SLAVE_ADS;
                    $obj3->fl = $fieldToFetch;
                    $from = $current;
                    $to = strtotime(date('d-m-Y',$current).' +1 day');
                    $summarizedData[$i]['count'] = $obj3->getfacetCountForSummarize($from,$to,$fieldToFetch,$facetField); //just send from date;
                    $summarizedData[$i]['date'] = date('d-m-Y',$current);

                    $current = strtotime(date('d-m-Y',$current).' +1 day');
                    $i++;
                    unset ($obj3);
                }

            break;
            case 'weekly':
                //$weeks = $this->getWeeksBetweenRange($dates['from'], $dates['to']);

                $fromInMicro = $dates['from'];
                $toInMicro = strtotime($dates['to']);

                $current = strtotime($dates['from']);

                $i = 0;
                while($current <= $toInMicro) {
                    $obj3 = new Model_AdsSolr($posts);
                    $obj3->solrUrl = SOLR_META_QUERY_SLAVE_ADS;
                    $obj3->fl = $fieldToFetch;

                    $from = $current;
                    $to = strtotime(date('d-m-Y',$current).' +1 week');

                    $summarizedData[$i]['count'] = $obj3->getfacetCountForSummarize($from,$to,$fieldToFetch,$facetField); //just send from date;
                    $summarizedData[$i]['date'] = date('d-m-Y',$current).' To '.date('d-m-Y',strtotime(date('d-m-Y',$current).'+1 week')-TO_DATE_INCREMENT);


                    $current = strtotime(date('d-m-Y',$current).' +1 week');
                    $i++;
                    unset ($obj3);
                }

            break;
            case 'monthly':


                $fromInMicro = $dates['from'];
                $toInMicro = strtotime($dates['to']);
                $current = strtotime($dates['from']);

                $i = 0;
                while($current <= $toInMicro) {
                    $obj3 = new Model_AdsSolr($posts);
                    $obj3->solrUrl = SOLR_META_QUERY_SLAVE_ADS;
                    $obj3->fl = $fieldToFetch;

                    $from = $current;
                    $to = strtotime(date('d-m-Y',$current).' +1 month');

                    $summarizedData[$i]['count'] = $obj3->getfacetCountForSummarize($from,$to,$fieldToFetch,$facetField); //just send from date;

                    $summarizedData[$i]['date'] = date('m-Y',$current).' To '.date('m-Y',strtotime(date('d-m-Y',$current).'+1 month')-TO_DATE_INCREMENT);

                    $current = strtotime(date('d-m-Y',$current).' +1 month');
                    $i++;
                    unset ($obj3);
                }


            break;
            case 'yearly':
                $fromInMicro = $dates['from'];
                $toInMicro = strtotime($dates['to']);
                $current = strtotime($dates['from']);

                $i = 0;
                while($current <= $toInMicro) {
                    //echo $current;
                    $obj3 = new Model_AdsSolr($posts);
                    $obj3->solrUrl = SOLR_META_QUERY_SLAVE_ADS;
                    $obj3->fl = $fieldToFetch;

                    $from = $current;
                    $to = strtotime(date('d-m-Y',$current).' +1 year');

                    $summarizedData[$i]['count'] = $obj3->getfacetCountForSummarize($from,$to,$fieldToFetch,$facetField); //just send from date;

                    $summarizedData[$i]['date'] = date('m-Y',$current).' To '.date('m-Y',strtotime(date('d-m-Y',$current).'+1 year')-TO_DATE_INCREMENT);

                    $current = strtotime(date('d-m-Y',$current).' +1 year');
                    $i++;
                    unset ($obj3);
                }
            break;
        }

        //print_r($summarizedData);
        return $summarizedData;
    }

     public function prepareRecordset($cols) {
        $this->view->columnsToShow = $cols;

    }

    public function timestampToDdmmyyyy($date) {
        return trim(date('d-m-Y',$date));
    }
    
}