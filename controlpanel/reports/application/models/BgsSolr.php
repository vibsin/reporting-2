<?php

class Model_BgsSolr {

    public $post;
    public $queryString;
    public $limit;
    public $explainOther = '';
    public $fl = '*,score';
    public $indent = 'on';
    public $start = '0';              //Start Row
//    /**
//     *
//     * $q Solr/Lucene Statement
//     */
    public $q = '';
    public $hl_fl = '';               //Fields to Highlight
    /**
     *
     * $qt Query Type
     */
    public $qt = 'standard';

    /**
     *
     * $wt Output Type
     */
    public $wt = 'json';       //Output Type
    public $fq = '';
    public $version = '2.2';
    public $rows = MAX_RESULTS_PER_PAGE;             //Maximum Rows Returned
    public $solrUrl = SOLR_META_QUERY_BGS;
    public $finalUrl = '';
    public $queryArray = array();
    public $columnsToShow = array();
    public $totalRecordsFound = '';
    public $records = '';
    public $separator = "|";
    public $sectionName = 'bgs';

    public function __construct($postedParams) {
        $this->post = $postedParams;
        //print_r($this->post);exit;
    }

    public function getfacetCountForSummarize($f, $t, $byDateOf) {

        //$this->setColumns();
        //now build query for every field
        $this->buildQueryForId();
        $this->buildQueryForLeadItemType();
        $this->buildQueryForLeadItemId();
        $this->buildQueryForLeadItemDate();
        $this->buildQueryForBgsPosterEmail();
        $this->buildQueryForBgsPosterId();
        $this->buildQueryForBgsPosterMobile();
        $this->buildQueryForActionItemId();
        
        $this->buildQueryForIsStar();
        $this->buildQueryForIsRead();
        $this->buildQueryForIsCalled();
        $this->buildQueryForIsSmsed();
        $this->buildQueryForIsReplied();
        $this->buildQueryForAppVersion();
        
        $this->buildQueryForActionItemCityId();
        $this->buildQueryForActionItemMetaCatId();
        $this->buildQueryForActionItemSubCatId();
        $this->buildQueryForActionItemStyle();
//        $this->buildQueryForActionItemTitle();
//        $this->buildQueryForActionItemDesc();
        
        $this->buildQueryForActionItemPackId();
        $this->buildQueryForActionItemPackStatus();
        $this->buildQueryForActionItemRoName();
        $this->buildQueryForActionItemPackOrderId();
        $this->buildQueryForActionItemPackCityId();
        
        $this->buildQueryForLeadItemEmail();
        //$this->buildQueryForLeadItemMobile();
        //$this->buildQueryForLeadItemDesc();
        $this->buildQueryForLeadItemStyle();
        
        
 
        if ($byDateOf == 'lead_item_date_tdt') {
            $this->buildQueryForDateSummarize($f, $t, $byDateOf);
        }
        

        if (empty($this->queryArray)) {
            $this->queryString = urlencode(trim('*:*'));
        } else {
            $this->queryString = urlencode(trim(implode(' AND ', $this->queryArray), '+'));
        }


        $solrVars = array(
            'indent' => $this->indent,
            'version' => $this->version,
            'start' => $this->start,
            'facet' => 'true',
            'rows' => '0',
            'wt' => $this->wt,
            'q' => trim($this->queryString, '+'),
            'facet.query' => trim($this->queryString, '+')
        );

        $solrVarsStr = '';
        foreach ($solrVars as $key => $val) {
            $solrVarsStr .= $key . '=' . trim($val) . '&';
        }

        //this is final query
        $finalUrl = rtrim($this->solrUrl . 'select?' . $solrVarsStr, '&');
        //echo $finalUrl."<br />";
        //$data = file_get_contents($finalUrl);
        try {
            $obj = new Utility_SolrQueryAnalyzer($finalUrl, __FILE__ . ' at line ' . __LINE__);
            $data = $obj->init();
        } catch (Exception $e) {
            trigger_error($e->getMessage());
        }


        if (!empty($data)) {
            $xmlData = json_decode($data);
            $dataArray = $xmlData->response->numFound;
            return $dataArray;
        }
    }

    public function getResults($forExcel = false) {
        //query solr
        //first set the columns to show
        $this->setColumns();

        //now build query for every field

        $this->buildQueryForId();
        $this->buildQueryForLeadItemType();
        $this->buildQueryForLeadItemId();
        $this->buildQueryForLeadItemDate();
        $this->buildQueryForBgsPosterEmail();
        $this->buildQueryForBgsPosterId();
        $this->buildQueryForBgsPosterMobile();
        $this->buildQueryForActionItemId();
        
        $this->buildQueryForIsStar();
        $this->buildQueryForIsRead();
        $this->buildQueryForIsCalled();
        $this->buildQueryForIsSmsed();
        $this->buildQueryForIsReplied();
        $this->buildQueryForAppVersion();
        
        $this->buildQueryForActionItemCityId();
        $this->buildQueryForActionItemMetaCatId();
        $this->buildQueryForActionItemSubCatId();
        $this->buildQueryForActionItemStyle();
//        $this->buildQueryForActionItemTitle();
//        $this->buildQueryForActionItemDesc();
        
        $this->buildQueryForActionItemPackId();
        $this->buildQueryForActionItemPackStatus();
        $this->buildQueryForActionItemRoName();
        $this->buildQueryForActionItemPackOrderId();
        $this->buildQueryForActionItemPackCityId();
        
        $this->buildQueryForLeadItemEmail();
        //$this->buildQueryForLeadItemMobile();
        //$this->buildQueryForLeadItemDesc();
        $this->buildQueryForLeadItemStyle();
        


        //echo trim(implode('+', $this->queryArray),'+');exit;
        if (empty($this->queryArray)) {
            $this->queryString = urlencode(trim('*:*'));
        } else {
            $this->queryString = urlencode(trim(implode(' AND ', $this->queryArray), '+'));
        }

        $this->buildSolrQueryString();
        //echo trim(implode(' AND ', $this->queryArray), '+');
        if ($forExcel) {
            $this->parseXmlDataForExcel();
        } else {
            $this->parseXmlData();
        }


        //print_r($xmlData);
    }

    public function parseXmlData() {
        //$data = file_get_contents($this->finalUrl);
        try {
            $obj = new Utility_SolrQueryAnalyzer($this->finalUrl, __FILE__ . ' at line ' . __LINE__);
            $data = $obj->init();
        } catch (Exception $e) {
            trigger_error($e->getMessage());
        }

        if (!empty($data)) {
            $xmlData = json_decode($data);
            //print_r($xmlData);exit;
            $this->totalRecordsFound = $xmlData->response->numFound;
            $stories = $xmlData->response->docs;
            foreach ($stories as $story) {
                $this->columnsToShow['data'][] = $this->fillBgsArray($story);
            }
        } else {
            $this->columnsToShow['data'] = '';
        }
    }

    public function buildSolrQueryString() {

        $solrVars = array(
            'indent' => $this->indent,
            'version' => $this->version,
            'fq' => $this->fq,
            'start' => $this->start,
            'rows' => $this->rows,
            'fl' => $this->fl,
            //'qt'        =>  $this->qt,
            'wt' => $this->wt,
            'explainOther' => $this->explainOther,
            'hl.fl' => $this->hl_fl,
            'q' => trim($this->queryString, '+')
        );

        $solrVarsStr = '';
        foreach ($solrVars as $key => $val) {
            $solrVarsStr .= $key . '=' . trim($val) . '&';
        }

        //this is final query
        $this->finalUrl = rtrim($this->solrUrl . 'select?' . $solrVarsStr, '&');
    }
    
    //done
    protected function buildQueryForId() {
        if (!empty($this->post['bgs_filter_id'])) {
            $this->queryArray[] = '+(id:'.$this->post["bgs_filter_id"].')';
        }
    }
    
    //done
    protected function buildQueryForLeadItemType() {
        if (!empty($this->post['bgs_filter_lead_item_type_s'])) {

            if (count($this->post['bgs_filter_lead_item_type_s']) == 1) {
                if ($this->post['bgs_filter_lead_item_type_s'][0] == 'reply') {
                    $this->queryArray[] = '+(lead_item_type_s:Reply)';
                } else if ($this->post['bgs_filter_lead_item_type_s'][0] == 'ad') {
                    $this->queryArray[] = '+(lead_item_type_s:Ad)';
                }
            } else if (count($this->post['bgs_filter_lead_item_type_s']) == 2) {
                $this->queryArray[] = '+((lead_item_type_s:Reply) OR (lead_item_type_s:Ad))';
            }
            //$this->columnsToShow[] = 'Want/Offering';
        }
    }
    
    //done
    protected function buildQueryForLeadItemId() {
        if (!empty($this->post['bgs_filter_lead_item_id_l'])) {
            $this->queryArray[] = '+(lead_item_id_l:'.$this->post["bgs_filter_lead_item_id_l"].')';
        }
    }
    
 
    //done
    protected function buildQueryForLeadItemDate() {
        if (!empty($this->post['bgs_filter_lead_date_from']) &&
                !empty($this->post['bgs_filter_lead_date_to'])) {
            $from = $this->toUtc($this->post['bgs_filter_lead_date_from']);
            $to = date("Y-m-d\TH:i:s\Z",strtotime($this->post['bgs_filter_lead_date_to']) + TO_DATE_INCREMENT);
            $this->queryArray[] = '+(lead_item_date_tdt:[' . $from . ' TO ' . $to . '])';
        }
    }
    
    //done
    protected function buildQueryForBgsPosterEmail() {
        if (trim($this->post['bgs_filter_bgs_poster_email_s_select_ece']) != 'none' &&
                trim($this->post['bgs_filter_bgs_poster_email_s_text']) != '') {
            $operator = trim($this->post['bgs_filter_bgs_poster_email_s_select_ece']);
            $text = trim($this->post['bgs_filter_bgs_poster_email_s_text']);


            switch ($operator) {
                case 'equals':
                    $this->queryArray[] = '+(bgs_poster_email_s:' . $text . ')'; //exact search
                    break;

                case 'contains':
                    $this->queryArray[] = '+(bgs_poster_email_s:*' . $text . '*)'; //anywhere in between the text
                    break;

                case 'excludes':
                    $this->queryArray[] = '-(bgs_poster_email_s:*' . $text . '*)'; //does not contain
                    break;

                default:
                    $this->queryArray[] = '+(bgs_poster_email_s:' . $text . ')'; //exact search
                    break;
            }
        }
        
    }
    
    //done
    protected function buildQueryForBgsPosterId() {
        if (!empty($this->post['bgs_filter_bgs_poster_id_l'])) {
            $this->queryArray[] = '+(bgs_poster_id_l:'.$this->post["bgs_filter_bgs_poster_id_l"].')';
        }
    }
    
    //done
    protected function buildQueryForBgsPosterMobile() {
        if (!empty($this->post['bgs_filter_bgs_poster_mobile_s'])) {
            $this->queryArray[] = '+(bgs_poster_mobile_s:'.$this->post["bgs_filter_bgs_poster_mobile_s"].')';
        }
    }
    
    //done
    protected function buildQueryForActionItemId() {
        if (!empty($this->post['bgs_filter_action_item_id_l'])) {
            $this->queryArray[] = '+(action_item_id_l:'.$this->post["bgs_filter_action_item_id_l"].')';
        }
    }
    
    //done
    protected function buildQueryForIsStar() {
        if(!empty($this->post['bgs_filter_is_star'])) {
            $this->queryArray[] = '+(is_star:'.ucfirst($this->post['bgs_filter_is_star']).')'; 
        }
    }
    //done
    protected function buildQueryForIsRead() {
        if(!empty($this->post['bgs_filter_is_read'])) {
            $this->queryArray[] = '+(is_read:'.ucfirst($this->post['bgs_filter_is_read']).')'; 
        }
    }
    
    //done
    protected function buildQueryForIsCalled() {
        if(!empty($this->post['bgs_filter_is_called'])) {
            $this->queryArray[] = '+(is_called:'.ucfirst($this->post['bgs_filter_is_called']).')'; 
        }
    }
    //done
    protected function buildQueryForIsSmsed() {
        if(!empty($this->post['bgs_filter_is_smsed'])) {
            $this->queryArray[] = '+(is_smsed:'.ucfirst($this->post['bgs_filter_is_smsed']).')'; 
        }
    }
    //done
    protected function buildQueryForIsReplied() {
        if(!empty($this->post['bgs_filter_is_replied'])) {
            $this->queryArray[] = '+(is_replied:'.ucfirst($this->post['bgs_filter_is_replied']).')'; 
        }
    }
    //done
    protected function buildQueryForAppVersion() {
        if (!empty($this->post['bgs_filter_app_version_text'])) {
            $this->queryArray[] = '+(app_version:'.$this->post["bgs_filter_app_version_text"].')';
        }
    }
    
     //done
    protected function buildQueryForActionItemCityId() {
        if (!empty($this->post['bgs_filter_city'])) {
            if ($this->post['bgs_filter_city'] == 'all') {
                $this->queryArray[] = '+(action_item_city_id_l:*)';
            } else {
                $this->queryArray[] = '+(action_item_city_id_l:' . trim($this->post['bgs_filter_city']) . ')';
            }
        }
    }
     //done
    protected function buildQueryForActionItemMetaCatId() {
        if (!empty($this->post['bgs_filter_metacat'])) {
            if ($this->post['bgs_filter_metacat'] != 'all') {
                //if doing a global search based on city
                if ($this->post['bgs_filter_city'] == 'all') {
                    $this->queryArray[] = '+(action_item_global_metacategory_id_l:' . trim($this->post['bgs_filter_metacat']) . ')';
                } else {
                    //doing city specific search
                    $this->queryArray[] = '+(action_item_category_id_l:' . trim($this->post['bgs_filter_metacat']) . ')';
                }
            }
            
        }
    }
     //done
    protected function buildQueryForActionItemSubCatId() {
        if (!empty($this->post['bgs_filter_subcat'])) {
            if ($this->post['bgs_filter_subcat'] != 'all') {
                //if doing a global search based on city
                if ($this->post['bgs_filter_city'] == 'all') {
                    $this->queryArray[] = '+(action_item_global_subcategory_id_l:' . trim($this->post['bgs_filter_subcat']) . ')';
                } else {
                    //doing city specific search
                    $this->queryArray[] = '+(action_item_subcategory_id_l:' . trim($this->post['bgs_filter_subcat']) . ')';
                }
            }
        }
    }
    
    //done
    protected function buildQueryForActionItemStyle() {

        $queryString = array();
        if(!empty($this->post['bgs_filter_action_item_style_s'])) {
            foreach($this->post['bgs_filter_action_item_style_s'] as $key => $val) {
                if($val == "TOP+URGENT") $val="ALL";
                $queryString[] = '(action_item_style_s:'.$val.')';
            }
            $this->queryArray[] = '('.implode(' OR ', $queryString).')';
        }
    }
    
    
    //done
    protected function buildQueryForActionItemPackId() {
        if (!empty($this->post['bgs_filter_action_item_pack_id_l'])) {
            $this->queryArray[] = '+(action_item_pack_id_l:'.$this->post["bgs_filter_action_item_pack_id_l"].')';
        }
    }

    //done
    protected function buildQueryForActionItemPackStatus() {
        if (!empty($this->post['bgs_filter_action_item_pack_status_s'])) {  
            if ($this->post['bgs_filter_action_item_pack_status_s'] == 'Enabled') {
                $this->queryArray[] = '+(action_item_pack_status_s:Enabled)';
            } else $this->queryArray[] = '-(action_item_pack_status_s:Enabled)';
        }
    }
    //done
    protected function buildQueryForActionItemRoName() {
        if (!empty($this->post['bgs_filter_action_item_ro_name_s'])) {
            $this->queryArray[] = '+(action_item_ro_name_s:"'.$this->post["bgs_filter_action_item_ro_name_s"].'")';
        }
    }
    
    //done
    protected function buildQueryForActionItemPackOrderId() {
        if (!empty($this->post['bgs_filter_action_item_pack_order_id_s'])) {
            $this->queryArray[] = '+(action_item_pack_order_id_s:'.$this->post["bgs_filter_action_item_pack_order_id_s"].')';
        }
    }
    //done
    protected function buildQueryForActionItemPackCityId() {
        if (!empty($this->post['bgs_filter_action_item_pack_city_id_l'])) {
            $this->queryArray[] = '+(action_item_pack_city_id_l:'.$this->post["bgs_filter_action_item_pack_city_id_l"].')';
        }
    }
    
    //done
    protected function buildQueryForLeadItemEmail() {
        if (trim($this->post['bgs_filter_lead_item_email_s_select_ece']) != 'none' &&
                trim($this->post['bgs_filter_lead_item_email_s_text']) != '') {
            $operator = trim($this->post['bgs_filter_lead_item_email_s_select_ece']);
            $text = trim($this->post['bgs_filter_lead_item_email_s_text']);


            switch ($operator) {
                case 'equals':
                    $this->queryArray[] = '+(lead_item_email_s:' . $text . ')'; //exact search
                    break;

                case 'contains':
                    $this->queryArray[] = '+(lead_item_email_s:*' . $text . '*)'; //anywhere in between the text
                    break;

                case 'excludes':
                    $this->queryArray[] = '-(lead_item_email_s:*' . $text . '*)'; //does not contain
                    break;

                default:
                    $this->queryArray[] = '+(lead_item_email_s:' . $text . ')'; //exact search
                    break;
            }
        }
        
    }
    
    //done
    protected function buildQueryForLeadItemStyle() {

        $queryString = array();
        if(!empty($this->post['bgs_filter_lead_item_style_s'])) {
            foreach($this->post['bgs_filter_lead_item_style_s'] as $key => $val) {
                if($val == "TOP+URGENT") $val="ALL";
                $queryString[] = '(lead_item_style_s:'.$val.')';
            }
            $this->queryArray[] = '('.implode(' OR ', $queryString).')';
        }
    }
    
    
    
    


    protected function buildQueryForDateSummarize($f, $t, $byDateOf) {
        $this->queryArray[] = '+(' . $byDateOf . ':[' . date("Y-m-d\TH:i:s\Z",$f) . ' TO ' . date("Y-m-d\TH:i:s\Z",$t) . '])';
    }


    protected function setColumns() {
        $postedColumns = $this->post['bgs_columns'];
        $this->fl = '';
        foreach ($postedColumns as $key => $val) {
            
            switch($val) {
                case "lead_item_type_s":
                        $this->columnsToShow['columns'][] = "Lead type";
                    break;
                
                case "lead_item_id_l":
                        $this->columnsToShow['columns'][] = "Lead id";
                    break;
                
                case "lead_item_date_tdt":
                        $this->columnsToShow['columns'][] = "Lead date";
                    break;
                
                case "lead_item_email_s":
                        $this->columnsToShow['columns'][] = "Lead Replier/Ad poster's email";
                    break;
                
                case "lead_item_mobile_s":
                        $this->columnsToShow['columns'][] = "Lead Replier/Ad poster's mobile";
                    break;
                
                case "lead_item_title_t":
                        $this->columnsToShow['columns'][] = "Lead title";
                    break;
                
                case "lead_item_desc_t":
                        $this->columnsToShow['columns'][] = "Lead desc";
                    break;
                
                case "lead_item_style_s":
                        $this->columnsToShow['columns'][] = "Lead style";
                    break;
                
                case "bgs_poster_email_s":
                        $this->columnsToShow['columns'][] = "Bgs poster email";
                    break;
                
                case "bgs_poster_id_l":
                        $this->columnsToShow['columns'][] = "Bgs poster id";
                    break;
                
                case "bgs_poster_mobile_s":
                        $this->columnsToShow['columns'][] = "Bgs poster mobile";
                    break;
                
                
                case "action_item_id_l":
                        $this->columnsToShow['columns'][] = "Bgs user's Ad/Alert id";
                    break;
                
                case "action_item_title_t":
                        $this->columnsToShow['columns'][] = "Bgs Ad Title";
                    break;
                
                case "action_item_desc_t":
                        $this->columnsToShow['columns'][] = "Bgs Ad Desc";
                    break;
                
                case "action_item_style_s":
                        $this->columnsToShow['columns'][] = "Bgs Ad style";
                    break;
                
                case "bgs_poster_mobile_s":
                        $this->columnsToShow['columns'][] = "Bgs poster mobile";
                    break;
                
                case "action_item_city_name_s":
                        $this->columnsToShow['columns'][] = "Bgs Ad/Alert City";
                    break;
                
                case "action_item_category_name_s":
                        $this->columnsToShow['columns'][] = "Bgs Ad/Alert Metacat";
                    break;
                
                case "action_item_subcategory_name_s":
                        $this->columnsToShow['columns'][] = "Bgs Ad/Alert Subcat";
                    break;
                
                case "action_item_pack_id_l":
                        $this->columnsToShow['columns'][] = "Bgs Ad/Alert Pack id";
                    break;
                
                case "action_item_pack_status_s":
                        $this->columnsToShow['columns'][] = "Bgs Ad/Alert Pack status";
                    break;
                
                case "action_item_ro_name_s":
                        $this->columnsToShow['columns'][] = "Bgs Ad/Alert RO name";
                    break;
                
                case "action_item_pack_order_id_s":
                        $this->columnsToShow['columns'][] = "Bgs Ad/Alert Order id";
                    break;
                
                case "action_item_pack_city_name_s":
                        $this->columnsToShow['columns'][] = "Bgs Ad/Alert Pack City";
                    break;
                
                
                
                
                default:
                    $this->columnsToShow['columns'][] = ucfirst(strtolower(str_replace('_', ' ', $val)));
                    break;
            }
            
            $this->fl .= $val . ","; 
        }
        $this->fl .= "score";
    }

   

    protected function ddmmyyyToTimestamp($date) {
        return strtotime($date);
    }
    
    protected function toUtc($date) {
        //echo date("Y-m-d\TH:i:s\Z",strtotime($date));exit;
        return date("Y-m-d\TH:i:s\Z",strtotime($date));
    }



    public function parseXmlDataForExcel() {

        try {
            $obj = new Utility_SolrQueryAnalyzer($this->finalUrl, __FILE__ . ' at line ' . __LINE__);
            $data = $obj->init();
        } catch (Exception $e) {
            trigger_error($e->getMessage());
        }


        if (!empty($data)) {
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

            foreach ($stories as $story) {

                $chunks = $this->prepareDataForExcel($story, $counter);
                $counter++;
                $str .= $chunks["str"] . "\n";
                $excel[$counter] = $chunks["arr"];
                unset($chunks);
            }

            $this->prepareDownLoadFile(array(
                "str" => trim($str),
                "arr" => $excel));
        }
    }

    protected function fillBgsArray($story) {
        //print_r($story);
        
        $bgs = array();
        $bgs['Id'] = $story->id;
        $bgs['Lead type'] = ($story->lead_item_type_s == '') ? 'NA' : $story->lead_item_type_s;
        $bgs['Lead id'] = ($story->lead_item_id_l == '') ? 'NA' : $story->lead_item_id_l;
        if (in_array('lead_item_date_tdt', $this->post['bgs_columns'])) {
            $UTC = new DateTimeZone("UTC");
            $date = new DateTime($story->lead_item_date_tdt, $UTC );   
            $bgs['Lead date'] = ($story->lead_item_date_tdt != 0) ? $date->format('Y-m-d H:i:s') : 'NA';
            unset($UTC);unset($date);
        }
        $bgs["Lead Replier/Ad poster's email"] = (!isset($story->lead_item_email_s) || $story->lead_item_email_s == '') ? 'NA' : $story->lead_item_email_s;
        $bgs["Lead Replier/Ad poster's mobile"] = (!isset($story->lead_item_mobile_s) || $story->lead_item_mobile_s == '') ? 'NA' : $story->lead_item_mobile_s;
        $bgs["Lead title"] = (!isset($story->lead_item_title_t) || $story->lead_item_title_t == '') ? 'NA' : $story->lead_item_title_t;
        $bgs["Lead desc"] = (!isset($story->lead_item_desc_t) || $story->lead_item_desc_t == '') ? 'NA' : $story->lead_item_desc_t;
        $bgs["Lead style"] = (!isset($story->lead_item_style_s) || $story->lead_item_style_s == '') ? 'NA' : $story->lead_item_style_s;
        
        
        $bgs['Bgs poster email'] = (!isset($story->bgs_poster_email_s) || $story->bgs_poster_email_s == '') ? 'NA' : $story->bgs_poster_email_s;
        $bgs['Bgs poster id'] = $story->bgs_poster_id_l;
        $bgs['Bgs poster mobile'] = (!isset($story->bgs_poster_mobile_s) || $story->bgs_poster_mobile_s == '') ? 'NA' : $story->bgs_poster_mobile_s;
        $bgs["Bgs user's Ad/Alert id"] = (!isset($story->action_item_id_l) || $story->action_item_id_l == '') ? 'NA' : $story->action_item_id_l;
        $bgs["Bgs Ad Title"] = (!isset($story->action_item_title_t) || $story->action_item_title_t == '') ? 'NA' : $story->action_item_title_t;
        $bgs["Bgs Ad Desc"] = (!isset($story->action_item_desc_t) || $story->action_item_desc_t == '') ? 'NA' : $story->action_item_desc_t;
        $bgs["Bgs Ad style"] = (!isset($story->action_item_style_s) || $story->action_item_style_s == '') ? 'NA' : $story->action_item_style_s;
        
        $bgs['Is star'] = (!isset($story->is_star) || $story->is_star == '') ? 'NA' : $story->is_star;
        $bgs['Is read'] = (!isset($story->is_read) || $story->is_read == '') ? 'NA' : $story->is_read;
        $bgs['Is called'] = (!isset($story->is_called) || $story->is_called == '') ? 'NA' : $story->is_called;
        $bgs['Is smsed'] = (!isset($story->is_smsed) || $story->is_smsed == '') ? 'NA' : $story->is_smsed;
        $bgs['Is replied'] = (!isset($story->is_replied) || $story->is_replied == '') ? 'NA' : $story->is_replied;
        $bgs['App version'] = (!isset($story->app_version) || $story->app_version == '') ? 'NA' : $story->app_version;
        
        $bgs['Bgs Ad/Alert City'] = (!isset($story->action_item_city_name_s) || $story->action_item_city_name_s == '') ? 'NA' : $story->action_item_city_name_s;
        $bgs['Bgs Ad/Alert Metacat'] = (!isset($story->action_item_category_name_s) || $story->action_item_category_name_s == '') ? 'NA' : $story->action_item_category_name_s;
        $bgs['Bgs Ad/Alert Subcat'] = (!isset($story->action_item_subcategory_name_s) || $story->action_item_subcategory_name_s == '') ? 'NA' : $story->action_item_subcategory_name_s;
        $bgs['Bgs Ad/Alert Pack id'] = (!isset($story->action_item_pack_id_l) || $story->action_item_pack_id_l == '') ? 'NA' : $story->action_item_pack_id_l;
        $bgs['Bgs Ad/Alert Pack status'] = (!isset($story->action_item_pack_status_s) || $story->action_item_pack_status_s == '') ? 'NA' : $story->action_item_pack_status_s;
        $bgs['Bgs Ad/Alert RO name'] = (!isset($story->action_item_ro_name_s) || $story->action_item_ro_name_s == '') ? 'NA' : $story->action_item_ro_name_s;
        $bgs['Bgs Ad/Alert Order id'] = (!isset($story->action_item_pack_order_id_s) || $story->action_item_pack_order_id_s == '') ? 'NA' : $story->action_item_pack_order_id_s;
        $bgs['Bgs Ad/Alert Pack City'] = (!isset($story->action_item_pack_city_name_s) || $story->action_item_pack_city_name_s == '') ? 'NA' : $story->action_item_pack_city_name_s;
        
       //print_r($bgs);exit;
        return $bgs;
    }

    protected function prepareHeadersForExcel() {
        $str = '';
        $excelHeaders = array();

        if (!empty($this->columnsToShow['columns'])) {
            $excelHeaders[] = "Sr. No.";
            //$excelHeaders[] = "Id";
            $str .= "\"Sr. No.\"" . $this->separator;
            foreach ($this->columnsToShow['columns'] as $key => $val) {
                $str .= '"' . $val . '"' . $this->separator;
                $excelHeaders[] = $val;
            }

            $str .= "\n";
        }
        return array("str" => $str, "arr" => $excelHeaders);
    }

    protected function prepareDataForExcel($story, $counter) {
        //print_r($story);
        $a = array();
        $str = "";

        $str .= '"' . $counter . '"' . $this->separator;
        $a[] = $counter;

        $s = explode(",","id,lead_item_type_s,lead_item_id_l,lead_item_date_tdt,lead_item_email_s,lead_item_mobile_s,lead_item_title_t,lead_item_desc_t,lead_item_style_s,bgs_poster_email_s,bgs_poster_id_l,bgs_poster_mobile_s,action_item_id_l,action_item_title_t,action_item_desc_t,action_item_style_s,is_star,is_read,is_called,is_smsed,is_replied,app_version,action_item_city_name_s,action_item_category_name_s,action_item_subcategory_name_s,action_item_pack_id_l,action_item_pack_status_s,action_item_ro_name_s,action_item_pack_order_id_s,action_item_pack_city_name_s");
      
        foreach($s as $k) {
            if (in_array($k, $this->post['bgs_columns'])) {
                
                if($k == "lead_item_date_tdt") {
                    $UTC = new DateTimeZone("UTC");
                    $date = new DateTime($story->$k, $UTC );   
                    $story->$k = ($story->$k != 0) ? $date->format('Y-m-d H:i:s') : 'NA';
                    unset($UTC);unset($date);
                }
                
                
                $str .= ((!isset($story->$k) || $story->$k == '') ? 'NA' : $story->$k) . $this->separator;
                $a[] = ((!isset($story->$k) || $story->$k == '') ? 'NA' : $story->$k);
            }
        }
        
        //print_r($a);exit;   

        
        return array("str" => $str, "arr" => $a);
    }

    protected function prepareDownLoadFile($dataArr) {
        $str = $dataArr["str"];
        $excel = $dataArr["arr"];


        $key = md5(serialize($this->post));
        $fileName = $this->sectionName . '_' . date('d-m-Y', strtotime('now')) . '_' . $key . '.csv';
        $filePath = BASE_PATH_CSV . '/' . $fileName;

        if (preg_match("/Windows/", $_SERVER["HTTP_USER_AGENT"])) {
            unset($str);
            //for windows

            $xls = new Quikr_ExcelXML('UTF-8', true, $fileName);
            $xls->addArray($excel);
            $contents = $xls->generateXML($fileName);

            $handle = fopen($filePath, 'w');
            fwrite($handle, $contents);
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
            $handle = fopen($filePath, 'w');
            fwrite($handle, $str);
            fclose($handle);

            //compress and create zip
            $filter = new Zend_Filter_Compress(array(
                        'adapter' => 'Zip',
                        'options' => array(
                            'archive' => $filePath . ".zip",
                        ),
                    ));

            $filter->filter($filePath);

            //send the zip file
            $csvFileLink = BASE_URL . '/assets/csv/' . $fileName . ".zip";
            header("Content-type: application/zip");
            header("Content-Disposition: attachment; filename=" . $fileName . ".zip");
            header("Pragma: no-cache");
            header("Expires: 0");
            readfile($filePath . ".zip");
            exit;
        }
    }
    
    public function getFieldsFromBgs($fieldToReturn,$id) {
            $solrVars = array(
                    'indent'    =>  $this->indent,
                    'version'   =>  $this->version,
                    'fq'        =>  $this->fq,
                    'start'     =>  $this->start,
                    'rows'      =>  1,
                    'fl'        =>  $fieldToReturn,
                    'wt'        =>  $this->wt,
                    'q'         => 'id:'.$id
            );

             $solrVarsStr = '';
             foreach($solrVars as $key => $val) {
                 $solrVarsStr .= $key.'='.trim($val).'&';
             }

             //this is final query
             $this->finalUrl =  rtrim($this->solrUrl.'select?'.$solrVarsStr,'&');
             //echo $this->finalUrl;
             //$data = file_get_contents($this->finalUrl);
             
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
    
    public function queryForIndexing($start,$rows) {
        $solrVars = array(
            'indent'    =>  $this->indent,
            'version'   =>  $this->version,
            'fq'        =>  $this->fq,
            'start'     =>  $start,
            'rows'      =>  $rows,
            'fl'        =>  "id,lead_type",
            'wt'        =>  $this->wt,
            'q'         => 'lead_type:Lead'
        );

        $solrVarsStr = '';
        foreach($solrVars as $key => $val) {
            $solrVarsStr .= $key.'='.trim($val).'&';
        }
        
         $url =  rtrim($this->solrUrl.'select?'.$solrVarsStr,'&');
         //echo "\n".$url;
        try {
                $obj = new Utility_SolrQueryAnalyzer($url,__FILE__.' at line '.__LINE__);
                $data = $obj->init();
            } catch (Exception $e) {
                trigger_error($e->getMessage());
            }
             
             
             if(!empty($data)) {
                $xmlData = json_decode($data);
               
                $stories = $xmlData->response->docs;
                $c = 0;
                foreach ($stories as $story) {
                    $tmp[$c]["id"]=$story->id;
                    //$tmp[$c]["lead_type"] = $story->lead_type;
                    $c++;
                }
                
                return $tmp;
                 
        }
        
        
        
    }
    
    
}