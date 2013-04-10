<?php


class Model_PremiumpackSolr {
	public $post;
	public $queryString;

	public $facetQstring;

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

	protected function setColumns() {		
		$postedColumns = $this->post['premiumpacks_columns'];
		//$this->fl = 'premiumads_order_id,';



		$map = self::getMap();

		$this->fl = '';



		foreach($postedColumns as $key => $val) {

			//few exception where we need to change the caption
			if($val == 'id') continue;
			//if($val == 'metacategory_name') $val = 'category';
			//  if($val == 'subcategory_name') $val = 'subcategory';
			//$this->columnsToShow['columns'][] = ucwords(strtolower(str_replace('_', ' ', $val)));

			$this->columnsToShow['columns'][] = $map[$val];
			$this->fl .= ($val=='premiumads_vd_previous_pack_bool'?'premiumads_vd_previous_pack,':$val.',');
		}
		$this->fl .= "score";
		
		if ($this->isAccrual || $this->isCashin){
			$newFl = 'premiumads_amount,premiumads_net_amount,premiumads_payment_status,premiumads_product_type,premiumads_payment_type,premiumads_actual_refund_accounting,premiumads_actual_accounting';
			
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
		
		//        $this->columnsToShow['columns'] = array('Email','Fullname', 'Mobile','City',
		//            'Is Registered', 'Registration Date', 'Last Login Date','No Of Ads','No Of Reply','No Of Alerts','Is Bulk Allowed');
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

		//this is final query
		$this->finalUrl =  rtrim($this->solrUrl.'select?'.$solrVarsStr,'&');
		//echo $this->finalUrl;
	}


	public static function getMap(){
		return array(
		"premiumads_user_name"=>"User name",
		"premiumads_user_email"=>"Email",
                 "premiumads_user_id"=>"User Id",
		"premiumads_user_mobile"=>"Mobile",
		"premiumads_ad_id"=>"Ad Id",
		"premiumads_metacategory_name"=>"Category",
		"premiumads_subcategory_name"=>"Sub-Category",
		"premiumads_city_name"=>"City",
		"premiumads_ad_localities"=>"Locality",
		"premiumads_payment_type"=>"Payment Mode",
		"premiumads_payment_for"=>"Premium Ad Type",
		"premiumads_ad_order_id"=>"Ad Order Id",
		"premiumads_pack_order_id"=>"Pack Order ID",
		"premiumads_vdu_id"=>"Pack ID",
		"premiumads_vdu_status"=>"Pack Status",
		"premiumads_vdu_created_date"=>"Pack Created Date",
		"premiumads_ad_order_activated_date"=>"Pack Activated Date",
		"premiumads_vdu_expiry_date"=>"Pack Expiry Date",
		"premiumads_pausestarttime"=>"Pack Pause Date",
		"premiumads_vd_total_credit"=>"Size of Pack",
		"premiumads_vdu_current_credits_used"=>"Number of Credits used",
		"premiumads_vdu_total_credits_used"=>"Total Number of Credits used Till Date",
		"premiumads_payment_status"=>"Order Payment Status",
		"premiumads_order_smb"=>"BGS",

		"premiumads_vd_previous_pack_bool"=>"Renewal",
		"premiumads_vd_previous_pack"=>"Renewal-ID",

		"premiumads_refund_date"=>"Admin Deleted Date",

		"attr_you_are"=>"Individual Dealer",
		"premiumads_tpsl_id"=>"Tpslid",
		"premiumads_vd_territory_manager_name"=>"Territory Manager Name",
		"premiumads_vd_telemarketer_tl_name"=>"Telemarketer Lead Name",
		"premiumads_vd_telemarketer_name"=>"Telemarketer Name",
		"premiumads_amount"=>"Gross Amount",
		"premiumads_vd_ro_name"=>"RO Name",
		"premiumads_net_amount"=>"Net Amount",
		"premiumads_tax"=>"Tax",
		"premiumads_cheque_no"=>"Cheque Number",
		"premiumads_remark"=>"Cheque Details",

		"premiumads_ad_order_accounting_date"=>"Accounting Date",
		"premiumads_ad_order_refund_accounting_date"=>"Refund Accounting Date",

		"premiumads_extended_product_type"=>"Accrual Type",

		"premiumads_reseller_pack_order_id"=>"Reseller Pack Order Id",
		"premiumads_vdu_force_consume_amount"=>"Force Consume Amount",
		"premiumads_vdu_remaining_credit"=>"Pack Remaining Credits"


		);
	}


	public function getResults($forExcel=false, $cashin=false) {
		//query solr
		//first set the columns to show
		$this->setColumns();

		if(!$cashin){
			//$this->queryArray[] = '+(premiumads_product_type:VolumeDiscount OR premiumads_payment_type:(AutoRenew OR UsedCredit))';

			//now build query for every field
			$this->buildQueryForCity();
			$this->buildQueryForLocality();
			$this->buildQueryForMetacategory();
			$this->buildQueryForSubcategory();
			$this->buildQueryForPaymentMode();
			$this->buildQueryForPaymentFor();
			$this->buildQueryForPackStatus();
			$this->buildQueryForProductType();
			$this->buildQueryForPaymentStatus();
			$this->buildQueryForEmail();
			$this->buildQueryForMobile();
			$this->buildQueryForUserId();
			$this->buildQueryForPackOrderId();
			$this->buildQueryForPackId();
			$this->buildQueryForResellerPackOrderId();
			$this->buildQueryForResellerPackId();
			$this->buildQueryForTpslId();
			$this->buildQueryForAdId();
			$this->buildQueryForAdOrderId();
			$this->buildQueryForChequeNumber();
			$this->buildQueryForAdminDeletedDate();
			$this->buildQueryForRefundDate();
			$this->buildQueryForAccountingDate();
			$this->buildQueryForCreateDate();
			$this->buildQueryForActivatedDate();
			$this->buildQueryForExpiredDate();
			$this->buildQueryForPauseDate();
			$this->buildQueryForUsageDate();
			$this->buildQueryForPackSize();
			$this->buildQueryForPackAmount();
			$this->buildQueryForAttributes();
			$this->biuldQueryForSmb();
		}else{

			if($cashin=="accruals"){
				$from = $this->ddmmyyyToTimestamp($this->post['cashin_date_from']);
				$to = $this->ddmmyyyToTimestamp($this->post['cashin_date_to'])+TO_DATE_INCREMENT;


				/*$this->queryArray[] = '(premiumads_product_type:Ad)';

				$this->queryArray[] = ' AND (premiumads_payment_status:(Successful OR PaymentAdminDeleted)) AND NOT(premiumads_ad_status:"Admin deleted")';
				$this->queryArray[] = 'AND (premiumads_ad_order_activated_date:['.$from.' TO '.$to.'])';
				*/
				$this->queryArray[] = '(premiumads_product_type:Ad AND premiumads_payment_status:(Successful OR Refund) AND premiumads_actual_accounting:['.$from.' TO '.$to.'])';


				$this->queryArray[] = ' OR (premiumads_product_type:Ad AND premiumads_payment_status:Refund AND premiumads_actual_refund_accounting:['.$from.' TO '.$to.'])';
				
				
				$this->queryArray[] = ' OR (premiumads_product_type:Ad AND premiumads_payment_status:PaymentAdminDeleted AND premiumads_payment_type:UsedCredit AND (premiumads_actual_refund_accounting:['.$from.' TO '.$to.'] OR premiumads_actual_accounting:['.$from.' TO '.$to.']))';

				$this->queryArray[] = 'OR (premiumads_product_type:VolumeDiscount AND premiumads_vdu_status:2 AND premiumads_vdu_remaining_credit:[1 TO *] AND premiumads_vdu_expiry_date:['.$from.' TO '.$to.'])';
			}else{
				$from = $this->ddmmyyyToTimestamp($this->post['cashin_date_from']);
				$to = $this->ddmmyyyToTimestamp($this->post['cashin_date_to'])+TO_DATE_INCREMENT;


				/*$this->queryArray[] = 'NOT((premiumads_payment_type:(UsedCredit OR AutoRenew)) AND  (premiumads_product_type:Ad)) AND NOT((premiumads_product_type:VolumeDiscount) AND (premiumads_vdu_admintype:"rsuser")) AND premiumads_product_type:(Ad OR VolumeDiscount)';

				//$this->queryArray[] = '(premiumads_usedcredits_pack_activated_date:[2 TO '.($from-1).'] AND premiumads_product_type:Ad AND premiumads_payment_type:UsedCredit))';
				$this->queryArray[] = ' AND (premiumads_payment_status:(Successful OR PaymentAdminDeleted)) AND NOT(premiumads_ad_status:"Admin deleted")';
				$this->queryArray[] = 'AND (premiumads_ad_order_activated_date:['.$from.' TO '.$to.'])';
				*/

				$this->queryArray[] = 'premiumads_product_type:(Ad OR VolumeDiscount) AND NOT(premiumads_payment_type:(UsedCredit OR AutoRenew)) AND NOT(premiumads_vdu_admintype:rsuser) AND ';


				$this->queryArray[] = '((premiumads_payment_status:(Successful OR Refund)  AND premiumads_actual_accounting:['.$from.' TO '.$to.'])';


				$this->queryArray[] = ' OR (premiumads_payment_status:Refund AND premiumads_actual_refund_accounting:['.$from.' TO '.$to.']))';

				/*
				* $this->queryArray[] = '((NOT(premiumads_payment_type:UsedCredit) AND premiumads_product_type:(Ad OR VolumeDiscount)) OR ';
				$this->queryArray[] = '(premiumads_usedcredits_pack_activated_date:[* TO '.($from-1).'] AND premiumads_product_type:Ad AND premiumads_payment_type:UsedCredit))';
				$this->queryArray[] = ' AND (premiumads_payment_status:Successful) AND ';
				$this->queryArray[] = '(premiumads_ad_order_activated_date:['.$from.' TO '.$to.'])';
				* */
			}

		}
                
                $auth = Zend_Auth::getInstance();
                $usertype = $auth->getIdentity()->user_type;
                if($usertype == "ro") { 
                    $model = new Model_RoSettings();
                    $rows = $model->getChildrenOfParent($auth->getIdentity()->id);
                    //print_r($rows);
                    if(is_array($rows)) {
                        $t = array();
                        foreach($rows as $k => $v) {
                            $t[] = "premiumads_vd_ro_name:\"".$v["username"]."\"";
                        }
                        if($t){
                            $this->queryArray[] = '+('.join(' OR ', $t).')';
                        }
                    }
                }
                
		if(empty($this->queryArray)) {
			$this->queryString = urlencode(trim('*:*'));
		} else {
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
		//$this->setColumns();

		//now build query for every field

		$this->buildQueryForCity();
		$this->buildQueryForMetacategory();
		$this->buildQueryForSubcategory();

		$this->buildQueryForPaymentMode();

		$this->buildQueryForPaymentFor();

		$this->buildQueryForPackStatus();

		$this->buildQueryForProductType();

		$this->buildQueryForPaymentStatus();

		$this->buildQueryForEmail();

		$this->buildQueryForMobile();

		$this->buildQueryForUserId();

		$this->buildQueryForPackOrderId();

		$this->buildQueryForPackId();

		$this->buildQueryForTpslId();
                
                $auth = Zend_Auth::getInstance();
                $usertype = $auth->getIdentity()->user_type;
                if($usertype == "ro") { 
                    $model = new Model_RoSettings();
                    $rows = $model->getChildrenOfParent($auth->getIdentity()->id);
                    //print_r($rows);
                    if(is_array($rows)) {
                        $t = array();
                        foreach($rows as $k => $v) {
                            $t[] = "premiumads_vd_ro_name:\"".$v["username"]."\"";
                        }
                        if($t){
                            $this->queryArray[] = '+('.join(' OR ', $t).')';
                        }
                    }
                }


		if($byDateOf=="premiumads_refund_date"){
			//if($facetField)$this->queryArray[] = '+(premiumads_refund_date:['.$f.' TO '.$t.'])';
			//else
			$this->queryArray[] = '+(premiumads_refund_date:['.$f.' TO '.$t.'])';
			$this->facetQstring[] = '{!key='.$byDateOf.'}premiumads_refund_date:['.$f.' TO '.$t.']';
		}else{
			$this->buildQueryForAdminDeletedDate();
		}


		if($byDateOf=="premiumads_vdu_created_date"){
			//if($facetField)$this->queryArray[] = '+(premiumads_vdu_created_date:['.$f.' TO '.$t.'])';
			//else
			$this->queryArray[] = '+(premiumads_vdu_created_date:['.$f.' TO '.$t.'])';
			$this->facetQstring[] = '{!key='.$byDateOf.'}premiumads_vdu_created_date:['.$f.' TO '.$t.']';
		}
		else{
			$this->buildQueryForCreateDate();
		}

		if($byDateOf=="premiumads_ad_order_activated_date"){
			//if($facetField)$this->queryArray[] = '+(premiumads_ad_order_activated_date:['.$f.' TO '.$t.'])';
			//else
			$this->queryArray[] = '+(premiumads_ad_order_activated_date:['.$f.' TO '.$t.'])';
			$this->facetQstring[] = '{!key='.$byDateOf.'}premiumads_ad_order_activated_date:['.$f.' TO '.$t.']';
		}else{
			$this->buildQueryForActivatedDate();
		}

		if($byDateOf=="premiumads_vdu_expiry_date"){
			//if($facetField)$this->queryArray[] = '+(premiumads_vdu_expiry_date:['.$f.' TO '.$t.'])';
			//else
			$this->queryArray[] = '+(premiumads_vdu_expiry_date:['.$f.' TO '.$t.'])';
			$this->facetQstring[] = '{!key='.$byDateOf.'}premiumads_vdu_expiry_date:['.$f.' TO '.$t.']';
		}else{
			$this->buildQueryForExpiredDate();
		}

		if($byDateOf=="premiumads_ad_order_created_date"){
			//if($facetField)$this->queryArray[] = '+(premiumads_ad_order_created_date:['.$f.' TO '.$t.'])';
			//else
			$this->queryArray[] = '+(premiumads_ad_order_created_date:['.$f.' TO '.$t.'])';
			$this->facetQstring[] = '{!key='.$byDateOf.'}premiumads_ad_order_created_date:['.$f.' TO '.$t.']';
		}else{
			$this->buildQueryForUsageDate();
		}


		$this->buildQueryForPackSize();

		$this->buildQueryForPackAmount();

		$this->buildQueryForAttributes();


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
		'facet'        => 'true',
		'wt'        => $this->wt,
		//'facet.field' => $this->fl,
		'q'         => trim($this->queryString,'+'),
		'facet.query' => urlencode(trim(join('', $this->facetQstring),'+'))
		);


		if($facetField) {
			$solrVars['facet.field'] = $facetField;
			if($byDateOf=="premiumads_ad_order_created_date"){
				$solrVars['facet.field'] .=  '&facet.field=premiumads_vdu_id';
			}
			$solrVars['facet.limit'] = '-1';
			$solrVars['facet.mincount'] = '1';
			$solrVars['facet.sort'] = 'count';
			unset($solrVars["facet.query"]);
		}elseif($byDateOf=="premiumads_ad_order_created_date"){
			$solrVars['facet.field'] = 'premiumads_vdu_id';
			$solrVars['facet.limit'] = '-1';
			$solrVars['facet.mincount'] = '1';
			$solrVars['facet.sort'] = 'count';
			unset($solrVars["facet.query"]);
		}

		if($isMTD){
			$solrVars['stats'] = 'true';
			if($byDateOf=="premiumads_ad_order_created_date"){
				$solrVars['stats.field'] = 'premiumads_amount';
			}else{
				$solrVars['stats.field'] = 'premiumads_vd_amount';
			}
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
				/* $d = $xmlData->facet_counts->facet_fields->poster_id;
				$iterations = count($d) /2;
				$s = 0;
				for($i=1; $i <= $iterations; $i=$i+2) {
				$s += $d[$i];
				}*/



				// if($byDateOf=="premiumads_ad_order_created_date"){
				//     $returnData =  count($xmlData->facet_counts->facet_fields->premiumads_vdu_id)/2;
				//  }else{
				$returnData =  count($xmlData->facet_counts->facet_fields->premiumads_user_id)/2;
				//    }
			}else{
				if($byDateOf=="premiumads_ad_order_created_date"){
					$returnData =  count($xmlData->facet_counts->facet_fields->premiumads_vdu_id)/2;
				}else{

					$dataArray =  $xmlData->facet_counts->facet_queries->{$byDateOf};
					$returnData =  $dataArray;
				}
			}

			if($isMTD){
				return array("count"=>$returnData, "amount"=>($byDateOf=="premiumads_ad_order_created_date"?$xmlData->stats->stats_fields->premiumads_amount->sum:$xmlData->stats->stats_fields->premiumads_vd_amount->sum));
			}else{
				return $returnData;
			}
		}




	}


	public function parseXmlData() {
		//$data = file_get_contents($this->finalUrl);

		try {
			$obj = new Utility_SolrQueryAnalyzer($this->finalUrl,__FILE__.' at line '.__LINE__);
			$data = $obj->init();
			//print_r($data);
		} catch (Exception $e) {
			trigger_error($e->getMessage());
		}


		if(!empty($data)) {
			$xmlData = json_decode($data); //simplexml_load_string($data);
			$this->totalRecordsFound = $xmlData->response->numFound; //$xmlData->result->attributes()->numFound;

			$users = array();
			$counter = 0;
			$stories = $xmlData->response->docs;
			foreach ($stories as $story) {
				$users['User name'] = $story->premiumads_user_name;
				$users['Email'] = (!$story->premiumads_user_email) ? 'NA': $story->premiumads_user_email;
				$users['User Id'] = (!$story->premiumads_user_id) ? 'NA': $story->premiumads_user_id;
                                $users['Mobile'] = (!$story->premiumads_user_mobile) ? 'NA': $story->premiumads_user_mobile;
				$users['Ad Id'] = (!$story->premiumads_ad_id) ? 'NA': $story->premiumads_ad_id;
				$users['Category'] = (!$story->premiumads_metacategory_name) ? 'NA': $story->premiumads_metacategory_name;
				$users['Sub-Category'] = (!$story->premiumads_subcategory_name) ? 'NA': $story->premiumads_subcategory_name;
				$users['City'] = (!$story->premiumads_city_name) ? 'NA': $story->premiumads_city_name;
				$users['Locality'] = (!$story->premiumads_ad_localities) ? 'NA': $story->premiumads_ad_localities;
				$users['Payment Mode'] = (!$story->premiumads_payment_type) ? 'NA': $story->premiumads_payment_type;
				$users['Premium Ad Type'] = (!$story->premiumads_payment_for) ? 'NA' : $story->premiumads_payment_for;
				$users['Ad Order Id'] = (!$story->premiumads_ad_order_id) ? 'NA':$story->premiumads_ad_order_id;
				$users['Pack Order ID'] = (!$story->premiumads_pack_order_id) ? 'NA': $story->premiumads_pack_order_id;
				$users['Pack ID'] = (!$story->premiumads_vdu_id) ? 'NA': $story->premiumads_vdu_id;
				$users['Pack Status'] = (is_null($story->premiumads_vdu_status)) ? 'NA': self::parseVDStatus($story->premiumads_vdu_status);
				$users['Pack Created Date'] = ($story->premiumads_vdu_created_date) ? date('d-m-Y',$story->premiumads_vdu_created_date) : 'NA';
				$users['Pack Activated Date'] = ($story->premiumads_ad_order_activated_date) ? date('d-m-Y',$story->premiumads_ad_order_activated_date) : 'NA';
				$users['Pack Expiry Date'] = ($story->premiumads_vdu_expiry_date) ? date('d-m-Y',$story->premiumads_vdu_expiry_date) : 'NA';
				$users['Pack Pause Date'] = ($story->premiumads_pausestarttime) ? date('d-m-Y',$story->premiumads_pausestarttime) : 'NA';
				$users['Size of Pack'] = (!$story->premiumads_vd_total_credit) ? 'NA': $story->premiumads_vd_total_credit;
				$users['Number of Credits used'] = (!$story->premiumads_vdu_current_credits_used) ? 'NA': $story->premiumads_vdu_current_credits_used;
				$users['Total Number of Credits used Till Date'] = (is_null($story->premiumads_vdu_total_credits_used)) ? 'NA' : $story->premiumads_vdu_total_credits_used;
				$users['Renewal'] = (!$story->premiumads_vd_previous_pack) ? 'No' : 'Yes';
				$users['Renewal-ID'] = (!$story->premiumads_vd_previous_pack) ? 'NA': $story->premiumads_vd_previous_pack;
				$users['Admin Deleted Date'] = ($story->premiumads_refund_date) ? date('d-m-Y',$story->premiumads_refund_date) : 'NA';
				$users['Tpslid'] = (!$story->premiumads_tpsl_id) ? 'NA': $story->premiumads_tpsl_id;
				$users['Territory Manager Name'] = (!$story->premiumads_vd_territory_manager_name) ? 'NA' :$story->premiumads_vd_territory_manager_name;
				$users['Telemarketer Lead Name'] = (!$story->premiumads_vd_telemarketer_tl_name) ? 'NA' : $story->premiumads_vd_telemarketer_tl_name;
				$users['Telemarketer Name'] = (!$story->premiumads_vd_telemarketer_name) ? 'NA': $story->premiumads_vd_telemarketer_name;
				$users['Gross Amount'] = (!$story->premiumads_amount) ? 'NA': $story->premiumads_amount;
				$users['RO Name'] = (!$story->premiumads_vd_ro_name) ? 'NA' : $story->premiumads_vd_ro_name;
				$users['Individual Dealer']= (!$story->attr_you_are[0])?'NA':$story->attr_you_are[0];
				$users['Net Amount']= (!$story->premiumads_net_amount)?'NA':$story->premiumads_net_amount;
				$users['Tax']= (!$story->premiumads_tax)?'NA':$story->premiumads_tax;
				$users['Cheque Number']= (!$story->premiumads_cheque_no)?'NA':$story->premiumads_cheque_no;
				$users['Cheque Details']= (!$story->premiumads_remark)?'NA':$story->premiumads_remark;
				$users['Order Payment Status'] = (!$story->premiumads_payment_status) ? 'NA': $story->premiumads_payment_status;
				$users['BGS'] =$story->premiumads_order_smb;

				$users['Accounting Date']=($story->premiumads_ad_order_accounting_date) ? date('d-m-Y',$story->premiumads_ad_order_accounting_date) : 'NA';
				$users['Refund Accounting Date']=($story->premiumads_ad_order_refund_accounting_date) ? date('d-m-Y',$story->premiumads_ad_order_refund_accounting_date) : 'NA';

				$users["Accrual Type"] = (!$story->premiumads_extended_product_type)?'NA':$story->premiumads_extended_product_type;

				$users["Reseller Pack Order Id"] = (!$story->premiumads_reseller_pack_order_id)?'NA':$story->premiumads_reseller_pack_order_id;
				
				$users["Force Consume Amount"] = (!$story->premiumads_vdu_force_consume_amount)?'NA':$story->premiumads_vdu_force_consume_amount;

				
				$users["Pack Remaining Credits"] = (!$story->premiumads_vdu_remaining_credit)?'NA':$story->premiumads_vdu_remaining_credit;
				//purvish need to check if accrual or casin was checked
				

				$this->getExtraEntry($users,$story);

				$this->columnsToShow['data'][] = $users;
				unset($users);
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

	public function getDate($value){
		if (is_null($value) || $value==0){
			return "NA";
		}else {
			return date('d-m-Y',$value);
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
				if(in_array($solrKey, $this->post['premiumpacks_columns'])) {
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
	
					$story1->premiumads_vdu_status = self::parseVDStatus($story1->premiumads_vdu_status);
	
					foreach ($map as $solrKey=>$userText){
						if(in_array($solrKey, $this->post['premiumpacks_columns'])) {
	
							$kk = ($solrKey=='premiumads_vd_previous_pack_bool'?'premiumads_vd_previous_pack':$solrKey);
	
							if(!is_null($story1->{$kk})){
								if(preg_match('/date$/', $solrKey) || preg_match('/premiumads_pausestarttime/', $solrKey)){
									if($story1->{$solrKey}){
										$row[] = '"'.date('d-m-Y',$story1->{$solrKey}).'"';
										$windExcel[]= date('Y-m-d',$story1->{$solrKey});//For window
									}else{
										$row[] = '"NA"';
										$windExcel[]="NA";
									}
								}else{
									if($solrKey=='premiumads_vd_previous_pack_bool'){
										$row[] = '"'.($story1->{$kk}?'Yes':'No').'"';
										$windExcel[]=($story1->{$kk}?'Yes':'No');
									}else{
										if(!$story1->{$kk} && !is_int($story1->{$kk})){
											$row[] = '"NA"';
											$windExcel[]="NA";
										}else{
											$row[] = '"'.$story1->{$kk}.'"';
											$windExcel[]= $story1->{$kk};
										}
									}
								}
							}else{
								if($solrKey=='premiumads_vd_previous_pack_bool'){
									$row[] = '"No"';
									$windExcel[]= "No";
								}else{
									$row[] = '"NA"';
									$windExcel[]= "NA";
								}
	
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


	public static function parseVDStatus($value){
		$map = array(0=>'InActive', 1=>'Active', 2=>'Expired', 3=>'User deleted', 4=>'Admin Deleted', 5=>'Zero Credits', 6=>'Refunded',7=>'Paused');
		return $map[$value];
	}


	protected function buildQueryForCity() {
		if(!empty($this->post['premiumpacks_filter_city'])) {
			if($this->post['premiumpacks_filter_city'] == 'all') {
				$this->queryArray[] = '+(premiumads_city_id:*)';
			} else {
				$this->queryArray[] = '+(premiumads_city_id:'.trim($this->post['premiumpacks_filter_city']).')';
			}
		}
	}

	protected function buildQueryForLocality() {
		if(!empty($this->post['premiumpacks_filter_localities'])) {
			if($this->post['premiumpacks_filter_city'] == '0') {
				$this->queryArray[] = '+(premiumads_ad_localities:*)';
			} else {
				$this->queryArray[] = '+(premiumads_ad_localities:"'.trim($this->post['premiumpacks_filter_localities']).'")';
			}

		}
	}


	protected function buildQueryForMetacategory() {
		if(!empty($this->post['premiumpacks_filter_metacat'])) {
			if($this->post['premiumpacks_filter_metacat'] == 'all') {
				//if doing a global search based on city
				if($this->post['premiumpacks_filter_metacat'] == 'all') {
					$this->queryArray[] = '+(premiumads_global_metacategory_id:[* TO *])';
				} else { //doing city specific search
					$this->queryArray[] = '+(premiumads_metacategory_id:[* TO *])';
				}
			} else {
				//if doing a global search based on city
				if($this->post['premiumpacks_filter_city'] == 'all') {
					$this->queryArray[] = '+(premiumads_global_metacategory_id:'.trim($this->post['premiumpacks_filter_metacat']).')';
				} else {
					//doing city specific search
					$this->queryArray[] = '+(premiumads_metacategory_id:'.trim($this->post['premiumpacks_filter_metacat']).')';
				}
			}
		}
	}


	protected function buildQueryForSubcategory() {
		if(!empty($this->post['premiumpacks_filter_subcat'])) {
			if($this->post['premiumpacks_filter_subcat'] == 'all') {
				//if doing a global search based on city
				if($this->post['premiumpacks_filter_city'] == 'all') {
					$this->queryArray[] = '+(premiumads_global_subcategory_id:[* TO *])';
				} else {
					//doing city specific search
					$this->queryArray[] = '+(premiumads_subcategory_id:[* TO *])';
				}

			} else {
				//if doing a global search based on city
				if($this->post['premiumpacks_filter_city'] == 'all') {
					$this->queryArray[] = '+(premiumads_global_subcategory_id:'.trim($this->post['premiumpacks_filter_subcat']).')';
				} else {
					//doing city specific search
					$this->queryArray[] = '+(premiumads_subcategory_id:'.trim($this->post['premiumpacks_filter_subcat']).')';
				}
			}
		}
	}


	protected function buildQueryForPaymentMode(){
		$queryString = '';
		$map = array('online'=>'Online', 'check-cash'=>'Cheque', 'tanla'=>'Tanla', 'used_credit'=>'UsedCredit','autorenew'=>'AutoRenew');

		$qmap = array();
		foreach ($this->post['premiumpacks_filter_payment_mode'] as $selection){

			$qmap[] = 'premiumads_payment_type:'.$map[$selection];
		}
		if($qmap){
			$this->queryArray[] = '+('.join(' OR ', $qmap).')';
		}
	}


	protected function buildQueryForPaymentFor(){
		$queryString = '';
		$map = array('top_of_page'=>'Top', 'urgent'=>'Highlight', 'top_of_page_+_urgent'=>'Top-Highlight');

		$qmap = array();
		foreach ($this->post['premiumads_payment_for'] as $selection){

			$qmap[] = 'premiumads_payment_for:'.$map[$selection];
		}
		if($qmap){
			$this->queryArray[] = '+('.join(' OR ', $qmap).')';
		}
	}

	protected function buildQueryForPackStatus(){
		$queryString = '';
		$map = array('flag_and_delay'=>0, 'active'=>1, 'expire'=>2, 'user_deleted'=>3, 'admin_deleted'=>4, 'zero_credits_remaining'=>5, 'refunded'=>6,'paused'=>7);

		$qmap = array();
		foreach ($this->post['premiumpacks_filter_status'] as $selection){

			$qmap[] = 'premiumads_vdu_status:'.$map[$selection];
		}
		if($qmap){
			$this->queryArray[] = '+('.join(' OR ', $qmap).')';
		}
	}

	protected function buildQueryForProductType(){
		$queryString = '';
		//$map = array('ad'=>'Ad', 'volume_discount'=>'VolumeDiscount');

		$map = array('ad'=>'Ad', 'packs_w/_reseller'=>'VolumeDiscount AND NOT(premiumads_vdu_admintype:rsuser)', 'packs_w/_reseller_distributed'=>'VolumeDiscount');

		$qmap = array();
		foreach ($this->post['premiumads_product_type'] as $selection){
			if($map[$selection]){
				$qmap[] = '(premiumads_product_type:'.$map[$selection].')';
			}
		}
		if($qmap){
			$this->queryArray[] = '+('.join(' OR ', $qmap).')';
		}
	}

	protected function buildQueryForPaymentStatus(){
		$queryString = '';
		$map = array('initialize'=>'Initialize','pending'=>'Pending','successful'=>'Successful','failure'=>'Failure','noresponse'=>'NoResponse','paymentafter3attempts'=>'PaymentAfter3Attempts','converttofreebeforepayment'=>'ConvertToFreeBeforePayment','refund'=>'Refund','paymentadmindeleted'=>'PaymentAdminDeleted');

		$qmap = array();
		foreach ($this->post['premiumpacks_filter_payment_status'] as $selection){
			$qmap[] = 'premiumads_payment_status:"'.$map[$selection].'"';
		}

		if($qmap){
			$this->queryArray[] = '+('.join(' OR ', $qmap).')';
		}

	}

	protected function buildQueryForEmail(){
		if(trim($this->post['premiumpacks_filter_user_email_opts']) != 'none' &&
		trim($this->post['premiumpacks_filter_user_email']) != '') {
			$operator = trim($this->post['premiumpacks_filter_user_email_opts']);
			$text = trim($this->post['premiumpacks_filter_user_email']);


			switch($operator) {
				case 'equals':
					$this->queryArray[] = '+(premiumads_user_email:'.$text.')'; //exact search
					break;

				case 'contains':
					$this->queryArray[] = '+(premiumads_user_email:*'.$text.'*)'; //anywhere in between the text
					break;

				case 'excludes':
					$this->queryArray[] =  '-(premiumads_user_email:*'.$text.'*)'; //does not contain
					break;

				default:
					$this->queryArray[] =  '+(premiumads_user_email:'.$text.')'; //exact search
					break;
			}
		}
	}


	protected function buildQueryForMobile(){
		$queryString = '';
		if(!empty($this->post['premiumpacks_filter_user_mobile'])) {
			$this->queryArray[] = '+(premiumads_user_mobile:'.trim($this->post['premiumpacks_filter_user_mobile']).')';
		}
	}

	protected function buildQueryForUserId(){
		$queryString = '';
		if(!empty($this->post['premiumpacks_filter_user_id'])) {
			$this->queryArray[] = '+(premiumads_user_id:'.trim($this->post['premiumpacks_filter_user_id']).')';
		}
	}


	protected function buildQueryForPackOrderId(){
		$queryString = '';
		if(!empty($this->post['premiumpacks_filter_pack_order_id'])) {
			$this->queryArray[] = '+(premiumads_pack_order_id:'.trim($this->post['premiumpacks_filter_pack_order_id']).')';
		}
	}

	protected function buildQueryForPackId(){
		$queryString = '';
		if(!empty($this->post['premiumpacks_filter_pack_id'])) {
			$this->queryArray[] = '+(premiumads_vdu_id:'.trim($this->post['premiumpacks_filter_pack_id']).')';
		}
	}



	protected function buildQueryForResellerPackOrderId(){
		$queryString = '';
		if(!empty($this->post['premiumpacks_filter_reseller_pack_order_id'])) {
			$this->queryArray[] = '+(premiumads_reseller_pack_order_id:'.trim($this->post['premiumpacks_filter_reseller_pack_order_id']).')';
		}
	}

	protected function buildQueryForResellerPackId(){
		$queryString = '';
		if(!empty($this->post['premiumpacks_filterrs_reseller_pack_id'])) {
			$this->queryArray[] = '+(premiumads_reseller_pack_id:'.trim($this->post['premiumpacks_filterrs_reseller_pack_id']).')';
		}
	}


	protected function buildQueryForTpslId(){
		$queryString = '';
		if(!empty($this->post['premiumpacks_filter_tpslid'])) {
			$this->queryArray[] = '+(premiumads_tpsl_id:'.trim($this->post['premiumpacks_filter_tpslid']).')';
		}
	}

	protected function buildQueryForAdId(){
		$queryString = '';
		if(!empty($this->post['premiumpacks_filter_adid'])) {
			$this->queryArray[] = '+(premiumads_ad_id:'.trim($this->post['premiumpacks_filter_adid']).')';
		}
	}

	protected function buildQueryForAdOrderId(){
		$queryString = '';
		if(!empty($this->post['premiumpacks_filter_adorderid'])) {
			$this->queryArray[] = '+(premiumads_ad_order_id:'.trim($this->post['premiumpacks_filter_adorderid']).')';
		}
	}

	protected function buildQueryForChequeNumber(){
		$queryString = '';
		if(!empty($this->post['premiumpacks_filter_cheque_number'])) {
			$this->queryArray[] = '+(premiumads_cheque_no:'.trim($this->post['premiumpacks_filter_cheque_number']).' OR premiumads_remark:'.trim($this->post['premiumpacks_filter_cheque_number']).')';
		}
	}


	protected function ddmmyyyToTimestamp($date) {
		return strtotime($date);
	}



	protected function buildQueryForAdminDeletedDate(){
		if(!empty($this->post['premiumpacks_filter_admin_deleted_date_from']) &&
		!empty($this->post['premiumpacks_filter_admin_deleted_date_to'])) {
			$from = $this->ddmmyyyToTimestamp($this->post['premiumpacks_filter_admin_deleted_date_from']);
			$to = $this->ddmmyyyToTimestamp($this->post['premiumpacks_filter_admin_deleted_date_to'])+TO_DATE_INCREMENT;
			$this->queryArray[] = '+(premiumads_refund_date:['.$from.' TO '.$to.'])';
		}
	}

	protected function buildQueryForRefundDate(){
		if(!empty($this->post['premiumpacks_filter_refund_date_from']) &&
		!empty($this->post['premiumpacks_filter_refund_date_to'])) {
			$from = $this->ddmmyyyToTimestamp($this->post['premiumpacks_filter_refund_date_from']);
			$to = $this->ddmmyyyToTimestamp($this->post['premiumpacks_filter_refund_date_to'])+TO_DATE_INCREMENT;
			$this->queryArray[] = '+(premiumads_ad_order_refund_accounting_date:['.$from.' TO '.$to.'])';
		}
	}

	protected function buildQueryForAccountingDate(){
		if(!empty($this->post['premiumpacks_filter_accounting_date_from']) &&
		!empty($this->post['premiumpacks_filter_accounting_date_to'])) {
			$from = $this->ddmmyyyToTimestamp($this->post['premiumpacks_filter_accounting_date_from']);
			$to = $this->ddmmyyyToTimestamp($this->post['premiumpacks_filter_accounting_date_to'])+TO_DATE_INCREMENT;
			$this->queryArray[] = '+(premiumads_ad_order_accounting_date:['.$from.' TO '.$to.'])';
		}
	}

	protected function buildQueryForCreateDate(){
		if(!empty($this->post['premiumpacks_filter_created_date_from']) &&
		!empty($this->post['premiumpacks_filter_created_date_to'])) {
			$from = $this->ddmmyyyToTimestamp($this->post['premiumpacks_filter_created_date_from']);
			$to = $this->ddmmyyyToTimestamp($this->post['premiumpacks_filter_created_date_to'])+TO_DATE_INCREMENT;
			$this->queryArray[] = '+(premiumads_vdu_created_date:['.$from.' TO '.$to.'])';
		}
	}

	protected function buildQueryForActivatedDate(){
		if(!empty($this->post['premiumpacks_filter_activated_date_from']) &&
		!empty($this->post['premiumpacks_filter_activated_date_to'])) {
			$from = $this->ddmmyyyToTimestamp($this->post['premiumpacks_filter_activated_date_from']);
			$to = $this->ddmmyyyToTimestamp($this->post['premiumpacks_filter_activated_date_to'])+TO_DATE_INCREMENT;
			$this->queryArray[] = '+(premiumads_ad_order_activated_date:['.$from.' TO '.$to.']) AND NOT(premiumads_ad_status:"Admin deleted")';
		}

	}

	protected function buildQueryForExpiredDate(){
		if(!empty($this->post['premiumpacks_filter_expiry_date_from']) &&
		!empty($this->post['premiumpacks_filter_expiry_date_to'])) {
			$from = $this->ddmmyyyToTimestamp($this->post['premiumpacks_filter_expiry_date_from']);
			$to = $this->ddmmyyyToTimestamp($this->post['premiumpacks_filter_expiry_date_to'])+TO_DATE_INCREMENT;
			$this->queryArray[] = '+(premiumads_vdu_expiry_date:['.$from.' TO '.$to.'])';
		}
	}
	
	protected function buildQueryForPauseDate(){
		if(!empty($this->post['premiumpacks_filter_pause_date_from']) &&
		!empty($this->post['premiumpacks_filter_pause_date_to'])) {
			$from = $this->ddmmyyyToTimestamp($this->post['premiumpacks_filter_pause_date_from']);
			$to = $this->ddmmyyyToTimestamp($this->post['premiumpacks_filter_pause_date_to'])+TO_DATE_INCREMENT;
			$this->queryArray[] = '+(premiumads_pausestarttime:['.$from.' TO '.$to.'])';
		}
	}

	protected function buildQueryForUsageDate(){
		if(!empty($this->post['premiumpacks_filter_usage_date_from']) &&
		!empty($this->post['premiumpacks_filter_usage_date_to'])) {
			$from = $this->ddmmyyyToTimestamp($this->post['premiumpacks_filter_usage_date_from']);
			$to = $this->ddmmyyyToTimestamp($this->post['premiumpacks_filter_usage_date_to'])+TO_DATE_INCREMENT;
			$this->queryArray[] = '+(premiumads_ad_order_created_date:['.$from.' TO '.$to.'])';
		}
	}

	protected function buildQueryForPackSize(){
		if(trim($this->post['user_filter_pack_size_range']) != '' &&
		trim($this->post['user_filter_pack_size_text']) != '') {

			$qty = trim($this->post['user_filter_pack_size_text']);

			$obj = new Zend_Validate_Int();
			$st = $obj->isValid($qty);

			if($st && $qty >= 0) {
				switch($this->post['user_filter_pack_size_range']) {
					case 'less':
						$this->queryArray[] = '+(premiumads_vd_total_credit:[* TO '.($qty - 1).'])';
						break;
					case 'less_equal':
						$this->queryArray[] = '+(premiumads_vd_total_credit:[* TO '.$qty.'])';
						break;
					case 'greater':
						$this->queryArray[] = '+(premiumads_vd_total_credit:['.($qty + 1).' TO *])';
						break;
					case 'greater_equal':
						$this->queryArray[] = '+(premiumads_vd_total_credit:['.$qty.' TO *])';
						break;
					case 'equal':
						$this->queryArray[] = '+(premiumads_vd_total_credit:'.$qty.')';
						break;
					case 'not_equal':
						$this->queryArray[] = '-(premiumads_vd_total_credit:'.$qty.')';
						break;
				}
			}
		}
	}

	protected function buildQueryForPackAmount(){
		if(trim($this->post['user_filter_pack_amount_range']) != '' &&
		trim($this->post['user_filter_pack_amount_text']) != '') {

			$qty = trim($this->post['user_filter_pack_amount_text']);

			$obj = new Zend_Validate_Int();
			$st = $obj->isValid($qty);

			if($st && $qty >= 0) {
				switch($this->post['user_filter_pack_amount_range']) {
					case 'less':
						$this->queryArray[] = '+(premiumads_vd_amount:[* TO '.($qty - 1).'])';
						break;
					case 'less_equal':
						$this->queryArray[] = '+(premiumads_vd_amount:[* TO '.$qty.'])';
						break;
					case 'greater':
						$this->queryArray[] = '+(premiumads_vd_amount:['.($qty + 1).' TO *])';
						break;
					case 'greater_equal':
						$this->queryArray[] = '+(premiumads_vd_amount:['.$qty.' TO *])';
						break;
					case 'equal':
						$this->queryArray[] = '+(premiumads_vd_amount:'.$qty.')';
						break;
					case 'not_equal':
						$this->queryArray[] = '-(premiumads_vd_amount:'.$qty.')';
						break;
				}
			}
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

	public function getCashinAccruals(){
		$this->setColumns();


		$from = $this->ddmmyyyToTimestamp($this->post['cashin_date_from']);
		$to = $this->ddmmyyyToTimestamp($this->post['cashin_date_to'])+TO_DATE_INCREMENT;

		$this->queryArray[] = '+(premiumads_ad_order_activated_date:['.$from.' TO '.$to.'])';
		$this->queryArray[] = '+(premiumads_product_type:(Ad OR VolumeDiscount))';
		$this->queryArray[] = '+(premiumads_payment_status:Successful)';


		$finalUrl = $this->solrUrl.'select?q='.urlencode('(premiumads_product_type:(Ad OR VolumeDiscount))(premiumads_payment_status:Successful)(premiumads_ad_order_activated_date:['.$from.' TO '.$to.'])').'&rows='.$this->rows.'&start='.$this->start.'&fl='.$this->fl;

		try {
			$obj = new Utility_SolrQueryAnalyzer($finalUrl,__FILE__.' at line '.__LINE__);
			$data = $obj->init();
		} catch (Exception $e) {
			trigger_error($e->getMessage());
		}


		$json = json_decode($data);



		return $returnData;
	}
}

?>