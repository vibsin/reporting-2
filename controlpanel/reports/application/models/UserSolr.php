<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


class Model_UserSolr {
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
    public $solrUrl         = SOLR_META_QUERY_USERS;
    public $finalUrl        = '';
    public $queryArray      = array();

    public $columnsToShow = array();

    public $totalRecordsFound = '';
    public $records = '';
    public $separator = "|";
    public $sectionName = 'user';

    public function  __construct($postedParams) {
        $this->post = $postedParams;
    }

	
public function getSingleFieldFromUsers($fieldToReturn,$userId) {
            $solrVars = array(
                    'indent'    =>  $this->indent,
                    'version'   =>  $this->version,
                    'fq'        =>  $this->fq,
                    'start'     =>  $this->start,
                    'rows'      =>  1,
                    'fl'        =>  $fieldToReturn,
                    'wt'        =>  $this->wt,
                    'explainOther'=> $this->explainOther,
                    'hl.fl'     =>  $this->hl_fl,
                    'q'         => 'id:'.$userId
            );

             $solrVarsStr = '';
             foreach($solrVars as $key => $val) {
                 $solrVarsStr .= $key.'='.trim($val).'&';
             }

             //this is final query
             $this->finalUrl =  rtrim($this->solrUrl.'select?'.$solrVarsStr,'&');
             
            try {
                $obj = new Utility_SolrQueryAnalyzer($this->finalUrl,__FILE__.' at line '.__LINE__);
                $data = $obj->init();
            } catch (Exception $e) {
                trigger_error($e->getMessage());
            }
             
             
             if(!empty($data)) {
                $xmlData = json_decode($data);
                $count = $xmlData->response->numFound;
                if($count > 0) {
                    return $xmlData;
                }  
        }
    }



    protected function buildQueryForDateSummarize($f,$t,$byDateOf) {
        $this->queryArray[] = '+('.$byDateOf.':['.$f.' TO '.$t.'])';
    }

    protected function setColumns() {
        $postedColumns = $this->post['user_columns'];
        $this->fl = 'id,';
        foreach($postedColumns as $key => $val) {

            //few exception where we need to change the caption
            //if($val == 'id') continue;
            if($val == 'metacategory_name') $val = 'category';
             if($val == 'subcategory_name') $val = 'subcategory';
            $this->columnsToShow['columns'][] = ucwords(strtolower(str_replace('_', ' ', $val)));
            $this->fl .= $val.",";
        }
        $this->fl .= "score";
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
            
                $stories = $xmlData->response->docs;
                 foreach ($stories as $story) {  
                        $this->columnsToShow['data'][] = $this->fillUsersArray($story);
                        //print_r($this->columnsToShow);exit;                        
                }

        } else {
            $this->columnsToShow['data'] = '';
        }
    }

    
    public function buildSolrQueryString() {
         //echo "START TIME:".microtime(true);
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

    protected function ddmmyyyToTimestamp($date) {
        return strtotime($date);
    }


    public function getfacetCountForSummarize($f,$t,$byDateOf) {
        //$this->setColumns();

        //now build query for every field

        $this->buildQueryForEmail();
        $this->buildQueryForFirstname();
        $this->buildQueryForMobile();
        $this->buildQueryForCity();
        $this->buildQueryForIsRegistered();
        if($byDateOf == 'registration_date') $this->buildQueryForDateSummarize($f,$t,$byDateOf);
        else $this->buildQueryForRegistrationDate();

        if($byDateOf == 'last_login_date') $this->buildQueryForDateSummarize($f,$t,$byDateOf);
        else $this->buildQueryForLastLoginDate();

        $this->buildQueryForNoOfAds();
        $this->buildQueryForNoOfReply();
        $this->buildQueryForNoOfAlert();
        $this->buildQueryForIsBulkUpload();

        if(empty($this->queryArray)) {
            $this->queryString = urlencode(trim('*:*'));
        } else {
            $this->queryString = urlencode(trim(implode(' AND ', $this->queryArray),'+'));
        }
        //echo $this->queryString;exit;

        $solrVars = array(
                'indent'    =>  $this->indent,
                'version'   =>  $this->version,
                'start'     =>  $this->start,
                'rows'     =>  '0',
                'wt'        =>  $this->wt,
                'facet'		=> 'true',
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
         //echo $finalUrl; exit;
         //$data = file_get_contents($finalUrl);
         
         try {
            $obj = new Utility_SolrQueryAnalyzer($finalUrl,__FILE__.' at line '.__LINE__);
            $data = $obj->init();
        } catch (Exception $e) {
            trigger_error($e->getMessage());
        }
         
         if(!empty($data)) {
            $xmlData = json_decode($data); //simplexml_load_string($data);
            //print_r($xmlData);
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
        $this->buildQueryForFirstname();
        $this->buildQueryForMobile();
        $this->buildQueryForCity();
        $this->buildQueryForIsRegistered();
        $this->buildQueryForRegistrationDate();
        $this->buildQueryForLastLoginDate();

        $this->buildQueryForNoOfAds();
        $this->buildQueryForNoOfReply();
        $this->buildQueryForNoOfAlert();
        $this->buildQueryForIsBulkUpload();




		//echo trim(implode('+', $this->queryArray),'+');exit;
        if(empty($this->queryArray)) {
            $this->queryString = urlencode(trim('*:*'));
        } else {
            $this->queryString = urlencode(trim(implode(' AND ', $this->queryArray),'+'));
        }

        $this->buildSolrQueryString();
        if($forExcel) {
            $this->parseXmlDataForExcel();
        } else {
            $this->parseXmlData();
        }
        //echo "<hr />END TIME:".microtime(true);

    }


     protected function buildQueryForEmail() {
        if(trim($this->post['user_filter_email_select_ece']) != 'none' &&
              trim($this->post['user_filter_email_text']) != '') {
            $operator = trim($this->post['user_filter_email_select_ece']);
            $text = trim($this->post['user_filter_email_text']);


            switch($operator) {
            case 'equals':
                $this->queryArray[] = '+(email:'.$text.')'; //exact search
            break;

            case 'contains':
                $this->queryArray[] = '+(email:*'.$text.'*)'; //anywhere in between the text
            break;

            case 'excludes':
                $this->queryArray[] =  '-(email:*'.$text.'*)'; //does not contain
            break;

            default:
                $this->queryArray[] =  '+(email:'.$text.')'; //exact search
            break;
        }
        } 
    }


    protected function buildQueryForFirstname() {
        $queryString = '';
        if(!empty($this->post['user_filter_firstname'])) {
            if($this->post['user_filter_firstname'] == 'present') {
                $this->queryArray[] = '-(firstname:NA)';

            } else if($this->post['user_filter_firstname'] == 'not_present') {
                $this->queryArray[] = '+(firstname:NA)';
            }
        }
    }


    protected function buildQueryForMobile() {
        $queryString = '';
        if(!empty($this->post['user_filter_mobile'])) {
            if($this->post['user_filter_mobile'] == 'present') {
                $this->queryArray[] = '-(mobile:NA)';

            } else if($this->post['user_filter_mobile'] == 'not_present') {
                $this->queryArray[] = '+(mobile:NA)';
            }
        }

    }


    protected function buildQueryForCity() {
        if(!empty($this->post['user_filter_city'])) {
            if($this->post['user_filter_city'] == 'all') {
                $this->queryArray[] = '+(city_id:*)';
            } else {
                $this->queryArray[] = '+(city_id:'.trim($this->post['user_filter_city']).')';
            }
        }
    }


    protected function buildQueryForIsRegistered() {
        $queryString = '';
        if(!empty($this->post['user_filter_registered'])) {
            if($this->post['user_filter_registered'] == 'yes') {
                $this->queryArray[] = '+(is_registered:Yes)'; //everything except blank which

            } else if($this->post['user_filter_registered'] == 'no') {
                $this->queryArray[] = '+(is_registered:No)';
            }
        }
    }


    protected function buildQueryForRegistrationDate() {
        if(!empty($this->post['user_filter_regdate_from']) &&
                !empty($this->post['user_filter_regdate_to'])) {
            $from = $this->ddmmyyyToTimestamp($this->post['user_filter_regdate_from']);
            $to = $this->ddmmyyyToTimestamp($this->post['user_filter_regdate_to'])+TO_DATE_INCREMENT;
            $this->queryArray[] = '+(registration_date:['.$from.' TO '.$to.'])';
        }
    }


    protected function buildQueryForLastLoginDate() {
        if(!empty($this->post['user_filter_lastlogin_from']) &&
                !empty($this->post['user_filter_lastlogin_from'])) {
            $from = $this->ddmmyyyToTimestamp($this->post['user_filter_lastlogin_from']);
            $to = $this->ddmmyyyToTimestamp($this->post['user_filter_lastlogin_from'])+TO_DATE_INCREMENT;
            $this->queryArray[] = '+(last_login_date:['.$from.' TO '.$to.'])';
        }
    }


    protected function buildQueryForNoOfAds() {
        if(trim($this->post['user_filter_no_of_ads_range']) != '' &&
              trim($this->post['user_filter_no_of_ads_text']) != '') {

            $qty = trim($this->post['user_filter_no_of_ads_text']);

            $obj = new Zend_Validate_Int();
            $st = $obj->isValid($qty);

            if($st && $qty >= 0) {
                switch($this->post['user_filter_no_of_ads_range']) {
                    case 'less':
                        $this->queryArray[] = '+(no_of_ads:[* TO '.($qty - 1).'])';
                        break;
                    case 'less_equal':
                        $this->queryArray[] = '+(no_of_ads:[* TO '.$qty.'])';
                        break;
                    case 'greater':
                        $this->queryArray[] = '+(no_of_ads:['.($qty + 1).' TO *])';
                        break;
                    case 'greater_equal':
                        $this->queryArray[] = '+(no_of_ads:['.$qty.' TO *])';
                        break;
                    case 'equal':
                        $this->queryArray[] = '+(no_of_ads:'.$qty.')';
                        break;
                    case 'not_equal':
                        $this->queryArray[] = '-(no_of_ads:'.$qty.')';
                        break;
                }
            }
        }
    }


    protected function buildQueryForNoOfReply() {
        if(trim($this->post['user_filter_no_of_replies_range']) != '' &&
              trim($this->post['user_filter_no_of_replies_text']) != '') {

            $qty = trim($this->post['user_filter_no_of_replies_text']);

            $obj = new Zend_Validate_Int();
            $st = $obj->isValid($qty);

            if($st && $qty >= 0) {
                switch($this->post['user_filter_no_of_replies_range']) {
                    case 'less':
                        $this->queryArray[] = '+(no_of_reply:[* TO '.($qty - 1).'])';
                        break;
                    case 'less_equal':
                        $this->queryArray[] = '+(no_of_reply:[* TO '.$qty.'])';
                        break;
                    case 'greater':
                        $this->queryArray[] = '+(no_of_reply:['.($qty + 1).' TO *])';
                        break;
                    case 'greater_equal':
                        $this->queryArray[] = '+(no_of_reply:['.$qty.' TO *])';
                        break;
                    case 'equal':
                        $this->queryArray[] = '+(no_of_reply:'.$qty.')';
                        break;
                    case 'not_equal':
                        $this->queryArray[] = '-(no_of_reply:'.$qty.')';
                        break;
                }
            }
        }
    }


    protected function buildQueryForNoOfAlert() {
        if(trim($this->post['user_filter_no_of_alerts_range']) != '' &&
              trim($this->post['user_filter_no_of_alerts_text']) != '') {

            $qty = trim($this->post['user_filter_no_of_alerts_text']);

            $obj = new Zend_Validate_Int();
            $st = $obj->isValid($qty);

            if($st && $qty >= 0) {
                switch($this->post['user_filter_no_of_alerts_range']) {
                    case 'less':
                        $this->queryArray[] = '+(no_of_alerts:[* TO '.($qty - 1).'])';
                        break;
                    case 'less_equal':
                        $this->queryArray[] = '+(no_of_alerts:[* TO '.$qty.'])';
                        break;
                    case 'greater':
                        $this->queryArray[] = '+(no_of_alerts:['.($qty + 1).' TO *])';
                        break;
                    case 'greater_equal':
                        $this->queryArray[] = '+(no_of_alerts:['.$qty.' TO *])';
                        break;
                    case 'equal':
                        $this->queryArray[] = '+(no_of_alerts:'.$qty.')';
                        break;
                    case 'not_equal':
                        $this->queryArray[] = '-(no_of_alerts:'.$qty.')';
                        break;
                }
            }
        }
    }



    protected function buildQueryForIsBulkUpload() {
        $queryString = '';
        if(!empty($this->post['user_filter_bulk_upload'])) {
            if($this->post['user_filter_bulk_upload'] == 'yes') {
                $this->queryArray[] = '+(is_bulk_allowed:Yes)'; //everything except blank which

            } else if($this->post['user_filter_bulk_upload'] == 'no') {
                $this->queryArray[] = '+(is_bulk_allowed:No)';
            }
        }
    }
    
    
    
    
     protected function fillUsersArray($story) {
        $users = array();
        $users['Id'] = $story->id;
        $users['Email'] = ($story->email == '') ? 'NA': $story->email;
        $users['Mobile'] = ($story->mobile == '') ? 'NA': $story->mobile;
        $users['Nickname'] = ($story->nickname == '') ? 'NA': $story->nickname;
        $users['Fullname'] = ($story->fullname == '') ? 'NA': $story->fullname;
        $users['City Name'] = ($story->city_name == '') ? 'NA': $story->city_name;
        $users['Is Registered'] = ($story->is_registered == '') ? 'NA': $story->is_registered;
        $users['Registration Date'] = ($story->registration_date != 0) ? date('d-m-Y',$story->registration_date) : 'NA';
        $users['Last Login Date'] = ($story->last_login_date != 0) ? date('d-m-Y',$story->last_login_date) : 'NA';
        $users['No Of Ads'] = ($story->no_of_ads != '') ? $story->no_of_ads : 'NA';
        $users['No Of Reply'] = ($story->no_of_reply != '') ? $story->no_of_reply : 'NA';
        $users['No Of Alerts'] = ($story->no_of_alerts != '') ? $story->no_of_alerts : 'NA';
        $users['Is Bulk Allowed'] = ($story->is_bulk_allowed != '') ? $story->is_bulk_allowed : 'NA';
        
        return $users;
    }
    
    public function parseXmlDataForExcel() {
        //$data = file_get_contents($this->finalUrl);
        //echo $this->finalUrl;
        try {
            $obj = new Utility_SolrQueryAnalyzer($this->finalUrl,__FILE__.' at line '.__LINE__);
            $data = $obj->init();
            //print_r($data);
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
    
    protected function prepareDataForExcel($story,$counter) {
        $a = array();
        $str = "";

        $str .= '"'.$counter.'"'.$this->separator;
        $a[] = $counter;
        
        $str .=  '"'.$story->id.'"'.$this->separator;
        $a[] = $story->id;

        if(in_array('email',$this->post['user_columns'])) {
            $str .= (($story->email == '') ? 'NA': $story->email).$this->separator;
            $a[] = (($story->email == '') ? 'NA': $story->email);
        }

        if(in_array('fullname',$this->post['user_columns'])) {
            $str .= (($story->fullname == '') ? 'NA': $story->fullname).$this->separator;
            $a[] = (($story->fullname == '') ? 'NA': $story->fullname);
        }

        if(in_array('mobile',$this->post['user_columns'])) {
            $str .= (($story->mobile == '') ? 'NA': $story->mobile).$this->separator;
            $a[] = (($story->mobile == '') ? 'NA': $story->mobile);
        }


        if(in_array('city_name',$this->post['user_columns'])) {
            $str .= (($story->city_name == '') ? 'NA': $story->city_name).$this->separator;
            $a[] = (($story->city_name == '') ? 'NA': $story->city_name);
        }

        if(in_array('is_registered',$this->post['user_columns'])) {
            $str .= ($story->is_registered == '') ? 'NA': $story->is_registered.$this->separator;
            $a[] = ($story->is_registered == '') ? 'NA': $story->is_registered;
        }

        if(in_array('registration_date',$this->post['user_columns'])) {
            $str .= (($story->registration_date != 0) ? date('d-m-Y',$story->registration_date) : 'NA').$this->separator;
            $a[] = (($story->registration_date != 0) ? date('Y-m-d',$story->registration_date) : 'NA');
        }

        if(in_array('last_login_date',$this->post['user_columns'])) {
            $str .= (($story->last_login_date != 0) ? date('d-m-Y',$story->last_login_date) : 'NA').$this->separator;
            $a[] = (($story->last_login_date != 0) ? date('Y-m-d',$story->last_login_date) : 'NA');
        }

        if(in_array('no_of_ads',$this->post['user_columns'])) {
            $str .= (($story->no_of_ads != '') ? $story->no_of_ads : 'NA').$this->separator;
            $a[] = (($story->no_of_ads != '') ? $story->no_of_ads : 'NA');
        }

        if(in_array('no_of_reply',$this->post['user_columns'])) {
            $str .= (($story->no_of_reply != '') ? $story->no_of_reply : 'NA').$this->separator;
            $a[] = (($story->no_of_reply != '') ? $story->no_of_reply : 'NA');
        }

        if(in_array('no_of_alerts',$this->post['user_columns'])) {
            $str .= (($story->no_of_alerts != '') ? $story->no_of_alerts : 'NA').$this->separator;
            $a[] = (($story->no_of_alerts != '') ? $story->no_of_alerts : 'NA');
        }

        if(in_array('is_bulk_allowed',$this->post['user_columns'])) {
            $str .= (($story->is_bulk_allowed != '') ? $story->is_bulk_allowed : 'NA').$this->separator;
            $a[] = (($story->is_bulk_allowed != '') ? $story->is_bulk_allowed : 'NA');
        }
        
        return array("str" => $str,"arr" => $a);
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
    
    
    
    

    
}