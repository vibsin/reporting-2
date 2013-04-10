<?php
include('IndexingAbstract.php');

class Premiumadsindexing extends IndexingAbstract {

	protected $dbTableName = 'babel_product_order';
	protected $section = 'premiumads'; //used in the parent class
	protected $indexingUrl = SOLR_PREMIUM_ADS_INDEXING_URL;
	protected $isIncrementalIndexing = false;

	protected $isCalledFromOtherScript = false;
	protected $AdUserInfoFromDatabase = false;
        protected $isRunTimeIndexing = false;
        protected $allowed_attributes = null;
	public function  __destruct() {
		parent::__destruct();
	}


	public function init($args) {
            $this->allowed_attributes = Zend_Registry::get('ALLOWED_ATTRIBUTES');
		if(!empty($args)) {
			$this->commandArgs = $args;
			$runAlertFor=$args[1];

			if(isset($args[2])) {
				$runInterval = $args[2];
				$this->isIncrementalIndexing = true;
			}
			//$runAlertFor ='ALL';
			switch($runAlertFor) {
				case 'ALL':

					/*$this->sql = 'SELECT * FROM '.$this->dbTableName;
					$this->countSql = 'SELECT count(id) as "count" FROM '.$this->dbTableName;*/

					$this->sql = 'SELECT * FROM '.$this->dbTableName.' where producttype IN ("ad", "vd")';
					$this->countSql = 'SELECT count(id) as "count" FROM '.$this->dbTableName.' where producttype IN ("ad", "vd")';

					//$this->countSql = 'SELECT 1000 as "count"';

					break;

				case 'ALLVD':

					$this->sql = 'SELECT * FROM '.$this->dbTableName.' where producttype ="vd"';
					$this->countSql = 'SELECT count(id) as "count" FROM '.$this->dbTableName.' where producttype ="vd"';
					break;

				case 'ALLTANLA':
					$this->sql = 'SELECT * FROM '.$this->dbTableName.' where producttype ="ad" and paymenttype ="mt"';
					$this->countSql = 'SELECT count(id) as "count" FROM '.$this->dbTableName.' where producttype ="ad" and paymenttype ="mt"';
					break;

				case 'ALLCREDIT':

					$this->sql = 'SELECT * FROM '.$this->dbTableName.' where producttype ="ad" and paymenttype IN ("v","ar")';
					$this->countSql = 'SELECT count(id) as "count" FROM '.$this->dbTableName.' where producttype ="ad" and paymenttype IN ("v","ar")';

					break;

				case 'IDIN':

					$this->sql = 'SELECT * FROM '.$this->dbTableName.' where producttype IN ("ad", "vd") AND id IN ('.$runInterval.')';
					$this->countSql = 'SELECT count(id) as "count" FROM '.$this->dbTableName.' where producttype IN ("ad", "vd") AND id IN ('.$runInterval.')';

					break;


					/**
             * PLEASE UPDATE QUERIES
             */
				case 'NEWEST':

					$past = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j")-$runInterval,date("Y"))));
					$now = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j"),date("Y"))));



					$this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE producttype IN ("ad", "vd") AND createdtimestamp BETWEEN '.$past.' AND '.$now;
					$this->countSql = 'SELECT count(id) as "count" FROM '.$this->dbTableName.' WHERE producttype IN ("ad", "vd") AND createdtimestamp BETWEEN '.$past.' AND '.$now;
					$this->isMasterIndexingScript = true;
					//$this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE producttype IN ("ad", "vd") AND createdtimestamp > '.strtotime($runInterval).'';
					//$this->countSql = 'SELECT count(id) as "count" FROM '.$this->dbTableName.' WHERE producttype IN ("ad", "vd") AND createdtimestamp > '.strtotime($runInterval).'';
					break;




				case 'REMAPPED':

					$past = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j")-$runInterval,date("Y"))));
					$now = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j"),date("Y"))));

					//$remappedTime = strtotime("-".$runInterval.' days');

					$this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE producttype IN ("ad", "vd") AND remapped BETWEEN '.$past.' AND '.$now;
					$this->countSql = 'SELECT count(id) as "count" FROM '.$this->dbTableName.' WHERE producttype IN ("ad", "vd") AND remapped BETWEEN '.$past.' AND '.$now;
					$this->isMasterIndexingScript = true;
					break;


				case 'NEWESTUPDATE':
					$past = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j")-$runInterval,date("Y"))));
					$now = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j"),date("Y"))));



					$this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE producttype IN ("ad", "vd") AND updatedtimestamp BETWEEN '.$past.' AND '.$now;
					$this->countSql = 'SELECT count(id) as "count" FROM '.$this->dbTableName.' WHERE producttype IN ("ad", "vd") AND updatedtimestamp BETWEEN '.$past.' AND '.$now;
					$this->isMasterIndexingScript = true;
					//$this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE producttype IN ("ad", "vd") AND updatedtimestamp > '.strtotime($runInterval).'';
					//$this->countSql = 'SELECT count(id) as "count" FROM '.$this->dbTableName.' WHERE producttype IN ("ad", "vd") AND updatedtimestamp > '.strtotime($runInterval).'';
					break;

				case 'REFUNDED':
					$past = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j")-$runInterval,date("Y"))));
					$now = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j"),date("Y"))));

					$this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE producttype IN ("ad", "vd") AND refund_date BETWEEN '.$past.' AND '.$now;
					$this->countSql = 'SELECT count(id) as "count" FROM '.$this->dbTableName.' WHERE producttype IN ("ad", "vd") AND refund_date BETWEEN '.$past.' AND '.$now;
					$this->isMasterIndexingScript = true;
					break;

				case 'PAIDTOFREE':
					$past = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j")-$runInterval,date("Y"))));
					$now = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j"),date("Y"))));

					$this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE producttype IN ("ad", "vd") AND orderconverttofreedate BETWEEN '.$past.' AND '.$now;
					$this->countSql = 'SELECT count(id) as "count" FROM '.$this->dbTableName.' WHERE producttype IN ("ad", "vd") AND orderconverttofreedate BETWEEN '.$past.' AND '.$now;
					$this->isMasterIndexingScript = true;
					break;

				case 'CHECKAUTORENEW':
					$past = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j")-$runInterval,date("Y"))));
					$now = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j"),date("Y"))));

					$this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE producttype IN ("ad", "vd") AND autorenewondate BETWEEN '.$past.' AND '.$now;
					$this->countSql = 'SELECT count(id) as "count" FROM '.$this->dbTableName.' WHERE producttype IN ("ad", "vd") AND autorenewondate BETWEEN '.$past.' AND '.$now;
					$this->isMasterIndexingScript = true;
					break;

				case 'UNCHECKAUTORENEW':
					$past = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j")-$runInterval,date("Y"))));
					$now = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j"),date("Y"))));

					$this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE producttype IN ("ad", "vd") AND autorenewoffdate BETWEEN '.$past.' AND '.$now;
					$this->countSql = 'SELECT count(id) as "count" FROM '.$this->dbTableName.' WHERE producttype IN ("ad", "vd") AND autorenewoffdate BETWEEN '.$past.' AND '.$now;
					$this->isMasterIndexingScript = true;
					break;

				case 'ID':
					$this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE id = '.$args[2];
					$this->countSql = 'SELECT count(id) as "count" FROM '.$this->dbTableName.' WHERE producttype IN ("ad", "vd") AND id = '.$args[2];

					break;



					/** This will index data given in the below range. Please dont give very large gap between dates
                 * usage:
                 * /usr/local/php/bin/php Alertsindexing.php DATE_RANGE FROM_DATE TO_DATE
                 * 
                 * FROM_DATE and TO_DATE should be of the format dd-mm-yyyy
                 * 
                 */
				case 'DATE_RANGE':
					$this->isIncrementalIndexing = false;
					$past = strtotime($args[2]);
					$now = strtotime($args[3]);


					$this->sql = 'SELECT * FROM '.$this->dbTableName.' WHERE producttype IN ("ad", "vd") AND remapped BETWEEN '.$past.' AND '.$now;
					$this->countSql = 'SELECT count(id) as "count" FROM '.$this->dbTableName.' WHERE producttype IN ("ad", "vd") AND remapped BETWEEN '.$past.' AND '.$now;

					break;

				case 'EXPIREDPACKAD':
					$past = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j")-$runInterval,date("Y"))));
					$now = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j"),date("Y"))));
					$this->sql = "select * from babel_product_order where packid IN (Select id   from babel_volume_discount_user where last_updated  BETWEEN ".$past." AND ".$now.") and producttype='ad'";
					$this->countSql = "select  count(id) as count from babel_product_order where packid IN (Select id   from babel_volume_discount_user where last_updated  BETWEEN ".$past." AND ".$now.") and producttype='ad'";
					$this->isMasterIndexingScript = true;
					break;



				case 'EXPIREDPACK':
					$past = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j")-$runInterval,date("Y"))));
					$now = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j"),date("Y"))));
					$this->sql = "select * from babel_product_order where productid IN (Select id   from babel_volume_discount_user where last_updated  BETWEEN ".$past." AND ".$now.") and producttype='vd'";
					$this->countSql = "select  count(id) as count from babel_product_order where productid IN (Select id   from babel_volume_discount_user where last_updated  BETWEEN ".$past." AND ".$now.") and producttype='vd'";
					$this->isMasterIndexingScript = true;
					break;

				case 'EXTRAQUERY':
					$past = strtotime($args[2]);
					$now = strtotime($args[3]);	
					$this->sql = "SELECT * FROM ".$this->dbTableName." where paymentstatus =1 AND producttype = 'ad' AND orderconverttofreedate =0  AND createdtimestamp BETWEEN $past AND $now";
					$this->countSql ="SELECT count(id) as count FROM ".$this->dbTableName." where paymentstatus =1 AND producttype = 'ad' AND orderconverttofreedate =0  AND createdtimestamp BETWEEN $past AND $now";
					break;
					
				case 'DROP':
					$this->dropsearchindexesAction();
					break;

				default:
					echo "NOTHING TO DO!";
					break;
			}
			echo "\nQuery=".$this->sql."\n";
			echo "\nCount=".$this->countSql."\n";

			//set the threshhold
			self::$threshold = $this->getMaxRecordsFromDB();

			$this->indexAction();

		} else {
			echo 'Please enter valid arguments'; die();
		}
	}

	/***
	* Following are the possible values for payment mode, data got from staging,
	* notice blank for first record...
	*
	*
	* paymenttype  	 count( `paymenttype` )
	10
	ar                      33
	c                       1371
	mt                      93
	o                       1023
	v                       519
	*
	*
	*
	*/


	protected function parsePaymentType($state) {
		if($state == '' || $state == null) {
			return '';
		} else {
			switch(trim($state)) {
				case "ar":
					return "AutoRenew";
					break;

				case "c":
					return "Cheque";
					break;

				case "mt":
					return "Tanla";
					break;

				case "o":
					return "Online";
					break;

				case "v":
					return "UsedCredit";
					break;

				case "promo":
					return "Promo";
					break;

				default:
					return $state;
					break;
			}
		}
	}

	/***
	* Following are the possible values for payment status, data got from staging,
	*
	paymentstatus  	 count( `paymentstatus` )
	-1 	685
	0 	178
	1 	1937
	2 	63
	3 	78
	4 	4
	5 	8
	6 	70
	7 	26
	*
	*
	*
	*/


	protected function parsePaymentStatus($state) {
		if($state == '' || $state == null) {
			return '';
		} else {
			switch(trim($state)) {
				case "-1":
					return "Initialize";
					break;

				case "0":
					return "Pending";
					break;

				case "1":
					return "Successful";
					break;

				case "2":
					return "Failure";
					break;

				case "3":
					return "NoResponse";
					break;

				case "4":
					return "PaymentAfter3Attempts";
					break;

				case "5":
					return "ConvertToFreeBeforePayment";
					break;

				case "6":
					return "Refund";
					break;

				case "7":
					return "PaymentAdminDeleted";
					break;


				default:
					return $state;
					break;
			}
		}
	}


	/***
	* Following are the possible values for refund_mode, data got from staging,
	*
	*
	refund_mode  	 count( `refund_mode` )
	2994
	cash 	19
	cheque 	36
	*
	*/

	protected function parseRefundMode($state) {
		if($state == '' || $state == null) {
			return '';
		} else {
			switch(trim($state)) {
				case "cash":
					return "Cash";
					break;

				case "cheque":
					return "Cheque";
					break;

				default:
					return $state;
					break;
			}
		}
	}

	/**
        paymentfor
        T --> top
        H --> higlight
        HT -->tp and highlight
        ICA --> alert
     */
	protected function parsePaymentFor($state) {
		//		if($state == '' || $state == null) {
		//			return '';
		//		} else {
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

			case "ICA":
				return "ICA";
				break;

			case "0":
				return "ALL";
				break;
			case "":
				return "ALL";
				break;

			default:
				return $state;
				break;
		}
		//}
	}


	/**
     * producttype
        ad
        intMobileAlert
        vd
     */
	protected function parseProductType($state) {
		if($state == '' || $state == null) {
			return '';
		} else {
			switch(trim($state)) {
				case "ad":
					return "Ad";
					break;

				case "intMobileAlert":
					return "MobileAlert";
					break;

				case "vd":
					return "VolumeDiscount";
					break;

				default:
					return $state;
					break;
			}
		}
	}

	/**
            productstatus
                U
                R
                N
                T**/
	protected function parseProductStatus($state) {
		if($state == '' || $state == null) {
			return '';
		} else {
			switch(trim($state)) {
				case "U":
					return "Upgrade";
					break;

				case "R":
					return "Renew";
					break;

				case "N":
					return "New";
					break;

				default:
					return $state;
					break;
			}
		}
	}


	/**
     * GEt tpsl id from babel_transaction
     * @param type $id
     * @return type 
     */
	protected function getTransactionId($id) {

		if (!$id) return 0;
		$sql ='SELECT tpslid FROM babel_transaction WHERE orderid = "'.$id.'"';
		$objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
		$objStmt->execute();
		$items = $objStmt->fetchAll();
		unset($objStmt);
		if(!empty($items)) return $items[0]['tpslid'];
		return 0;
	}

	/**
     * orderconverttofreeflag
        NULL
        r
        e
        h
     */

	protected function parseConvertToFreeStatus($state) {
		if($state == '' || $state == null) {
			return '';
		} else {
			switch(trim($state)) {
				case "r":
					return "Renew";
					break;

				case "e":
					return "Expired";
					break;

				case "h":
					return "Hanging";
					break;

				default:
					return $state;
					break;
			}
		}
	}

	protected function parseAutoRenewType($state) {
		switch(trim($state)) {
			case '0':
				return "Disable";
				break;

			case '1':
				return "Enable";
				break;
			default:
				return $state;
				break;
		}
	}


	protected function getVDUDetails($id) {

		if (!$id) return 0;
		$sql ='SELECT * FROM babel_volume_discount_user WHERE id = '.$id;
		$objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
		$objStmt->execute();
		$items = $objStmt->fetchAll();
		unset($objStmt);

		if(!empty($items[0])) return $items[0];
		else return false;
	}

	protected function getVDUOrderDetails($id) {

		if (!$id) return 0;
		$sql ='SELECT * FROM babel_product_order WHERE producttype="vd" and   productid = '.$id;
		$objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
		$objStmt->execute();
		$items = $objStmt->fetchAll();
		unset($objStmt);

		if(!empty($items[0])) return $items[0];
		else return false;
	}

	protected function getUsedCredit($id,$packId){

		if (!$id || !$packId) return 0;
		$sql ='SELECT count(id) as total FROM babel_product_order WHERE paymenttype IN ("v","ar") and   id <= '.$id.' and paymentstatus=1 and packid='.$packId;
		$objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
		$objStmt->execute();
		$items = $objStmt->fetchAll();
		unset($objStmt);

		if(!empty($items[0])) return $items[0]["total"];
		else return false;
	}

	protected function getMaseterPackSize($id){

		if (!$id) return 0;
		$sql ='SELECT * FROM babel_volume_discount WHERE id='.$id;
		$objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
		$objStmt->execute();
		$items = $objStmt->fetchAll();
		unset($objStmt);

		if(!empty($items[0])) return $items[0];
		else return false;
	}

	function getCategoryData($metaId, $areaid){

		if (!$metaId || !$areaid) return 0;
		$sql = 'SELECT node_id, nod_name, nod_areaid, nod_title, nod_areaname, nod_globalId  FROM babel_node WHERE nod_globalId = '.$metaId.' AND nod_title != "" and nod_level=100 and nod_areaid='.$areaid.' LIMIT 1';
		$objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
		$objStmt->execute();
		$items = $objStmt->fetchAll();
		return $items[0];

	}

	function getAreaData($areaid){
		if (!$areaid) return 0;
		$sql = 'SELECT area_id,area_title from babel_area where area_id='.$areaid.' and area_level=1 LIMIT 1';
		$objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
		$objStmt->execute();
		$items = $objStmt->fetchAll();
		return $items[0];
	}


	function getVolumeDiscoutData($vdId){

		if (!$vdId) return 0;
		$sql = 'select * from babel_volume_discount where id='. $vdId;
		$objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
		$objStmt->execute();
		$items = $objStmt->fetchAll();
		unset($objStmt);

		if(!empty($items[0])) return $items[0];
		else return false;


	}

	protected function initBuildingData() {

		foreach(self::$data as $key => $val) {

			/**** indexing all fields from babel_product_order START***/
			//print_r($val);exit;
			self::$dataToIndex[self::$counter]['id'] = $val['id']; //not done


			self::$dataToIndex[self::$counter]['premiumads_order_id'] = $val['orderid']; //done
			self::$dataToIndex[self::$counter]['premiumads_cheque_no'] = $val['cheque_no']; //done
			self::$dataToIndex[self::$counter]['premiumads_cheque_bank_detail'] = $val['cheque_bank_detail']; //done
			self::$dataToIndex[self::$counter]['premiumads_payment_status'] = $this->parsePaymentStatus($val['paymentstatus']); //done
			self::$dataToIndex[self::$counter]['premiumads_payment_type'] = $this->parsePaymentType($val['paymenttype']); //done
			self::$dataToIndex[self::$counter]['premiumads_ad_order_created_date'] = self::getNumbers($val['createdtimestamp']); //done
			self::$dataToIndex[self::$counter]['premiumads_ad_order_updated_date'] = self::getNumbers($val['updatedtimestamp']); //done
			self::$dataToIndex[self::$counter]['premiumads_order_updated_reason'] = $val['orderupdatedreason']; //done
			self::$dataToIndex[self::$counter]['premiumads_product_type'] = $this->parseProductType($val['producttype']); //done
			self::$dataToIndex[self::$counter]['premiumads_ad_order_activated_date'] = self::getNumbers($val['activeordertimestamp']); //done


			$this->getAccountingdate($val);



			self::$dataToIndex[self::$counter]['premiumads_ad_order_accounting_date'] = self::getNumbers($val['accounting_timestamp']); //done
			self::$dataToIndex[self::$counter]['premiumads_ad_order_refund_accounting_date'] = self::getNumbers($val['refund_accounting_timestamp']); //done

			self::$dataToIndex[self::$counter]['premiumads_ad_order_expiry_date'] = self::getNumbers($val['expireordertimestamp']); //done
			self::$dataToIndex[self::$counter]['premiumads_payment_for'] = $this->parsePaymentFor($val['paymentfor']); //done
			self::$dataToIndex[self::$counter]['premiumads_product_id'] = $val['productid']; //done
			self::$dataToIndex[self::$counter]['premiumads_order_smb'] = self::getNumbers($val['smb']); //done


			self::$dataToIndex[self::$counter]['premiumads_promo_id'] = $val['parent_promo_id']; //done

			self::$dataToIndex[self::$counter]['premiumads_attempts'] = self::getNumbers($val['attempts']); //done
			/*if ($val['paymenttype']=='mt'){
			self::$dataToIndex[self::$counter]['premiumads_amount'] = ($val['amount'] > 0) ? 9 : 0.00; //done
			}else {
			self::$dataToIndex[self::$counter]['premiumads_amount'] = self::getNumbers(($val['amount'] > 0) ? str_replace(',','',$val['amount']) : 0.00; //done
			}*/

			$this->getAmount($val);
			self::$dataToIndex[self::$counter]['premiumads_refund_amount'] = self::getNumbers($val['refund_amount']); //done
			self::$dataToIndex[self::$counter]['premiumads_refund_reason'] = $val['refund_reason']; //done
			self::$dataToIndex[self::$counter]['premiumads_refund_date'] = self::getNumbers($val['refund_date']); //done
			self::$dataToIndex[self::$counter]['premiumads_refund_by_admin'] = $val['refund_by_admin']; //done
			self::$dataToIndex[self::$counter]['premiumads_refund_mode'] = $this->parseRefundMode($val['refund_mode']);//done
			self::$dataToIndex[self::$counter]['premiumads_referrer'] = $val['referrer']; //done
			self::$dataToIndex[self::$counter]['premiumads_product_status'] = $this->parseProductStatus($val['productstatus']); //done
			self::$dataToIndex[self::$counter]['premiumads_remark'] = $val['remark']; //done
			self::$dataToIndex[self::$counter]['premiumads_invoiceid'] = $val['invoiceid']; //done
			self::$dataToIndex[self::$counter]['premiumads_user_billing_address'] = $val['user_billing_address']; //done
			self::$dataToIndex[self::$counter]['premiumads_active_order'] = $val['active_order']; //done
			self::$dataToIndex[self::$counter]['premiumads_pack_id'] = self::getNumbers($val['packid']); //done
			self::$dataToIndex[self::$counter]['premiumads_parent_pack_id'] = self::getNumbers($val['parentpackid']); //done
			//self::$dataToIndex[self::$counter]['premiumads_auto_renew_status'] = $val['autorenewstatus']; //done
			self::$dataToIndex[self::$counter]['premiumads_auto_renew_status'] = $this->parseAutoRenewType($val['autorenewstatus']); //done
			self::$dataToIndex[self::$counter]['premiumads_auto_renew_on_date'] = self::getNumbers($val['autorenewondate']); //done
			self::$dataToIndex[self::$counter]['premiumads_auto_renew_off_date'] = self::getNumbers($val['autorenewoffdate']); //done
			self::$dataToIndex[self::$counter]['premiumads_auto_renew_parent_order_id'] = $val['autorenewparentorderid']; //done
			self::$dataToIndex[self::$counter]['premiumads_order_convert_to_free_date'] = self::getNumbers($val['orderconverttofreedate']); //done
			self::$dataToIndex[self::$counter]['premiumads_order_convert_to_free_flag'] = $this->parseConvertToFreeStatus($val['orderconverttofreeflag']); //done
			self::$dataToIndex[self::$counter]['premiumads_mode_of_payment_gateway'] = $val['modeofpaymentGateway']; //done

			if($val['paymentstatus'] == '7') {
				self::$dataToIndex[self::$counter]['premiumads_ad_order_delete_date'] = self::getNumbers($val['refund_date']); //done
			} else {
				self::$dataToIndex[self::$counter]['premiumads_ad_order_delete_date'] = 0; //done
			}

			//if($val['producttype'] == "vd") {
			self::$dataToIndex[self::$counter]['premiumads_tpsl_id'] = $this->getTransactionId($val['orderid']); //done
			//}
			//self::$dataToIndex[self::$counter]['premiumads_keep_running_status'] = $val['alert_userid']; //not done


			self::$dataToIndex[self::$counter]['premiumads_ad_order_remapped_date'] = self::getNumbers($val['remapped']); //done
			/**** indexing all fields from babel_product_order ENDS***/



			if($val['producttype'] == "ad") {

				/**** indexing all fields for ads START***/
				/**
	             * For ads we will hit the ads core to fetch the data, since ads will be indexed beforehand,
	             * we are sure to get the data
	             * 
	             * productid from babel_product_order is/can be ad id or volume discount id (primary key)//plz confirm
	             */

				self::$dataToIndex[self::$counter]['premiumads_ad_order_id'] = $val['orderid']; //done
				self::$dataToIndex[self::$counter]['premiumads_extended_product_type'] = "Ads";

				if ($this->AdUserInfoFromDatabase != false) {
					$objAds = new Model_AdsSolr(array());
					$adsData= $objAds->getSingleFieldFromAds('*', $val['productid']);
					$adsArr = array();

					if(!empty($adsData) && $adsData->response->numFound > 0) {
						$adStories = $adsData->response->docs;
						foreach ($adStories as $story) {
							foreach ($story as $k => $v) {
								$name = $k;
								$value = $v;
								$adsArr[$name] = $value;
							}
						}







						self::$dataToIndex[self::$counter]['premiumads_ad_id'] = self::getNumbers($adsArr['id']); //done
						self::$dataToIndex[self::$counter]['premiumads_premium_ad_type'] = $adsArr['premium_ad_type']; //done
						self::$dataToIndex[self::$counter]['premiumads_ad_status'] = $adsArr['ad_status']; //done
						self::$dataToIndex[self::$counter]['premiumads_ad_title'] = $adsArr['ad_title']; //done
						self::$dataToIndex[self::$counter]['premiumads_ad_description'] = $adsArr['ad_description']; //done
						self::$dataToIndex[self::$counter]['premiumads_reply_count'] = self::getNumbers($adsArr['no_of_replies']); //done
						self::$dataToIndex[self::$counter]['premiumads_city_id'] = self::getNumbers($adsArr['city_id']); //done
						self::$dataToIndex[self::$counter]['premiumads_city_name'] = $adsArr['city_name']; //done
						self::$dataToIndex[self::$counter]['premiumads_metacategory_id'] = self::getNumbers($adsArr['metacategory_id']); //done
						self::$dataToIndex[self::$counter]['premiumads_global_metacategory_id'] = $adsArr['global_metacategory_id']; //done
						self::$dataToIndex[self::$counter]['premiumads_metacategory_name'] = $adsArr['metacategory_name']; //done
						self::$dataToIndex[self::$counter]['premiumads_subcategory_id'] = self::getNumbers($adsArr['subcategory_id']); //done
						self::$dataToIndex[self::$counter]['premiumads_global_subcategory_id'] = self::getNumbers($adsArr['global_subcategory_id']); //done
						self::$dataToIndex[self::$counter]['premiumads_ad_localities'] = $adsArr['localities']; //done

						//self::$dataToIndex[self::$counter]['premiumads_ad_locality'] = $adsArr['localities']; //done


						$subcat = explode(',', $adsArr['subcategory_name']);

						self::$dataToIndex[self::$counter]['premiumads_subcategory_name'] = $subcat[1]; //done
						//self::$dataToIndex[self::$counter]['premiumads_visitor_count'] = self::getNumbers($adsArr['no_of_visitors']); //done
						self::$dataToIndex[self::$counter]['premiumads_ad_type'] = $adsArr['ad_type']; //done
						self::$dataToIndex[self::$counter]['premiumads_ad_expiry_date'] = self::getNumbers($adsArr['ad_expire_time']); //done
						self::$dataToIndex[self::$counter]['premiumads_ad_first_created_date'] = self::getNumbers($adsArr['tpc_firstcreated']); //done
						self::$dataToIndex[self::$counter]['premiumads_ad_created_date'] = self::getNumbers($adsArr['ad_created_date']); //done

						/*** using dynamic field in Solr for attributes,
						*
						*/
						$attr = explode("|",$adsArr['attributes']);



						if(is_array($attr) && count($attr) > 0) {
							foreach($attr as $key=>$val2) {
								if(preg_match('/condition:(.*)/',strtolower($val2),$match)) {
									if(!empty($match)) {
										self::$dataToIndex[self::$counter]['attr_condition'] = $this->cleanHtml2($match[1]); //done
									}
								}


								if(preg_match('/you_are:(.*)/',strtolower($val2),$match)) {
									if(!empty($match)) {
										self::$dataToIndex[self::$counter]['attr_you_are'] = $this->cleanHtml2($match[1]); //done
									}
								}


								if(preg_match('/brand_name:(.*)/',strtolower($val2),$match)) {
									if(!empty($match)) {
										self::$dataToIndex[self::$counter]['attr_brand_name'] = $this->cleanHtml2($match[1]); //done
									}
								}

								if(preg_match('/no_of_rooms:(.*)/',strtolower($val2),$match)) {
									if(!empty($match)) {
										self::$dataToIndex[self::$counter]['attr_no_of_rooms'] = $this->cleanHtml2($match[1]); //done
									}
								}

								if(preg_match('/type_of_land:(.*)/',strtolower($val2),$match)) {
									if(!empty($match)) {
										self::$dataToIndex[self::$counter]['attr_type_of_land'] = $this->cleanHtml2($match[1]); //done
									}
								}

								if(preg_match('/year:(.*)/',strtolower($val2),$match)) {
									if(!empty($match)) {
										self::$dataToIndex[self::$counter]['attr_year'] = $this->cleanHtml2($match[1]); //done
									}
								}

								if(preg_match('/type_of_job:(.*)/',strtolower($val2),$match)) {
									if(!empty($match)) {
										self::$dataToIndex[self::$counter]['attr_type_of_job'] = $this->cleanHtml2($match[1]); //done
									}
								}
							}
						}
						/** attr ends **/

					}else {
						Quikr_Logger::getInstance()->log("\n Ad not found in Ad Solr id=".$val['productid'].', orderid='.$val['orderid']);
						Quikr_Logger::getInstance()->logMissingAds('premiumad#id='.$val['id'].'#orderid='.$val['orderid'].'#productid='.$val['productid']);
                                                $shellStr =  PHP_EXECUTABLE_PATH.' '.APPLICATION_PATH.'/indexing_scripts/Adsindexing.php RUNTIME '.$val['productid'].' 1';  
                                                shell_exec($shellStr);
                                                unset($shellStr);

					}
				}else {//Get Ad data From database
                                        
                                        //1st try babel_topic
					$sql = "SELECT * FROM babel_topic WHERE  tpc_id ='{$val['productid']}'";
					$objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('writedb'), $sql);
					$objStmt->execute();
                                        
                                        if($objStmt->rowCount() == 0) {
                                            
                                            $shellStr =  PHP_EXECUTABLE_PATH.' '.APPLICATION_PATH.'/indexing_scripts/Adsindexing.php RUNTIME '.$val['productid'].' 1';  
                                            shell_exec($shellStr);
                                            unset($shellStr);
                                            
                                            echo "\n ".$val['productid']." not found in babel_topic";
                                            $sql = "SELECT * FROM babel_topic_admin_deleted WHERE tpc_id ='{$val['productid']}'";
                                            $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
                                            $objStmt->execute();
                                            
                                            if($objStmt->rowCount() == 0) {
                                                
                                                $shellStr =  PHP_EXECUTABLE_PATH.' '.APPLICATION_PATH.'/indexing_scripts/Adsindexing.php RUNTIME '.$val['productid'].' 1';  
                                                shell_exec($shellStr);
                                                unset($shellStr);
                                                
                                                
                                                echo "\n ".$val['productid']." not found in babel_topic_Admin_deleted";
                                                $sql = "SELECT * FROM babel_topic_admin_deleted WHERE tpc_id ='{$val['productid']}'";
                                                $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('archivedb'), $sql);
                                                $objStmt->execute();
                                                if($objStmt->rowCount() == 0) {
                                                    $shellStr =  PHP_EXECUTABLE_PATH.' '.APPLICATION_PATH.'/indexing_scripts/Adsindexing.php RUNTIME '.$val['productid'].' 1';  
                                                    shell_exec($shellStr);
                                                    unset($shellStr);
                                                    
                                                    echo "\n ".$val['productid']." not found in archive \n";
                                                } else $items = $objStmt->fetchAll();
                                            } else $items = $objStmt->fetchAll();
                                        } else $items = $objStmt->fetchAll();
                                        
					if ($objStmt->rowCount()>0) {
						//$items = $objStmt->fetchAll();
						$adsArr=$items[0];

						self::$dataToIndex[self::$counter]['premiumads_ad_id'] = self::getNumbers($adsArr['tpc_id']); //done
						self::$dataToIndex[self::$counter]['premiumads_premium_ad_type'] = $this->parsePremiumAdType($adsArr['tpc_adStyle']); //done
						self::$dataToIndex[self::$counter]['premiumads_ad_status'] = $this->parseAdStatus($adsArr['tpc_status']); // check with shrutika
						self::$dataToIndex[self::$counter]['premiumads_ad_title'] = $adsArr['tpc_title']; //done
						self::$dataToIndex[self::$counter]['premiumads_ad_description'] = $adsArr['tpc_content']; //done
						self::$dataToIndex[self::$counter]['premiumads_reply_count'] = 0; //self::getNumbers($adsArr['no_of_replies']); //done
						self::$dataToIndex[self::$counter]['premiumads_city_id'] = self::getNumbers($adsArr['tpc_pppid']); //done
						self::$dataToIndex[self::$counter]['premiumads_city_name'] = $this->getCityName($adsArr['tpc_pppid']); //done
						self::$dataToIndex[self::$counter]['premiumads_metacategory_id'] = $adsArr['tpc_ppid']; //done
						self::$dataToIndex[self::$counter]['premiumads_global_metacategory_id'] = $adsArr['tpc_globalppid']; //done
						self::$dataToIndex[self::$counter]['premiumads_metacategory_name'] =$this->getMetacatName($adsArr['tpc_ppid']); //done
						self::$dataToIndex[self::$counter]['premiumads_subcategory_id'] =$adsArr['tpc_pid']; //done
						self::$dataToIndex[self::$counter]['premiumads_global_subcategory_id'] = $adsArr['tpc_globalId']; //done
						self::$dataToIndex[self::$counter]['premiumads_ad_localities'] = $this->parseLocations($adsArr['tpc_location']); //done
						$subcat = explode(',', $adsArr['tpc_pname']);

						self::$dataToIndex[self::$counter]['premiumads_subcategory_name'] = $subcat[1]; //done
						//self::$dataToIndex[self::$counter]['premiumads_visitor_count'] = self::getNumbers($adsArr['no_of_visitors']); //done
						self::$dataToIndex[self::$counter]['premiumads_ad_type'] = $this->parseAdType($adsArr['tpc_bak2']);//done
						self::$dataToIndex[self::$counter]['premiumads_ad_expiry_date'] = $adsArr['tpc_ad_expire_time']; //done
						self::$dataToIndex[self::$counter]['premiumads_ad_first_created_date'] = $adsArr['tpc_firstcreated']; //done
						self::$dataToIndex[self::$counter]['premiumads_ad_created_date'] = $adsArr['tpc_created']; //done

						/*** using dynamic field in Solr for attributes,
						*
						*/
						$at=$this->parseAttributes($adsArr['tpc_description']); //done
						$attr = explode("|",$at); //done);



						if(is_array($attr) && count($attr) > 0) {
							foreach($attr as $key=>$val2) {
								if(preg_match('/condition:(.*)/',strtolower($val2),$match)) {
									if(!empty($match)) {
										self::$dataToIndex[self::$counter]['attr_condition'] = $this->cleanHtml2($match[1]); //done
									}
								}


								if(preg_match('/you_are:(.*)/',strtolower($val2),$match)) {
									if(!empty($match)) {
										self::$dataToIndex[self::$counter]['attr_you_are'] = $this->cleanHtml2($match[1]); //done
									}
								}


								if(preg_match('/brand_name:(.*)/',strtolower($val2),$match)) {
									if(!empty($match)) {
										self::$dataToIndex[self::$counter]['attr_brand_name'] = $this->cleanHtml2($match[1]); //done
									}
								}

								if(preg_match('/no_of_rooms:(.*)/',strtolower($val2),$match)) {
									if(!empty($match)) {
										self::$dataToIndex[self::$counter]['attr_no_of_rooms'] = $this->cleanHtml2($match[1]); //done
									}
								}

								if(preg_match('/type_of_land:(.*)/',strtolower($val2),$match)) {
									if(!empty($match)) {
										self::$dataToIndex[self::$counter]['attr_type_of_land'] = $this->cleanHtml2($match[1]); //done
									}
								}

								if(preg_match('/year:(.*)/',strtolower($val2),$match)) {
									if(!empty($match)) {
										self::$dataToIndex[self::$counter]['attr_year'] = $this->cleanHtml2($match[1]); //done
									}
								}

								if(preg_match('/type_of_job:(.*)/',strtolower($val2),$match)) {
									if(!empty($match)) {
										self::$dataToIndex[self::$counter]['attr_type_of_job'] = $this->cleanHtml2($match[1]); //done
									}
								}
							}
						}
                                                
					}else {
						//Mail ad id not found
						Quikr_Logger::getInstance()->log("\n Ad not found in Ad DB id=".$val['productid'].', orderid='.$val['orderid']);
						Quikr_Logger::getInstance()->logMissingAds('premiumad#id='.$val['id'].'#orderid='.$val['orderid'].'#productid='.$val['productid']);
                                                $shellStr =  PHP_EXECUTABLE_PATH.' '.APPLICATION_PATH.'/indexing_scripts/Adsindexing.php RUNTIME '.$val['productid'].' 1';  
                                                shell_exec($shellStr);
                                                unset($shellStr);
					}
					/** attr ends **/

				}

			}
			/**** indexing all fields for ads ENDS***/




			/**** indexing all fields for user STARTS***/
			/**
             * For users, we will fetch data from User's core as user data will already be indexed
             */

			if ($this->AdUserInfoFromDatabase != false) {//Get Date from User Solr core
				if($val['userid'] != 0) { //because we had some data where this is 0!!
					$objUsers = new Model_UserSolr(array());
					$userData= $objUsers->getSingleFieldFromUsers('id,fullname,email,mobile', $val['userid']);
					$userArr = array();
					if(!empty($userData) && $userData->response->numFound > 0) {
						$userStories = $userData->response->docs;
						foreach ($userStories as $story) {
							foreach ($story as $k => $v) {
								$name = $k;
								$value = $v;
								$userArr[$name] = $value;
							}
						}
						self::$dataToIndex[self::$counter]['premiumads_user_id'] = self::getNumbers($userArr['id']); //done
						self::$dataToIndex[self::$counter]['premiumads_user_name'] = $userArr['fullname']; //done
						self::$dataToIndex[self::$counter]['premiumads_user_email'] = $userArr['email']; //done
						self::$dataToIndex[self::$counter]['premiumads_user_mobile'] = $userArr['mobile']; //done

					}
				}
			}else {//Get Date from User database tabele

				$sql = 'SELECT usr_id,usr_first,usr_last,usr_email,usr_mobile FROM babel_user WHERE usr_id = '.$val['userid'];
				$objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('writedb'), $sql);
				$objStmt->execute();
				if ($objStmt->rowCount()>0) {
					$usritems = $objStmt->fetchAll();
					$usr=$usritems[0];

					self::$dataToIndex[self::$counter]['premiumads_user_id'] = trim($usr['usr_id']); //done
					self::$dataToIndex[self::$counter]['premiumads_user_name'] = $usr['usr_first'].' '.$usr['usr_last'];
					self::$dataToIndex[self::$counter]['premiumads_user_email'] = (!is_null ($usr['usr_email'])) ? trim($usr['usr_email']) : 'NA'; //done
					self::$dataToIndex[self::$counter]['premiumads_user_mobile'] = (!is_null ($usr['usr_mobile']) && !empty($usr['usr_mobile'])) ? trim($usr['usr_mobile']) : 'NA'; //done
				}else {
					//Send Mail code of Details

				}

			}

			/**** indexing all fields for user ENDS***/



			/**** indexing all fields for PAck CREDIT details START***/
			/**
             * in this case babel_product_order.productid = babel_volume_discount_user.id
             */


			if($val['producttype'] == "vd") {
				$vduArr = $this->getVDUDetails($val['productid']);
				$this->fillVolumeDiscoutData($vduArr['volume_discount_id'],$vduArr['areaid'],true);
				$masterPacksize=$this->getMaseterPackSize($vduArr['volume_discount_id']);

				self::$dataToIndex[self::$counter]['premiumads_pack_order_id'] = $val['orderid'];

				if(is_array($vduArr)) {

					if($vduArr["admintype"]=="reseller"){
						self::$dataToIndex[self::$counter]['premiumads_extended_product_type'] = "Reseller Pack";
					}elseif($vduArr["admintype"]=="rsuser"){
						self::$dataToIndex[self::$counter]['premiumads_extended_product_type'] = "Reseller Distributed Pack";
					}else{
						self::$dataToIndex[self::$counter]['premiumads_extended_product_type'] = "Pack";
					}



					self::$dataToIndex[self::$counter]['premiumads_vdu_id'] = self::getNumbers($vduArr['id']); //not done
					self::$dataToIndex[self::$counter]['premiumads_vdu_vd_id'] = self::getNumbers($vduArr['volume_discount_id']); //not done
					self::$dataToIndex[self::$counter]['premiumads_vdu_uid_id'] = self::getNumbers($vduArr['uid']); //not done
					self::$dataToIndex[self::$counter]['premiumads_vdu_remaining_credit'] = $vduArr['remaining_credit']; //not done



					$this->calculateForceConsume($vduArr, $val, $masterPacksize);


					self::$dataToIndex[self::$counter]['premiumads_vdu_total_credits_used'] = $masterPacksize["total_credit"] - $vduArr['remaining_credit']; //not done

					//self::$dataToIndex[self::$counter]['premiumads_vdu_total_used_credit'] = '';//$vduArr['mobile']; //not done
					//self::$dataToIndex[self::$counter]['premiumads_vdu_current_credit_number'] = ''; //$vduArr['mobile']; //not done
					self::$dataToIndex[self::$counter]['premiumads_vdu_created_date'] = self::getNumbers($vduArr['created_on']); //not done
					self::$dataToIndex[self::$counter]['premiumads_vdu_expiry_date'] = self::getNumbers($vduArr['expired_on']); //not done
					self::$dataToIndex[self::$counter]['premiumads_vdu_last_updated_date'] = self::getNumbers($vduArr['last_updated']); //not done
					self::$dataToIndex[self::$counter]['premiumads_vdu_status'] = $vduArr['status']; //not done
					self::$dataToIndex[self::$counter]['premiumads_vdu_area_id'] = $vduArr['areaid']; //not done
					self::$dataToIndex[self::$counter]['premiumads_vdu_http_referer'] = $vduArr['http_referer']; //not done

					self::$dataToIndex[self::$counter]['premiumads_vdu_admintype'] = $vduArr['admintype']; //not done
					self::$dataToIndex[self::$counter]['premiumads_pausestarttime'] = self::getNumbers($vduArr['pausestarttime']); //not done
					self::$dataToIndex[self::$counter]['premiumads_zerocredit_status_date'] = self::getNumbers($vduArr['zerocredit_status_date']); //not done
				}


				if($val['paymenttype'] == "v"){
					$resellerPack = $this->getVDUOrderDetails($val['packid']);

					self::$dataToIndex[self::$counter]['premiumads_reseller_pack_order_id'] = $resellerPack['orderid']; //not done

					self::$dataToIndex[self::$counter]['premiumads_reseller_pack_id'] = $val['packid']; //not done
				}


			}

			/**** indexing all fields for PAck CREDIT details END***/
			if(($val['paymenttype'] == "v" || $val['paymenttype'] == "ar") && $val['producttype'] == "ad") {

				$vduArr = $this->getVDUDetails($val['packid']);
				$this->fillVolumeDiscoutData($vduArr['volume_discount_id'],$vduArr['areaid'],false);
				$vduOrdArr = $this->getVDUOrderDetails($val['packid']);

				self::$dataToIndex[self::$counter]['premiumads_ad_order_id'] = $val['orderid']; //done


				if(is_array($vduArr)) {

					if($vduArr["admintype"]=="reseller"){
						self::$dataToIndex[self::$counter]['premiumads_reseller_pack_order_id'] = $vduOrdArr['orderid']; //not done
						self::$dataToIndex[self::$counter]['premiumads_reseller_pack_id'] = $val['packid']; //not done
					}elseif($vduArr["admintype"]=="rsuser"){
						$resellerPack = $this->getVDUOrderDetails($val['parentpackid']);
						self::$dataToIndex[self::$counter]['premiumads_reseller_pack_id'] = $val['parentpackid']; //not done
						self::$dataToIndex[self::$counter]['premiumads_reseller_pack_order_id'] = $resellerPack['orderid']; //not done
					}



					self::$dataToIndex[self::$counter]['premiumads_pack_order_id'] = $vduOrdArr['orderid']; //not done


					self::$dataToIndex[self::$counter]['premiumads_usedcredits_pack_activated_date'] = self::getNumbers($vduOrdArr['activeordertimestamp']); //not done

					self::$dataToIndex[self::$counter]['premiumads_vdu_id'] = $vduArr['id']; //not done
					self::$dataToIndex[self::$counter]['premiumads_vdu_vd_id'] = $vduArr['volume_discount_id']; //not done
					self::$dataToIndex[self::$counter]['premiumads_vdu_uid_id'] = $vduArr['uid']; //not done
					self::$dataToIndex[self::$counter]['premiumads_vdu_remaining_credit'] = self::getNumbers($vduArr['remaining_credit']); //not done

					$userCredit=$this->getUsedCredit($val['id'],$val['packid']);
					$masterPacksize=$this->getMaseterPackSize($vduArr['volume_discount_id']);

					$this->calculateForceConsume($vduArr, $val, $masterPacksize);

					self::$dataToIndex[self::$counter]['premiumads_vdu_total_credits_used'] = self::getNumbers($masterPacksize["total_credit"] - $vduArr['remaining_credit']); //not done
					self::$dataToIndex[self::$counter]['premiumads_vdu_current_credits_used'] = self::getNumbers($userCredit);
					self::$dataToIndex[self::$counter]['premiumads_vdu_current_credits_remaining'] = self::getNumbers($masterPacksize["total_credit"] - $userCredit);

					self::$dataToIndex[self::$counter]['premiumads_vdu_created_date'] = $vduArr['created_on']; //not done
					self::$dataToIndex[self::$counter]['premiumads_vdu_expiry_date'] = $vduArr['expired_on']; //not done
					self::$dataToIndex[self::$counter]['premiumads_vdu_last_updated_date'] = $vduArr['last_updated']; //not done
					self::$dataToIndex[self::$counter]['premiumads_vdu_status'] = $vduArr['status']; //not done
					self::$dataToIndex[self::$counter]['premiumads_vdu_area_id'] = self::getNumbers($vduArr['areaid']); //not done
					self::$dataToIndex[self::$counter]['premiumads_vdu_http_referer'] = $vduArr['http_referer']; //not done
				}
			}

			//when this got indexed
			self::$dataToIndex[self::$counter]['data_indexed_time'] = time(); //done

			self::$counter++;
		}
	}


	public function calculateForceConsume($vduArr, $val, $masterPacksize){

		if(($val['paymenttype'] == "v" || $val["producttype"]=="vd") && $vduArr['status']==2){

			$forceConsumeAmount = 0;
			if($val['paymenttype'] == "v") {

				$forceConsumeAmount = $vduArr['remaining_credit']*$val["amount"];

			}else if($val["producttype"]=="vd"){

				$packCredit = $masterPacksize["total_credit"];
				$orderAmount = $val["amount"];

				$singleItemAmount = $orderAmount/$packCredit;

				$forceConsumeAmount = $vduArr['remaining_credit'] * $singleItemAmount;

			}

			self::$dataToIndex[self::$counter]['premiumads_vdu_force_consume_amount'] = round( self::getNumbers($forceConsumeAmount), 3);

		}else{
			self::$dataToIndex[self::$counter]['premiumads_vdu_force_consume_amount'] = 0;
		}
	}

	protected function parseAdStatus($val) {
		//        Active = 0;
		//        EXPIRED_BY_SELF AD = 1;
		//        EXPIRED_BY_MASTER AD= 2;
		//        User deleted = 3;
		//        Admin deleted = 4;
		//        Flag and Delay = 11;
		//        PENDING AD = 20;
		if($val == '0') {
			return 'Active';
		} else if($val == '1' || $val == '2') {
			return 'Expired';
		} else if($val == '3') {
			return 'User deleted';
		} else if($val == '4') {
			return 'Admin deleted';
		} else if($val == '20') {
			return 'Flag and Delay';
		} else if($val == '11') {
			return 'Pending';
		}
	}

	//ppp

	protected function parseAttributes($val) {
		//        $sql = 'SELECT tpc_description FROM babel_topic WHERE tpc_id  = 67709765';
		//        $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
		//        $objStmt->execute();
		//        $items = $objStmt->fetchAll();
		//        $val = $items[0]['tpc_description'];


		preg_match_all('/(.*?)\:(.*?)([\r\n]+|$)/',$val,$attributes);
		$attrString = '';
		$allowedAttr = $this->allowed_attributes;
		//print_r($allowedAttr); exit;
		for($i=0; $i < count($attributes[0]); $i++) {

			//if(in_array($attributes[1][$i], $allowedAttr)) {
			$attrString .= $attributes[0][$i].'|';
			//}
		}

		//echo rtrim($attrString, '|'); exit;

		return rtrim($attrString, '|');

	}

	protected function parseAdType($val) {

		preg_match('/Ad_Type:offer/',$val,$match1);
		if(!empty($match1[0])) {
			return 'Offer';
		}

		preg_match('/Ad_Type:want/',$val,$match2);
		if(!empty($match2[0])) {
			return 'Want';
		}
	}

	protected function parseLocations($val) {
		$locs = explode('|',$val);
		return trim(implode(', ',$locs));

	}

	protected function getMetacatName($metaId) {
		$key = new Rediska_Key('METACAT_ID|'.$metaId);
		$value = $key->getValue();
		if($value == null) {
			$sql = 'SELECT nod_name FROM babel_node WHERE node_id = '.$metaId.' AND nod_title != ""';
			$objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
			$objStmt->execute();
			$items = $objStmt->fetchAll();
			$key->setValue($items[0]['nod_name']);
			return strip_tags($items[0]['nod_name']);
		} else {
			return $value;
		}
	}

	protected function getCityName($cityId) {
		$key = new Rediska_Key('CITY_ID|'.$cityId);
		$value = $key->getValue();
		if($value == null) {

			$sql ='SELECT area_name FROM babel_area WHERE area_id = '.$cityId.' AND area_title != ""';
			$objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get("dbconnection"), $sql);
			$objStmt->execute();
			$items = $objStmt->fetchAll();
			unset($objStmt);

			//set redis key
			$key->setValue(strip_tags($items[0]["area_name"]));

			return strip_tags($items[0]["area_name"]);
		} else {
			return $value;
		}

	}

	protected function getReplyCountForAd($adId) {
		if($adId != '' || !empty($adId)) {
			$obj = new Model_ReplySolr(array());
			$obj->solrUrl = SOLR_META_QUERY_REPLIES;
			$count = $obj->getReplyCountForAd($adId);
			if($count != "" && !is_null($count)) return (int) $count; else return "0";
		} else return "0";
	}

	protected function parsePremiumAdType($type) {
		//        B -basic ad/ free ad
		//        T - Top ad
		//        H - Urgent ad
		//        HT - TOp and urgent ad
		if($type == 'T') {
			return 'TOP';
		} else if($type == 'H') {
			return 'URGENT';
		} else if($type == 'HT') {
			return 'ALL';
		}

	}
	function getAccountingdate(&$val){

		$activated_date = $val['activeordertimestamp'];
		$accounting_date = $val['accounting_timestamp'];

		if($accounting_date){
			self::$dataToIndex[self::$counter]['premiumads_actual_accounting'] = self::getNumbers($accounting_date); //done
		}else{
			self::$dataToIndex[self::$counter]['premiumads_actual_accounting'] = self::getNumbers($activated_date); //done
		}

		$refund_date = $val['refund_date'];
		$refund_accounting_date = $val['refund_accounting_timestamp'];

		if($refund_accounting_date){
			self::$dataToIndex[self::$counter]['premiumads_actual_refund_accounting'] = self::getNumbers($refund_accounting_date); //done
		}else{
			self::$dataToIndex[self::$counter]['premiumads_actual_refund_accounting'] = self::getNumbers($refund_date); //done
		}
	}



	function fillVolumeDiscoutData($vdId, $areaid, $flag){

		if ($vdId){
			$vdData = $this->getVolumeDiscoutData($vdId);
			self::$dataToIndex[self::$counter]['premiumads_vd_id'] = self::getNumbers($vdData['id']); //done
			self::$dataToIndex[self::$counter]['premiumads_vd_adstyle'] = $this->parseAdstyle($vdData['adstyle']); //done
			self::$dataToIndex[self::$counter]['premiumads_vd_category'] = self::getNumbers($vdData['category']); //done
			self::$dataToIndex[self::$counter]['premiumads_vd_discount'] = self::getNumbers($vdData['discount']); //done
			self::$dataToIndex[self::$counter]['premiumads_vd_amount'] = self::getNumbers($vdData['amount']); //done
			self::$dataToIndex[self::$counter]['premiumads_vd_total_credit'] = self::getNumbers($vdData['total_credit']); //done
			self::$dataToIndex[self::$counter]['premiumads_vd_status'] = $this->parseVduStatus($vdData['status']); //done
			self::$dataToIndex[self::$counter]['premiumads_vd_validity'] = self::getNumbers($vdData['pack_validity']); //done
			self::$dataToIndex[self::$counter]['premiumads_vd_posttype'] = $this->parsePostType($vdData['posttype']); //done
			self::$dataToIndex[self::$counter]['premiumads_vd_telemarketer_name'] = $vdData['telemarketer_name']; //done
			self::$dataToIndex[self::$counter]['premiumads_vd_ro_name'] = $vdData['ro_name']; //done
			self::$dataToIndex[self::$counter]['premiumads_vd_telemarketer_tl_name'] = $vdData['telemarketer_tl_name']; //done
			self::$dataToIndex[self::$counter]['premiumads_vd_territory_manager_name'] = $vdData['territory_manager_name']; //done
			self::$dataToIndex[self::$counter]['premiumads_vd_previous_pack'] = $vdData['previous_pack']; //done

			self::$dataToIndex[self::$counter]['premiumads_vd_sales_manager'] = $vdData['sales_manager']; //done
			self::$dataToIndex[self::$counter]['premiumads_vd_bgs_model_name'] = $vdData['bgs_model_name']; //done
			self::$dataToIndex[self::$counter]['premiumads_vd_bgs_iemi_number'] = $vdData['bgs_iemi_number']; //done
			self::$dataToIndex[self::$counter]['premiumads_vd_is_microsite'] = $vdData['is_microsite']; //done
			self::$dataToIndex[self::$counter]['premiumads_vd_payment_type'] = $vdData['payment_type']; //done
			self::$dataToIndex[self::$counter]['premiumads_vd_installment'] = $vdData['installment']; //done


			if($flag){
				if ($vdData['category']){
					$catData = $this->getCategoryData($vdData['category'], $areaid);
					self::$dataToIndex[self::$counter]['premiumads_city_id'] = $catData['nod_areaid']; //done
					self::$dataToIndex[self::$counter]['premiumads_city_name'] = $catData['nod_areaname']; //done
					self::$dataToIndex[self::$counter]['premiumads_metacategory_id'] = $catData['node_id']; //done
					self::$dataToIndex[self::$counter]['premiumads_global_metacategory_id'] = $catData['nod_globalId']; //done
					self::$dataToIndex[self::$counter]['premiumads_metacategory_name'] = $catData['nod_name']; //done
				}else {
					if ($areaid){
						$areaData=$this->getAreaData($areaid);
						self::$dataToIndex[self::$counter]['premiumads_city_id'] = $areaData['area_id']; //done
						self::$dataToIndex[self::$counter]['premiumads_city_name'] = $areaData['area_title']; //done
					}
				}
			}
		}

	}

	public function getAmount($val){
		//return;
		$amt = $val['amount'];
		if ($val['paymenttype']=='mt'){
			$amt = ($val['amount'] > 0) ? 9 : 0.00; //done
		}else {
			$amt = self::getNumbers((($val['amount'] > 0) ? str_replace(',','',$val['amount']) : 0.00)); //done
		}

		$aprilTimestamp = strtotime("1 April 2012 00:00:00");

		$tax = 10.3;

		$ts = $val["activeordertimestamp"];

		if(!$ts){
			$ts = $val["createdtimestamp"];
		}
		if($ts >= $aprilTimestamp){
			$tax = 12.36;
		}

		//$net = round($amt - $amt*$tax/100, 2);

		$net = round($amt*100/($tax + 100), 2);


		self::$dataToIndex[self::$counter]['premiumads_tax'] = $tax;
		self::$dataToIndex[self::$counter]['premiumads_net_amount'] = $net;
		self::$dataToIndex[self::$counter]['premiumads_amount'] = $amt;
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

	protected function initiateUpdatingOfDepenedantCores() {
		return true;

	}

}



//get command line arguments
$args = $argv;

//start indexing from here:
$objIndexing = new Premiumadsindexing();
$objIndexing->init($args);
