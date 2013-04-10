<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


class Model_ReplySolr {
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
    public $solrUrl         = SOLR_META_QUERY_REPLY_WITH_ADS;
    public $finalUrl        = '';
    public $queryArray      = array();

    public $columnsToShow = array();

    public $totalRecordsFound = '';
    public $records = '';
    
    public $separator = "|";
    public $sectionName = 'reply';

    public function  __construct($postedParams) {
        $this->post = $postedParams;
    }


    function getReplyCountForUser($userId) {
        $solrVars = array(
            'indent'    =>  $this->indent,
            'version'   =>  $this->version,
            'start'     =>  0,
            'rows'  => 0,
            'facet'     => 'true',
            'wt'        => $this->wt,
            'facet.limit' => 1,
            'facet.field' => 'rpl_user_id',
            'q'         => 'rpl_user_id:'.$userId,
            'facet.query' => 'rpl_user_id:'.$userId
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


    function getReplyCountForAd($adId) {
        $solrVars = array(
            'indent'    =>  $this->indent,
            'version'   =>  $this->version,
            'start'     =>  0,
            'rows'  => 0,
            'facet'     => 'true',
            'wt'        => $this->wt,
            'facet.limit' => 1,
            'facet.field' => 'ad_id',
            'q'         => 'ad_id:'.$adId,
            'facet.query' => 'ad_id:'.$adId
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
            $dataArray =  $xmlData->response->numFound;
            return $dataArray;
        }
    }



    function getSingleFieldFromReply($fieldToReturn='',$replyId) {
        $solrVars = array(
                'indent'    =>  $this->indent,
                'version'   =>  $this->version,
                'fq'        =>  $this->fq,
                'start'     =>  $this->start,
                'rows'      =>  1,
                'wt'        =>  $this->wt,
                'fl'        =>  $fieldToReturn,
                //'qt'        =>  $this->qt,
                'explainOther'=> $this->explainOther,
                'hl.fl'     =>  $this->hl_fl,
                'q'         => 'id:'.$replyId
        );

         $solrVarsStr = '';
         foreach($solrVars as $key => $val) {
             $solrVarsStr .= $key.'='.trim($val).'&';
         }

         //this is final query
         $this->finalUrl =  rtrim($this->solrUrl.'select?'.$solrVarsStr,'&');

         //$data = file_get_contents($this->finalUrl);
         //echo $this->finalUrl;
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
                //print_r($xmlData);
                return $xmlData;
            } 

    }
    }



    protected function buildQueryForDateSummarize($f,$t,$byDateOf) {
        $this->queryArray[] = '+('.$byDateOf.':['.$f.' TO '.$t.'])';
    }

    public function getfacetCountForSummarize($f,$t,$byDateOf,$facetField='') {

        //$this->setColumns();

        //now build query for every field

        $this->buildQueryForReplierEmail();
        
        if($byDateOf == 'rpl_createdTime') $this->buildQueryForDateSummarize($f,$t,$byDateOf);
        else $this->buildQueryForReplierDate();
        
        $this->buildQueryForReplierContent();
        $this->buildQueryForReplierMobile();

        $this->buildQueryForAdId();
        $this->buildQueryForAdTitle();

        $this->buildQueryForAdDeleteReason();
        $this->buildQueryForPosterEmail();
        $this->buildQueryForPosterMobile();
        $this->buildQueryForCreatedDate();
        $this->buildQueryForUpdatedDate();
        $this->buildQueryForDeletedDate();


        $this->buildQueryForFreePremium();
        $this->buildQueryForPremiumAdType();
        $this->buildQueryForNoOfImages();
        $this->buildQueryForNoOfVisitors();
        $this->buildQueryForPrice();
        $this->buildQueryForReplyUserAgent();
        $this->buildQueryForAdType();
        $this->buildQueryForAdStatus();
        $this->buildQueryForFlagReason();
        $this->buildQueryForRegularNoClickAd();
        $this->buildQueryForPriceType();
        $this->buildQueryForCity();
        $this->buildQueryForLocality();
        $this->buildQueryForMetacategory();
        $this->buildQueryForSubcategory();
        $this->buildQueryForBrandName();
        $this->buildQueryForNoOfRooms();
        $this->buildQueryForTypeOfLand();
        $this->buildQueryForTypeOfJob();
        $this->buildQueryForCondition();
        $this->buildQueryForYouAre();
        $this->buildQueryForYear();
        
		//echo trim(implode('+', $this->queryArray),'+');exit;
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
                'facet'		=> 'true',
                'wt'        => $this->wt,
                'facet.field' => $this->fl,
                'q'         => trim($this->queryString,'+'),
                'facet.query' => trim($this->queryString,'+'),
                
        );
        
        if($facetField) {
            $solrVars['facet.field'] = $facetField;
            $solrVars['facet.limit'] = '-1';
            $solrVars['facet.mincount'] = '1';
            $solrVars['facet.sort'] = 'count';
            
        }
        
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
            //print_r($xmlData);
            if($facetField) {
                 /**
                 * Basically for replier count we are running equivalent of the following SQL query in SOlr:
                 * 
                 * mysql> SELECT COUNT(distinct(rpl_usr_id)) 
                        -> FROM babel_myquikrreply 
                        -> WHERE rpl_created BETWEEN 1326220200 AND 1326306600
                 */
                
                $d = $xmlData->facet_counts->facet_fields->{$facetField};
                $s = count($d) /2;
                return $s; 
            }
            
            $dataArray =   $xmlData->response->numFound;
            return $dataArray;
            
            
         }
    }
    
    
    public function getResults($forExcel=false) {
        //query solr
        //first set the columns to show
        $this->setColumns();

        //now build query for every field

        $this->buildQueryForReplierEmail();            
        $this->buildQueryForReplierDate();
        $this->buildQueryForReplierContent();
        $this->buildQueryForReplierMobile();
        
        $this->buildQueryForAdId();
        $this->buildQueryForAdTitle();
        
        $this->buildQueryForAdDeleteReason();
        $this->buildQueryForPosterEmail();
        $this->buildQueryForPosterMobile();
        $this->buildQueryForCreatedDate();
        $this->buildQueryForUpdatedDate();
        $this->buildQueryForDeletedDate();

        
        $this->buildQueryForFreePremium();
        $this->buildQueryForPremiumAdType();
        $this->buildQueryForNoOfImages();
        $this->buildQueryForNoOfVisitors();
        $this->buildQueryForPrice();
        $this->buildQueryForReplyUserAgent();
        $this->buildQueryForAdType();
        $this->buildQueryForAdStatus();
        $this->buildQueryForFlagReason();
        $this->buildQueryForRegularNoClickAd();
        $this->buildQueryForPriceType();
        $this->buildQueryForCity();
        $this->buildQueryForLocality();
        $this->buildQueryForMetacategory();
        $this->buildQueryForSubcategory();
        $this->buildQueryForBrandName();
        $this->buildQueryForNoOfRooms();
        $this->buildQueryForTypeOfLand();
        $this->buildQueryForTypeOfJob();
        $this->buildQueryForCondition();
        $this->buildQueryForYouAre();
        $this->buildQueryForYear();


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


    }
    
    
    public function parseXmlData() {//echo $this->finalUrl;
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
                $this->columnsToShow['data'][] = $this->fillReplyArray($story);
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
         //echo $this->finalUrl;exit;
    }

    protected function setColumns() {
        $postedColumns = $this->post['reply_columns'];
        $this->fl = 'id,';
        foreach($postedColumns as $key => $val) {

            //few exception where we need to change the caption
            //if($val == 'id') continue;
            if($val == 'reply_email') $this->fl .= 'rpl_email,';
            if($val == 'reply_date') $this->fl .= 'rpl_createdTime,';
            if($val == 'reply_content') $this->fl .= 'rpl_content,';
            if($val == 'reply_mobile') $this->fl .= 'rpl_mobile,';
            if($val == 'reply_poster_id') $this->fl .= 'rpl_post_usr_id,';
            if($val == 'reply_user_agent') $this->fl .= 'user_agent_flag_t,';
            
            if($val == 'metacategory_name') {$val = 'category'; $this->fl .= 'metacategory_name,';}
            if($val == 'subcategory_name') {$val = 'subcategory'; $this->fl .= 'subcategory_name,';}
            if($val == 'Ad_locality') {$this->fl .= 'localities,';}
            
            
              /**
             * for attributes
             */
            if($val == 'Condition') {$this->fl .= 'attr_condition,';}
            if($val == 'individual_dealer') {$this->fl .= 'attr_you_are,';}
            if($val == 'brand_name') {$this->fl .= 'attr_brand_name,';}
            if($val == 'no_of_bedrooms') {$this->fl .= 'attr_no_of_rooms,';}
            if($val == 'type_of_land') {$this->fl .= 'attr_type_of_land,';}
            if($val == 'Ad_year_of_make') {$this->fl .= 'attr_year,';}
            if($val == 'type_of_job') {$this->fl .= 'attr_type_of_job,';}
            
            
            $this->columnsToShow['columns'][] = ucwords(strtolower(str_replace('_', ' ', $val)));
            $this->fl .= $val.",";
        }
        $this->fl .= "attributes,score";
        //return $this->columnsToShow;
    }


    protected function buildQueryForReplierEmail() {
        if(trim($this->post['reply_filter_email_select_ece']) != 'none' &&
              trim($this->post['reply_filter_email_text']) != '') {
            $operator = trim($this->post['reply_filter_email_select_ece']);
            $text = trim($this->post['reply_filter_email_text']);


            switch($operator) {
                case 'equals':
                $this->queryArray[] = '+(rpl_email:'.$text.')'; //exact search
                break;

                case 'contains':
                $this->queryArray[] = '+(rpl_email:\*'.$text.'\*)'; //anywhere in between the text
                break;

                case 'excludes':
                $this->queryArray[] =  '-(rpl_email:\*'.$text.'\*)'; //does not contain
                break;

                default:
                $this->queryArray[] =  '+(rpl_email:'.$text.')'; //exact search
                break;
            }
        }
    }

    protected function buildQueryForReplierDate() {
        if(!empty($this->post['reply_filter_date_from']) &&
                !empty($this->post['reply_filter_date_to'])) {
            $from = $this->ddmmyyyToTimestamp($this->post['reply_filter_date_from']);
            $to = $this->ddmmyyyToTimestamp($this->post['reply_filter_date_to'])+TO_DATE_INCREMENT;
            $this->queryArray[] = '+(rpl_createdTime:['.$from.' TO '.$to.'])';
        }

    }


    protected function buildQueryForReplierContent() {
        if(trim($this->post['reply_filter_reply_content_ece']) != 'none' &&
              trim($this->post['reply_filter_reply_content_text']) != '') {
            $operator = trim($this->post['reply_filter_reply_content_ece']);
            $text = trim($this->post['reply_filter_reply_content_text']);


            switch($operator) {
                case 'equals':
                $this->queryArray[] = '+(rpl_content:'.$text.')'; //exact search
                break;

                case 'contains':
                $this->queryArray[] = '+(rpl_content:\*'.$text.'\*)'; //anywhere in between the text
                break;

                case 'excludes':
                $this->queryArray[] =  '-(rpl_content:\*'.$text.'\*)'; //does not contain
                break;

                default:
                $this->queryArray[] =  '+(rpl_content:'.$text.')'; //exact search
                break;
            }
        }
    }


    protected function buildQueryForReplierMobile() {
        $queryString = '';
        if(!empty($this->post['reply_filter_repliermobile'])) {
            if($this->post['reply_filter_repliermobile'] == 'present') {
                $this->queryArray[] = '+(rpl_mobile:[* TO *])';
            } else if($this->post['reply_filter_repliermobile'] == 'not_present') {
                $this->queryArray[] = '-(rpl_mobile:[* TO *])';
            }
        }
    }


    protected function buildQueryForAdId() {
        $queryString = '';
        if(!empty($this->post['reply_filter_ad_id'])) {
            $this->queryArray[] = '+(ad_id:'.trim($this->post['reply_filter_ad_id']).')';
        }
    }

    protected function buildQueryForAdTitle() {
        if(trim($this->post['reply_filter_ad_title_select_ece']) != 'none' &&
              trim($this->post['reply_filter_ad_title_text']) != '') {
            $operator = trim($this->post['reply_filter_ad_title_select_ece']);
            $text = trim($this->post['reply_filter_ad_title_text']);


            switch($operator) {
                case 'equals':
                $this->queryArray[] = '+(ad_title:'.$text.')'; //exact search
                break;

                case 'contains':
                $this->queryArray[] = '+(ad_title:\*'.$text.'\*)'; //anywhere in between the text
                break;

                case 'excludes':
                $this->queryArray[] =  '-(ad_title:\*'.$text.'\*)'; //does not contain
                break;

                default:
                $this->queryArray[] =  '+(ad_title:'.$text.')'; //exact search
                break;
            }
        }
    }


    protected function buildQueryForAdDesc() {
        if(trim($this->post['reply_filter_ad_description_select_ece']) != 'none' &&
              trim($this->post['reply_filter_ad_description_text']) != '') {
            $operator = trim($this->post['reply_filter_ad_description_select_ece']);
            $text = trim($this->post['reply_filter_ad_description_text']);


            switch($operator) {
                case 'equals':
                $this->queryArray[] = '+(ad_description:'.$text.')'; //exact search
                break;

                case 'contains':
                $this->queryArray[] = '+(ad_description:\*'.$text.'\*)'; //anywhere in between the text
                break;

                case 'excludes':
                $this->queryArray[] =  '-(ad_description:\*'.$text.'\*)'; //does not contain
                break;

                default:
                $this->queryArray[] =  '+(ad_description:'.$text.')'; //exact search
                break;
            }
        }
    }


    protected function buildQueryForPosterEmail() {
        if(trim($this->post['reply_filter_poster_email']) != 'none' &&
              trim($this->post['reply_filter_poster_email_text']) != '') {
            $operator = trim($this->post['reply_filter_poster_email']);
            $text = trim($this->post['reply_filter_poster_email_text']);


            switch($operator) {
                case 'equals':
                $this->queryArray[] = '+(poster_email:'.$text.')'; //exact search
                break;

                case 'contains':
                $this->queryArray[] = '+(poster_email:\*'.$text.'\*)'; //anywhere in between the text
                break;

                case 'excludes':
                $this->queryArray[] =  '-(poster_email:\*'.$text.'\*)'; //does not contain
                break;

                default:
                $this->queryArray[] =  '+(poster_email:'.$text.')'; //exact search
                break;
            }
        }
    }


    protected function buildQueryForAdDeleteReason() {
        if(trim($this->post['reply_filter_addelete_reason_ece']) != 'none' &&
              trim($this->post['reply_filter_addelete_reason_text']) != '') {
            $operator = trim($this->post['reply_filter_addelete_reason_ece']);
            $text = trim($this->post['reply_filter_addelete_reason_text']);


            switch($operator) {
                case 'equals':
                $this->queryArray[] = '+(ad_delete_reason:'.$text.')'; //exact search
                break;

                case 'contains':
                $this->queryArray[] = '+(ad_delete_reason:\*'.$text.'\*)'; //anywhere in between the text
                break;

                case 'excludes':
                $this->queryArray[] =  '-(ad_delete_reason:\*'.$text.'\*)'; //does not contain
                break;

                default:
                $this->queryArray[] =  '+(ad_delete_reason:'.$text.')'; //exact search
                break;
            }
        }
    }


    protected function buildQueryForCreatedDate() {
        if(!empty($this->post['reply_filter_createdate_from']) &&
                !empty($this->post['reply_filter_createdate_to'])) {
            $from = $this->ddmmyyyToTimestamp($this->post['reply_filter_createdate_from']);
            $to = $this->ddmmyyyToTimestamp($this->post['reply_filter_createdate_to'])+TO_DATE_INCREMENT;
            $this->queryArray[] = '+(ad_created_date:['.$from.' TO '.$to.'])';
        }
    }

    protected function buildQueryForUpdatedDate() {
        if(!empty($this->post['reply_filter_adlastupdate_from']) &&
                !empty($this->post['reply_filter_adlastupdate_to'])) {
            $from = $this->ddmmyyyToTimestamp($this->post['reply_filter_adlastupdate_from']);
            $to = $this->ddmmyyyToTimestamp($this->post['reply_filter_adlastupdate_to'])+TO_DATE_INCREMENT;
            $this->queryArray[] = '+(ad_modified_date:['.$from.' TO '.$to.'])';
        }
    }


    protected function buildQueryForDeletedDate() {
        if(!empty($this->post['reply_filter_addeletedate_from']) &&
                !empty($this->post['reply_filter_addeletedate_to'])) {
            $from = $this->ddmmyyyToTimestamp($this->post['reply_filter_addeletedate_from']);
            $to = $this->ddmmyyyToTimestamp($this->post['reply_filter_addeletedate_to'])+TO_DATE_INCREMENT;
            $this->queryArray[] = '+(ad_delete_date:['.$from.' TO '.$to.'])';
        }
    }

    protected function buildQueryForExpiredDate() {
        if(!empty($this->post['reply_filter_expiretime_from']) &&
                !empty($this->post['reply_filter_expiretime_to'])) {
            $from = $this->ddmmyyyToTimestamp($this->post['reply_filter_expiretime_from']);
            $to = $this->ddmmyyyToTimestamp($this->post['reply_filter_expiretime_to'])+TO_DATE_INCREMENT;
            $this->queryArray[] = '+(expired_time:['.$from.' TO '.$to.'])';
        }
    }

    protected function buildQueryForRepostedDate() {
        if(!empty($this->post['reply_filter_reposttime_from']) &&
                !empty($this->post['reply_filter_reposttime_to'])) {
            $from = $this->ddmmyyyToTimestamp($this->post['reply_filter_reposttime_from']);
            $to = $this->ddmmyyyToTimestamp($this->post['reply_filter_reposttime_to'])+TO_DATE_INCREMENT;
            $this->queryArray[] = '+(repost_time:['.$from.' TO '.$to.'])';
        }
    }


    protected function buildQueryForIsReposted() {
        $queryString = '';
        if(!empty($this->post['reply_filter_reposted'])) {
            if($this->post['reply_filter_reposted'] == 'yes') {
                $this->queryArray[] = '+(reposted:*)'; //everything except blank which

            } else if($this->post['reply_filter_reposted'] == 'no') {
                $this->queryArray[] = '-(reposted:*)';
            }
        }

    }


    protected function buildQueryForPosterMobile() {
        $queryString = '';
        if(!empty($this->post['reply_filter_postermobile'])) {
            if($this->post['reply_filter_postermobile'] == 'present') {
                $this->queryArray[] = '+(poster_mobile:[* TO *])';
            } else if($this->post['reply_filter_postermobile'] == 'not_present') {
                $this->queryArray[] = '-(poster_mobile:[* TO *])';
            }
        }
    }


    protected function buildQueryForFreePremium() {
        $queryString = '';
        if(!empty($this->post['reply_filter_free_premium'])) {

            if(count($this->post['reply_filter_free_premium']) == 1) {
                if($this->post['reply_filter_free_premium'][0] == 'free') {
                    $this->queryArray[] = '+(free_premium_type:Free)';

                } else if($this->post['reply_filter_free_premium'][0] == 'premium') {
                    $this->queryArray[] = '+(free_premium_type:Premium)';
                }

            } else if(count($this->post['reply_filter_free_premium']) == 2) {
                $this->queryArray[] = '+((free_premium_type:Free) OR (free_premium_type:Premium))';
            }
        }
    }


    protected function buildQueryForPremiumAdType() {
        $queryString = '';
        if(!empty($this->post['reply_filter_premium_ad_type'])) {

            if(count($this->post['reply_filter_premium_ad_type']) == 1) {
                if($this->post['reply_filter_premium_ad_type'][0] == 'top_of_page') {
                    $this->queryArray[] = '+(premium_ad_type:T)';

                } else if($this->post['reply_filter_premium_ad_type'][0] == 'urgent') {
                    $this->queryArray[] = '+(premium_ad_type:H)';
                }

            } else if(count($this->post['reply_filter_premium_ad_type']) == 2) {
                $this->queryArray[] = '+(premium_ad_type:HT)';
            }
        }
    }

    protected function buildQueryForReplyUserAgent() {
        $queryString = '';
        if(!empty($this->post['reply_filter_reply_useragent'])) {

            if(count($this->post['reply_filter_reply_useragent']) == 1) {
                if($this->post['reply_filter_reply_useragent'][0] == 'web') {
                    $this->queryArray[] = '+(user_agent_flag_t:Web)';

                } else if($this->post['reply_filter_reply_useragent'][0] == 'mobile') {
                    $this->queryArray[] = '+(user_agent_flag_t:Mobile)';
                }

            } else if(count($this->post['reply_filter_reply_useragent']) == 2) {
                $this->queryArray[] = '+((user_agent_flag_t:Web) OR (user_agent_flag_t:Mobile))';
            }
        }
    }


    protected function buildQueryForAdType() {

        $queryString = '';
        if(!empty($this->post['reply_filter_ad_type'])) {

            if(count($this->post['reply_filter_ad_type']) == 1) {
                if($this->post['reply_filter_ad_type'][0] == 'offering') {
                    $this->queryArray[] = '+(ad_type:Offer)';

                } else if($this->post['reply_filter_ad_type'][0] == 'want') {
                    $this->queryArray[] = '+(ad_type:Want)';
                }

            } else if(count($this->post['reply_filter_ad_type']) == 2) {
                $this->queryArray[] = '+((ad_type:Offer) OR (ad_type:Want))';
            }
        }
    }


    protected function buildQueryForAdStatus() {

        $queryString = array();
        if(!empty($this->post['reply_filter_status'])) {
            foreach($this->post['reply_filter_status'] as $key => $val) {
                switch($val) {
                    case 'active':
                        $queryString[] = '(ad_status:Active)';
                        break;
                    case 'expire':
                        $queryString[] = '(ad_status:Expired)';
                        break;
                    case 'user_deleted':
                        $queryString[] = '(ad_status:User deleted)';
                        break;
                    case 'admin_delete':
                        $queryString[] = '(ad_status:Admin deleted)';
                        break;
                    case 'flag_and_delay':
                        $queryString[] = '(ad_status:Flag and Delay)';
                        break;
                    case 'payment_pending':
                        $queryString[] = '(ad_status:Pending)';
                        break;
                }

                
            }
            $this->queryArray[] = '+('.implode(' OR ', $queryString).')';
        }
    }



    protected function buildQueryForFlagReason() {

        $queryString = array();
        if(!empty($this->post['reply_filter_flag_reason'])) {
            foreach($this->post['reply_filter_flag_reason'] as $key => $val) {
                switch($val) {
                    case 'banned_word':
                        $queryString[] = '(flag_reason:Banned word)';
                        break;
                    case 'paid_ad':
                        $queryString[] = '(flag_reason:Paid Ad)';
                        break;
                    case 'payment_pending':
                        $queryString[] = '(flag_reason:Payment pending)';
                        break;
                    case 'duplicated':
                        $queryString[] = '(flag_reason:Duplicate Ad)';
                        break;
                } 
            }
            $this->queryArray[] = '+('.implode(' OR ', $queryString).')';
        }
    }

    protected function buildQueryForRegularNoClickAd() {

        $queryString = array();
        if(!empty($this->post['reply_filter_regular'])) {
            foreach($this->post['reply_filter_regular'] as $key => $val) {
                switch($val) {
                    case 'regular':
                        $queryString[] = '(regular_noclick:Regular)';
                        break;
                    case 'no-click':
                        $queryString[] = '(regular_noclick:No-Click)';
                        break;
                }
            }
            $this->queryArray[] = '+('.implode(' OR ', $queryString).')';
        }
    }


    protected function buildQueryForNoOfImages() {
        if(trim($this->post['reply_filter_no_of_images_range']) != '' &&
              trim($this->post['reply_filter_no_of_images_text']) != '') {

            $qty = trim($this->post['reply_filter_no_of_images_text']);

            $obj = new Zend_Validate_Int();
            $st = $obj->isValid($qty);

            if($st && $qty >= 0) {
                switch($this->post['reply_filter_no_of_images_range']) {
                    case 'less':
                        $this->queryArray[] = '+(no_of_images:[* TO '.($qty - 1).'])';
                        break;
                    case 'less_equal':
                        $this->queryArray[] = '+(no_of_images:[* TO '.$qty.'])';
                        break;
                    case 'greater':
                        $this->queryArray[] = '+(no_of_images:['.($qty + 1).' TO *])';
                        break;
                    case 'greater_equal':
                        $this->queryArray[] = '+(no_of_images:['.$qty.' TO *])';
                        break;
                    case 'equal':
                        $this->queryArray[] = '+(no_of_images:'.$qty.')';
                        break;
                    case 'not_equal':
                        $this->queryArray[] = '-(no_of_images:'.$qty.')';
                        break;
                }
            }
        }
    }


    protected function buildQueryForNoOfVisitors() {
        if(trim($this->post['reply_filter_no_of_visitors_range']) != '' &&
              trim($this->post['reply_filter_no_of_visitors_text']) != '') {

            $qty = trim($this->post['reply_filter_no_of_visitors_text']);

            $obj = new Zend_Validate_Int();
            $st = $obj->isValid($qty);

            if($st && $qty >= 0) {
                switch($this->post['reply_filter_no_of_visitors_range']) {
                    case 'less':
                        $this->queryArray[] = '+(no_of_visitors:[* TO '.($qty - 1).'])';
                        break;
                    case 'less_equal':
                        $this->queryArray[] = '+(no_of_visitors:[* TO '.$qty.'])';
                        break;
                    case 'greater':
                        $this->queryArray[] = '+(no_of_visitors:['.($qty + 1).' TO *])';
                        break;
                    case 'greater_equal':
                        $this->queryArray[] = '+(no_of_visitors:['.$qty.' TO *])';
                        break;
                    case 'equal':
                        $this->queryArray[] = '+(no_of_visitors:'.$qty.')';
                        break;
                    case 'not_equal':
                        $this->queryArray[] = '-(no_of_visitors:'.$qty.')';
                        break;
                }
            }
        }
    }

    protected function buildQueryForNoOfReply() {
        if(trim($this->post['reply_filter_no_of_replies_range']) != '' &&
              trim($this->post['reply_filter_no_of_replies_text']) != '') {

            $qty = trim($this->post['reply_filter_no_of_replies_text']);

            $obj = new Zend_Validate_Int();
            $st = $obj->isValid($qty);

            if($st && $qty >= 0) {
                switch($this->post['reply_filter_no_of_replies_range']) {
                    case 'less':
                        $this->queryArray[] = '+(no_of_replies:[* TO '.($qty - 1).'])';
                        break;
                    case 'less_equal':
                        $this->queryArray[] = '+(no_of_replies:[* TO '.$qty.'])';
                        break;
                    case 'greater':
                        $this->queryArray[] = '+(no_of_replies:['.($qty + 1).' TO *])';
                        break;
                    case 'greater_equal':
                        $this->queryArray[] = '+(no_of_replies:['.$qty.' TO *])';
                        break;
                    case 'equal':
                        $this->queryArray[] = '+(no_of_replies:'.$qty.')';
                        break;
                    case 'not_equal':
                        $this->queryArray[] = '-(no_of_replies:'.$qty.')';
                        break;
                }
            }
        }
    }


    protected function buildQueryForPrice() {
        if(trim($this->post['reply_filter_price_range']) != '' &&
              trim($this->post['reply_filter_price_text']) != '') {

            $qty = trim($this->post['reply_filter_price_text']);

            $obj = new Zend_Validate_Int();
            $st = $obj->isValid($qty);

            if($st && $qty >= 0) {
                switch($this->post['reply_filter_price_range']) {
                    case 'less':
                        $this->queryArray[] = '+(price_value:[* TO '.($qty - 1).'])';
                        break;
                    case 'less_equal':
                        $this->queryArray[] = '+(price_value:[* TO '.$qty.'])';
                        break;
                    case 'greater':
                        $this->queryArray[] = '+(price_value:['.($qty + 1).' TO *])';
                        break;
                    case 'greater_equal':
                        $this->queryArray[] = '+(price_value:['.$qty.' TO *])';
                        break;
                    case 'equal':
                        $this->queryArray[] = '+(price_value:'.$qty.')';
                        break;
                    case 'not_equal':
                        $this->queryArray[] = '-(price_value:'.$qty.')';
                        break;
                }
            }
        }
    }

    protected function buildQueryForCity() {
        if(!empty($this->post['reply_filter_city'])) {
            if($this->post['reply_filter_city'] == 'all') {
                $this->queryArray[] = '+(city_id:*)';
            } else {
                $this->queryArray[] = '+(city_id:'.trim($this->post['reply_filter_city']).')';
            }
        }
    }

    protected function buildQueryForLocality() {
        if(!empty($this->post['reply_filter_localities'])) {
            if($this->post['reply_filter_localities'] == '0') {
                $this->queryArray[] = '+(localities:*)';
            } else {
                $this->queryArray[] = '+(localities:'.trim($this->post['reply_filter_localities']).')';
            }

        }
    }

    protected function buildQueryForMetacategory() {
        if(!empty($this->post['reply_filter_metacat'])) {
            if($this->post['reply_filter_metacat'] == 'all') {
                    //if doing a global search based on city
                if($this->post['reply_filter_city'] == 'all') {
                    $this->queryArray[] = '+(global_metacategory_id:[* TO *])';
                } else { //doing city specific search
                    $this->queryArray[] = '+(metacategory_id:[* TO *])';
                }
            } else {
                //if doing a global search based on city
                if($this->post['reply_filter_city'] == 'all') {
                    $this->queryArray[] = '+(global_metacategory_id:'.trim($this->post['reply_filter_metacat']).')';
                } else {
                        //doing city specific search
                    $this->queryArray[] = '+(metacategory_id:'.trim($this->post['reply_filter_metacat']).')';
                }
            }
        }
    }

    protected function buildQueryForSubcategory() {
         if(!empty($this->post['reply_filter_subcat'])) {
            if($this->post['reply_filter_subcat'] == 'all') {
                //if doing a global search based on city
                if($this->post['reply_filter_city'] == 'all') {
                    $this->queryArray[] = '+(global_subcategory_id:[* TO *])';
                } else {
                        //doing city specific search
                    $this->queryArray[] = '+(subcategory_id:[* TO *])';
                }

            } else {
                //if doing a global search based on city
                if($this->post['reply_filter_city'] == 'all') {
                    $this->queryArray[] = '+(global_subcategory_id:'.trim($this->post['reply_filter_subcat']).')';
                } else {
                        //doing city specific search
                    $this->queryArray[] = '+(subcategory_id:'.trim($this->post['reply_filter_subcat']).')';
                }
            }
        }
    }


    protected function buildQueryForPriceType() {

        $queryString = array();
        if(!empty($this->post['reply_filter_price_type'])) {
            foreach($this->post['reply_filter_price_type'] as $key => $val) {
                $queryString[] = '+(price_type:'.$val.')';
            }
            $this->queryArray[] = '('.implode(' OR ', $queryString).')';
        }
    }

    protected function buildQueryForBrandName() {

        $queryString = array();
        if(!empty($this->post['Brand_name'])) {
            foreach($this->post['Brand_name'] as $key => $val) {
               $queryString[] = '+(attr_brand_name:"'.$val.'")';
            }
            $this->queryArray[] = '('.implode(' OR ', $queryString).')';
        }
    }


    protected function buildQueryForNoOfRooms() {

        $queryString = array();
        if(!empty($this->post['No_of_Rooms'])) {
            foreach($this->post['No_of_Rooms'] as $key => $val) {
                $queryString[] = '+(attr_no_of_rooms:"'.$val.'")';
            }
            $this->queryArray[] = '('.implode(' OR ', $queryString).')';
        }
    }


    protected function buildQueryForTypeOfLand() {

        $queryString = array();
        if(!empty($this->post['Type_of_land'])) {
            foreach($this->post['Type_of_land'] as $key => $val) {
                $queryString[] = '+(attr_type_of_land:"'.$val.'")';
            }
            $this->queryArray[] = '('.implode(' OR ', $queryString).')';
        }
    }


    protected function buildQueryForTypeOfJob() {

        $queryString = array();
        if(!empty($this->post['Type_of_Job'])) {
            foreach($this->post['Type_of_Job'] as $key => $val) {
                $queryString[] = '+(attr_type_of_job:"'.$val.'")';
            }
            $this->queryArray[] = '('.implode(' OR ', $queryString).')';
        }
    }

    protected function buildQueryForCondition() {

        $queryString = array();
        if(!empty($this->post['Condition'])) {
            foreach($this->post['Condition'] as $key => $val) {
                $queryString[] = '+(attr_condition:"'.$val.'")';
            }
            $this->queryArray[] = '('.implode(' OR ', $queryString).')';
        }
    }


    protected function buildQueryForYouAre() {

        $queryString = array();
        if(!empty($this->post['You_are'])) {
            foreach($this->post['You_are'] as $key => $val) {
                $queryString[] = '+(attr_you_are:"'.$val.'")';
            }
            $this->queryArray[] = '('.implode(' OR ', $queryString).')';
        }
    }


    protected function buildQueryForYear() {

        $queryString = array();
        if(!empty($this->post['Year'])) {
            foreach($this->post['Year'] as $key => $val) {
                $queryString[] = '+(attr_year:"'.$val.'")';
            }
            $this->queryArray[] = '('.implode(' OR ', $queryString).')';
        }
    }

    protected function ddmmyyyToTimestamp($date) {
        return strtotime($date);
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
    
    protected function fillReplyArray($story) {
        $reply = array();
        $reply['Id'] = $story->id;
        $reply['Reply Email'] = $story->rpl_email;
        $reply['Reply Date'] = ($story->rpl_createdTime != 0) ? date('d-m-Y',$story->rpl_createdTime) : 'NA';
        $reply['Reply Content'] = utf8_decode(($story->rpl_content == '') ? 'NA': $story->rpl_content);
        $reply['Reply Mobile'] = $story->rpl_mobile;
        $reply['Reply User Agent'] = $story->user_agent_flag_t;
        $reply['Ad Id'] = $story->ad_id;
        $reply['Ad Title'] = utf8_decode(($story->ad_title == '') ? 'NA': $story->ad_title);
        $reply['Ad Description'] = utf8_decode(($story->ad_description == '') ? 'NA': $story->ad_description);
        $reply['Poster Id'] = ($story->poster_id == '') ? 'NA': $story->poster_id;
        $reply['Poster Email'] = ($story->poster_email == '') ? 'NA': $story->poster_email;
        $reply['Poster Mobile'] = ($story->poster_mobile == '') ? 'NA': $story->poster_mobile;
        $reply['Ad Created Date'] = ($story->ad_created_date != 0) ? date('d-m-Y',$story->ad_created_date) : 'NA';
        $reply['Ad Modified Date'] = ($story->ad_modified_date != 0) ? date('d-m-Y',$story->ad_modified_date) : 'NA';
        $reply['Free Premium Type'] = ($story->free_premium_type == '') ? 'NA': $story->free_premium_type;
        $reply['Premium Ad Type'] = ($story->premium_ad_type == '') ? 'NA': $story->premium_ad_type;
        $reply['Regular Noclick'] = ($story->regular_noclick == '') ? 'NA': $story->regular_noclick;
        $reply['No Of Images'] = ($story->no_of_images == '') ? 'NA': $story->no_of_images;
        $reply['No Of Visitors'] = ($story->no_of_visitors == '') ? 'NA': $story->no_of_visitors;
        $reply['No Of Replies'] = ($story->no_of_replies == '') ? 'NA': $story->no_of_replies;
        $reply['Price Type'] = ($story->price_type == '') ? 'NA': $story->price_type;
        $reply['Price Value'] = ($story->price_value == '') ? 'NA': $story->price_value;
        $reply['Ad Type'] = ($story->ad_type == '') ? 'NA': $story->ad_type;
        $reply['Ad Status'] = ($story->ad_status == '') ? 'NA': $story->ad_status;
        $reply['Ad Delete Date'] = ($story->ad_delete_date != 0) ? date('d-m-Y',$story->ad_delete_date) : 'NA';
        $reply['Ad Delete Reason'] = ($story->ad_delete_reason == '') ? 'NA': $story->ad_delete_reason;
        $reply['Expired Time'] = ($story->expired_time != 0) ? date('d-m-Y',$story->expired_time) : 'NA';
        $reply['Reposted'] = ($story->reposted == '') ? 'NA': $story->reposted;
        $reply['Repost Time'] = ($story->repost_time != 0) ? date('d-m-Y',$story->repost_time) : 'NA';
        $reply['Flag Reason'] = ($story->flag_reason == '') ? 'NA': $story->flag_reason;
        $reply['User Agent'] = ($story->user_agent != '') ? $story->user_agent : 'NA';
        $reply['City Name'] = $story->city_name;
        $reply['Ad Locality'] = $story->localities;
        $reply['Category'] = $story->metacategory_name;
        $reply['Subcategory'] = $story->subcategory_name;
        $reply['Reply Poster Id'] = $story->rpl_post_usr_id;
        $reply['Brand Name'] = ($story->attr_brand_name != '') ? $story->attr_brand_name[0] : 'NA';
        $reply['Ad Year Of Make']= ($story->attr_year != '') ? $story->attr_year[0] : 'NA';
        $reply['No Of Bedrooms']= ($story->attr_no_of_rooms != '') ? $story->attr_no_of_rooms[0] : 'NA';
        $reply['Type Of Land']= ($story->attr_type_of_land != '') ? $story->attr_type_of_land[0] : 'NA';
        $reply['Type Of Job']= ($story->attr_type_of_job != '') ? $story->attr_type_of_job[0] : 'NA';
        $reply['Condition']= ($story->attr_condition != '') ? $story->attr_condition[0] : 'NA';
        $reply['Individual Dealer']= ($story->attr_you_are != '') ? $story->attr_you_are[0] : 'NA';
        return $reply;
    }

    protected function prepareDataForExcel($story,$counter) {
        $a = array();
        $str = "";
        
        $str .= '"'.$counter.'"'.$this->separator;
        $a[] = $counter;
        
        $str .=  '"'.$story->id.'"'.$this->separator;
        $a[] = $story->id;


        if(in_array('reply_email',$this->post['reply_columns'])) {
            $str .= '"'.(($story->rpl_email == '') ? 'NA': $story->rpl_email).'"'.$this->separator;
            $a[] = (($story->rpl_email == '') ? 'NA': $story->rpl_email);
        }

        if(in_array('reply_date',$this->post['reply_columns'])) {
            $str .= '"'.(($story->rpl_createdTime != 0) ? date('d-m-Y',$story->rpl_createdTime) : 'NA').'"'.$this->separator;
            $a[] = (($story->rpl_createdTime != 0) ? date('Y-m-d',$story->rpl_createdTime) : 'NA');
        }

        if(in_array('reply_content',$this->post['reply_columns'])) {
            $str .= '"'.htmlentities((($story->rpl_content == '') ? 'NA': $story->rpl_content),ENT_COMPAT).'"'.$this->separator;
            $a[] = utf8_decode(($story->rpl_content == '') ? 'NA': $story->rpl_content);
        }

        if(in_array('reply_mobile',$this->post['reply_columns'])) {
            $str .= '"'.(($story->rpl_mobile == '') ? 'NA': $story->rpl_mobile).'"'.$this->separator;
            $a[] = (($story->rpl_mobile == '') ? 'NA': $story->rpl_mobile);
        }
        
        if(in_array('reply_user_agent',$this->post['reply_columns'])) {
            $str .= '"'.(($story->user_agent_flag_t == '') ? 'NA': $story->user_agent_flag_t).'"'.$this->separator;
            $a[] = (($story->user_agent_flag_t == '') ? 'NA': $story->user_agent_flag_t);
        }
        
        if(in_array('ad_id',$this->post['reply_columns'])) {
            $str .= '"'.(($story->ad_id == '') ? 'NA': $story->ad_id).'"'.$this->separator;
            $a[] = (($story->ad_id == '') ? 'NA': $story->ad_id);
        }
        
        if(in_array('ad_title',$this->post['reply_columns'])) {
            $str .= '"'.htmlentities(($story->ad_title == '') ? 'NA': $story->ad_title,ENT_COMPAT).'"'.$this->separator;
            $a[] = utf8_decode(($story->ad_title == '') ? 'NA': $story->ad_title);
        }
     
        if(in_array('poster_email',$this->post['reply_columns'])) {
            $str .= '"'.(($story->poster_email == '') ? 'NA': $story->poster_email).'"'.$this->separator;
            $a[] = (($story->poster_email == '') ? 'NA': $story->poster_email);
        }
   
        if(in_array('ad_created_date',$this->post['reply_columns'])) {
            $str .= '"'.(($story->ad_created_date != 0) ? date('d-m-Y',$story->ad_created_date) : 'NA').'"'.$this->separator;
            $a[] = (($story->ad_created_date != 0) ? date('Y-m-d',$story->ad_created_date) : 'NA');
        }
        
        if(in_array('reply_poster_id',$this->post['reply_columns'])) {
            $str .= '"'.(($story->rpl_post_usr_id == '') ? 'NA': $story->rpl_post_usr_id).'"'.$this->separator;
            $a[] = (($story->rpl_post_usr_id == '') ? 'NA': $story->rpl_post_usr_id);
        }
        
        if(in_array('ad_modified_date',$this->post['reply_columns'])) {
            $str .= '"'.(($story->ad_modified_date != 0) ? date('d-m-Y',$story->ad_modified_date) : 'NA').'"'.$this->separator;
            $a[] = (($story->ad_modified_date != 0) ? date('Y-m-d',$story->ad_modified_date) : 'NA');
        }
       
        if(in_array('city_name',$this->post['reply_columns'])) {
            $str .= '"'.(($story->city_name == '') ? 'NA': $story->city_name).'"'.$this->separator;
            $a[] = (($story->city_name == '') ? 'NA': $story->city_name);
        }
                
        if(in_array('Ad_locality',$this->post['reply_columns'])) {
            $str .= '"'.(($story->localities == '') ? 'NA': $story->localities).'"'.$this->separator;
            $a[] = (($story->localities == '') ? 'NA': $story->localities);
        }


        if(in_array('metacategory_name',$this->post['reply_columns'])) {
            $str .= '"'.(($story->metacategory_name == '') ? 'NA' : $story->metacategory_name).'"'.$this->separator;
            $a[] = (($story->metacategory_name == '') ? 'NA' : $story->metacategory_name);
        }

        if(in_array('subcategory_name',$this->post['reply_columns'])) {
            $str .= '"'.(($story->subcategory_name == '') ? 'NA' : str_replace(",", "-->",$story->subcategory_name)).'"'.$this->separator;
            $a[] = (($story->subcategory_name == '') ? 'NA' : str_replace(",", "-->",$story->subcategory_name));
        }

        if(in_array('free_premium_type',$this->post['reply_columns'])) {
            $str .= '"'.(($story->free_premium_type == '') ? 'NA': $story->free_premium_type).'"'.$this->separator;
            $a[] = (($story->free_premium_type == '') ? 'NA': $story->free_premium_type);
        }

        if(in_array('ad_type',$this->post['reply_columns'])) {
            $str .= '"'.(($story->ad_type == '') ? 'NA': $story->ad_type).'"'.$this->separator;
            $a[] = (($story->ad_type == '') ? 'NA': $story->ad_type);
        }


        if(in_array('ad_status',$this->post['reply_columns'])) {
            $str .= '"'.(($story->ad_status == '') ? 'NA': $story->ad_status).'"'.$this->separator;
            $a[] = (($story->ad_status == '') ? 'NA': $story->ad_status);
        }

        if(in_array('regular_noclick',$this->post['reply_columns'])) {
            $str .= '"'.(($story->regular_noclick == '') ? 'NA': $story->regular_noclick).'"'.$this->separator;
            $a[] =  (($story->regular_noclick == '') ? 'NA': $story->regular_noclick); 
        }

        if(in_array('no_of_images',$this->post['reply_columns'])) {
            $str .= '"'.(($story->no_of_images == '') ? 'NA': $story->no_of_images).'"'.$this->separator;
            $a[] = (($story->no_of_images == '') ? 'NA': $story->no_of_images);
        }

        if(in_array('no_of_visitors',$this->post['reply_columns'])) {
            $str .= '"'.(($story->no_of_visitors == '') ? 'NA': $story->no_of_visitors).'"'.$this->separator;
            $a[] = (($story->no_of_visitors == '') ? 'NA': $story->no_of_visitors);
        }

        if(in_array('poster_mobile',$this->post['reply_columns'])) {
            $str .= '"'.(($story->poster_mobile == '') ? 'NA': $story->poster_mobile).'"'.$this->separator;
            $a[] = (($story->poster_mobile == '') ? 'NA': $story->poster_mobile);
        }

        if(in_array('no_of_replies',$this->post['reply_columns'])) {
            $str .= '"'.(($story->no_of_replies == '') ? 'NA': $story->no_of_replies).'"'.$this->separator;
            $a[] = (($story->no_of_replies == '') ? 'NA': $story->no_of_replies);
        }

        if(in_array('price_type',$this->post['reply_columns'])) {
            $str .= '"'.(($story->price_type == '') ? 'NA': $story->price_type).'"'.$this->separator;
            $a[] = (($story->price_type == '') ? 'NA': $story->price_type);
        }

        if(in_array('price_value',$this->post['reply_columns'])) {
            $str .= '"'.(($story->price_value == '') ? 'NA': $story->price_value).'"'.$this->separator;
            $a[] = (($story->price_value == '') ? 'NA': $story->price_value);
        }

        if(in_array('premium_ad_type',$this->post['reply_columns'])) {
            $str .= '"'.(($story->premium_ad_type == '') ? 'NA': $story->premium_ad_type).'"'.$this->separator;
            $a[] = (($story->premium_ad_type == '') ? 'NA': $story->premium_ad_type);
        }

        if(in_array('ad_delete_date',$this->post['reply_columns'])) {
            $str .= '"'.(($story->ad_delete_date != 0) ? date('d-m-Y',$story->ad_delete_date) : 'NA').'"'.$this->separator;
            $a[] = (($story->ad_delete_date != 0) ? date('Y-m-d',$story->ad_delete_date) : 'NA');
        }

        if(in_array('ad_delete_reason',$this->post['reply_columns'])) {
            $str .= '"'.(($story->ad_delete_reason == '') ? 'NA': $story->ad_delete_reason).'"'.$this->separator;
            $a[] = (($story->ad_delete_reason == '') ? 'NA': $story->ad_delete_reason);
        }

        if(in_array('repost_time',$this->post['reply_columns'])) {
            $str .= '"'.(($story->repost_time != 0) ? date('d-m-Y',$story->repost_time) : 'NA').'"'.$this->separator;
            $a[] = (($story->repost_time != 0) ? date('Y-m-d',$story->repost_time) : 'NA');
        }
                
                
        if(in_array('reposted',$this->post['reply_columns'])) {
            $str .= '"'.(($story->reposted == '') ? 'NA': $story->reposted).'"'.$this->separator;
            $a[] = (($story->reposted == '') ? 'NA': $story->reposted);
        }

        if(in_array('Condition',$this->post['reply_columns'])) {
            $str .= '"'.(($story->attr_condition == '') ? 'NA': $story->attr_condition[0]).'"'.$this->separator;
            $a[] = (($story->attr_condition == '') ? 'NA': $story->attr_condition[0]);
        }

        if(in_array('individual_dealer',$this->post['reply_columns'])) {
            $str .= '"'.(($story->attr_you_are == '') ? 'NA': $story->attr_you_are[0]).'"'.$this->separator;
            $a[] = (($story->attr_you_are == '') ? 'NA': $story->attr_you_are[0]);
        }

        if(in_array('brand_name',$this->post['reply_columns'])) {
            $str .= '"'.(($story->attr_brand_name == '') ? 'NA': $story->attr_brand_name[0]).'"'.$this->separator;
            $a[] = (($story->attr_brand_name == '') ? 'NA': $story->attr_brand_name[0]);
        }

        if(in_array('no_of_bedrooms',$this->post['reply_columns'])) {
            $str .= '"'.(($story->attr_no_of_rooms == '') ? 'NA': $story->attr_no_of_rooms[0]).'"'.$this->separator;
            $a[] = (($story->attr_no_of_rooms == '') ? 'NA': $story->attr_no_of_rooms[0]);
        }

        if(in_array('type_of_land',$this->post['reply_columns'])) {
            $str .= '"'.(($story->attr_type_of_land == '') ? 'NA': $story->attr_type_of_lande[0]).'"'.$this->separator;
            $a[] = (($story->attr_type_of_land == '') ? 'NA': $story->attr_type_of_lande[0]);
        }

        if(in_array('Ad_year_of_make',$this->post['reply_columns'])) {
            $str .= '"'.(($story->attr_year == '') ? 'NA': $story->attr_year[0]).'"'.$this->separator;
            $a[] = (($story->attr_year == '') ? 'NA': $story->attr_year[0]);
        }
                
        if(in_array('type_of_job',$this->post['reply_columns'])) {
            $str .= '"'.(($story->attr_type_of_job == '') ? 'NA': $story->attr_type_of_job[0]).'"'.$this->separator;
            $a[] = (($story->attr_type_of_job == '') ? 'NA': $story->attr_type_of_job[0]);
        }
        
        return array("str" => $str,"arr" => $a);

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
}