<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


class Model_AdsSolr {
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
    public $solrUrl         = SOLR_META_QUERY_ADS;
    public $finalUrl        = '';
    public $queryArray      = array();

    public $columnsToShow = array();

    public $totalRecordsFound = '';
    public $records = '';
    
    public $separator = "|";
    public $sectionName = 'ads';
    
    
    public function  __construct($postedParams) {
        $this->post = $postedParams;
    }

    protected function buildQueryForDateSummarize($f,$t,$byDateOf) {
        $this->queryArray[] = '+('.$byDateOf.':['.$f.' TO '.$t.'])';
    }

    
    public function getfacetCountForSummarize($f,$t,$byDateOf,$facetField='') {

        //$this->setColumns();

        //now build query for every field
        
        $this->buildQueryForAdId();
        $this->buildQueryForAdTitle();
        $this->buildQueryForAdDesc();
        $this->buildQueryForPosterEmail();
        $this->buildQueryForAdDeleteReason();
        
        if($byDateOf == 'ad_created_date') $this->buildQueryForDateSummarize($f,$t,$byDateOf);
        else $this->buildQueryForCreatedDate();
        
        if($byDateOf == 'ad_modified_date') $this->buildQueryForDateSummarize($f,$t,$byDateOf);
        else $this->buildQueryForUpdatedDate();

        if($byDateOf == 'ad_delete_date') $this->buildQueryForDateSummarize($f,$t,$byDateOf);
        else $this->buildQueryForDeletedDate();

        if($byDateOf == 'expired_time') $this->buildQueryForDateSummarize($f,$t,$byDateOf);
        else $this->buildQueryForExpiredDate();

        if($byDateOf == 'repost_time') $this->buildQueryForDateSummarize($f,$t,$byDateOf);
        else $this->buildQueryForRepostedDate();
        
        if($byDateOf == 'tpc_firstcreated') $this->buildQueryForDateSummarize($f,$t,$byDateOf);
        else $this->buildQueryForFirstCreatedDate();
        
        $this->buildQueryForIsReposted();
        $this->buildQueryForPosterMobile();
        $this->buildQueryForFreePremium();
        $this->buildQueryForPremiumAdType();
        $this->buildQueryForUserAgent();
        $this->buildQueryForAdType();
        $this->buildQueryForAdStatus();
        $this->buildQueryForFlagReason();
        $this->buildQueryForRegularNoClickAd();
        $this->buildQueryForNoOfImages();
        $this->buildQueryForNoOfReply();
        $this->buildQueryForNoOfVisitors();
        $this->buildQueryForPrice();
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
                //'version'   =>  $this->version,
                'start'     =>  $this->start,
                'rows'     =>  '0',
                'facet'		=> 'true',
                'wt'        => $this->wt,
                //'facet.field' => $this->fl,
                'q'         => trim($this->queryString,'+')
                //'facet.query' => trim($this->queryString,'+')
        );
        
        
        if($facetField) {
            $solrVars['facet.field'] = $facetField;
            $solrVars['facet.limit'] = '-1';
            $solrVars['facet.mincount'] = '1';
            //$solrVars['facet.sort'] = 'count';
            
        }

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
            if($facetField) {
                $obj->writeToFile = true;
                $obj->isLoggingEnabled = true;
            }
            $data = $obj->init();
        } catch (Exception $e) {
            trigger_error($e->getMessage());
        }
         
         
         
         if(!empty($data)) {
            $xmlData = json_decode($data); //simplexml_load_string($data);
            
            if($facetField) {
                return $data;
                /**
                 * Basically we are running this:
                 * SELECT COUNT(distinct(tpc_uid))  FROM babel_topic  WHERE tpc_created BETWEEN 1326220200 AND 1326306600;
                 */
//                $d = $xmlData->facet_counts->facet_fields->poster_id;
//                $s = count($d) /2;
//                return $s;
            }
            
            $dataArray =  $xmlData->response->numFound;
            return $dataArray;
         }
    }


    public function getResults($forExcel=false) {
        //query solr
        //first set the columns to show
        $this->setColumns();

        //now build query for every field

        $this->buildQueryForAdId();
        $this->buildQueryForAdTitle();
        $this->buildQueryForAdDesc();
        $this->buildQueryForPosterEmail();
        $this->buildQueryForAdDeleteReason();
        $this->buildQueryForCreatedDate();
        $this->buildQueryForUpdatedDate();
        $this->buildQueryForDeletedDate();
        $this->buildQueryForExpiredDate();
        $this->buildQueryForRepostedDate();
        $this->buildQueryForFirstCreatedDate();
        $this->buildQueryForIsReposted();        
        $this->buildQueryForPosterMobile();
        $this->buildQueryForFreePremium();
        $this->buildQueryForPremiumAdType();
        $this->buildQueryForUserAgent();
        $this->buildQueryForAdType();
        $this->buildQueryForAdStatus();
        $this->buildQueryForFlagReason();
        $this->buildQueryForRegularNoClickAd();
        $this->buildQueryForNoOfImages();
        $this->buildQueryForNoOfReply();
        $this->buildQueryForNoOfVisitors();
        $this->buildQueryForPrice();
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
            if($_GET['show_matching'] == 1) {
                $this->parseXmlDataForExcelForMatchingAds();
            } else {
                $this->parseXmlDataForExcel();
            }
        } else {
            if($_GET['show_matching'] == 1) {
                $this->parseXmlDataForMatchingAds();
            } else {
                $this->parseXmlData();
            }
        }


    }

    protected function setColumns() {
        $postedColumns = $this->post['ads_columns'];
        $this->fl = 'id,';
        foreach($postedColumns as $key => $val) {

            //few exception where we need to change the caption
            if($val == 'id') {$val = 'id';}
            if($val == 'metacategory_name') {$val = 'category'; $this->fl .= 'metacategory_name,';}
            if($val == 'subcategory_name') {$val = 'subcategory'; $this->fl .= 'subcategory_name,';}
            if($val == 'Ad_locality') {$this->fl .= 'localities,';}
            if($val == 'ad_remapped_date') {$this->fl .= 'remapped,';}
            if($val == 'ad_first_created_date') {$this->fl .= 'tpc_firstcreated,';}
            
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
            $ads = array();
            $counter = 0;
            $stories = $xmlData->response->docs;
            
            foreach ($stories as $story) {            
                $counter++;
                $this->columnsToShow['data'][] = $this->fillAdsArray($story);

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
         
         if($_GET['show_matching'] == 1) {
             
             $solrVars['mlt.maxqt']="10";
             $solrVars['mlt.fl'] = "ad_title,ad_description";
             $solrVars['mlt.mindf'] = "1";
             $solrVars['mlt.mintf'] = "1";
             $solrVars['mlt.boost'] = "true";
         }
         
         $solrVarsStr = '';
         foreach($solrVars as $key => $val) {
             $solrVarsStr .= $key.'='.trim($val).'&';
         }

         //this is final query
         $this->finalUrl =  rtrim($this->solrUrl.'select?'.$solrVarsStr,'&');
         if($_GET['show_matching'] == 1) {
             $this->finalUrl =  rtrim($this->solrUrl.'mlt?'.$solrVarsStr,'&');
         }
         
         //echo "<pre>".$this->finalUrl."<hr />";
    }


    protected function buildQueryForAdId() {
        $queryString = '';
        if(!empty($this->post['ads_filter_ad_id'])) {
            $this->queryArray[] = '+(id:'.trim($this->post['ads_filter_ad_id']).')';
        }
    }

    protected function buildQueryForAdTitle() {
        if(trim($this->post['ads_filter_ad_title_select_ece']) != 'none' &&
              trim($this->post['ads_filter_ad_title_text']) != '') {
            $operator = trim($this->post['ads_filter_ad_title_select_ece']);
            $text = trim($this->post['ads_filter_ad_title_text']);


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
        if(trim($this->post['ads_filter_ad_description_select_ece']) != 'none' &&
              trim($this->post['ads_filter_ad_description_text']) != '') {
            $operator = trim($this->post['ads_filter_ad_description_select_ece']);
            $text = trim($this->post['ads_filter_ad_description_text']);


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
        if(trim($this->post['reply_filter_poster_email_select_ece']) != 'none' &&
              trim($this->post['reply_filter_poster_email_text']) != '') {
            $operator = trim($this->post['reply_filter_poster_email_select_ece']);
            $text = trim($this->post['reply_filter_poster_email_text']);


            switch($operator) {
                case 'equals':
                $this->queryArray[] = '+(poster_email:'.$text.')'; //exact search
                break;

                case 'contains':
                $this->queryArray[] = '+(poster_email:*'.$text.'*)'; //anywhere in between the text
                break;

                case 'excludes':
                $this->queryArray[] =  '-(poster_email:*'.$text.'*)'; //does not contain
                break;

                default:
                $this->queryArray[] =  '+(poster_email:'.$text.')'; //exact search
                break;
            }
        }
    }


    protected function buildQueryForAdDeleteReason() {
        if(trim($this->post['ads_filter_delete_reason_select_ece']) != 'none' &&
              trim($this->post['ads_filter_delete_reason_text']) != '') {
            $operator = trim($this->post['ads_filter_delete_reason_select_ece']);
            $text = trim($this->post['ads_filter_delete_reason_text']);


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
        if(!empty($this->post['ads_filter_createdate_from']) &&
                !empty($this->post['ads_filter_createdate_to'])) {
            $from = $this->ddmmyyyToTimestamp($this->post['ads_filter_createdate_from']);
            $to = $this->ddmmyyyToTimestamp($this->post['ads_filter_createdate_to'])+TO_DATE_INCREMENT;
            $this->queryArray[] = '+(ad_created_date:['.$from.' TO '.$to.'])';
        }
    }

    protected function buildQueryForUpdatedDate() {
        if(!empty($this->post['ads_filter_adlastupdate_from']) &&
                !empty($this->post['ads_filter_adlastupdate_to'])) {
            $from = $this->ddmmyyyToTimestamp($this->post['ads_filter_adlastupdate_from']);
            $to = $this->ddmmyyyToTimestamp($this->post['ads_filter_adlastupdate_to'])+TO_DATE_INCREMENT;
            $this->queryArray[] = '+(ad_modified_date:['.$from.' TO '.$to.'])';
        }
    }


    protected function buildQueryForDeletedDate() {
        if(!empty($this->post['ads_filter_addeletedate_from']) &&
                !empty($this->post['ads_filter_addeletedate_to'])) {
            $from = $this->ddmmyyyToTimestamp($this->post['ads_filter_addeletedate_from']);
            $to = $this->ddmmyyyToTimestamp($this->post['ads_filter_addeletedate_to'])+TO_DATE_INCREMENT;
            $this->queryArray[] = '+(ad_delete_date:['.$from.' TO '.$to.'])';
        }
    }

    protected function buildQueryForExpiredDate() {
        if(!empty($this->post['ads_filter_expiretime_from']) &&
                !empty($this->post['ads_filter_expiretime_to'])) {
            $from = $this->ddmmyyyToTimestamp($this->post['ads_filter_expiretime_from']);
            $to = $this->ddmmyyyToTimestamp($this->post['ads_filter_expiretime_to'])+TO_DATE_INCREMENT;
            $this->queryArray[] = '+(expired_time:['.$from.' TO '.$to.'])';
        }
    }

    protected function buildQueryForRepostedDate() {
        if(!empty($this->post['ads_filter_reposttime_from']) &&
                !empty($this->post['ads_filter_reposttime_to'])) {
            $from = $this->ddmmyyyToTimestamp($this->post['ads_filter_reposttime_from']);
            $to = $this->ddmmyyyToTimestamp($this->post['ads_filter_reposttime_to'])+TO_DATE_INCREMENT;
            $this->queryArray[] = '+(repost_time:['.$from.' TO '.$to.'])';
        }
    }
    
    
    protected function buildQueryForFirstCreatedDate() {
        if(!empty($this->post['ads_filter_first_created_from']) &&
                !empty($this->post['ads_filter_first_created_to'])) {
            $from = $this->ddmmyyyToTimestamp($this->post['ads_filter_first_created_from']);
            $to = $this->ddmmyyyToTimestamp($this->post['ads_filter_first_created_to'])+TO_DATE_INCREMENT;
            $this->queryArray[] = '+(tpc_firstcreated:['.$from.' TO '.$to.'])';
        }
    }


    protected function buildQueryForIsReposted() {
        $queryString = '';
        if(!empty($this->post['ads_filter_reposted'])) {
            if($this->post['ads_filter_reposted'] == 'yes') {
                $this->queryArray[] = '+(reposted:*)'; //everything except blank which

            } else if($this->post['ads_filter_reposted'] == 'no') {
                $this->queryArray[] = '-(reposted:*)';
            }
        }

    }


    protected function buildQueryForPosterMobile() {
        $queryString = '';
        if(!empty($this->post['ads_filter_postermobile'])) {
            if($this->post['ads_filter_postermobile'] == 'present') {
                $this->queryArray[] = '+(poster_mobile:[* TO *])';
            } else if($this->post['ads_filter_postermobile'] == 'not_present') {
                $this->queryArray[] = '-(poster_mobile:[* TO *])';
            }
        }
    }


    protected function buildQueryForFreePremium() {
        $queryString = '';
        if(!empty($this->post['ads_filter_free_premium'])) {

            if(count($this->post['ads_filter_free_premium']) == 1) {
                if($this->post['ads_filter_free_premium'][0] == 'free') {
                    $this->queryArray[] = '+(free_premium_type:Free)';

                } else if($this->post['ads_filter_free_premium'][0] == 'premium') {
                    $this->queryArray[] = '+(free_premium_type:Premium)';
                }

            } else if(count($this->post['ads_filter_free_premium']) == 2) {
                $this->queryArray[] = '+((free_premium_type:Free) OR (free_premium_type:Premium))';
            }
        }
    }


    protected function buildQueryForPremiumAdType() {
        $queryString = '';
        if(!empty($this->post['ads_filter_premium_ad_type'])) {

            if(count($this->post['ads_filter_premium_ad_type']) == 1) {
                if($this->post['ads_filter_premium_ad_type'][0] == 'top_of_page') {
                    $this->queryArray[] = '+(premium_ad_type:TOP)';

                } else if($this->post['ads_filter_premium_ad_type'][0] == 'urgent') {
                    $this->queryArray[] = '+(premium_ad_type:URGENT)';
                }

            } else if(count($this->post['ads_filter_premium_ad_type']) == 2) {
                $this->queryArray[] = '+(premium_ad_type:ALL)';
            }
        }
    }

    protected function buildQueryForUserAgent() {
        $queryString = '';
        if(!empty($this->post['ads_filter_useragent'])) {

            if(count($this->post['ads_filter_useragent']) == 1) {
                if($this->post['ads_filter_useragent'][0] == 'web') {
                    $this->queryArray[] = '+(user_agent:Web)';

                } else if($this->post['ads_filter_useragent'][0] == 'mobile') {
                    $this->queryArray[] = '+(user_agent:Mobile)';
                }

            } else if(count($this->post['ads_filter_useragent']) == 2) {
                $this->queryArray[] = '+((user_agent:Web) OR (user_agent:Mobile))';
            }
        }
    }


    protected function buildQueryForAdType() {

        $queryString = '';
        if(!empty($this->post['ads_filter_ad_type'])) {

            if(count($this->post['ads_filter_ad_type']) == 1) {
                if($this->post['ads_filter_ad_type'][0] == 'offering') {
                    $this->queryArray[] = '+(ad_type:Offer)';

                } else if($this->post['ads_filter_ad_type'][0] == 'want') {
                    $this->queryArray[] = '+(ad_type:Want)';
                }

            } else if(count($this->post['ads_filter_ad_type']) == 2) {
                $this->queryArray[] = '+((ad_type:Offer) OR (ad_type:Want))';
            }
        }
    }


    protected function buildQueryForAdStatus() {

        $queryString = array();
        if(!empty($this->post['ads_filter_status'])) {
            foreach($this->post['ads_filter_status'] as $key => $val) {
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
        if(!empty($this->post['ads_filter_flag_reason'])) {
            foreach($this->post['ads_filter_flag_reason'] as $key => $val) {
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
        if(!empty($this->post['ads_filter_regular'])) {
            foreach($this->post['ads_filter_regular'] as $key => $val) {
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
        if(trim($this->post['ads_filter_no_of_images_range']) != '' &&
              trim($this->post['ads_filter_no_of_images_text']) != '') {

            $qty = trim($this->post['ads_filter_no_of_images_text']);

            $obj = new Zend_Validate_Int();
            $st = $obj->isValid($qty);
            
            if($st && $qty >= 0) {
                switch($this->post['ads_filter_no_of_images_range']) {
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
        if(trim($this->post['ads_filter_no_of_visitors_range']) != '' &&
              trim($this->post['ads_filter_no_of_visitors_text']) != '') {

            $qty = trim($this->post['ads_filter_no_of_visitors_text']);

            $obj = new Zend_Validate_Int();
            $st = $obj->isValid($qty);

            if($st && $qty >= 0) {
                switch($this->post['ads_filter_no_of_visitors_range']) {
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
        if(trim($this->post['ads_filter_no_of_replies_range']) != '' &&
              trim($this->post['ads_filter_no_of_replies_text']) != '') {

            $qty = trim($this->post['ads_filter_no_of_replies_text']);

            $obj = new Zend_Validate_Int();
            $st = $obj->isValid($qty);

            if($st && $qty >= 0) {
                switch($this->post['ads_filter_no_of_replies_range']) {
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
        if(trim($this->post['ads_filter_price_range']) != '' &&
              trim($this->post['ads_filter_price_text']) != '') {

            $qty = trim($this->post['ads_filter_price_text']);

            $obj = new Zend_Validate_Int();
            $st = $obj->isValid($qty);

            if($st && $qty >= 0) {
                switch($this->post['ads_filter_price_range']) {
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
        if(!empty($this->post['ads_filter_city'])) {
            if($this->post['ads_filter_city'] == 'all') {
                $this->queryArray[] = '+(city_id:*)';
            } else {
                $this->queryArray[] = '+(city_id:'.trim($this->post['ads_filter_city']).')';
            }
        }
    }

    protected function buildQueryForLocality() {
        if(!empty($this->post['ads_filter_localities'])) {
            if($this->post['ads_filter_localities'] == '0') {
                $this->queryArray[] = '+(localities:*)';
            } else {
                $this->queryArray[] = '+(localities:'.trim($this->post['ads_filter_localities']).')';
            }

        }
    }

    protected function buildQueryForMetacategory() {
        if(!empty($this->post['ads_filter_metacat'])) {
            if($this->post['ads_filter_metacat'] == 'all') {
                    //if doing a global search based on city
                if($this->post['ads_filter_city'] == 'all') {
                    $this->queryArray[] = '+(global_metacategory_id:[* TO *])';
                } else { //doing city specific search
                    $this->queryArray[] = '+(metacategory_id:[* TO *])';
                }
            } else {
                //if doing a global search based on city
                if($this->post['ads_filter_city'] == 'all') {
                    $this->queryArray[] = '+(global_metacategory_id:'.trim($this->post['ads_filter_metacat']).')';
                } else {
                        //doing city specific search
                    $this->queryArray[] = '+(metacategory_id:'.trim($this->post['ads_filter_metacat']).')';
                }
            }
        }
    }

    protected function buildQueryForSubcategory() {
         if(!empty($this->post['ads_filter_subcat'])) {
            if($this->post['ads_filter_subcat'] == 'all') {
                //if doing a global search based on city
                if($this->post['ads_filter_city'] == 'all') {
                    $this->queryArray[] = '+(global_subcategory_id:[* TO *])';
                } else {
                        //doing city specific search
                    $this->queryArray[] = '+(subcategory_id:[* TO *])';
                }

            } else {
                //if doing a global search based on city
                if($this->post['ads_filter_city'] == 'all') {
                    $this->queryArray[] = '+(global_subcategory_id:'.trim($this->post['ads_filter_subcat']).')';
                } else {
                        //doing city specific search
                    $this->queryArray[] = '+(subcategory_id:'.trim($this->post['ads_filter_subcat']).')';
                }
            }
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





    public function getAdsCountForUser($userId) {
        $solrVars = array(
            'indent'    =>  $this->indent,
            'version'   =>  $this->version,
            'start'     =>  0,
            'rows'  => 0,
            'facet'     => 'true',
            'facet.limit' => 1,
            'wt'        =>  $this->wt,
            'facet.field' => 'poster_id',
            'q'         => 'poster_id:'.$userId,
            'facet.query' => 'poster_id:'.$userId
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


    public function getSingleFieldFromAds($fieldToReturn,$adId) {
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
                    'q'         => 'id:'.$adId
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

    protected function ddmmyyyToTimestamp($date) {
        return strtotime($date);
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
    
    protected function cleanHtml($input) {
        //$input= preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F\0xa4\0xa0\0xcb]/', '', $input);
        $str = preg_replace( array('/\x00/', '/\x01/', '/\x02/', '/\x03/', '/\x04/', '/\x05/', '/\x06/', '/\x07/', '/\x08/', '/\x09/', '/\x0A/', '/\x0B/','/\x0C/','/\x0D/', '/\x0E/', '/\x0F/', '/\x10/', '/\x11/', '/\x12/','/\x13/','/\x14/','/\x15/', '/\x16/', '/\x17/', '/\x18/', '/\x19/','/\x1A/','/\x1B/','/\x1C/','/\x1D/', '/\x1E/', '/\x1F/','/\\\u000A/','/\\\u000D/'), array(""), $input);
        return (utf8_decode($str));
    }
    
    
    
    public function parseXmlDataForMatchingAds() {
        //$data = file_get_contents($this->finalUrl);
        
        try {
            $obj = new Utility_SolrQueryAnalyzer($this->finalUrl,__FILE__.' at line '.__LINE__);
            $data = $obj->init();
        } catch (Exception $e) {
            trigger_error($e->getMessage());
        }
        
        
        if(!empty($data)) {
            $xmlData = json_decode($data);
            $ads = array();
            $counter = 0;
            
            $originalAd = $xmlData->match->docs[0];
            if(!empty($originalAd)) {
                $counter++;
                $this->columnsToShow['data'][] = $this->fillAdsArray($originalAd);


                $stories = $xmlData->response->docs;
                if(!empty($stories)) {
                    $scoreFilter = empty($this->post["ads_filter_score_text"]) ? 3 : $this->post["ads_filter_score_text"];
                    foreach ($stories as $story) {
                        if($story->score > $scoreFilter) {
                            $counter++;
                            $this->columnsToShow['data'][] = $this->fillAdsArray($story);
                        }
                    }
                }
            }
                
            
        } else {
            $this->columnsToShow['data'] = '';
        }
        
        $this->totalRecordsFound = $counter;
        
    }
    
    
    public function parseXmlDataForExcelForMatchingAds() {
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
            $originalAd = $xmlData->match->docs[0];
            
            //header
            $hChunks = $this->prepareHeadersForExcel();
            $str .= $hChunks["str"];
            $excel = array(
                $counter => $hChunks["arr"]
            );
            
            //original ad
            $oChunks = $this->prepareDataForExcel($originalAd, $counter);
            $counter++;
            $str .= $oChunks["str"]."\n";
            $excel[$counter] = $oChunks["arr"];
            
            
            //matching ads
            
            $stories = $xmlData->response->docs;
            if(!empty($xmlData->response->docs)) {
                $scoreFilter = empty($this->post["ads_filter_score_text"]) ? 3 : $this->post["ads_filter_score_text"];
                foreach ($stories as $story) {
                    if($story->score > $scoreFilter) {
                        $mChunks = $this->prepareDataForExcel($story, $counter);
                        $counter++;
                        $str .= $mChunks["str"]."\n";
                        $excel[$counter] = $mChunks["arr"];
                        unset($mChunks);
                    }
                }
            }
            
            $this->prepareDownLoadFile(array(
                                    "str" => trim($str),
                                    "arr" => $excel));


        }
    }
    
    
    protected function fillAdsArray($story) {
        $ads = array();
        $ads['Id'] = $story->id;
        $ads['Ad Title'] = utf8_decode(($story->ad_title == '') ? 'NA': $story->ad_title);
        $ads['Ad Description'] = utf8_decode(($story->ad_description == '') ? 'NA': $story->ad_description);
        $ads['Poster Id'] = ($story->poster_id == '') ? 'NA': $story->poster_id;
        $ads['Poster Email'] = ($story->poster_email == '') ? 'NA': $story->poster_email;
        $ads['Poster Mobile'] = ($story->poster_mobile == '') ? 'NA': $story->poster_mobile;
        $ads['Ad Created Date'] = ($story->ad_created_date != 0) ? date('d-m-Y',$story->ad_created_date) : 'NA';
        $ads['Ad Modified Date'] = ($story->ad_modified_date != 0) ? date('d-m-Y',$story->ad_modified_date) : 'NA';
        $ads['Free Premium Type'] = ($story->free_premium_type == '') ? 'NA': $story->free_premium_type;
        $ads['Premium Ad Type'] = ($story->premium_ad_type == '') ? 'NA': $story->premium_ad_type;
        $ads['Regular Noclick'] = ($story->regular_noclick == '') ? 'NA': $story->regular_noclick;
        $ads['No Of Images'] = ($story->no_of_images == '') ? 'NA': $story->no_of_images;
        $ads['No Of Visitors'] = ($story->no_of_visitors == '') ? 'NA': $story->no_of_visitors;
        $ads['No Of Replies'] = ($story->no_of_replies == '') ? 'NA': $story->no_of_replies;
        $ads['Price Type'] = ($story->price_type == '') ? 'NA': $story->price_type;
        $ads['Price Value'] = ($story->price_value == '') ? 'NA': $story->price_value;
        $ads['Ad Type'] = ($story->ad_type == '') ? 'NA': $story->ad_type;
        $ads['Ad Status'] = ($story->ad_status == '') ? 'NA': $story->ad_status;
        $ads['Ad Delete Date'] = ($story->ad_delete_date != 0) ? date('d-m-Y',$story->ad_delete_date) : 'NA';
        $ads['Ad Delete Reason'] = ($story->ad_delete_reason == '') ? 'NA': $story->ad_delete_reason;
        $ads['Expired Time'] = ($story->expired_time != 0) ? date('d-m-Y',$story->expired_time) : 'NA';
        $ads['Reposted'] = ($story->reposted == '') ? 'NA': $story->reposted;
        $ads['Repost Time'] = ($story->repost_time != 0) ? date('d-m-Y',$story->repost_time) : 'NA';
        $ads['Flag Reason'] = ($story->flag_reason == '') ? 'NA': $story->flag_reason;
        $ads['User Agent'] = ($story->user_agent != '') ? $story->user_agent : 'NA';
        $ads['City Name'] = $story->city_name;
        $ads['Ad Locality'] = $story->localities;
        $ads['Category'] = $story->metacategory_name;
        $ads['Subcategory'] = $story->subcategory_name;
        $ads['Attributes'] = $this->cleanHtml($story->attributes);
        $ads['Ad Remapped Date'] = ($story->remapped != 0) ? date('d-m-Y',$story->remapped) : 'NA';
        $ads['Ad First Created Date'] = ($story->tpc_firstcreated != 0) ? date('d-m-Y',$story->tpc_firstcreated) : 'NA';
        $ads['Brand Name'] = ($story->attr_brand_name != '') ? $story->attr_brand_name[0] : 'NA';
        $ads['Ad Year Of Make']= ($story->attr_year != '') ? $story->attr_year[0] : 'NA';
        $ads['No Of Bedrooms']= ($story->attr_no_of_rooms != '') ? $story->attr_no_of_rooms[0] : 'NA';
        $ads['Type Of Land']= ($story->attr_type_of_land != '') ? $story->attr_type_of_land[0] : 'NA';
        $ads['Type Of Job']= ($story->attr_type_of_job != '') ? $story->attr_type_of_job[0] : 'NA';
        $ads['Condition']= ($story->attr_condition != '') ? $story->attr_condition[0] : 'NA';
        $ads['Individual Dealer']= ($story->attr_you_are != '') ? $story->attr_you_are[0] : 'NA';
        $ads['Score']= ($story->score != '') ? $story->score : 'NA'; 
        return $ads;
    }
    
    
    protected function prepareHeadersForExcel() {
        $str = '';
        $excelHeaders = array();

        if(!empty($this->columnsToShow['columns'])) {
            $excelHeaders[] = "Sr. No.";
            $str .= "\"Sr. No.\"".$this->separator;
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
        
        $a[] = $counter;
        $str .= '"'.$counter.'"'.$this->separator;
        
        if(in_array('id',$this->post['ads_columns'])) {
            $a[] = $story->id;
            $str .= '"'.(($story->id == '') ? 'NA': $story->id).'"'.$this->separator;
        }

        if(in_array('ad_title',$this->post['ads_columns'])) {
            $str .= '"'.(($story->ad_title == '') ? 'NA': $story->ad_title).'"'.$this->separator;
            $a[] = (($story->ad_title == '') ? 'NA': $story->ad_title);
        }

        if(in_array('ad_description',$this->post['ads_columns'])) {
            $str .= '"'.(($story->ad_description == '') ? 'NA': $story->ad_description).'"'.$this->separator;
            $a[] = (($story->ad_description == '') ? 'NA': $story->ad_description);
        }

        if(in_array('poster_email',$this->post['ads_columns'])) {
            $str .= '"'.(($story->poster_email == '') ? 'NA': $story->poster_email).'"'.$this->separator;
            $a[] = (($story->poster_email == '') ? 'NA': $story->poster_email);
        }


        if(in_array('ad_created_date',$this->post['ads_columns'])) {
            $str .= '"'.(($story->ad_created_date != 0) ? date('d-m-Y',$story->ad_created_date) : 'NA').'"'.$this->separator;
            $a[] = (($story->ad_created_date != 0) ? date('Y-m-d',$story->ad_created_date) : 'NA');
        }

        if(in_array('ad_modified_date',$this->post['ads_columns'])) {
            $str .= '"'.(($story->ad_modified_date != 0) ? date('d-m-Y',$story->ad_modified_date) : 'NA').'"'.$this->separator;
            $a[] = (($story->ad_modified_date != 0) ? date('Y-m-d',$story->ad_modified_date) : 'NA');
        }



        if(in_array('city_name',$this->post['ads_columns'])) {
            $str .= '"'.(($story->city_name == '') ? 'NA': $story->city_name).'"'.$this->separator;
            $a[] = (($story->city_name == '') ? 'NA': $story->city_name);
        }

        if(in_array('Ad_locality',$this->post['ads_columns'])) {
            $str .= '"'.(($story->localities == '') ? 'NA': $story->localities).'"'.$this->separator;
            $a[] = (($story->localities == '') ? 'NA': $story->localities);
        }

        if(in_array('expired_time',$this->post['ads_columns'])) {
            $str .= '"'.(($story->expired_time != 0) ? date('d-m-Y',$story->expired_time) : 'NA').'"'.$this->separator;
            $a[] = (($story->expired_time != 0) ? date('Y-m-d',$story->expired_time) : 'NA');
        }

        if(in_array('user_agent',$this->post['ads_columns'])) {
            $str .= '"'.(($story->user_agent == '') ? 'NA' : $story->user_agent).'"'.$this->separator;
            $a[] = (($story->user_agent == '') ? 'NA' : $story->user_agent);
        }

        if(in_array('metacategory_name',$this->post['ads_columns'])) {
            $str .= '"'.(($story->metacategory_name == '') ? 'NA' : $story->metacategory_name).'"'.$this->separator;
            $a[] = (($story->metacategory_name == '') ? 'NA' : $story->metacategory_name);
        }

        if(in_array('subcategory_name',$this->post['ads_columns'])) {
            $str .= '"'.(($story->subcategory_name == '') ? 'NA' : str_replace(",", "-->",$story->subcategory_name)).'"'.$this->separator;
            $a[] = (($story->subcategory_name == '') ? 'NA' : str_replace(",", "-->",$story->subcategory_name));
        }

        if(in_array('free_premium_type',$this->post['ads_columns'])) {
            $str .= '"'.(($story->free_premium_type == '') ? 'NA': $story->free_premium_type).'"'.$this->separator;
            $a[] = (($story->free_premium_type == '') ? 'NA': $story->free_premium_type);
        }

        if(in_array('ad_type',$this->post['ads_columns'])) {
            $str .= '"'.(($story->ad_type == '') ? 'NA': $story->ad_type).'"'.$this->separator;
            $a[] = (($story->ad_type == '') ? 'NA': $story->ad_type);
        }


        if(in_array('ad_status',$this->post['ads_columns'])) {
            $str .= '"'.(($story->ad_status == '') ? 'NA': $story->ad_status).'"'.$this->separator;
            $a[] = (($story->ad_status == '') ? 'NA': $story->ad_status);
        }

        if(in_array('regular_noclick',$this->post['ads_columns'])) {
            $str .= '"'.(($story->regular_noclick == '') ? 'NA': $story->regular_noclick).'"'.$this->separator;
            $a[] = (($story->regular_noclick == '') ? 'NA': $story->regular_noclick);
        }

        if(in_array('no_of_images',$this->post['ads_columns'])) {
            $str .= '"'.(($story->no_of_images == '') ? 'NA': $story->no_of_images).'"'.$this->separator;
            $a[] = (($story->no_of_images == '') ? 'NA': $story->no_of_images);
        }

        if(in_array('no_of_visitors',$this->post['ads_columns'])) {
            $str .= '"'.(($story->no_of_visitors == '') ? 'NA': $story->no_of_visitors).'"'.$this->separator;
            $a[] = (($story->no_of_visitors == '') ? 'NA': $story->no_of_visitors);
        }

        if(in_array('poster_mobile',$this->post['ads_columns'])) {
            $str .= '"'.(($story->poster_mobile == '') ? 'NA': $story->poster_mobile).'"'.$this->separator;
            $a[] = (($story->poster_mobile == '') ? 'NA': $story->poster_mobile);
        }

        if(in_array('no_of_replies',$this->post['ads_columns'])) {
            $str .= '"'.(($story->no_of_replies == '') ? 'NA': $story->no_of_replies).'"'.$this->separator;
            $a[] = (($story->no_of_replies == '') ? 'NA': $story->no_of_replies);
        }

        if(in_array('price_type',$this->post['ads_columns'])) {
            $str .= '"'.(($story->price_type == '') ? 'NA': $story->price_type).'"'.$this->separator;
            $a[] = (($story->price_type == '') ? 'NA': $story->price_type);
        }

        if(in_array('price_value',$this->post['ads_columns'])) {
            $str .= '"'.(($story->price_value == '') ? 'NA': $story->price_value).'"'.$this->separator;
            $a[] = (($story->price_value == '') ? 'NA': $story->price_value);
        }

        if(in_array('premium_ad_type',$this->post['ads_columns'])) {
            $str .= '"'.(($story->premium_ad_type == '') ? 'NA': $story->premium_ad_type).'"'.$this->separator;
            $a[] = (($story->premium_ad_type == '') ? 'NA': $story->premium_ad_type);
        }

        if(in_array('ad_delete_date',$this->post['ads_columns'])) {
            $str .= '"'.(($story->ad_delete_date != 0) ? date('d-m-Y',$story->ad_delete_date) : 'NA').'"'.$this->separator;
            $a[] = (($story->ad_delete_date != 0) ? date('Y-m-d',$story->ad_delete_date) : 'NA');
        }

        if(in_array('ad_delete_reason',$this->post['ads_columns'])) {
            $str .= '"'.(($story->ad_delete_reason == '') ? 'NA': $story->ad_delete_reason).'"'.$this->separator;
            $a[] = (($story->ad_delete_reason == '') ? 'NA': $story->ad_delete_reason);
        }

        if(in_array('repost_time',$this->post['ads_columns'])) {
            $str .= '"'.(($story->repost_time != 0) ? date('d-m-Y',$story->repost_time) : 'NA').'"'.$this->separator;
            $a[] = (($story->repost_time != 0) ? date('Y-m-d',$story->repost_time) : 'NA');
        }


        if(in_array('reposted',$this->post['ads_columns'])) {
            $str .= '"'.(($story->reposted == '') ? 'NA': $story->reposted).'"'.$this->separator;
            $a[] = (($story->reposted == '') ? 'NA': $story->reposted);
        }


        if(in_array('Condition',$this->post['ads_columns'])) {
            $str .= '"'.(($story->attr_condition == '') ? 'NA': $story->attr_condition[0]).'"'.$this->separator;
            $a[] = (($story->attr_condition == '') ? 'NA': $story->attr_condition[0]);
        }

        if(in_array('individual_dealer',$this->post['ads_columns'])) {
            $str .= '"'.(($story->attr_you_are == '') ? 'NA': $story->attr_you_are[0]).'"'.$this->separator;
            $a[] = (($story->attr_you_are == '') ? 'NA': $story->attr_you_are[0]);
        }

        if(in_array('brand_name',$this->post['ads_columns'])) {
            $str .= '"'.(($story->attr_brand_name == '') ? 'NA': $story->attr_brand_name[0]).'"'.$this->separator;
            $a[] = (($story->attr_brand_name == '') ? 'NA': $story->attr_brand_name[0]);
        }

        if(in_array('no_of_bedrooms',$this->post['ads_columns'])) {
            $str .= '"'.(($story->attr_no_of_rooms == '') ? 'NA': $story->attr_no_of_rooms[0]).'"'.$this->separator;
            $a[] = (($story->attr_no_of_rooms == '') ? 'NA': $story->attr_no_of_rooms[0]);
        }

        if(in_array('type_of_land',$this->post['ads_columns'])) {
            $str .= '"'.(($story->attr_type_of_land == '') ? 'NA': $story->attr_type_of_lande[0]).'"'.$this->separator;
            $a[] = (($story->attr_type_of_land == '') ? 'NA': $story->attr_type_of_lande[0]);
        }

        if(in_array('Ad_year_of_make',$this->post['ads_columns'])) {
            $str .= '"'.(($story->attr_year == '') ? 'NA': $story->attr_year[0]).'"'.$this->separator;
            $a[] = (($story->attr_year == '') ? 'NA': $story->attr_year[0]);
        }

        if(in_array('type_of_job',$this->post['ads_columns'])) {
            $str .= '"'.(($story->attr_type_of_job == '') ? 'NA': $story->attr_type_of_job[0]).'"'.$this->separator;
            $a[] = (($story->attr_year == '') ? 'NA': $story->attr_year[0]);
        }

        if(in_array('flag_reason',$this->post['ads_columns'])) {
            $str .= '"'.(($story->flag_reason == '') ? 'NA': $story->flag_reason).'"'.$this->separator;
            $a[] = (($story->flag_reason == '') ? 'NA': $story->flag_reason);
        }

        if(in_array('attributes',$this->post['ads_columns'])) {
            $str .= '"'.(($story->attributes == '') ? 'NA': $this->cleanHtml($story->attributes)).'"'.$this->separator;
            $a[] = (($story->attributes == '') ? 'NA': $this->cleanHtml($story->attributes));
        }

        if(in_array('ad_remapped_date',$this->post['ads_columns'])) {
            $str .= '"'.(($story->remapped != 0) ? date('d-m-Y',$story->remapped) : 'NA').'"'.$this->separator;
            $a[] = (($story->remapped != 0) ? date('Y-m-d',$story->remapped) : 'NA');
        }

        if(in_array('ad_first_created_date',$this->post['ads_columns'])) {
            $str .= '"'.(($story->tpc_firstcreated != 0) ? date('d-m-Y',$story->tpc_firstcreated) : 'NA').'"'.$this->separator;
            $a[] = (($story->tpc_firstcreated != 0) ? date('Y-m-d',$story->tpc_firstcreated) : 'NA');
        }

        if(in_array('poster_id',$this->post['ads_columns'])) {
            $str .= '"'.(($story->poster_id == '') ? 'NA': $story->poster_id).'"'.$this->separator;
            $a[] = (($story->poster_id == '') ? 'NA': $story->poster_id);
        }

        if(in_array('score',$this->post['ads_columns'])) {
            $str .= '"'.(($story->score == '') ? 'NA': $story->score).'"'.$this->separator;
            $a[] = (($story->score == '') ? 'NA': $story->score);
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
    
    
}