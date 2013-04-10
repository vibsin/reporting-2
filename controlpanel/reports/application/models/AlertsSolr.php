<?php
class Model_AlertsSolr {

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
    public $solrUrl         = SOLR_META_QUERY_ALERTS;
    public $finalUrl        = '';
    public $queryArray      = array();

    public $columnsToShow = array();

    public $totalRecordsFound = '';
    public $records = '';
    
    public $separator = "|";
    public $sectionName = 'alerts';

    public function  __construct($postedParams) {
        $this->post = $postedParams;
    }


    public function getfacetCountForSummarize($f,$t,$byDateOf) {

        //$this->setColumns();

        //now build query for every field

        $this->buildQueryForEmail();
        $this->buildQueryForMobile();
        $this->buildQueryForWantOffering();
        $this->buildQueryForStatus();
        $this->buildQueryForCity();
        $this->buildQueryForLocality();
        $this->buildQueryForMetacategory();
        $this->buildQueryForSubcategory();
        if($byDateOf == 'creation_date') $this->buildQueryForDateSummarize($f,$t,$byDateOf);
        else $this->buildQueryForCreatedate();

        if($byDateOf == 'unsubscribe_date') $this->buildQueryForDateSummarize($f,$t,$byDateOf);
        else $this->buildQueryForSubscribedate();

        
		//echo trim(implode('+', $this->queryArray),'+');exit;
        if(empty($this->queryArray)) {
            $this->queryString = urlencode(trim('*:*'));
        } else {
            $this->queryString = urlencode(trim(implode(' AND ', $this->queryArray),'+'));
        }


        $solrVars = array(
                'indent'    =>  $this->indent,
                'version'   =>  $this->version,
                'start'     =>  $this->start,
                'facet'		=> 'true',
                'rows'     =>  '0',
                'wt'        =>  $this->wt,
                //'facet.field' => $this->fl,
                'q'         => trim($this->queryString,'+'),
                'facet.query' => trim($this->queryString,'+')
        );

         $solrVarsStr = '';
         foreach($solrVars as $key => $val) {
             $solrVarsStr .= $key.'='.trim($val).'&';
         }

         //this is final query
         $finalUrl =  rtrim($this->solrUrl.'select?'.$solrVarsStr,'&');
         //echo $finalUrl;
         //$data = file_get_contents($finalUrl);
        try {
            $obj = new Utility_SolrQueryAnalyzer($finalUrl,__FILE__.' at line '.__LINE__);
            $data = $obj->init();
        } catch (Exception $e) {
            trigger_error($e->getMessage());
        }
         
         
         if(!empty($data)) {
            $xmlData = json_decode($data);
            $dataArray =  $xmlData->response->numFound;
            return $dataArray;
         }
    }


    public function getResults($forExcel=false) {
        //query solr
        //first set the columns to show
        $this->setColumns();

        //now build query for every field
        
        $this->buildQueryForEmail();
        $this->buildQueryForMobile();
        $this->buildQueryForWantOffering();
        $this->buildQueryForStatus();
        $this->buildQueryForCity();
        $this->buildQueryForLocality();
        $this->buildQueryForMetacategory();
        $this->buildQueryForSubcategory();
        $this->buildQueryForCreatedate();
        $this->buildQueryForSubscribedate();
		//echo trim(implode('+', $this->queryArray),'+');exit;
        if(empty($this->queryArray)) {
            $this->queryString = urlencode(trim('*:*'));
        } else {
            $this->queryString = urlencode(trim(implode(' AND ', $this->queryArray),'+'));
        }

        $this->buildSolrQueryString();
        
        

        if($forExcel) {//echo $this->finalUrl;exit;
            $this->parseXmlDataForExcel();
        } else {
            $this->parseXmlData();
        }
        
        
        //print_r($xmlData);

    }
    
    public function parseXmlData() {
        //$data = file_get_contents($this->finalUrl);
        try {
            $obj = new Utility_SolrQueryAnalyzer($this->finalUrl,__FILE__.' at line '.__LINE__);
            $data = $obj->init();
        } catch (Exception $e) {
            trigger_error($e->getMessage());
        }

        if(!empty($data)) {
            $xmlData = json_decode($data); 
            $this->totalRecordsFound = $xmlData->response->numFound;
            $stories = $xmlData->response->docs;
            foreach ($stories as $story) { 
                $this->columnsToShow['data'][] = $this->fillAlertsArray($story);
            }
        } else {
            $this->columnsToShow['data'] = '';
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

         //this is final query
         $this->finalUrl =  rtrim($this->solrUrl.'select?'.$solrVarsStr,'&');
    }


    protected function buildQueryForEmail() {
        if(trim($this->post['alerts_filter_email_select_ece']) != 'none' &&
              trim($this->post['alerts_filter_email_text']) != '') {
            $operator = trim($this->post['alerts_filter_email_select_ece']);
            $text = trim($this->post['alerts_filter_email_text']);
            
            
            switch($operator) {
                case 'equals':
                    $this->queryArray[] = '+(email:'.$text.')'; //exact search
                break;

                case 'contains':
                    $this->queryArray[] = '+(email:\*'.$text.'\*)'; //anywhere in between the text
                break;

                case 'excludes':
                    $this->queryArray[] =  '-(email:\*'.$text.'\*)'; //does not contain
                break;

                default:
                    $this->queryArray[] =  '+(email:'.$text.')'; //exact search
                break;
            }
        } 
        //return urlencode($queryString);
    }

    /**
     * For radio button
     *
     */
    protected function buildQueryForMobile() {
        $queryString = '';
        if(!empty($this->post['alerts_filter_mobile'])) {
                if($this->post['alerts_filter_mobile'] == 'present') {
                    $this->queryArray[] = '+(mobile:[* TO *])';
                    
                } else if($this->post['alerts_filter_mobile'] == 'not_present') {
                    $this->queryArray[] = '-(mobile:[* TO *])';
                }
            } 
        
    }
    
    protected function buildQueryForWantOffering() {
        $queryString = '';
        if(!empty($this->post['alerts_filter_wantoffering'])) {

            if(count($this->post['alerts_filter_wantoffering']) == 1) {
                if($this->post['alerts_filter_wantoffering'][0] == 'want') {
                    $this->queryArray[] = '+(ad_type:Want)';

                } else if($this->post['alerts_filter_wantoffering'][0] == 'offering') {
                    $this->queryArray[] = '+(ad_type:Offering)';
                }

            } else if(count($this->post['alerts_filter_wantoffering']) == 2) {
                $this->queryArray[] = '+((ad_type:Want) OR (ad_type:Offering))';
            }
            //$this->columnsToShow[] = 'Want/Offering';

        }
    }

    protected function buildQueryForStatus() {;
        $queryString = '';
        if(!empty($this->post['alerts_filter_status'])) {

            if(count($this->post['alerts_filter_status']) == 1) {
                if($this->post['alerts_filter_status'][0] == '0') {
                    $this->queryArray[] = '+(status:0)';

                } else if($this->post['alerts_filter_status'][0] == '2') {
                    $this->queryArray[] = '+(status:2)';
                }

            } else if(count($this->post['alerts_filter_status']) == 2) {
                $this->queryArray[] = '+((status:0) OR (status:2))';
            }

            //$this->columnsToShow[] = 'Status';

        }
    }

    protected function buildQueryForCity() {
        if(!empty($this->post['alerts_filter_city'])) {
        	if($this->post['alerts_filter_city'] == 'all') {
        		$this->queryArray[] = '+(city_id:*)';
        	} else {
        		$this->queryArray[] = '+(city_id:'.trim($this->post['alerts_filter_city']).')';
        	}
            
            //$this->columnsToShow[] = 'City';
        }
    }

    protected function buildQueryForLocality() {
        if(!empty($this->post['alerts_filter_localities'])) {
        	if($this->post['alerts_filter_localities'] == '0') {
        		$this->queryArray[] = '+(localities:*)';
        		
        	} else {
        		
        		$this->queryArray[] = '+(localities:'.trim($this->post['alerts_filter_localities']).')';
        	}
            
            //$this->columnsToShow[] = 'Locality';
        }
    }

    protected function buildQueryForMetacategory() {
        if(!empty($this->post['alerts_filter_metacat'])) {
        	if($this->post['alerts_filter_metacat'] == 'all') {
        		//if doing a global search based on city
        		if($this->post['alerts_filter_city'] == 'all') {
        			$this->queryArray[] = '+(global_metacategory_id:[* TO *])';
        		} else { //doing city specific search
        			$this->queryArray[] = '+(metacategory_id:[* TO *])';
        		}
        	} else {
        		//if doing a global search based on city
        		if($this->post['alerts_filter_city'] == 'all') {
        			$this->queryArray[] = '+(global_metacategory_id:'.trim($this->post['alerts_filter_metacat']).')';
        		} else {
        			//doing city specific search
        			$this->queryArray[] = '+(metacategory_id:'.trim($this->post['alerts_filter_metacat']).')';
        		}
        	}
            //$this->columnsToShow[] = 'Category';
        }
    }

    protected function buildQueryForSubcategory() {
         if(!empty($this->post['alerts_filter_subcat'])) {
         	if($this->post['alerts_filter_subcat'] == 'all') {
         		//if doing a global search based on city
        		if($this->post['alerts_filter_city'] == 'all') {
        			$this->queryArray[] = '+(global_subcategory_id:[* TO *])';
        		} else {
        			//doing city specific search
        			$this->queryArray[] = '+(subcategory_id:[* TO *])';
        		}
            	
         	} else {
         		//if doing a global search based on city
        		if($this->post['alerts_filter_city'] == 'all') {
        			$this->queryArray[] = '+(global_subcategory_id:'.trim($this->post['alerts_filter_subcat']).')';
        		} else {
        			//doing city specific search
        			$this->queryArray[] = '+(subcategory_id:'.trim($this->post['alerts_filter_subcat']).')';
        		}	
         	}
        }
    }


    protected function buildQueryForSubscribedateSummarize() {
        if(!empty($f)) {
            $from = $this->ddmmyyyToTimestamp($f);
            $to = $this->ddmmyyyToTimestamp($f)+TO_DATE_INCREMENT;
            $this->queryArray[] = '+(unsubscribe_date:['.$from.' TO '.$to.'])';
        }
    }


    protected function buildQueryForDateSummarize($f,$t,$byDateOf) {
        $this->queryArray[] = '+('.$byDateOf.':['.$f.' TO '.$t.'])';
    }

    protected function buildQueryForCreatedate() {
        if(!empty($this->post['alerts_filter_createdate_from']) &&
                !empty($this->post['alerts_filter_createdate_to'])) {
            $from = $this->ddmmyyyToTimestamp($this->post['alerts_filter_createdate_from']);
            $to = $this->ddmmyyyToTimestamp($this->post['alerts_filter_createdate_to'])+TO_DATE_INCREMENT;
            $this->queryArray[] = '+(creation_date:['.$from.' TO '.$to.'])';

        }
        
    }


    


    protected function buildQueryForSubscribedate() {
        if(!empty($this->post['alerts_filter_unsubscribedate_from']) &&
                !empty($this->post['alerts_filter_unsubscribedate_to'])) {
            $from = $this->ddmmyyyToTimestamp($this->post['alerts_filter_unsubscribedate_from']);
            $to = $this->ddmmyyyToTimestamp($this->post['alerts_filter_unsubscribedate_to'])+TO_DATE_INCREMENT;
            $this->queryArray[] = '+(unsubscribe_date:['.$from.' TO '.$to.'])';
            //$this->columnsToShow[] = 'Unsubscribe Date';
        }
    }


    protected function setColumns() {
        $postedColumns = $this->post['alerts_columns'];
        $this->fl = 'id,';
        foreach($postedColumns as $key => $val) {

            //few exception where we need to change the caption
            if($val == 'meta_category') { $val = 'category'; $this->fl .= 'metacategory_name,'; }
            if($val == 'sub_category') { $this->fl .= 'subcategory_name,'; }
            if($val == 'city') { $this->fl .= 'city_name,'; }
            if($val == 'locality') { $this->fl .= 'localities,'; }
            if($val == 'want_offering') { $this->fl .= 'ad_type,'; }
            if($val == 'create_date') { $this->fl .= 'creation_date,'; }
            
            
            $this->columnsToShow['columns'][] = ucwords(strtolower(str_replace('_', ' ', $val)));
            $this->fl .= $val.",";
        }
        $this->fl .= "score";
    }


    /*****utiltiy functions for solr--not used****/
    function textSearch($solrField, $operator, $text) {
        switch($operator) {
            case 'equals':
                return '+('.$solrField.':'.$text.')'; //exact search
            break;

            case 'contains':
                return '+('.$solrField.':*'.$text.'*)'; //anywhere in between the text
            break;

            case 'excludes':
                return '-('.$solrField.':*'.$text.'*)'; //does not contain
            break;

            default:
                return $solrField.':'.$text; //exact search
            break;
        }
    }

    protected function ddmmyyyToTimestamp($date) {
        return strtotime($date);
    }

    function getAlertCountForUser($userId) {
        $solrVars = array(
            'indent'    =>  $this->indent,
            'version'   =>  $this->version,
            'start'     =>  0,
            'rows'      => 0,
            'wt'        => $this->wt,
            'facet'     => 'true',
            'facet.limit' => 1,
            'facet.field' => 'user_id',
            'q'         => 'user_id:'.$userId,
            'facet.query' => 'user_id:'.$userId
        );

        $solrVarsStr = '';
        foreach($solrVars as $key => $val) {
         $solrVarsStr .= $key.'='.trim($val).'&';
        }

        //this is final query
        $finalUrl =  rtrim($this->solrUrl.'select?'.$solrVarsStr,'&');
         //echo $finalUrl;exit;
        //$data = file_get_contents($finalUrl);
        try {
            $obj = new Utility_SolrQueryAnalyzer($finalUrl,__FILE__.' at line '.__LINE__);
            $data = $obj->init();
        } catch (Exception $e) {
            trigger_error($e->getMessage());
        }
        
        
        if(!empty($data)) {
            $xmlData = json_decode($data);
            //just get the number of counts for this query
            $dataArray =  $xmlData->response->numFound;
            return $dataArray;
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
            $xmlData = json_decode($data);
            $excel = array();
            $str = "";
            $counter = 1;
            $stories = $xmlData->response->docs;
            
            $hChunks = $this->prepareHeadersForExcel();
            
            $str .= $hChunks["str"];

            $excel = array(
                $counter => $hChunks["arr"]
            );
 
            foreach($stories as $story) {
                
                $chunks = $this->prepareDataForExcel($story, $counter);
                $counter++;
                $str .= $chunks["str"]."\n";
                $excel[$counter] = $chunks["arr"];
                unset($chunks);
                
            }
            
            $this->prepareDownLoadFile(array(
                                    "str" => trim($str),
                                    "arr" => $excel));
            

        }
                
    }
    
    
    protected function fillAlertsArray($story) {
        $alerts = array();
        $alerts['Id'] = $story->id;
        $alerts['Create Date'] = ($story->creation_date != 0) ? date('d-m-Y',$story->creation_date) : 'NA';
        $alerts['Email'] = ($story->email == '') ? 'NA': $story->email;
        $alerts['Status'] = ($story->status == '0') ? 'Active' : 'Unsubscribed';
        $alerts['Unsubscribe Date'] = ($story->unsubscribe_date != 0) ? date('d-m-Y',$story->unsubscribe_date) : 'NA';
        $alerts['Mobile'] = ($story->mobile != '') ? $story->mobile : 'NA';
        $alerts['Want Offering'] = ($story->ad_type != '') ? $story->ad_type : 'NA';
        $alerts['City'] = $story->city_name;
        $alerts['Locality'] = ($story->localities != '') ? $story->localities : 'NA';
        $alerts['Category'] = $story->metacategory_name;
        $alerts['Sub Category'] = $story->subcategory_name;
        return $alerts;
    }
    
    
    protected function prepareHeadersForExcel() {
        $str = '';
        $excelHeaders = array();

        if(!empty($this->columnsToShow['columns'])) {
            $excelHeaders[] = "Sr. No.";
            $excelHeaders[] = "Id";
            $str .= "\"Sr. No.\"".$this->separator."\"Id\"".$this->separator;
            foreach($this->columnsToShow['columns'] as $key => $val) {
                $str .= '"'.$val.'"'.$this->separator;
                $excelHeaders[] = $val;
            }

            $str .= "\n";
        }
        return array("str" => $str, "arr" => $excelHeaders);
    }
    
    protected function prepareDataForExcel($story,$counter) {
        $a = array();
        $str = "";

        $str .= '"'.$counter.'"'.$this->separator;
        $a[] = $counter;
        
        $str .=  '"'.$story->id.'"'.$this->separator;
        $a[] = $story->id;
        
        
        if(in_array('email',$this->post['alerts_columns'])) {
            $str .= (($story->email == '') ? 'NA': $story->email).$this->separator;
            $a[] = ($story->email == '') ? 'NA': $story->email;
        }

        if(in_array('mobile',$this->post['alerts_columns'])) {
            $str .= (($story->mobile == '') ? 'NA': $story->mobile).$this->separator;
            $a[] = (($story->mobile == '') ? 'NA': $story->mobile);
        }

        if(in_array('city',$this->post['alerts_columns'])) {
            $str .= (($story->city_name == '') ? 'NA': $story->city_name).$this->separator;
            $a[] = (($story->city_name == '') ? 'NA': $story->city_name);
        }

        if(in_array('locality',$this->post['alerts_columns'])) {
            $str .= (($story->localities == '') ? 'NA': $story->localities).$this->separator;
            $a[] = (($story->localities == '') ? 'NA': $story->localities);
        }


        if(in_array('meta_category',$this->post['alerts_columns'])) {
            $str .= (($story->metacategory_name == '') ? 'NA': $story->metacategory_name).$this->separator;
            $a[] = (($story->metacategory_name == '') ? 'NA': $story->metacategory_name);
        }


        if(in_array('sub_category',$this->post['alerts_columns'])) {
            $str .= (($story->subcategory_name == '') ? 'NA': $story->subcategory_name).$this->separator;
            $a[] = (($story->subcategory_name == '') ? 'NA': $story->subcategory_name);
        }

        if(in_array('want_offering',$this->post['alerts_columns'])) {
            $str .= (($story->ad_type == '') ? 'NA': $story->ad_type).$this->separator;
            $a[] = (($story->ad_type == '') ? 'NA': $story->ad_type);
        }

        if(in_array('status',$this->post['alerts_columns'])) {
            $str .= (($story->status == '0') ? 'Active' : 'Unsubscribed').$this->separator;
            $a[] = (($story->status == '0') ? 'Active' : 'Unsubscribed');
        }

        if(in_array('create_date',$this->post['alerts_columns'])) {
            $str .= (($story->creation_date != 0) ? date('d-m-Y',$story->creation_date) : 'NA').$this->separator;
            $a[] = (($story->creation_date != 0) ? date('Y-m-d',$story->creation_date) : 'NA');
        }


        if(in_array('unsubscribe_date',$this->post['alerts_columns'])) {
            $str .= (($story->unsubscribe_date != 0) ? date('d-m-Y',$story->unsubscribe_date) : 'NA').$this->separator;
            $a[]  = (($story->unsubscribe_date != 0) ? date('Y-m-d',$story->unsubscribe_date) : 'NA');
        }
        
        return array("str" => $str,"arr" => $a);
                
    }
    
    protected function prepareDownLoadFile($dataArr) {
        $str = $dataArr["str"];
        $excel = $dataArr["arr"];
        
        
        $key = md5(serialize($this->post));
        $fileName = $this->sectionName.'_'.date('d-m-Y',strtotime('now')).'_'.$key.'.csv';
        $filePath = BASE_PATH_CSV.'/'.$fileName;

        if(preg_match("/Windows/", $_SERVER["HTTP_USER_AGENT"])) {
            unset($str);
            //for windows

            $xls = new Quikr_ExcelXML('UTF-8', true, $fileName);
            $xls->addArray($excel);
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

        } else {
            //for linux
            unset($a);
            //write to csv file
            $handle = fopen($filePath,'w');
            fwrite($handle,$str);
            fclose($handle);

            //compress and create zip
            $filter     = new Zend_Filter_Compress(array(
            'adapter' => 'Zip',
            'options' => array(
                'archive' => $filePath.".zip",
            ),
            ));

            $filter->filter($filePath);

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
    
    
    function getSingleFieldFromAlert($alertId, $fieldToReturn = '') {
        $solrVars = array(
            'start' => $this->start,
            'rows' => 1,
            'wt' => $this->wt,
            'fl' => $fieldToReturn,
            'q' => 'id:' . $alertId
        );

        $solrVarsStr = '';
        foreach ($solrVars as $key => $val) {
            $solrVarsStr .= $key . '=' . trim($val) . '&';
        }

        //this is final query
        $this->finalUrl = rtrim($this->solrUrl . 'select?' . $solrVarsStr, '&');

        //$data = file_get_contents($this->finalUrl);
        
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