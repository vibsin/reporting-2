<?php

/*
* To change this template, choose Tools | Templates
* and open the template in the editor.
*/

class PremiumpacksController extends Zend_Controller_Action {

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
	public $sectionName = 'premium_packs';



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
						$this->view->summarizedData = $this->getDateItems($posts['pack_summarize_interval_of'][0],$posts);
						if($posts['is_export_request'] == 'yes') {
							if($this->view->mtd===true){
								$str = "User Count".$this->separator."Amount".$this->separator."Period\n";
							}else{
								$str = "User Count".$this->separator."Period\n";
							}
							foreach($this->view->summarizedData as $key => $val) {
								if($this->view->mtd===true){
									$str .= $val['count'].$this->separator.$val['amount'].$this->separator.$val['date']."\n";
								}else{
									$str .= $val['count'].$this->separator.$val['date']."\n";
								}
							}
							$this->downloadCSV($str);
						}
					} else {
						$this->view->summarizeError = $status;
					}
				}elseif($posts['is_export_request'] == 'yes') {
					$cacheKey = md5(serialize($posts));
					//$fileName = $this->sectionName.'_'.date('d-m-Y',strtotime('now')).'_'.$cacheKey.'.csv.zip';
					// $this->downloadExistingCSV($fileName);




					$obj3 = new Model_PremiumpackSolr($posts);
					//$obj3->start = $i;
					$obj3->rows = 10000000;

					if($isCashin=='on'){
						//$obj3->isCashin=true;
						
						if($posts["cashin_accruals_radio"]=="cashin"){
							$obj3->isCashin=true;
						}elseif($posts["cashin_accruals_radio"]=="accruals"){
							$obj3->isAccrual=true;
						}
						
						$status = $this->validateCashin($posts);
						if($status===true){
							$obj3->getResults(true, $posts["cashin_accruals_radio"]);
						}else{
							$this->view->summarizeError = $status;
						}
					}else{
						$obj3->getResults(true);
					}



					//$obj3->getResults(true);
					unset($obj3);



				}else{
					$obj2 = new Model_PremiumpackSolr($posts);
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


			}else{
				$this->view->summarizeError = $isvalidPost;
			}
		}

		$this->view->posts = $posts;
		$this->renderScript('premiumpacks/index.phtml');
	}

	function validateCashin($posts){

		if(!$this->validateDate($posts['cashin_date_from']) ||
		!$this->validateDate($posts['cashin_date_to'])) {
			return 'Please select valid date ranges for Cash-in/Accruals';
		}


		if(!empty($posts['cashin_date_from']) &&
		!empty($posts['cashin_date_to'])) {
			$from = strtotime($posts['premiumpacks_filter_usage_date_from']);
			$to = strtotime($posts['premiumpacks_filter_usage_date_to']);
			if($from > $to) {
				return INVALID_DATE_ERROR;
			}
		}

		return true;

	}

	protected function validateDate($date,$format='dd-mm-yyyy') {
		$validator = new Zend_Validate_Date(array('format' => $format));
		return $validator->isValid($date);

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


	public function validateSummarize($posts) {
		if(!empty($posts['pack_summarize_by_count_of']) &&
		!empty($posts['pack_summarize_for_date']) &&
		!empty($posts['pack_summarize_interval_of'])) {


			//now check if the dates are not blank and if present should be proper dates

			switch($posts['pack_summarize_for_date'][0]) {
				case 'premiumpacks_filter_admin_deleted_date':
					if(!$this->validateDate($posts['premiumpacks_filter_admin_deleted_date_from']) ||
					!$this->validateDate($posts['premiumpacks_filter_admin_deleted_date_to'])) {
						return 'Please select valid date ranges for Admin deleted date';
					}
					break;

				case 'premiumpacks_filter_created_date':
					if(!$this->validateDate($posts['premiumpacks_filter_created_date_from']) ||
					!$this->validateDate($posts['premiumpacks_filter_created_date_to'])) {
						return 'Please select valid date ranges for pack create date';
					}
					break;

				case 'premiumpacks_filter_activated_date':
					if(!$this->validateDate($posts['premiumpacks_filter_activated_date_from']) ||
					!$this->validateDate($posts['premiumpacks_filter_activated_date_to'])) {
						return 'Please select valid date ranges for pack activated date';
					}
					break;

				case 'premiumpacks_filter_expiry_date':
					if(!$this->validateDate($posts['premiumpacks_filter_expiry_date_from']) ||
					!$this->validateDate($posts['premiumpacks_filter_expiry_date_to'])) {
						return 'Please select valid date ranges for pack expiry date';
					}
					break;

				case 'premiumpacks_filter_usage_date':
					if(!$this->validateDate($posts['premiumpacks_filter_usage_date_from']) ||
					!$this->validateDate($posts['premiumpacks_filter_usage_date_to'])) {
						return 'Please select valid date ranges for pack usage date';
					}
					break;
			}


			return true;
		}
		return 'Please select fields for summarize option';
	}


	public function prepareRecordset($cols) {
		$this->view->columnsToShow = $cols;

	}


	protected function getFacetField($posts) {
		if(in_array("unique_pack_users",$posts['pack_summarize_by_count_of'])){
			return "premiumads_user_id";
		} else return false;
	}

	function getDateItems($interval,$posts){
		$dates = $this->getDatesForThisPeriod($posts['pack_summarize_for_date'][0], $posts);
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
					$obj3 = new Model_PremiumpackSolr($posts);
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
					$obj3 = new Model_PremiumpackSolr($posts);
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
					$obj3 = new Model_PremiumpackSolr($posts);
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
					$obj3 = new Model_PremiumpackSolr($posts);
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
					$obj3 = new Model_PremiumpackSolr($posts);
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
					$theData = $obj3->getfacetCountForSummarize($from,$to+TO_DATE_INCREMENT,$fieldToFetch,$facetField, true); //just send from date;

					$summarizedData[$i]['count'] = $theData["count"];
					$summarizedData[$i]['amount'] = (double)$theData["amount"];
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
			case 'premiumpacks_filter_admin_deleted_date':
				return array('from' => $posts['premiumpacks_filter_admin_deleted_date_from'],
				'to' => $posts['premiumpacks_filter_admin_deleted_date_to'],
				'caption' => 'Admin Deleted Date',
				'field_to_fetch' => 'premiumads_refund_date');
				break;
			case 'premiumpacks_filter_created_date':
				return array('from' => $posts['premiumpacks_filter_created_date_from'],
				'to' => $posts['premiumpacks_filter_created_date_to'],
				'caption' => 'Pack Created Date',
				'field_to_fetch' => 'premiumads_vdu_created_date');
				break;

			case 'premiumpacks_filter_activated_date':
				return array('from' => $posts['premiumpacks_filter_activated_date_from'],
				'to' => $posts['premiumpacks_filter_activated_date_to'],
				'caption' => 'Pack Activated Date',
				'field_to_fetch' => 'premiumads_ad_order_activated_date');
				break;

			case 'premiumpacks_filter_expiry_date':
				return array('from' => $posts['premiumpacks_filter_expiry_date_from'],
				'to' => $posts['premiumpacks_filter_expiry_date_to'],
				'caption' => 'Pack Expiry Date',
				'field_to_fetch' => 'premiumads_vdu_expiry_date');
				break;

			case 'premiumpacks_filter_usage_date':
				return array('from' => $posts['premiumpacks_filter_usage_date_from'],
				'to' => $posts['premiumpacks_filter_usage_date_to'],
				'caption' => 'Pack Usage Date',
				'field_to_fetch' => 'premiumads_ad_order_created_date');
				break;
		}
	}

	protected function validateDates($posts) {
		if(!empty($posts['premiumpacks_filter_admin_deleted_date_from']) &&
		!empty($posts['premiumpacks_filter_admin_deleted_date_to'])) {
			$from = strtotime($posts['premiumpacks_filter_admin_deleted_date_from']);
			$to = strtotime($posts['premiumpacks_filter_admin_deleted_date_to']);
			if($from > $to) {
				return INVALID_DATE_ERROR;
			}
		}

		//last update date
		if(!empty($posts['premiumpacks_filter_created_date_from']) &&
		!empty($posts['premiumpacks_filter_created_date_to'])) {
			$from = strtotime($posts['premiumpacks_filter_created_date_from']);
			$to = strtotime($posts['premiumpacks_filter_created_date_to']);
			if($from > $to) {
				return INVALID_DATE_ERROR;
			}
		}


		//expire date
		if(!empty($posts['premiumpacks_filter_activated_date_from']) &&
		!empty($posts['premiumpacks_filter_activated_date_to'])) {
			$from = strtotime($posts['premiumpacks_filter_activated_date_from']);
			$to = strtotime($posts['premiumpacks_filter_activated_date_to']);
			if($from > $to) {
				return INVALID_DATE_ERROR;
			}
		}

		//delete date
		if(!empty($posts['premiumpacks_filter_expiry_date_from']) &&
		!empty($posts['premiumpacks_filter_expiry_date_to'])) {
			$from = strtotime($posts['premiumpacks_filter_expiry_date_from']);
			$to = strtotime($posts['premiumpacks_filter_expiry_date_to']);
			if($from > $to) {
				return INVALID_DATE_ERROR;
			}
		}

		//repost date
		if(!empty($posts['premiumpacks_filter_usage_date_from']) &&
		!empty($posts['premiumpacks_filter_usage_date_to'])) {
			$from = strtotime($posts['premiumpacks_filter_usage_date_from']);
			$to = strtotime($posts['premiumpacks_filter_usage_date_to']);
			if($from > $to) {
				return INVALID_DATE_ERROR;
			}
		}

		return true;
	}
}
?>