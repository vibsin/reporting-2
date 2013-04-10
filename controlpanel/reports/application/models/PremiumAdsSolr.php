<?php
/*
* To change this template, choose Tools | Templates
* and open the template in the editor.
*/


class Model_PremiumAdsSolr {
	public $post;
	public $queryString;

	public $limit;
	public $explainOther    = '';
	public $fl              = '*,score';
	public $indent          = 'on';
	public $start           = '0';              //Start Row
	//    /**
	//     *
	//     * $q Solr/Lucene Statement
	//     */
	public $q               = '';
	public $hl_fl           = '';               //Fields to Highlight
	/**
     *
     * $qt Query Type
     */
	public $qt              = 'standard';
	/**
     *
     * $wt Output Type
     */
	public $wt              = 'json';       //Output Type
	public $fq              = '';
	public $version         = '2.2';
	public $rows            = MAX_RESULTS_PER_PAGE;             //Maximum Rows Returned
	public $solrUrl         = SOLR_META_QUERY_PREMIUM_AD;
	public $finalUrl        = '';
	public $queryArray      = array();

	public $columnsToShow = array();

	public $totalRecordsFound = '';
	public $records = '';
	public $separator = "|";
	public $sectionName = 'premiumads';
	public $isCashin=false;
	public $isAccrual=false;
	public function  __construct($postedParams) {
		$this->post = $postedParams;
	}

	public static function getMap(){
		return array(
		"premiumads_user_name"=>"User Name",
		"premiumads_user_email"=>"User Email",
                 "premiumads_user_id"=>"User Id",
		"premiumads_user_mobile"=>"User Mobile",
		"premiumads_city_name"=>"City Name",
		"premiumads_ad_localities"=>"Locality",
		"premiumads_metacategory_name"=>"Metacategory Name",
		"premiumads_subcategory_name"=>"Subcategory Name",
		"premiumads_order_id"=>"Order Id",
		"premiumads_ad_id"=>"Ad Id",
		"premiumads_pack_id"=>"Pack Id",
		"premiumads_pack_order_id"=>"Pack Order Id",
		"premiumads_amount"=>"Gross Amount",
		"premiumads_payment_for"=>"Premium Ad Type",
		"premiumads_payment_type"=>"Payment Mode",
		"premiumads_ad_expiry_date"=>"Ad Expiry Date",
		"premiumads_ad_first_created_date"=>"Ad First Created Date",
		"premiumads_ad_order_activated_date"=>"Ad Order Activated Date",
		"premiumads_refund_date"=>"Admin Deleted Date",
		"premiumads_ad_order_created_date"=>"Ad Order Created Date",
		"premiumads_ad_order_expiry_date"=>"Ad Order Expiry Date",
		"premiumads_ad_order_delete_date"=>"Ad Order Delete Date",
		"premiumads_ad_status"=>"Ad Status",
		"premiumads_payment_status"=>"Order Payment Status",
		"premiumads_ad_title"=>"Ad Title",
		"premiumads_ad_description"=>"Ad Description",
		"premiumads_reply_count"=>"Reply Count",
		"premiumads_visitor_count"=>"Visitor Count",
		"premiumads_ad_type"=>"Ad Type",
		"attr_you_are"=>"Individual Dealer",
		"premiumads_vd_telemarketer_name"=>"Telemarketer Name",
		"premiumads_vd_telemarketer_tl_name"=>"Telemarketer Lead Name",
		"premiumads_vd_territory_manager_name"=>"Territory Manager Name",
		"premiumads_vd_ro_name"=>"RO Name",
		"premiumads_refund_amount"=>"Refund Amount",
		"premiumads_refund_reason"=>"Refund Reason",
		"premiumads_refund_mode"=>"Refund Mode",
		"premiumads_vdu_created_date"=>"Pack Created Date",
		"premiumads_vdu_expiry_date"=>"Pack Expiry Date",
		"premiumads_tpsl_id"=>"Tpsl Id",
		"premiumads_auto_renew_status"=>"Keep Running Status",
		"premiumads_net_amount"=>"Net Amount",
		"premiumads_tax"=>"Tax",
		"premiumads_cheque_no"=>"Cheque Number",
		"premiumads_remark"=>"Cheque Details",
		"premiumads_ad_order_accounting_date"=>"Accounting Date",
		"premiumads_ad_order_refund_accounting_date"=>"Refund Accounting Date",
		"premiumads_extended_product_type"=>"Accrual Type",
		"premiumads_order_smb"=>"BGS",
		"premiumads_reseller_pack_order_id"=>"Reseller Pack Order Id",
		"premiumads_vdu_force_consume_amount"=>"Force Consume Amount",
		"premiumads_vdu_remaining_credit"=>"Pack Remaining Credits"
		);
	}

	protected function setColumns() {
		$postedColumns = $this->post['premiumads_columns'];

		$map = self::getMap();
		$this->fl = '';

		foreach($postedColumns as $key => $val) {


			if($val == 'id') continue;
			$this->columnsToShow['columns'][] = $map[$val];
			$this->fl .= $val.",";
		}
		$this->fl .= "score";
		
		if ($this->isAccrual || $this->isCashin){
			$newFl = 'premiumads_ad_order_activated_date,premiumads_amount,premiumads_net_amount,premiumads_payment_status,premiumads_product_type,premiumads_payment_type,premiumads_actual_refund_accounting,premiumads_actual_accounting';
				
			$this->fl .= ",$newFl";
				
			/*$this->post['premiumpacks_columns'][]='premiumads_ad_order_activated_date';
			 $this->post['premiumpacks_columns'][]='premiumads_ad_order_accounting_date';
			$this->post['premiumpacks_columns'][]='premiumads_ad_order_refund_accounting_date';
			$this->post['premiumpacks_columns'][]='premiumads_refund_date';
		
			$this->post['premiumpacks_columns'][]='premiumads_amount';
			$this->post['premiumpacks_columns'][]='premiumads_net_amount';
			$this->post['premiumpacks_columns'][]='premiumads_payment_status';
			$this->post['premiumpacks_columns'][]='premiumads_product_type';
			$this->post['premiumpacks_columns'][]='premiumads_payment_type';*/
		}
	}
	public function parseXmlData() {

		try {
			$obj = new Utility_SolrQueryAnalyzer($this->finalUrl,__FILE__.' at line '.__LINE__);
			$data = $obj->init();
		} catch (Exception $e) {
			trigger_error($e->getMessage());
		}


		if(!empty($data)) {
			$xmlData = json_decode($data); //simplexml_load_string($data);
			$this->totalRecordsFound = $xmlData->response->numFound; //$xmlData->result->attributes()->numFound;

			$premiumad = array();
			$counter = 0;
			$stories = $xmlData->response->docs;
			foreach ($stories as $story) {

				$premiumad['User Name'] = (!$story->premiumads_user_name) ? 'NA': $story->premiumads_user_name;
				$premiumad['User Email'] = (!$story->premiumads_user_email) ? 'NA': $story->premiumads_user_email;
				$premiumad['User Id'] = (!$story->premiumads_user_id) ? 'NA': $story->premiumads_user_id;
                                $premiumad['User Mobile'] = (!$story->premiumads_user_mobile) ? 'NA': $story->premiumads_user_mobile;
				$premiumad['City Name'] = (!$story->premiumads_city_name) ? 'NA': $story->premiumads_city_name;
				$premiumad['Locality'] = (!$story->premiumads_ad_localities) ? 'NA': $story->premiumads_ad_localities;
				$premiumad['Metacategory Name'] = (!$story->premiumads_metacategory_name) ? 'NA': $story->premiumads_metacategory_name;
				$premiumad['Subcategory Name'] = (!$story->premiumads_subcategory_name) ? 'NA': $story->premiumads_subcategory_name;
				$premiumad['Order Id'] = (!$story->premiumads_order_id) ? 'NA': $story->premiumads_order_id;
				$premiumad['Ad Id'] = (!$story->premiumads_ad_id) ? 'NA': $story->premiumads_ad_id;
				$premiumad['Pack Id'] = (!$story->premiumads_pack_id ) ? 'NA': $story->premiumads_pack_id;
				$premiumad['Pack Order Id'] = (!$story->premiumads_pack_order_id) ? 'NA': $story->premiumads_pack_order_id;
				$premiumad['Gross Amount'] = (!$story->premiumads_amount) ? 'NA': $story->premiumads_amount;
				$premiumad['Premium Ad Type'] = (!$story->premiumads_payment_for) ? 'NA': $story->premiumads_payment_for;
				$premiumad['Payment Mode'] = (!$story->premiumads_payment_type) ? 'NA': $story->premiumads_payment_type;
				$premiumad['Ad Expiry Date'] = $this->getDate($story->premiumads_ad_expiry_date);
				$premiumad['Ad First Created Date'] = $this->getDate($story->premiumads_ad_first_created_date);
				$premiumad['Ad Order Activated Date'] = $this->getDate($story->premiumads_ad_order_activated_date);
				$premiumad['Ad Order Created Date'] =$this->getDate($story->premiumads_ad_order_created_date);
				$premiumad['Ad Order Expiry Date'] = $this->getDate($story->premiumads_ad_order_expiry_date);
				$premiumad['Ad Order Delete Date'] = $this->getDate($story->premiumads_ad_order_delete_date);
				$premiumad['Ad Status'] = (!$story->premiumads_ad_status) ? 'NA': $story->premiumads_ad_status;
				$premiumad['Order Payment Status'] = (!$story->premiumads_payment_status) ? 'NA': $story->premiumads_payment_status;
				$premiumad['Ad Title'] = (!$story->premiumads_ad_title) ? 'NA': $story->premiumads_ad_title;
				$premiumad['Ad Description'] = (!$story->premiumads_ad_description) ? 'NA': $story->premiumads_ad_description;
				$premiumad['Reply Count'] = (!$story->premiumads_reply_count) ? 'NA': $story->premiumads_reply_count;
				$premiumad['Visitor Count'] = (!$story->premiumads_visitor_count) ? 'NA': $story->premiumads_visitor_count;
				$premiumad['Ad Type'] = (!$story->premiumads_ad_type) ? 'NA': $story->premiumads_ad_type;
				$premiumad['Telemarketer Name'] = (!$story->premiumads_vd_telemarketer_name) ? 'NA': $story->premiumads_vd_telemarketer_name;
				$premiumad['Telemarketer Lead Name'] = (!$story->premiumads_vd_telemarketer_tl_name) ? 'NA': $story->premiumads_vd_telemarketer_tl_name;
				$premiumad['Territory Manager Name'] = (!$story->premiumads_vd_territory_manager_name) ? 'NA': $story->premiumads_vd_territory_manager_name;
				$premiumad['RO Name'] = (!$story->premiumads_vd_ro_name) ? 'NA': $story->premiumads_vd_ro_name;
				$premiumad['Refund Amount'] = (!$story->premiumads_refund_amount) ? 'NA': $story->premiumads_refund_amount;
				$premiumad['Refund Reason'] = (!$story->premiumads_refund_reason) ? 'NA': $story->premiumads_refund_reason;
				$premiumad['Refund Mode'] = (!$story->premiumads_refund_mode) ? 'NA': $story->premiumads_refund_mode;
				$premiumad['Pack Created Date'] = $this->getDate($story->premiumads_vdu_created_date);
				$premiumad['Pack Expiry Date'] = $this->getDate($story->premiumads_vdu_expiry_date);
				$premiumad['Tpsl Id'] = (!$story->premiumads_tpsl_id) ? 'NA': $story->premiumads_tpsl_id;
				$premiumad['Keep Running Status'] = (!$story->premiumads_auto_renew_status) ? 'NA': $story->premiumads_auto_renew_status;
				$premiumad['Individual Dealer']= (!$story->attr_you_are[0])?'NA':$story->attr_you_are[0];
				$premiumad['Net Amount']= (!$story->premiumads_net_amount)?'NA':$story->premiumads_net_amount;
				$premiumad['Tax']= (!$story->premiumads_tax)?'NA':$story->premiumads_tax;
				$premiumad['Cheque Number']= (!$story->premiumads_cheque_no)?'NA':$story->premiumads_cheque_no;
				$premiumad['Cheque Details']= (!$story->premiumads_remark)?'NA':$story->premiumads_remark;

				$premiumad['Admin Deleted Date'] = ($story->premiumads_refund_date) ? date('d-m-Y',$story->premiumads_refund_date) : 'NA';
				$premiumad['Accounting Date']= ($story->premiumads_ad_order_accounting_date) ? date('d-m-Y',$story->premiumads_ad_order_accounting_date) : 'NA';
				$premiumad['Refund Accounting Date']= ($story->premiumads_ad_order_refund_accounting_date) ? date('d-m-Y',$story->premiumads_ad_order_refund_accounting_date) : 'NA';

				$premiumad["Accrual Type"] = (!$story->premiumads_extended_product_type)?'NA':$story->premiumads_extended_product_type;
				//$premiumad['Net Amount']= (!$story->premiumads_net_amount)?'NA':$story->premiumads_net_amount;
				$premiumad["Reseller Pack Order Id"] = (!$story->premiumads_reseller_pack_order_id)?'NA':$story->premiumads_reseller_pack_order_id;
				$premiumad['BGS'] =$story->premiumads_order_smb;

				$premiumad["Force Consume Amount"] = (!$story->premiumads_vdu_force_consume_amount)?'NA':$story->premiumads_vdu_force_consume_amount;
				
				
				$premiumad["Pack Remaining Credits"] = (!$story->premiumads_vdu_remaining_credit)?'NA':$story->premiumads_vdu_remaining_credit;
				

				$this->getExtraEntry($premiumad,$story);

				$this->columnsToShow['data'][] = $premiumad;
				unset($premiumad);
			}

		} else {
			$this->columnsToShow['data'] = '';
		}
	}


	public function getExtraEntry(&$users,&$story, $excel=false){

		
		if (($story->premiumads_payment_status=='Refund' && ($this->isCashin || $this->isAccrual)) || ($this->isAccrual && $story->premiumads_payment_status=='PaymentAdminDeleted' &&  $story->premiumads_product_type=="Ad" && $story->premiumads_payment_type=="UsedCredit")){

			$from = $this->ddmmyyyToTimestamp($this->post['cashin_date_from']);
			$to = $this->ddmmyyyToTimestamp($this->post['cashin_date_to'])+TO_DATE_INCREMENT;
			
			$allow=false;
			$refundAllow = false;
			
			if ($story->premiumads_actual_accounting >= $from && $story->premiumads_actual_accounting <= $to){
				$allow=true;
			}
			
			if ($story->premiumads_actual_refund_accounting >= $from && $story->premiumads_actual_refund_accounting <= $to){
				$refundAllow=true;
			}
			
			if (!$allow && $refundAllow){
				if(!$excel){
					$users['Gross Amount']=(!$story->premiumads_amount) ? 'NA':-$story->premiumads_amount;
					$users['Net Amount']=(!$story->premiumads_net_amount)?'NA':-$story->premiumads_net_amount;
				}else{
					if($story->premiumads_amount)$story->premiumads_amount = -$story->premiumads_amount;
					if($story->premiumads_net_amount)$story->premiumads_net_amount = -$story->premiumads_net_amount;
				}
				
			}

			/*if (!$allow){				
				$users['Gross Amount']=(!$story->premiumads_amount) ? 'NA': -$story->premiumads_amount;
				$users['Net Amount']=(!$story->premiumads_net_amount)?'NA':-$story->premiumads_net_amount;				
			}*/

			if ($allow && $refundAllow){
				if(!$excel){
					$newuser =$users;
					if ($newuser['Gross Amount']){
						$newuser['Gross Amount']=(!$story->premiumads_amount) ? 'NA': -$story->premiumads_amount;
					}
					if ($newuser['Net Amount']){
						$newuser['Net Amount']=(!$story->premiumads_net_amount)?'NA':-$story->premiumads_net_amount;
					}
					$this->columnsToShow['data'][]=$newuser;
				}else{
					$cloned = clone $story;
					if($cloned->premiumads_amount)$cloned->premiumads_amount = -$cloned->premiumads_amount;
					if($cloned->premiumads_net_amount)$cloned->premiumads_net_amount = -$cloned->premiumads_net_amount;
					
					$story = array($story, $cloned);
				}
			}
		}
	}

	public function parseXmlDataForExcel() {

		try {
			$obj = new Utility_SolrQueryAnalyzer($this->finalUrl,__FILE__.' at line '.__LINE__);
			$data = $obj->init();
		} catch (Exception $e) {
			trigger_error($e->getMessage());
		}


		if(!empty($data)) {
			$xmlData = json_decode($data); //simplexml_load_string($data);

			$content = array();
			$head = array();
			$map = self::getMap();

			$winFinal = array();
			$windExcel=array();

			$head[] = '"Sr. No."';
			$windExcel[]= "Sr. No.";
			foreach ($map as $solrKey=>$userText){
				if(in_array($solrKey, $this->post['premiumads_columns'])) {
					$head[] = '"'.$userText.'"';
					$windExcel[]=$userText;
				}
			}
			$winFinal[]=$windExcel;
			unset($windExcel);


			$content[] = join($this->separator, $head);
			$stories = $xmlData->response->docs;
			$srNo = 1;
			$row = array();
			foreach ($stories as $story){
				$fake = "";
				$this->getExtraEntry ($fake, $story, true);
				if(is_object($story)){
					$story = array($story);
				}
				
				foreach($story as $story1){
					$row = array();
					$row[] = '"'.$srNo.'"';
					$windExcel[]=$srNo;
					//	$story->premiumads_vdu_status = self::parseVDStatus($story->premiumads_vdu_status);
	
					foreach ($map as $solrKey=>$userText){
						if(in_array($solrKey, $this->post['premiumads_columns'])) {
	
							if(!is_null($story1->{$solrKey})){
								if(preg_match('/date$/', $solrKey)){
									if($story1->{$solrKey}){
										$row[] = '"'.date('d-m-Y',$story1->{$solrKey}).'"';
										$windExcel[] = date('Y-m-d',$story1->{$solrKey});
									}else{
										$row[] = '"NA"';
										$windExcel[] = "NA";
									}
								}
								elseif (is_array($story1->{$solrKey})){
									if (!$story1->{$solrKey}[0]){
										$row[] = '"NA"';
										$windExcel[] = "NA";
									}else {
										$row[] = '"'.$story1->{$solrKey}[0].'"';
										$windExcel[] = $story1->{$solrKey}[0];
									}
								}
								else{
									if (!$story1->{$solrKey}){
										$row[] = '"NA"';
										$windExcel[] = "NA";
									}else {
										$row[] = '"'.$story1->{$solrKey}.'"';
										$windExcel[] = $story1->{$solrKey};
									}
								}
							}else{
								$row[] = '"NA"';
								$windExcel[] = "NA";
							}
						}
					}
					$winFinal[]=$windExcel;
					unset($windExcel);
	
					$content[] = join($this->separator, $row);
					unset($row);
					$srNo++;
				}
			}

			$key = md5(serialize($this->post));
			$fileName = $this->sectionName.'_'.date('d-m-Y',strtotime('now')).'_'.$key.'.csv';
			$filePath = BASE_PATH_CSV.'/'.$fileName;
			//write to csv file

			if(preg_match("/Windows/", $_SERVER["HTTP_USER_AGENT"])) {


				$xls = new Quikr_ExcelXML('UTF-8', true, $fileName);
				$xls->addArray($winFinal);
				$contents = $xls->generateXML($fileName);

				$handle = fopen($filePath,'w');
				fwrite($handle,$contents);
				fclose($handle);


				header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
				header("Content-Disposition: inline; filename=\"" . $fileName . ".xls\"");
				header("Pragma: no-cache");
				header("Expires: 0");

				readfile($filePath);
				exit;

			}else {
				$handle = fopen($filePath,'w');
				fwrite($handle,join("\n", $content));
				fclose($handle);
				shell_exec('zip -j "'.BASE_PATH_CSV.'/'.$fileName.'.zip" "'.BASE_PATH_CSV.'/'.$fileName.'"');
				//send the zip file
				$csvFileLink = BASE_URL.'/assets/csv/'.$fileName.".zip";
				header("Content-type: application/zip");
				header("Content-Disposition: attachment; filename=".$fileName.".zip");
				header("Pragma: no-cache");
				header("Expires: 0");
				readfile($filePath.".zip");
				exit;
			}

		}

	}

	protected function ddmmyyyToTimestamp($date) {
		return strtotime($date);
	}

	public function getDate($value){
		if (is_null($value) || $value==0){
			return "NA";
		}else {
			return date('d-m-Y',$value);
		}
	}

	public function getResults($forExcel=false,$cashin=false) {

		$this->setColumns();
		if(!$cashin){
			$this->buildQeueryForPremiumAd();
			$this->buildQueryForCity();
			$this->buildQueryForLocality();
			$this->buildQueryForMetacategory();
			$this->buildQueryForSubcategory();
			$this->buildQueryForPackOrderId();
			$this->buildQueryForPackId();
			$this->buildQueryForUserEmailId();
			$this->buildQueryForUserId();
			$this->buildQueryForUserMobileNo();
			$this->buildQueryForPaymentMode();
			$this->buildQueryForTpslId();
			$this->buildQueryForWantOffer();
			$this->buildQueryForChequeNumber();
			$this->buildQueryForPaymentFor();
			$this->buildQueryForAdStatus();
			$this->buildQueryForAttributes();
			$this->buildQueryForOrderCreatedDate();
			$this->buildQueryForOrderActivatedDate();
			$this->buildQueryForOrderExpiredDate();
			$this->buildQueryForOrderAdminDeleteDate();
			$this->buildQueryForOrderRefundDate();
			$this->buildQueryForOrderAccounting();
			$this->buildQueryForOrderUserDeleteDate();
			$this->buildQueryForOrderId();
			$this->buildQueryForAdId();
			$this->buildQueryForPaymentStatus();
			$this->biuldQueryForSmb();
		}else {

			if($cashin=="accruals"){
				$from = $this->ddmmyyyToTimestamp($this->post['cashin_date_from']);
				$to = $this->ddmmyyyToTimestamp($this->post['cashin_date_to'])+TO_DATE_INCREMENT;


				$this->queryArray[] = '(premiumads_product_type:Ad AND premiumads_payment_status:(Successful OR Refund) AND premiumads_actual_accounting:['.$from.' TO '.$to.'])';
				
				
				$this->queryArray[] = ' OR (premiumads_product_type:Ad AND premiumads_payment_status:Refund AND premiumads_actual_refund_accounting:['.$from.' TO '.$to.'])';
								
				$this->queryArray[] = ' OR (premiumads_product_type:Ad AND premiumads_payment_status:PaymentAdminDeleted AND premiumads_payment_type:UsedCredit AND (premiumads_actual_refund_accounting:['.$from.' TO '.$to.'] OR premiumads_actual_accounting:['.$from.' TO '.$to.']))';
				
				$this->queryArray[] = 'OR (premiumads_product_type:VolumeDiscount AND premiumads_vdu_status:2 AND premiumads_vdu_remaining_credit:[1 TO *] AND premiumads_vdu_expiry_date:['.$from.' TO '.$to.'])';

				//$this->queryArray[] = ' AND (premiumads_ad_order_activated_date:['.$from.' TO '.$to.'] OR premiumads_ad_order_accounting_date:['.$from.' TO '.$to.'])';
				//$this->queryArray[] = '(premiumads_ad_order_activated_date:['.$from.' TO '.$to.'] OR premiumads_ad_order_accounting_date:['.$from.' TO '.$to.'])';
			}else{
				//OR (premiumads_product_type:VolumeDiscount AND premiumads_vdu_status:2 AND premiumads_vdu_remaining_credit:[1 TO *])
				/*$from = $this->ddmmyyyToTimestamp($this->post['cashin_date_from']);
				$to = $this->ddmmyyyToTimestamp($this->post['cashin_date_to'])+TO_DATE_INCREMENT;


				$this->queryArray[] = '(premiumads_product_type:Ad)';

				$this->queryArray[] = ' AND (premiumads_payment_status:Successful) AND NOT(premiumads_ad_status:"Admin deleted") AND';
				$this->queryArray[] = '(premiumads_ad_order_activated_date:['.$from.' TO '.$to.'])';*/

				/*
				* $this->queryArray[] = '((NOT(premiumads_payment_type:UsedCredit) AND premiumads_product_type:(Ad OR VolumeDiscount)) OR ';
				$this->queryArray[] = '(premiumads_usedcredits_pack_activated_date:[* TO '.($from-1).'] AND premiumads_product_type:Ad AND premiumads_payment_type:UsedCredit))';
				$this->queryArray[] = ' AND (premiumads_payment_status:Successful) AND ';
				$this->queryArray[] = '(premiumads_ad_order_activated_date:['.$from.' TO '.$to.'])';
				* */
			}
		}
//                $auth = Zend_Auth::getInstance();
//                $usertype = $auth->getIdentity()->user_type;
//                if($usertype == "ro") {
//                    $this->queryArray[] = "+(premiumads_vd_ro_name:\"".$auth->getIdentity()->username."\")";
//                }
                

		if(empty($this->queryArray)) {
			$this->queryString = urlencode(trim('*:*'));
		} else {
			//$this->queryString = urlencode(trim(implode(' AND ', $this->queryArray),'+'));
			if($cashin){
				$this->queryString = urlencode(trim(implode(' ', $this->queryArray),'+'));
			}else{
				$this->queryString = urlencode(trim(implode(' AND ', $this->queryArray),'+'));
			}
		}

		$this->buildSolrQueryString();
		if($forExcel) {
			$this->parseXmlDataForExcel();
		} else {
			$this->parseXmlData();
		}
	}

	public function getfacetCountForSummarize($f,$t,$byDateOf,$facetField='',$isMTD=false) {

		$this->buildQeueryForPremiumAd();
		$this->buildQueryForCity();
		$this->buildQueryForMetacategory();
		$this->buildQueryForSubcategory();
		$this->buildQueryForPackOrderId();
		$this->buildQueryForPackId();
		$this->buildQueryForUserEmailId();
		$this->buildQueryForUserId();
		$this->buildQueryForUserMobileNo();
		$this->buildQueryForPaymentMode();
		$this->buildQueryForTpslId();
		$this->buildQueryForWantOffer();
		$this->buildQueryForPaymentFor();
		$this->buildQueryForAdStatus();
		$this->buildQueryForAttributes();
		$this->buildQueryForOrderId();
		$this->buildQueryForPaymentStatus();
                $auth = Zend_Auth::getInstance();
//                $usertype = explode(",",$auth->getIdentity()->user_type);
//                if($usertype == "ro") {
//                    $this->queryArray[] = "+(premiumads_vd_ro_name:\"".$auth->getIdentity()->username."\")";
//                }
                
                
                
		if($byDateOf=="premiumads_ad_order_created_date"){
			//			if($facetField)$this->queryArray[] = '+(premiumads_ad_order_created_date:['.$f.' TO '.$t.'])';
			//			else
			$this->queryArray[] = '+(premiumads_ad_order_created_date:['.$f.' TO '.$t.'])';
			$this->facetQstring[] = '{!key='.$byDateOf.'}premiumads_ad_order_created_date:['.$f.' TO '.$t.']';
		}else{
			$this->buildQueryForOrderCreatedDate();
		}

		if($byDateOf=="premiumads_ad_order_activated_date"){
			//			if($facetField)$this->queryArray[] = '+(premiumads_ad_order_activated_date:['.$f.' TO '.$t.'])';
			//			else
			$this->queryArray[] = '+(premiumads_ad_order_activated_date:['.$f.' TO '.$t.'])';
			$this->facetQstring[] = '{!key='.$byDateOf.'}premiumads_ad_order_activated_date:['.$f.' TO '.$t.']';
		}else{
			$this->buildQueryForOrderActivatedDate();
		}

		if($byDateOf=="premiumads_ad_order_expiry_date"){
			//			if($facetField)$this->queryArray[] = '+(premiumads_ad_order_expiry_date:['.$f.' TO '.$t.'])';
			//			else
			$this->queryArray[] = '+(premiumads_ad_order_expiry_date:['.$f.' TO '.$t.'])';
			$this->facetQstring[] = '{!key='.$byDateOf.'}premiumads_ad_order_expiry_date:['.$f.' TO '.$t.']';
		}else{
			$this->buildQueryForOrderExpiredDate();
		}


		if($byDateOf=="premiumads_admin_refund_date"){
			//			if($facetField)$this->queryArray[] = '+(premiumads_refund_date:['.$f.' TO '.$t.'])';
			//			else
			$this->queryArray[] = '+(premiumads_refund_date:['.$f.' TO '.$t.'])';
			$this->facetQstring[] = '{!key='.$byDateOf.'}premiumads_refund_date:['.$f.' TO '.$t.']';
		}else{
			$this->buildQueryForOrderAdminDeleteDate();
		}


		if($byDateOf=="premiumads_user_refund_date"){
			//			if($facetField)$this->queryArray[] = '+(premiumads_refund_date:['.$f.' TO '.$t.'])';
			//			else
			$this->queryArray[] = '+(premiumads_refund_date:['.$f.' TO '.$t.'])';
			$this->facetQstring[] = '{!key='.$byDateOf.'}premiumads_refund_date:['.$f.' TO '.$t.']';
		}else{
			$this->buildQueryForOrderUserDeleteDate();
		}



		if(empty($this->queryArray)) {
			$this->queryString = urlencode(trim('*:*'));
		} else {
			$this->queryString = urlencode(trim(implode(' AND ', $this->queryArray),'+'));
		}

                
                
		$solrVars = array(
		'indent'    =>  $this->indent,
		'version'   =>  $this->version,
		'start'     =>  $this->start,
		'rows'     =>  '0',
		'facet'		=> 'true',
		'wt'        => $this->wt,
		//'facet.field' => $this->fl,
		'q'         => trim($this->queryString,'+'),
		'facet.query' => urlencode(trim(join('', $this->facetQstring),'+'))
		);


		if($facetField) {
			$solrVars['facet.field'] = $facetField;
			$solrVars['facet.limit'] = '-1';
			$solrVars['facet.mincount'] = '1';
			$solrVars['facet.sort'] = 'count';
			unset($solrVars["facet.query"]);

		}

		if($isMTD){
			$solrVars['stats'] = 'true';
			$solrVars['stats.field'] = 'premiumads_amount';
		}


		$solrVarsStr = '';
		foreach($solrVars as $key => $val) {
			$solrVarsStr .= $key.'='.trim($val).'&';
		}

		//this is final query
		$finalUrl =  rtrim($this->solrUrl.'select?'.$solrVarsStr,'&');

		try {
			$obj = new Utility_SolrQueryAnalyzer($finalUrl,__FILE__.' at line '.__LINE__);
			$data = $obj->init();
		} catch (Exception $e) {
			trigger_error($e->getMessage());
		}



		$returnData = null;

		if(!empty($data)) {
			$xmlData = json_decode($data); //simplexml_load_string($data);

			if($facetField) {
				$returnData =  count($xmlData->facet_counts->facet_fields->premiumads_user_id)/2;
			}

			$dataArray =  $xmlData->facet_counts->facet_queries->{$byDateOf};
			$returnData =  $dataArray;

			if($isMTD){
				return array("count"=>$returnData, "amount"=>$xmlData->stats->stats_fields->premiumads_amount->sum);
			}else{
				return $returnData;
			}
		}
	}

	public function buildSolrQueryString() {

		$solrVars = array(
		'indent'    =>  $this->indent,
		'version'   =>  $this->version,
		'fq'        =>  $this->fq,
		'start'     =>  $this->start,
		'rows'      =>  $this->rows,
		'fl'        =>  $this->fl,
		//'qt'        =>  $this->qt,
		'wt'        =>  $this->wt,
		'explainOther'=> $this->explainOther,
		'hl.fl'     =>  $this->hl_fl,
		'q'         => trim($this->queryString,'+')
		);

		$solrVarsStr = '';
		foreach($solrVars as $key => $val) {
			$solrVarsStr .= $key.'='.trim($val).'&';
		}

		$this->finalUrl =  rtrim($this->solrUrl.'select?'.$solrVarsStr,'&');
		echo $this->finalUrl;
	}
	protected function buildQueryForCity() {
		if(!empty($this->post['premiumads_filter_city'])) {
			if($this->post['premiumads_filter_city'] == 'all') {
				$this->queryArray[] = '+(premiumads_city_id:*)';
			} else {
				$this->queryArray[] = '+(premiumads_city_id:'.trim($this->post['premiumads_filter_city']).')';
			}
		}
	}

	protected function buildQueryForLocality() {
		if(!empty($this->post['premiumads_filter_localities'])) {
			if($this->post['premiumads_filter_city'] == '0') {
				$this->queryArray[] = '+(premiumads_ad_localities:*)';
			} else {
				$this->queryArray[] = '+(premiumads_ad_localities:"'.trim($this->post['premiumads_filter_localities']).'")';
			}

		}
	}

	protected function buildQueryForPackOrderId(){
		if(!empty($this->post['premiumads_filter_pack_order_id'])) {
			$this->queryArray[] = '+(premiumads_pack_order_id:'.trim($this->post['premiumads_filter_pack_order_id']).')';
		}
	}

	protected function buildQueryForPackId(){
		if(!empty($this->post['premiumads_filter_pack_id'])) {
			$this->queryArray[] = '+(premiumads_pack_id:'.trim($this->post['premiumads_filter_pack_id']).')';
		}
	}

	protected function buildQueryForUserEmailId(){
		if(!empty($this->post['premiumads_filter_user_email'])){
			$this->queryArray[]='+(premiumads_user_email:'.trim($this->post['premiumads_filter_user_email']).')';
		}
	}

	protected function buildQueryForUserId(){
		if(!empty($this->post['premiumads_filter_user_id'])) {
			$this->queryArray[]='+(premiumads_user_id:'.trim($this->post['premiumads_filter_user_id']).')';
		}
	}

	protected function buildQueryForUserMobileNo(){
		if(!empty($this->post['premiumads_filter_user_mobile'])) {
			$this->queryArray[]='+(premiumads_user_mobile:'.trim($this->post['premiumads_filter_user_mobile']).')';
		}
	}

	protected function buildQueryForTpslId(){
		if(!empty($this->post['premiumads_filter_tpslid'])) {
			$this->queryArray[]='+(premiumads_tpsl_id:'.trim($this->post['premiumads_filter_tpslid']).')';
		}
	}


	protected function buildQueryForWantOffer() {

		$queryString = '';
		if(!empty($this->post['premiumads_filter_ad_type'])) {

			if(count($this->post['premiumads_filter_ad_type']) == 1) {
				if($this->post['premiumads_filter_ad_type'][0] == 'offer') {
					$this->queryArray[] = '+(premiumads_ad_type:Offer)';

				} else if($this->post['premiumads_filter_ad_type'][0] == 'want') {
					$this->queryArray[] = '+(premiumads_ad_type:Want)';
				}

			} else if(count($this->post['premiumads_filter_ad_type']) == 2) {
				$this->queryArray[] = '+((premiumads_ad_type:Offer) OR (premiumads_ad_type:Want))';
			}
		}
	}


	protected function buildQueryForChequeNumber(){
		$queryString = '';
		if(!empty($this->post['premiumads_filter_cheque_number'])) {
			$this->queryArray[] = '+(premiumads_cheque_no:'.trim($this->post['premiumads_filter_cheque_number']).')';
		}
	}

	protected function buildQeueryForPremiumAd(){
		$this->queryArray[] = '+(premiumads_product_type:Ad)';
	}

	protected function buildQueryForPaymentFor(){
		$queryString = '';
		$map = array('top_of_page'=>'Top', 'urgent'=>'Highlight', 'top_of_page_+_urgent'=>'Top-Highlight');

		$qmap = array();
		foreach ($this->post['premiumads_filter_payment_for'] as $selection){

			$qmap[] = 'premiumads_payment_for:'.$map[$selection];
		}
		if($qmap){
			$this->queryArray[] = '+('.join(' OR ', $qmap).')';
		}
	}


	protected function buildQueryForAdStatus(){
		$queryString = '';
		$map = array('active'=>'Active','expire'=>'Expired','user_deleted'=>'User deleted','admin_delete'=>'Admin deleted','flag_and_delay'=>'Flag and Delay');

		$qmap = array();
		foreach ($this->post['premiumads_filter_status'] as $selection){

			$qmap[] = 'premiumads_ad_status:"'.$map[$selection].'"';
		}
		if($qmap){
			$this->queryArray[] = '+('.join(' OR ', $qmap).')';
		}
	}

	protected function buildQueryForMetacategory() {
		if(!empty($this->post['premiumads_filter_metacat'])) {
			if($this->post['premiumads_filter_metacat'] == 'all') {
				//if doing a global search based on city
				if($this->post['premiumads_filter_metacat'] == 'all') {
					$this->queryArray[] = '+(premiumads_global_metacategory_id:[* TO *])';
				} else { //doing city specific search
					$this->queryArray[] = '+(premiumads_metacategory_id:[* TO *])';
				}
			} else {
				//if doing a global search based on city
				if($this->post['premiumads_filter_city'] == 'all') {
					$this->queryArray[] = '+(premiumads_global_metacategory_id:'.trim($this->post['premiumads_filter_metacat']).')';
				} else {
					//doing city specific search
					$this->queryArray[] = '+(premiumads_metacategory_id:'.trim($this->post['premiumads_filter_metacat']).')';
				}
			}
		}
	}


	protected function buildQueryForSubcategory() {
		if(!empty($this->post['premiumads_filter_subcat'])) {
			if($this->post['premiumads_filter_subcat'] == 'all') {
				//if doing a global search based on city
				if($this->post['premiumpacks_filter_city'] == 'all') {
					$this->queryArray[] = '+(premiumads_global_subcategory_id:[* TO *])';
				} else {
					//doing city specific search
					$this->queryArray[] = '+(premiumads_subcategory_id:[* TO *])';
				}

			} else {
				//if doing a global search based on city
				if($this->post['premiumads_filter_city'] == 'all') {
					$this->queryArray[] = '+(premiumads_global_subcategory_id:'.trim($this->post['premiumads_filter_subcat']).')';
				} else {
					//doing city specific search
					$this->queryArray[] = '+(premiumads_subcategory_id:'.trim($this->post['premiumads_filter_subcat']).')';
				}
			}
		}
	}





	protected function buildQueryForPaymentMode(){
		$queryString = '';
		$map = array('online'=>'Online', 'cheque'=>'Cheque', 'tanla'=>'Tanla', 'usedcredit'=>'UsedCredit','autorenew'=>'AutoRenew', 'promo'=>"Promo");

		$qmap = array();
		foreach ($this->post['premiumads_filter_payment_mode'] as $selection){

			$qmap[] = 'premiumads_payment_type:'.$map[$selection];
		}
		if($qmap){
			$this->queryArray[] = '+('.join(' OR ', $qmap).')';
		}
	}

	protected function buildQueryForAttributes(){

		$map = Zend_Registry::get('ALLOWED_ATTRIBUTES');

		foreach($map as $attrKey){
			$qmap = array();
			foreach ($this->post[$attrKey] as $selection){
				$qmap[] = '"'.strtolower($selection).'"';
			}

			if($qmap){
				$this->queryArray[] = '+(attr_'.strtolower($attrKey).':('.join( ' OR ', $qmap).'))';
			}
		}


	}


	protected function buildQueryForOrderActivatedDate(){
		if(!empty($this->post['premiumads_filter_ad_order_activated_date_from']) &&
		!empty($this->post['premiumads_filter_ad_order_activated_date_to'])) {
			$from = $this->ddmmyyyToTimestamp($this->post['premiumads_filter_ad_order_activated_date_from']);
			$to = $this->ddmmyyyToTimestamp($this->post['premiumads_filter_ad_order_activated_date_to'])+TO_DATE_INCREMENT;
			$this->queryArray[] = '+(premiumads_ad_order_activated_date:['.$from.' TO '.$to.']) AND NOT(premiumads_ad_status:"Admin deleted")';
		}
	}

	protected function buildQueryForOrderCreatedDate(){
		if(!empty($this->post['premiumads_filter_ad_order_created_date_from']) &&
		!empty($this->post['premiumads_filter_ad_order_created_date_to'])) {
			$from = $this->ddmmyyyToTimestamp($this->post['premiumads_filter_ad_order_created_date_from']);
			$to = $this->ddmmyyyToTimestamp($this->post['premiumads_filter_ad_order_created_date_to'])+TO_DATE_INCREMENT;
			$this->queryArray[] = '+(premiumads_ad_order_created_date:['.$from.' TO '.$to.'])';
		}
	}


	protected function buildQueryForOrderExpiredDate(){
		if(!empty($this->post['premiumads_filter_ad_order_expiry_date_from']) &&
		!empty($this->post['premiumads_filter_ad_order_expiry_date_to'])) {
			$from = $this->ddmmyyyToTimestamp($this->post['premiumads_filter_ad_order_expiry_date_from']);
			$to = $this->ddmmyyyToTimestamp($this->post['premiumads_filter_ad_order_expiry_date_to'])+TO_DATE_INCREMENT;
			$this->queryArray[] = '+(premiumads_ad_order_expiry_date:['.$from.' TO '.$to.'])';
		}
	}


	protected function buildQueryForOrderAdminDeleteDate(){
		if(!empty($this->post['premiumads_filter_admin_order_date_from']) &&
		!empty($this->post['premiumads_filter_admin_order_date_to'])) {
			$from = $this->ddmmyyyToTimestamp($this->post['premiumads_filter_admin_order_date_from']);
			$to = $this->ddmmyyyToTimestamp($this->post['premiumads_filter_admin_order_date_to'])+TO_DATE_INCREMENT;
			$this->queryArray[] = '+(premiumads_refund_date:['.$from.' TO '.$to.'])';
		}
	}


	protected function buildQueryForOrderRefundDate(){
		if(!empty($this->post['premiumads_filter_refund_date_from']) &&
		!empty($this->post['premiumads_filter_refund_date_to'])) {
			$from = $this->ddmmyyyToTimestamp($this->post['premiumads_filter_refund_date_from']);
			$to = $this->ddmmyyyToTimestamp($this->post['premiumads_filter_refund_date_to'])+TO_DATE_INCREMENT;
			$this->queryArray[] = '+(premiumads_ad_order_refund_accounting_date:['.$from.' TO '.$to.'])';
		}
	}


	protected function buildQueryForOrderAccounting(){
		if(!empty($this->post['premiumads_filter_accounting_date_from']) &&
		!empty($this->post['premiumads_filter_accounting_date_to'])) {
			$from = $this->ddmmyyyToTimestamp($this->post['premiumads_filter_accounting_date_from']);
			$to = $this->ddmmyyyToTimestamp($this->post['premiumads_filter_accounting_date_to'])+TO_DATE_INCREMENT;
			$this->queryArray[] = '+(premiumads_ad_order_accounting_date:['.$from.' TO '.$to.'])';
		}
	}



	protected function buildQueryForOrderUserDeleteDate(){
		if(!empty($this->post['premiumads_filter_user_order_date_from']) &&
		!empty($this->post['premiumads_filter_user_order_date_to'])) {
			$from = $this->ddmmyyyToTimestamp($this->post['premiumads_filter_user_order_date_from']);
			$to = $this->ddmmyyyToTimestamp($this->post['premiumads_filter_user_order_date_to'])+TO_DATE_INCREMENT;
			$this->queryArray[] = '+(premiumads_refund_date:['.$from.' TO '.$to.']) AND +(premiumads_payment_status:PaymentAdminDeleted) AND +(premiumads_ad_status:"User deleted")';
		}
	}

	protected function buildQueryForOrderId(){
		if(!empty($this->post['premiumads_filter_order_id'])){
			$this->queryArray[]='+(premiumads_order_id:'.trim($this->post['premiumads_filter_order_id']).')';
		}
	}

	protected function buildQueryForAdId(){
		if(!empty($this->post['premiumads_filter_ad_id'])){
			$this->queryArray[]='+(premiumads_ad_id:'.trim($this->post['premiumads_filter_ad_id']).')';
		}
	}

	protected function buildQueryForPaymentStatus(){
		$queryString = '';
		$map = array('initialize'=>'Initialize','pending'=>'Pending','successful'=>'Successful','failure'=>'Failure','noresponse'=>'NoResponse','paymentafter3attempts'=>'PaymentAfter3Attempts','converttofreebeforepayment'=>'ConvertToFreeBeforePayment','refund'=>'Refund','paymentadmindeleted'=>'PaymentAdminDeleted');

		$qmap = array();
		foreach ($this->post['premiumads_filter_payment_status'] as $selection){

			$qmap[] = 'premiumads_payment_status:"'.$map[$selection].'"';
		}

		if($qmap){
			$this->queryArray[] = '+('.join(' OR ', $qmap).')';
		}
	}

	public function biuldQueryForSmb(){
		$queryString = '';
        if(!empty($this->post['premiumads_filter_smb'])) {

            if(count($this->post['premiumads_filter_smb']) == 1) {
                if($this->post['premiumads_filter_smb'][0] == 'yes') {
                    $this->queryArray[] = '+(premiumads_order_smb:1)';

                } else if($this->post['premiumads_filter_smb'][0] == 'no') {
                    $this->queryArray[] = '+(premiumads_order_smb:0)';
                }

            } else if(count($this->post['premiumads_filter_smb']) == 2) {
                $this->queryArray[] = '+((premiumads_order_smb:1) OR (premiumads_order_smb:0))';
            }
        }
	}
	

	public function prepareDataForWinow(){

	}
        
        function getSingleFieldFromPremiumAds($id, $fieldToReturn = '') {
        $solrVars = array(
            'indent' => $this->indent,
            'version' => $this->version,
            'fq' => $this->fq,
            'start' => $this->start,
            'rows' => 1,
            'wt' => $this->wt,
            'fl' => $fieldToReturn,
            //'qt'        =>  $this->qt,
            'explainOther' => $this->explainOther,
            'hl.fl' => $this->hl_fl,
            'q' => 'premiumads_ad_id:' . $id
        );

        $solrVarsStr = '';
        foreach ($solrVars as $key => $val) {
            $solrVarsStr .= $key . '=' . trim($val) . '&';
        }

        //this is final query
        $this->finalUrl = rtrim($this->solrUrl . 'select?' . $solrVarsStr, '&');

        //$data = file_get_contents($this->finalUrl);
        //echo $this->finalUrl;
        try {
            $obj = new Utility_SolrQueryAnalyzer($this->finalUrl, __FILE__ . ' at line ' . __LINE__);
            $data = $obj->init();
        } catch (Exception $e) {
            trigger_error($e->getMessage());
        }

        if (!empty($data)) {
            $xmlData = json_decode($data);

            $count = $xmlData->response->numFound;
            if ($count > 0) {
                //print_r($xmlData);
                return $xmlData;
            }
        }
    }

}
?>