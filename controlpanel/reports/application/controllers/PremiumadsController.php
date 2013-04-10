<?php

/*
* To change this template, choose Tools | Templates
* and open the template in the editor.
*/

class PremiumadsController extends Zend_Controller_Action {

	/**
     *
     * @var array
     */
	public $data;

	/**
     *
     * @var type 
     */
	public $summarizedData;

	/**
     *
     * @var type 
     */
	public $separator = "|";

	/**
     *
     * @var type 
     */
	public $sectionName = 'premium_ads';



	public function indexAction() {


		if($this->getRequest()->isPost()) {
			$posts = $this->getRequest()->getPost();
			$isvalidPost = $this->validateDates($posts);
			if($isvalidPost === true) {
				$isSummarize = $this->getRequest()->getParam('show_summarize');
				$isCashin = $this->getRequest()->getParam('show_cashin');
				if($isSummarize == 'on') {
					$status = $this->validateSummarize($posts);
					if($status === true) {
						$this->view->summarizedData = $this->getDateItems($posts['premiumads_summarize_interval_of'][0],$posts);
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


						$obj2 = new Model_PremiumAdsSolr($posts);
						$obj2->rows = 10000000;

						if($isCashin=='on'){
							//$obj2->isCashin=true;
							if($posts["cashin_accruals_radio"]=="cashin"){
								$obj2->isCashin=true;
							}elseif($posts["cashin_accruals_radio"]=="accruals"){
								$obj2->isAccrual=true;
							}
							$status = $this->validateCashin($posts);
							if($status===true){
								$obj2->getResults(true, $posts["cashin_accruals_radio"]);
							}else{
								$this->view->summarizeError = $status;
							}
						}else{
							$obj2->getResults(true);
						}


					} else {
						$obj2 = new Model_PremiumAdsSolr($posts);
						$pageNumber = $this->getRequest()->getParam('start_rows');
						$obj2->start = $pageNumber -1;

						if($isCashin=='on'){
							if($posts["cashin_accruals_radio"]=="cashin"){
								$obj2->isCashin=true;
							}elseif($posts["cashin_accruals_radio"]=="accruals"){
								$obj2->isAccrual=true;
							}
							$status = $this->validateCashin($posts);
							if($status===true){
								$obj2->getResults(false, $posts["cashin_accruals_radio"]);
							}else{
								$this->view->summarizeError = $status;
							}
						}else{
							$obj2->getResults(false);
						}

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
		$this->renderScript('premiumads/index.phtml');
	}

	public function validateSummarize($posts) {


		if(!empty($posts['premiumads_summarize_by_count_of']) &&
		!empty($posts['premiumads_summarize_for_date']) &&
		!empty($posts['premiumads_summarize_interval_of'])) {

			switch($posts['premiumads_summarize_for_date'][0]) {
				case 'premiumads_filter_ad_order_created_date':
					if(!$this->validateDate($posts['premiumads_filter_ad_order_created_date_from']) ||
					!$this->validateDate($posts['premiumads_filter_ad_order_created_date_to'])) {
						return 'Please select valid date ranges for Created date';
					}
					break;

				case 'premiumads_filter_ad_order_activated_date':
					if(!$this->validateDate($posts['premiumads_filter_ad_order_activated_date_from']) ||
					!$this->validateDate($posts['premiumads_filter_ad_order_activated_date_to'])) {
						return 'Please select valid date ranges for Activated date';
					}
					break;

				case 'premiumads_filter_ad_order_expiry_date':
					if(!$this->validateDate($posts['premiumads_filter_ad_order_expiry_date_from']) ||
					!$this->validateDate($posts['premiumads_filter_ad_order_expiry_date_to'])) {
						return 'Please select valid date ranges for Expired date';
					}
					break;

				case 'premiumads_filter_admin_order_date':
					if(!$this->validateDate($posts['premiumads_filter_admin_order_date_from']) ||
					!$this->validateDate($posts['premiumads_filter_admin_order_date_to'])) {
						return 'Please select valid date ranges for Admin date';
					}
					break;

				case 'premiumads_filter_user_order_date':
					if(!$this->validateDate($posts['premiumads_filter_user_order_date_from']) ||
					!$this->validateDate($posts['premiumads_filter_user_order_date_to'])) {
						return 'Please select valid date ranges for User Admin date';
					}
					break;

			}
			return true;
		}
		return 'Please select fields for summarize option';
	}

	protected function getFacetField($posts) {
		if(in_array("unique_premiumads_users",$posts['premiumads_summarize_by_count_of'])){
			return "premiumads_user_id";
		} else return false;
	}

	function getDateItems($interval,$posts){
		$dates = $this->getDatesForThisPeriod($posts['premiumads_summarize_for_date'][0], $posts);
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
					$obj3 = new Model_PremiumAdsSolr($posts);
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
					$obj3 = new Model_PremiumAdsSolr($posts);
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
					$obj3 = new Model_PremiumAdsSolr($posts);
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
					$obj3 = new Model_PremiumAdsSolr($posts);
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

			case 'mtd':
				$fromInMicro = $dates['from'];
				$toInMicro = strtotime($dates['to']);
				$current = strtotime($dates['from']);

				$i = 0;
				while($i<13) {
					//echo $current;
					$obj3 = new Model_PremiumAdsSolr($posts);
					$obj3->fl = $fieldToFetch;

					if($i==0){
						$from = $current;
						$to = $toInMicro;
					}else{
						$oldCurrent = $from;
						$oldtoInMicro = $to;
						$from = strtotime('-'.$i.' months', $current);
						$to = strtotime('-'.$i.' months', $toInMicro);

						if(date('d', $oldCurrent)==31 && date('d', $from)==1){
							$from = strtotime('-'.$i.' months -1 day', $current);
						}

						if(date('d', $oldtoInMicro)==31 && date('d', $to)==1){
							$to = strtotime('-'.$i.' months -1 day', $toInMicro);
						}
					}
					$this->view->mtd = true;
					$theData = $obj3->getfacetCountForSummarize($from,$to,$fieldToFetch,$facetField, true); //just send from date;

					$summarizedData[$i]['count'] = $theData["count"];
					$summarizedData[$i]['amount'] = $theData["amount"];
					$summarizedData[$i]['date'] = date('d-m-Y',$from).' To '.date('d-m-Y',$to);


					$i++;
					unset ($obj3);
				}
				break;
		}

		//print_r($summarizedData);
		return $summarizedData;
	}

	function getDatesForThisPeriod($filterDate,$posts) {

		switch($filterDate) {
			case 'premiumads_filter_ad_order_created_date':
				return array('from' => $posts['premiumads_filter_ad_order_created_date_from'],
				'to' => $posts['premiumads_filter_ad_order_created_date_to'],
				'caption' => 'Create Date',
				'field_to_fetch' => 'premiumads_ad_order_created_date');
				break;

			case 'premiumads_filter_ad_order_activated_date':
				return array('from' => $posts['premiumads_filter_ad_order_activated_date_from'],
				'to' => $posts['premiumads_filter_ad_order_activated_date_to'],
				'caption' => 'Activated date',
				'field_to_fetch' => 'premiumads_ad_order_activated_date');
				break;

			case 'premiumads_filter_ad_order_expiry_date':
				return array('from' => $posts['premiumads_filter_ad_order_expiry_date_from'],
				'to' => $posts['premiumads_filter_ad_order_expiry_date_to'],
				'caption' => 'Expired date',
				'field_to_fetch' => 'premiumads_ad_order_expiry_date');
				break;

				//need to work here more
			case 'premiumads_filter_admin_order_date':
				return array('from' => $posts['premiumads_filter_admin_order_date_from'],
				'to' => $posts['premiumads_filter_admin_order_date_to'],
				'caption' => 'Admin Deleted Date',
				'field_to_fetch' => 'premiumads_admin_refund_date');
				break;

				//need to work here more
			case 'premiumads_filter_user_order_date':
				return array('from' => $posts['premiumads_filter_user_order_date_from'],
				'to' => $posts['premiumads_filter_user_order_date_to'],
				'caption' => 'User Deleted date',
				'field_to_fetch' => 'premiumads_user_refund_date');
				break;

		}
	}

	protected function validateDates($posts) {
		if(!empty($posts['premiumads_filter_ad_order_created_date_from']) &&
		!empty($posts['premiumads_filter_ad_order_created_date_to'])) {
			$from = strtotime($posts['premiumads_filter_ad_order_created_date_from']);
			$to = strtotime($posts['premiumads_filter_ad_order_created_date_to']);
			if($from > $to) {
				return INVALID_DATE_ERROR;
			}
		}

		if(!empty($posts['premiumads_filter_ad_order_activated_date_from']) &&
		!empty($posts['premiumads_filter_ad_order_activated_date_to'])) {
			$from = strtotime($posts['premiumads_filter_ad_order_activated_date_from']);
			$to = strtotime($posts['premiumads_filter_ad_order_activated_date_to']);
			if($from > $to) {
				return INVALID_DATE_ERROR;
			}
		}

		if(!empty($posts['premiumads_filter_ad_order_expiry_date_from']) &&
		!empty($posts['premiumads_filter_ad_order_expiry_date_to'])) {
			$from = strtotime($posts['premiumads_filter_ad_order_expiry_date_from']);
			$to = strtotime($posts['premiumads_filter_ad_order_expiry_date_to']);
			if($from > $to) {
				return INVALID_DATE_ERROR;
			}
		}

		if(!empty($posts['premiumads_filter_admin_order_date_from']) &&
		!empty($posts['premiumads_filter_admin_order_date_to'])) {
			$from = strtotime($posts['premiumads_filter_admin_order_date_from']);
			$to = strtotime($posts['premiumads_filter_admin_order_date_to']);
			if($from > $to) {
				return INVALID_DATE_ERROR;
			}
		}

		if(!empty($posts['premiumads_filter_user_order_date_from']) &&
		!empty($posts['premiumads_filter_user_order_date_to'])) {
			$from = strtotime($posts['premiumads_filter_user_order_date_from']);
			$to = strtotime($posts['premiumads_filter_user_order_date_to']);
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
	protected function validateDate($date,$format='dd-mm-yyyy') {
		$validator = new Zend_Validate_Date(array('format' => $format));
		return $validator->isValid($date);

	}
	public function prepareRecordset($cols) {
		$this->view->columnsToShow = $cols;

	}

	public function timestampToDdmmyyyy($date) {
		return trim(date('d-m-Y',$date));
	}

	function validateCashin($posts){

		if(!$this->validateDate($posts['cashin_date_from']) ||
		!$this->validateDate($posts['cashin_date_to'])) {
			return 'Please select valid date ranges for Cash-in/Accruals';
		}


		if(!empty($posts['cashin_date_from']) &&
		!empty($posts['cashin_date_to'])) {
			$from = strtotime($posts['premiumads_filter_usage_date_from']);
			$to = strtotime($posts['premiumads_filter_usage_date_to']);
			if($from > $to) {
				return INVALID_DATE_ERROR;
			}
		}

		return true;

	}
}?>