<?php
class AlertsController extends Zend_Controller_Action {

    public $data;
    public $summarizedData;
    public $separator = "|";
    public $sectionName = 'alerts';
    
    public function indexAction() {
        $posts = $this->getRequest()->getPost();
        if($this->getRequest()->isPost()) {
            $posts = $this->getRequest()->getPost();	//print_r($posts);	
            $isvalidPost = $this->validateDates($posts);
            if($isvalidPost === true) {
                
                //first check if summarize was selected
                $isSummarize = $this->getRequest()->getParam('show_summarize');

                if($isSummarize == 'on') {

                    //check if required fields are present. most importantly chekc dates
                    $status = $this->validateSummarize($posts);

                    if($status === true) {

                        $this->view->summarizedData = $this->getDateItems($posts['alerts_summarize_intervals_of'][0],$posts);

                        if($posts['is_export_request'] == 'yes') {
                            //build summarize excel 
                            //generate string for excel file

                            $str = "Alert Count".$this->separator."Period\n";

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
                            
                        $obj2 = new Model_AlertsSolr($posts);
                        $obj2->start = 0;
                        $obj2->rows = 0;
                        $obj2->fl = 'id';
                        $obj2->getResults();
                        $max = $obj2->totalRecordsFound;
                        unset($obj2);


                        for($i=0;$i<=$max;$i=$i+100000) {
                            $obj3 = new Model_AlertsSolr($posts);
                            $obj3->rows = $max;
                            $obj3->getResults(true);
                            unset($obj3);
                        }

                    } else {

                        $obj2 = new Model_AlertsSolr($posts);
                        //set the current page number default is 1
                        $pageNumber = $this->getRequest()->getParam('start_rows');
                        $obj2->start = $pageNumber - 1;

                        $obj2->getResults();   	
                        //paginator work.
                        $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Null($obj2->totalRecordsFound));
                        $paginator->setItemCountPerPage(MAX_RESULTS_PER_PAGE);
                        //will come from url

                        $paginator->setCurrentPageNumber(($pageNumber+(MAX_RESULTS_PER_PAGE - 1)) / MAX_RESULTS_PER_PAGE);
                        $this->view->paginator = $paginator;
                        $this->view->requestUrl = $obj2->finalUrl;
                        //print_r($obj2->columnsToShow);
                        $this->prepareRecordset($obj2->columnsToShow);
                    }
                }		
            } else {
                $this->view->summarizeError = $isvalidPost;
            }

            $this->view->posts = $posts;
            $this->renderScript('alerts/index.phtml');
        }

    }

	
    protected function validateDates($posts) {
    	//from date  should always be lesser than or equal to 'to' date
    	
    	//unsubscribe date
    	if(!empty($posts['alerts_filter_unsubscribedate_from']) &&
                !empty($posts['alerts_filter_unsubscribedate_to'])) {
            $from = strtotime($posts['alerts_filter_unsubscribedate_from']);
            $to = strtotime($posts['alerts_filter_unsubscribedate_to']);
            
            //if($from > time() || $to > time()) return FUTURE_DATE_ERROR;
            
            if($from > $to) {
            	//$this->view->summarizeError = INVALID_DATE_ERROR;
            	return INVALID_DATE_ERROR;
            }
        }
        
        //created date
        if(!empty($posts['alerts_filter_createdate_from']) &&
                !empty($posts['alerts_filter_createdate_to'])) {
            $from = strtotime($posts['alerts_filter_createdate_from']);
            $to = strtotime($posts['alerts_filter_createdate_to']);
            
            //if($from > time() || $to > time()) return FUTURE_DATE_ERROR;
            
            if($from > $to) {
            	//$this->view->summarizeError = INVALID_DATE_ERROR;
            	return INVALID_DATE_ERROR;
            }
        }
        
    	return true;
    }
    
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
            header("Content-type: application/zip");
            header("Content-Disposition: attachment; filename=".$fileName);
            header("Pragma: no-cache");
            header("Expires: 0");
            readfile($filePath); 
            exit;
        }
    }
    
    
    protected function downloadCSV($str='',$cacheKey='') {
    	//create the file
    	//$str = 'Vibhor, Singh';
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
    	
    	//return $csvFileLink;
    }
    
    
    /**
     * 
     * @param <type> $posts
     * @return <type> validation for summarize
     */
    public function validateSummarize($posts) {
    	//print_r($posts['alerts_summarize_for_date']);  exit;
        if(!empty($posts['alerts_summarize_by_count_of']) &&
                        !empty($posts['alerts_summarize_for_date']) &&
                        !empty($posts['alerts_summarize_intervals_of'])) {
                        	
                        	
        	//now check if the dates are not blank and if present should be proper dates

        	switch($posts['alerts_summarize_for_date'][0]) {
        		case 'alerts_create_date':
        			if(!$this->validateDate($posts['alerts_filter_createdate_from']) || 
        				!$this->validateDate($posts['alerts_filter_createdate_to'])) {
        				return 'Please select valid date ranges for Create date';
        			}
        			break;
        		case 'alerts_unsubscribe_date':
        			if(!$this->validateDate($posts['alerts_filter_unsubscribedate_from']) || 
        				!$this->validateDate($posts['alerts_filter_unsubscribedate_to'])) {
        				return 'Please select valid date ranges for Unsubscribe date';
        			}

        			break;	
        	}
        	
        	
            return true;
        }
        return 'Please select fields for summarize option';
    }

    
    /**
     * will validate date using Zend_Validate_Date
     *
     * @param unknown_type $date
     * @param unknown_type $format
     */
    protected function validateDate($date,$format='dd-mm-yyyy') {
    	$validator = new Zend_Validate_Date(array('format' => $format));
    	return $validator->isValid($date);
    	
    }
    
//    public function fetchDataForSummarize($posts) {
//        $summarizedData =
//
//        print_r($summarizedData); exit;
//    }

    protected function getFacetedData($posts,$items,$caption,$intervalCaption) {
        $summarizedData = array();
        $counter = count($items);
        
        for($i=0; $i< $counter; $i++) {

            
            $obj3 = new Model_AlertsSolr($posts);
            if($caption == 'Create Date') $obj3->fl = 'creation_date';
            else if ($caption == 'Unsubscribe Date') $obj3->fl = 'unsubscribe_date';

            $summarizedData[$i]['count'] = $obj3->getfacetCount($items[$i],$obj3->fl,$intervalCaption); //just send from date;

            if($intervalCaption == 'daily') $summarizedData[$i]['date'] = $items[$i];
            else if($intervalCaption == 'weekly') $summarizedData[$i]['date'] = $items[$i].' To '.date('d-m-Y',strtotime($items[$i].'+1 week'));
            else if($intervalCaption == 'monthly') $summarizedData[$i]['date'] = $items[$i].' To '.date('d-m-Y',strtotime($items[$i].'+1 month'));
            else if($intervalCaption == 'yearly') $summarizedData[$i]['date'] = $items[$i].' To '.date('d-m-Y',strtotime($items[$i].'+1 year'));

        }

        return $summarizedData;
    }




    public function getDateItems($interval,$posts) {

        $dates = $this->getDatesForThisPeriod($posts['alerts_summarize_for_date'][0], $posts);
        $caption = '';

        if($posts['alerts_summarize_for_date'][0] == 'alerts_create_date') {
            $caption = 'Create Date';
        } else if($posts['alerts_summarize_for_date'][0] == 'alerts_unsubscribe_date') {
            $caption = 'Unsubscribe Date';
        }

        switch($interval) {
            case 'daily':
                //$days = $this->getDaysBetweenRange($dates['from'], $dates['to']);

                $fromInMicro = $dates['from'];
                $toInMicro = strtotime($dates['to']);

                $current = strtotime($dates['from']);

                $i = 0;
                while($current <= $toInMicro) {
                    //echo $current;
                    $obj3 = new Model_AlertsSolr($posts);
                    if($caption == 'Create Date') $obj3->fl = 'creation_date';
                    else if ($caption == 'Unsubscribe Date') $obj3->fl = 'unsubscribe_date';

                    $from = $current;
                    $to = strtotime(date('d-m-Y',$current).' +1 day');

                    $summarizedData[$i]['count'] = $obj3->getfacetCountForSummarize($from,$to,$obj3->fl); //just send from date;

                    $summarizedData[$i]['date'] = date('d-m-Y',$current);
                    
                    $current = strtotime(date('d-m-Y',$current).' +1 day');
                    $i++;
                }
                
            break;
            case 'weekly':
                //$weeks = $this->getWeeksBetweenRange($dates['from'], $dates['to']);

                $fromInMicro = $dates['from'];
                $toInMicro = strtotime($dates['to']);

                $current = strtotime($dates['from']);

                $i = 0;
                while($current <= $toInMicro) {
                    $obj3 = new Model_AlertsSolr($posts);
                    if($caption == 'Create Date') $obj3->fl = 'creation_date';
                    else if ($caption == 'Unsubscribe Date') $obj3->fl = 'unsubscribe_date';

                    $from = $current;
                    $to = strtotime(date('d-m-Y',$current).' +1 week');

                    $summarizedData[$i]['count'] = $obj3->getfacetCountForSummarize($from,$to,$obj3->fl); //just send from date;
                    $summarizedData[$i]['date'] = date('d-m-Y',$current).' To '.date('d-m-Y',strtotime(date('d-m-Y',$current).'+1 week')-TO_DATE_INCREMENT);

                    
                    $current = strtotime(date('d-m-Y',$current).' +1 week');
                    $i++;
                    
                }

            break;
            case 'monthly':


                $fromInMicro = $dates['from'];
                $toInMicro = strtotime($dates['to']);
                $current = strtotime($dates['from']);

                $i = 0;
                while($current <= $toInMicro) {
                    //echo $current;
                    $obj3 = new Model_AlertsSolr($posts);
                    if($caption == 'Create Date') $obj3->fl = 'creation_date';
                    else if ($caption == 'Unsubscribe Date') $obj3->fl = 'unsubscribe_date';

                    $from = $current;
                    $to = strtotime(date('d-m-Y',$current).' +1 month');

                    $summarizedData[$i]['count'] = $obj3->getfacetCountForSummarize($from,$to,$obj3->fl); //just send from date;

                    $summarizedData[$i]['date'] = date('m-Y',$current).' To '.date('m-Y',strtotime(date('d-m-Y',$current).'+1 month')-TO_DATE_INCREMENT);

                    $current = strtotime(date('d-m-Y',$current).' +1 month');
                    $i++;
                }

                
            break;
            case 'yearly':
                $fromInMicro = $dates['from'];
                $toInMicro = strtotime($dates['to']);
                $current = strtotime($dates['from']);

                $i = 0;
                while($current <= $toInMicro) {
                    //echo $current;
                    $obj3 = new Model_AlertsSolr($posts);
                    if($caption == 'Create Date') $obj3->fl = 'creation_date';
                    else if ($caption == 'Unsubscribe Date') $obj3->fl = 'unsubscribe_date';

                    $from = $current;
                    $to = strtotime(date('d-m-Y',$current).' +1 year');

                    $summarizedData[$i]['count'] = $obj3->getfacetCountForSummarize($from,$to,$obj3->fl); //just send from date;

                    $summarizedData[$i]['date'] = date('m-Y',$current).' To '.date('m-Y',strtotime(date('d-m-Y',$current).'+1 year')-TO_DATE_INCREMENT);

                    $current = strtotime(date('d-m-Y',$current).' +1 year');
                    $i++;
                }
            break;
        }

        return $summarizedData;
    }

    public function getDatesForThisPeriod($filterDate,$posts) {

        switch($filterDate) {
            case 'alerts_create_date':
                return array('from' => $posts['alerts_filter_createdate_from'], 'to' => $posts['alerts_filter_createdate_to']);
            break;

            case 'alerts_unsubscribe_date':
                return array('from' => $posts['alerts_filter_unsubscribedate_from'], 'to' => $posts['alerts_filter_unsubscribedate_to']);
            break;

            default:
                //create date
                return array('from' => $posts['alerts_filter_createdate_from'], 'to' => $posts['alerts_filter_createdate_to']);
            break;
        }

    }

    public function getDaysBetweenRange($from, $to) {
        $fromInMicro = strtotime($from);
        $toInMicro = strtotime($to)+TO_DATE_INCREMENT;
        $items = array();
        $diff = round(($toInMicro - $fromInMicro) / 86400);
        $start = $fromInMicro;
        
        for($i=0; $i < $diff; $i++) {
            //echo $start.'<br />';
//            $items[$i]['date'] = date('d-m-Y',$start);
//            $items[$i]['count'] = 0;

            $items[] = date('d-m-Y',$start);
            

            $start += 86400;
        }

        return $items;


        // return format
    }

    public function getWeeksBetweenRange($from, $to) {
        $fromInMicro = strtotime($from);
        $toInMicro = strtotime($to)+TO_DATE_INCREMENT;
        $items = array();
        //number of weeks
        $diff = round(($toInMicro - $fromInMicro) / 604800);

        $start = $fromInMicro;
        //incase the dates fall in the same week
        if($diff == 0) $diff = 1;

        for($i=0; $i < $diff; $i++) {
            $items[] = date('d-m-Y',$start);
            $start += 604800;
        }
        //print_r($items);
        return $items;
        

    }

    public function getMonthsBetweenRange($from, $to) {
	    $time1  = strtotime($from);
	    $time2  = strtotime($to)+TO_DATE_INCREMENT;
            $my     = date('mY', $time2);
	    $months = array($time1);
	
	   while($time1 < $time2) {
	      $time1 = strtotime(date('Y-m-d', $time1).' +1 month');
	      if(date('mY', $time1) != $my && ($time1 < $time2))
	         $months[] = $time1;
	   }
	
	   $months[] = $time2;
	   //print_r($months); exit;
	   return $months;
    }

    public function getYearsBetweenRange($from, $to) {
		$time1  = strtotime($from);
	   $time2  = strtotime($to)+TO_DATE_INCREMENT;
	   $my     = $time2;
	
	   $years = array($time1);
	
	   while($time1 < $time2) {
	      $time1 = strtotime(date('Y-m-d', $time1).' +1 year');
	      if(date('Y', $time1) != $my && ($time1 < $time2))
	         $years[] = $time1;
	   }
	
	   $years[] = $time2;
	   //print_r($years); exit;
	   return $years;
    }


    public function prepareRecordset($cols) {
        $this->view->columnsToShow = $cols;
        
    }

    public function timestampToDdmmyyyy($date) {
        return trim(date('d-m-Y',$date));
    }


    

    
	
	
}

?>