<?php

class UserController extends Zend_Controller_Action {


    public $data;
    public $summarizedData;
    public $separator = "|";
    public $sectionName = 'user';

    public function indexAction() {

        
        if($this->getRequest()->isPost()) {
            $posts = $this->getRequest()->getPost();
            $isvalidPost = $this->validateDates($posts);
            if($isvalidPost === true) {
                $isSummarize = $this->getRequest()->getParam('show_summarize');
                if($isSummarize == 'on') {
                    $status = $this->validateSummarize($posts);
                    if($status === true) {
                        $this->view->summarizedData = $this->getDateItems($posts['user_summarize_intervals_of'][0],$posts);
                        if($posts['is_export_request'] == 'yes') {
                            $str = "User Count".$this->separator."Period\n";
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
                        //$cache = new Rediska_Key($cacheKey);
                        
                        //if the file is already present,send it
                        $fileName = $this->sectionName.'_'.date('d-m-Y',strtotime('now')).'_'.$cacheKey.'.csv.zip';
                        $this->downloadExistingCSV($fileName);
                        
                        //else we check cache
                        //$result = null; //$cache->getValue();
                        //if($result === null) { 
                            $obj2 = new Model_UserSolr($posts);
                            $obj2->start = 0;
                            $obj2->rows = 0;
                            $obj2->fl = 'id';
                            $obj2->getResults();
                            $max = $obj2->totalRecordsFound;
                            unset($obj2);
                            
                            for($i=0;$i<=$max;$i=$i+100000) {
                            
                                $obj3 = new Model_UserSolr($posts);
                                //$obj3->start = $i;
                                $obj3->rows = $max;
                                $obj3->getResults(true);
                                unset($obj3);
                            }
                            //$obj3->parseXmlDataForExcel(); 
                            //exit;
                            //$result = $obj3->columnsToShow;
                            //echo "loaded from Solr";exit;
                            //$cache->setValue($obj3->columnsToShow);
                        //} else {
                            //echo "loaded from cache";exit;
                            //$result = $cache->getValue();
                        //}

                        //$str = $this->generateExcelString($result);
                        
                        //unset($obj3);
                        //$this->downloadCSV($str,$cacheKey);

                    } else {
                        $obj2 = new Model_UserSolr($posts);
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
        $this->renderScript('user/index.phtml');
    }


    protected function validateDates($posts) {
    	//from date  should always be lesser than or equal to 'to' date

    	//create date
    	if(!empty($posts['user_filter_regdate_from']) &&
                !empty($posts['user_filter_regdate_to'])) {
            $from = strtotime($posts['user_filter_regdate_from']);
            $to = strtotime($posts['user_filter_regdate_to']);
            if($from > $to) {
            	return INVALID_DATE_ERROR;
            }
        }

        //last login date
        if(!empty($posts['user_filter_lastlogin_from']) &&
                !empty($posts['user_filter_lastlogin_to'])) {
            $from = strtotime($posts['user_filter_lastlogin_from']);
            $to = strtotime($posts['user_filter_lastlogin_to']);
            if($from > $to) {
            	return INVALID_DATE_ERROR;
            }
        }

    	return true;
    }

    //generate columns
    protected function generateExcelString($columns) {
    	$str = '';

    	if(!empty($columns['columns'])) {
            sort($columns['columns'],SORT_STRING);
            $str .= "Sr. No.".$this->separator."Item Id".$this->separator;
            foreach($columns['columns'] as $key => $val) {
                $str .= '"'.$val.'"'.$this->separator;
            }

            $str .= "\n";
    	}

    	if(!empty($columns['data'])) {
            $srNo = 1;
            foreach($columns['data'] as $key => $val) {
                $str .= $srNo.$this->separator.$val['Item Id'].$this->separator;
                foreach($columns['columns'] as $k => $v) {
                    $str .= '"'.$val[$v].'"'.$this->separator;
                }

                $str .= "\n";
                $srNo++;
            }
    	}

    	return $str;
    }
    
    protected function downloadExistingCSV($fileName) {
        $filePath = BASE_PATH_CSV.'/'.$fileName;
        if(!file_exists($filePath)) {
            return false;
        } else {
            header("Content-type: application/csv");
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
        if(!empty($posts['user_summarize_by_count_of']) &&
        !empty($posts['user_summarize_for_date']) &&
        !empty($posts['user_summarize_intervals_of'])) {


        //now check if the dates are not blank and if present should be proper dates

            switch($posts['user_summarize_for_date'][0]) {
                case 'user_reg_date':
                    if(!$this->validateDate($posts['user_filter_regdate_from']) ||
                    !$this->validateDate($posts['user_filter_regdate_to'])) {
                        return 'Please select valid date ranges for Registration date';
                    }
                break;

                case 'user_last_login_date':
                    if(!$this->validateDate($posts['user_filter_lastlogin_from']) ||
                    !$this->validateDate($posts['user_filter_lastlogin_to'])) {
                        return 'Please select valid date ranges for Last login date';
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
            case 'user_reg_date':
                return array('from' => $posts['user_filter_regdate_from'],
                    'to' => $posts['user_filter_regdate_to'],
                    'caption' => 'Registration Date',
                    'field_to_fetch' => 'registration_date');
            break;
            case 'user_last_login_date':
                return array('from' => $posts['user_filter_lastlogin_from'],
                    'to' => $posts['user_filter_lastlogin_to'],
                    'caption' => 'Last login Date',
                    'field_to_fetch' => 'last_login_date');
            break;
        }
    }


    public function getDateItems($interval,$posts) {

        $dates = $this->getDatesForThisPeriod($posts['user_summarize_for_date'][0], $posts);
        $caption = $dates['caption'];
        $fieldToFetch = $dates['field_to_fetch'];


        switch($interval) {
            case 'daily':
                $fromInMicro = $dates['from'];
                $toInMicro = strtotime($dates['to']);

                $current = strtotime($dates['from']);

                $i = 0;
                while($current <= $toInMicro) {
                    //echo date('d-m-Y',$current);
                    $obj3 = new Model_UserSolr($posts);
                    $obj3->fl = $fieldToFetch;
                    $from = $current;
                    $to = strtotime(date('d-m-Y',$current).' +1 day');
                    $summarizedData[$i]['count'] = $obj3->getfacetCountForSummarize($from,$to,$fieldToFetch); //just send from date;
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
                    $obj3 = new Model_UserSolr($posts);
                    $obj3->fl = $fieldToFetch;

                    $from = $current;
                    $to = strtotime(date('d-m-Y',$current).' +1 week');

                    $summarizedData[$i]['count'] = $obj3->getfacetCountForSummarize($from,$to,$fieldToFetch); //just send from date;
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
                    $obj3 = new Model_UserSolr($posts);
                    $obj3->fl = $fieldToFetch;

                    $from = $current;
                    $to = strtotime(date('d-m-Y',$current).' +1 month');

                    $summarizedData[$i]['count'] = $obj3->getfacetCountForSummarize($from,$to,$fieldToFetch); //just send from date;

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
                    $obj3 = new Model_UserSolr($posts);
                    $obj3->fl = $fieldToFetch;

                    $from = $current;
                    $to = strtotime(date('d-m-Y',$current).' +1 year');

                    $summarizedData[$i]['count'] = $obj3->getfacetCountForSummarize($from,$to,$fieldToFetch); //just send from date;

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