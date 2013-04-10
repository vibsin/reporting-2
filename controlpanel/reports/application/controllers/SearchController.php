<?php
//echo date('d-m-Y h:m:s',1260901800).'<hr />'; 
class SearchController extends Zend_Controller_Action {

    public $data;
    public $summarizedData;
    public $separator = "|";
    public $sectionName = 'search';
    
    
    public function indexAction() {
        $posts = $this->getRequest()->getPost();
        if($this->getRequest()->isPost()) {
            $posts = $this->getRequest()->getPost();
			//print_r($posts);
            
			//validate all fields here
			
			$isvalidPost = $this->validateDates($posts);
			
			if($isvalidPost === true) {
				
	            //first check if summarize was selected
	            $isSummarize = $this->getRequest()->getParam('show_summarize');
		
	            if($isSummarize == 'on') {
	            	
	                //check if required fields are present. most importantly chekc dates
	                $status = $this->validateSummarize($posts);
	                
	                if($status === true) {
	                	
	                	
	                    $obj2 = new Model_SearchSolr($posts);
	                    
	                    //first we fetch the maximum no of records for such query and provide it to fetch records
	                    $obj2->rows = 1; //just one row is enough
	                   	$obj2->fl = 'id';
	                    $obj2->getResults();
	                    
	                    //now fetch max records
	                    $max = $obj2->totalRecordsFound;
	                    unset($obj2);
	                    //flush();
	     
	                    $obj3 = new Model_SearchSolr($posts);
	                    $obj3->rows = $max;
	                    //$obj3->rows = MAX_SOLR_RESULTS;
	                    $obj3->fl = 'search_date';
	                    $obj3->getResults();
	                    
	                    $this->data = $obj3->columnsToShow['data'];
	                    //print_r($obj2->columnsToShow); exit;
	                    //$this->fetchDataForSummarize($posts);
	                    $this->view->summarizedData = $this->getDateItems($posts['search_summarize_intervals_of'][0],$posts);
	                    
	                    if($posts['is_export_request'] == 'yes') {
	                    	//build summarize excel 
	                    	//generate string for excel file
	
	                    	$str = "Search Count".$this->separator."Period\n";
	                    	
	                    	foreach($this->view->summarizedData as $key => $val) {
	                    		$str .= $val['count'].$this->separator.$val['date']."\n";
	                    	}
	                    	
	                    	
	                    	$this->downloadCSV($str);
	                    	//$this->view->excelLink = 
	                    	
	                    }
	
	                } else {
	                	
	                    $this->view->summarizeError = $status;
	                }
	            } else {
	
		            if($posts['is_export_request'] == 'yes') {
		            	$obj2 = new Model_SearchSolr($posts);
		            	$obj2->start = 0;
		            	$obj2->fl = 'id';
		            	$obj2->getResults();  
		            	$max = $obj2->totalRecordsFound;
	                    unset($obj2);
	                    
	                    
	                    $obj3 = new Model_SearchSolr($posts);
	                    $obj3->rows = $max;
	                    $obj3->getResults();
	                    
	                    $str = $this->generateExcelString($obj3->columnsToShow);
	                    $this->downloadCSV($str);
	                    //print_r($obj3->columnsToShow); 
		            	
		            	
		            	
		            } else {
		            	
		                $obj2 = new Model_SearchSolr($posts);
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
        $this->renderScript('search/index.phtml');
         
        }

    }
	
    
    protected function validateDates($posts) {
    	//from date  should always be lesser than or equal to 'to' date
    	
    	//search date
    	if(!empty($posts['search_filter_searchdate_from']) &&
                !empty($posts['search_filter_searchdate_to'])) {
            $from = strtotime($posts['search_filter_searchdate_from']);
            $to = strtotime($posts['search_filter_searchdate_to']);
            
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
    		//sort($columns['columns'],SORT_STRING);
    		$str .= "Sr. No.".$this->separator;
    		foreach($columns['columns'] as $key => $val) {
    			$str .= '"'.$val.'"'.$this->separator;
    		}
    		
    		$str .= "\n";
    	}
    	
    	
    	if(!empty($columns['data'])) {
    		$srNo = 1;
    		foreach($columns['data'] as $key => $val) {
    			$str .= $srNo.$this->separator;
    			foreach($columns['columns'] as $k => $v) {
    				$str .= '"'.$val[$v].'"'.$this->separator;
    			}
    			
    			$str .= "\n";
    			$srNo++;
    		}
    	}
    	
    	return $str;
    }
    
    protected function downloadCSV($str='') {
    	//create the file
    	//$str = 'Vibhor, Singh';
    	$fileName = $this->sectionName.'_'.date('d-m-Y',strtotime('now')).'_'.uniqid().'.csv';
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
    	//print_r($posts['search_summarize_for_date']);  exit;
        if(!empty($posts['search_summarize_by_count_of']) &&
                        !empty($posts['search_summarize_for_date']) &&
                        !empty($posts['search_summarize_intervals_of'])) {
                        	
                        	
        	//now check if the dates are not blank and if present should be proper dates

        	switch($posts['search_summarize_for_date'][0]) {
        		case 'search_search_date':
        			if(!$this->validateDate($posts['search_filter_searchdate_from']) || 
        				!$this->validateDate($posts['search_filter_searchdate_to'])) {
        				return 'Please select valid date ranges for Search date';
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

    public function getDateItems($interval,$posts) {

        $dates = $this->getDatesForThisPeriod($posts['search_summarize_for_date'][0], $posts);
        $caption = '';

        if($posts['search_summarize_for_date'][0] == 'search_search_date') {
            $caption = 'Search Date';
        } 

        switch($interval) {
            case 'daily':
                $days = $this->getDaysBetweenRange($dates['from'], $dates['to']);
                //solr data
                $data = $this->data;
                $summarizedData;
                //print_r($days); exit;
                for($i=0; $i< count($days); $i++) {
	                foreach($data as $key => $val) {
	                	//echo strtotime($items[$i].'<br />';
	                    if(strtotime($days[$i]) == strtotime($val[$caption])) {
	                        $summarizedData[$val[$caption]]['count'] += 1;
	                        $summarizedData[$val[$caption]]['date'] = $val[$caption];
	                    }
	                }
                }

            break;
            case 'weekly':
                $weeks = $this->getWeeksBetweenRange($dates['from'], $dates['to']);
                $data = $this->data;
                

                    for($i = 0; $i < count($weeks);$i++) {
                        $startOfWeek = $weeks[$i];
                        $endOfWeek = $weeks[$i+1];
                        $weekRange = date('d-m-Y',$startOfWeek).' To '.date('d-m-Y',$endOfWeek);
                        //echo $weekRange; exit;
                        foreach($data as $key => $val) {
                        if(strtotime($val[$caption]) >= $startOfWeek &&
                                strtotime($val[$caption]) < $endOfWeek) {
                            $summarizedData[$weekRange]['count'] += 1;
                            $summarizedData[$weekRange]['date'] = date('d-m-Y',$startOfWeek).' To '.date('d-m-Y',$endOfWeek);
                        }
                    }
                }
                //print_r($summarizedData); exit;
                //print_r($weeks); exit;
            break;
            case 'monthly':
                $months = $this->getMonthsBetweenRange($dates['from'], $dates['to']);
                $data = $this->data;
//                foreach($data as $key => $val) {
//
//                    for($i = 0; $i < count($months);$i++) {
//                        $startOfWeek = $months[$i];
//                        $endOfWeek = $months[$i+1];
//                        $weekRange = date('M\'y',$startOfWeek).' To '.date('M\'y',$endOfWeek);
//                        //echo $weekRange; exit;
//                        if(strtotime($val[$caption]) >= $startOfWeek &&
//                                strtotime($val[$caption]) < $endOfWeek) {
//                            $summarizedData[$weekRange]['count'] += 1;
//                            $summarizedData[$weekRange]['date'] = $weekRange;
//                        }
//                    }
//                }
                
        for($i = 0; $i < count($months);$i++) {
                        $startOfWeek = $months[$i];
                        $endOfWeek = $months[$i+1];
                        $weekRange = date('M\'y',$startOfWeek).' To '.date('M\'y',$endOfWeek);
                        //echo $weekRange; exit;
                        foreach($data as $key => $val) {
	                        if(strtotime($val[$caption]) >= $startOfWeek &&
	                                strtotime($val[$caption]) < $endOfWeek) {
	                            $summarizedData[$weekRange]['count'] += 1;
	                            $summarizedData[$weekRange]['date'] = $weekRange;
	                        }
                        }
                    }
                //print_r($summarizedData); exit;
                
            break;
            case 'yearly':
                $years = $this->getYearsBetweenRange($dates['from'], $dates['to']);
        		$data = $this->data;
                foreach($data as $key => $val) {

                    for($i = 0; $i < count($years);$i++) {
                        $startOfWeek = $years[$i];
                        $endOfWeek = $years[$i+1];
                        $weekRange = date('Y',$startOfWeek).' To '.date('Y',$endOfWeek);
                        //echo $weekRange; exit;
                        if(strtotime($val[$caption]) >= $startOfWeek &&
                                strtotime($val[$caption]) < $endOfWeek) {
                            $summarizedData[$weekRange]['count'] += 1;
                            $summarizedData[$weekRange]['date'] = $weekRange;
                        }
                    }
                }
            break;
        }

        return $summarizedData;
    }

    public function getDatesForThisPeriod($filterDate,$posts) {

        switch($filterDate) {
            case 'search_search_date':
                return array('from' => $posts['search_filter_searchdate_from'], 'to' => $posts['search_filter_searchdate_to']);
            break;

            default:
                //create date
                return array('from' => $posts['search_filter_searchdate_from'], 'to' => $posts['search_filter_searchdate_to']);
            break;
        }

    }

    public function getDaysBetweenRange($from, $to) {
        $fromInMicro = strtotime($from);
        $toInMicro = strtotime($to);
        $items = array();
        $diff = ($toInMicro - $fromInMicro) / 86400;
        $start = $fromInMicro;
        
        for($i=0; $i <= $diff; $i++) {
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
        $toInMicro = strtotime($to);
        $items = array();
        //number of weeks
        $diff = ($toInMicro - $fromInMicro) / 604800;
        $start = $fromInMicro;
        if($diff == 0) $diff = 1;
        for($i=0; $i <= $diff; $i++) {
            $items[] = $start;
            $start += 604800 ;
        }

        return $items;
        

    }

    public function getMonthsBetweenRange($from, $to) {
	    $time1  = strtotime($from);
	    $time2  = strtotime($to);
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
	   $time2  = strtotime($to);
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