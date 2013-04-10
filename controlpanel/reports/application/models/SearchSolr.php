<?php
class Model_SearchSolr {

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
    public $wt              = 'xml';       //Output Type
    public $fq              = '';
    public $version         = '2.2';
    public $rows            = MAX_RESULTS_PER_PAGE;             //Maximum Rows Returned
    public $solrUrl         = SOLR_META_QUERY_SEARCH;
    public $finalUrl        = '';
    public $queryArray      = array();

    public $columnsToShow = array();

    public $totalRecordsFound = '';
    public $records = '';
    
    public $facetCount = 1;

    public function  __construct($postedParams) {
        $this->post = $postedParams;
    }
    
    public function getResults() {
        //query solr
        //first set the columns to show
        $this->setColumns();

        //now build query for every field
        $this->buildQueryForKeyword();
        $this->buildQueryForCity();
        $this->buildQueryForMetacategory();
        $this->buildQueryForSubcategory();
        $this->buildQueryForSearchdate();
        $this->buildQueryForSearchcount();
        $this->buildQueryForNoOfResults();

        if(empty($this->queryArray)) {
            $this->queryString = urlencode(trim('*:*'));
        } else {
            $this->queryString = urlencode(trim(implode(' AND ', $this->queryArray),'+'));
        }
        $this->buildSolrQueryString();
        //echo $this->finalUrl;

        $this->parseXmlData();
        
        
        //print_r($xmlData);

    }
    
    function buildQueryForSearchcount() {
    	if(trim($this->post['search_filter_searchcount_range']) != 'none' &&
              trim($this->post['search_filter_searchcount_text']) != '') {
           $this->facetCount = trim($this->post['search_filter_searchcount_text']);
    	}
    }
    /**
     * This function will fetch the total facets and will be used in pagination
     *
     * @return unknown
     */
    public function getfacetCount() {
        
        $solrVars = array(
                'indent'    =>  $this->indent,
                'version'   =>  $this->version,
                'fq'        =>  $this->fq,
                'start'     =>  $this->start,
                'rows'      =>  0,//$this->rows,
                'fl'        =>  $this->fl,
                //'qt'        =>  $this->qt,
                'wt'        =>  $this->wt,
                'explainOther'=> $this->explainOther,
                'hl.fl'     =>  $this->hl_fl,
                'facet'		=> 'true',
                'facet.field' => 'keyword',
                'facet.sort'  => 'true',
                'facet.offset' => 0,
                'facet.limit'  => -1,
                'facet.mincount' => $this->facetCount,
                'q'         => trim($this->queryString,'+')
        );

         $solrVarsStr = '';
         foreach($solrVars as $key => $val) {
             $solrVarsStr .= $key.'='.trim($val).'&';
         }

         //this is final query
         $finalUrl =  rtrim($this->solrUrl.'select?'.$solrVarsStr,'&'); 

         //$data = file_get_contents($finalUrl);
         try {
            $obj = new Utility_SolrQueryAnalyzer($finalUrl,__FILE__.' at line '.__LINE__);
            $data = $obj->init();
        } catch (Exception $e) {
            trigger_error($e->getMessage());
        }
         
         
         if(!empty($data)) {
            $xmlData = simplexml_load_string($data);
            $search = array();
            $counter = 0;
            $dataArray =  $xmlData->lst[1]->lst[1]->lst->int;
            return count($dataArray);
         } 
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
            $xmlData = simplexml_load_string($data);
            //print_r($xmlData); //exit;
            $this->totalRecordsFound = $this->getfacetCount(); //$xmlData->result->attributes()->numFound;
    
            $search = array();
            $counter = 0;
            
            $dataArray =  $xmlData->lst[1]->lst[1]->lst->int;
    
            foreach($dataArray  as $story) {
                $keyword = (string) $story->attributes()->name;
                //echo $keyword;
                $this->columnsToShow['data'][$counter]['Keyword'] = $keyword;
                $this->columnsToShow['data'][$counter]['Count'] = (string) $story[0];
    
                $counter++;
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
                'rows'      =>  0,//$this->rows,
                'fl'        =>  $this->fl,
                //'qt'        =>  $this->qt,
                'wt'        =>  $this->wt,
                'explainOther'=> $this->explainOther,
                'hl.fl'     =>  $this->hl_fl,
                'facet'		=> 'true',
                'facet.field' => 'keyword',
                'facet.sort'  => 'true',
                'facet.offset' => $this->start,
                'facet.limit'  => $this->rows,
                'facet.mincount' => $this->facetCount,
                'facet.query' => $this->queryString,
                'q'         => trim($this->queryString,'+')
        );

         $solrVarsStr = '';
         foreach($solrVars as $key => $val) {
             $solrVarsStr .= $key.'='.trim($val).'&';
         }

         //this is final query
         $this->finalUrl =  rtrim($this->solrUrl.'select?'.$solrVarsStr,'&');
         
    }


    protected function buildQueryForKeyword() {
        if(trim($this->post['search_filter_keyword_select_ece']) != 'none' &&
              trim($this->post['search_filter_keyword_text']) != '') {
            $operator = trim($this->post['search_filter_keyword_select_ece']);
            $text = trim($this->post['search_filter_keyword_text']);
            
            
            switch($operator) {
            case 'equals':
                $this->queryArray[] = '+(keyword:'.$text.')'; //exact search
            break;

            case 'contains':
                $this->queryArray[] = '+(keyword:*'.$text.'*)'; //anywhere in between the text
            break;

            case 'excludes':
                $this->queryArray[] =  '-(keyword:*'.$text.'*)'; //does not contain
            break;

            default:
                $this->queryArray[] =  '+(keyword:'.$text.')'; //exact search
            break;
        }
            
        } 
       
    }

    
    protected function buildQueryForNoOfResults() {
         if(trim($this->post['search_filter_no_of_results_range']) != 'none' &&
              trim($this->post['search_filter_no_of_results_text']) != '') {
            $operator = trim($this->post['search_filter_no_of_results_range']);
            $text = (int) trim($this->post['search_filter_no_of_results_text']);
            
            
            switch($operator) {
            case 'less':
                $this->queryArray[] = '+(no_of_results:[* TO '.($text-1).'])'; //exact search
            break;

            case 'less_equal':
                $this->queryArray[] = '+(no_of_results:[* TO '.$text.'])'; //anywhere in between the text
            break;

            case 'greater':
                $this->queryArray[] =  '+(no_of_results:['.($text+1).' TO *])'; //does not contain
            break;
            
            case 'greater_equal':
                $this->queryArray[] =  '+(no_of_results:['.$text.' TO *])'; //does not contain
            break;
            
            case 'equal':
                $this->queryArray[] =  '+(no_of_results:'.$text.')'; //does not contain
            break;
            
            case 'not_equal':
                $this->queryArray[] =  '-(no_of_results:'.$text.')'; //does not contain
            break;

            default:
                $this->queryArray[] =  '+(no_of_results:'.$text.')'; //exact search
            break;
        }
            
        } 
        
    }
    
 
    protected function buildQueryForCity() {
        if(!empty($this->post['search_filter_city'])) {
        	if($this->post['search_filter_city'] == 'all') {
        		$this->queryArray[] = '+(city_id:*)';
        	} else {
        		$this->queryArray[] = '+(city_id:'.trim($this->post['search_filter_city']).')';
        	}
            
            //$this->columnsToShow[] = 'City';
        }
    }

    
    protected function buildQueryForMetacategory() {
        if(!empty($this->post['search_filter_metacat'])) {
        	if($this->post['search_filter_metacat'] == 'all') {
        		//if doing a global search based on city
        		if($this->post['search_filter_city'] == 'all') {
        			$this->queryArray[] = '+(global_metacategory_id:[* TO *])';
        		} else { //doing city specific search
        			$this->queryArray[] = '+(metacategory_id:[* TO *])';
        		}
        	} else {
        		//if doing a global search based on city
        		if($this->post['search_filter_city'] == 'all') {
        			$this->queryArray[] = '+(global_metacategory_id:'.trim($this->post['search_filter_metacat']).')';
        		} else {
        			//doing city specific search
        			$this->queryArray[] = '+(metacategory_id:'.trim($this->post['search_filter_metacat']).')';
        		}
        	}
            //$this->columnsToShow[] = 'Category';
        }
    }

    protected function buildQueryForSubcategory() {
         if(!empty($this->post['search_filter_subcat'])) {
         	if($this->post['search_filter_subcat'] == 'all') {
         		//if doing a global search based on city
        		if($this->post['search_filter_city'] == 'all') {
        			$this->queryArray[] = '+(global_subcategory_id:[* TO *])';
        		} else {
        			//doing city specific search
        			$this->queryArray[] = '+(subcategory_id:[* TO *])';
        		}
            	
         	} else {
         		//if doing a global search based on city
        		if($this->post['search_filter_city'] == 'all') {
        			$this->queryArray[] = '+(global_subcategory_id:'.trim($this->post['search_filter_subcat']).')';
        		} else {
        			//doing city specific search
        			$this->queryArray[] = '+(subcategory_id:'.trim($this->post['search_filter_subcat']).')';
        		}	
         	}
        }
    }

    protected function buildQueryForSearchdate() {
        if(!empty($this->post['search_filter_searchdate_from']) &&
                !empty($this->post['search_filter_searchdate_to'])) {
            $from = $this->ddmmyyyToTimestamp($this->post['search_filter_searchdate_from']);
            $to = $this->ddmmyyyToTimestamp($this->post['search_filter_searchdate_to'])+TO_DATE_INCREMENT;
            $this->queryArray[] = '+(search_date:['.$from.' TO '.$to.'])';
            //$this->columnsToShow[] = 'Create Date';
        }
        
    }


    protected function setColumns() {
        //$postedColumns = $this->post['search_columns'];
        //foreach($postedColumns as $key => $val) {
        //
        //    //few exception where we need to change the caption
        //    if($val == 'meta_category') $val = 'category';
        //    if($val == 'no_of_results') $val = 'search results';
        //    $this->columnsToShow['columns'][] = ucwords(strtolower(str_replace('_', ' ', $val)));
        //}
        $this->columnsToShow['columns'][] = 'Keyword';
        $this->columnsToShow['columns'][] = 'Count';
        
        //return $this->columnsToShow;
    }


    protected function ddmmyyyToTimestamp($date) {
        return strtotime($date);
    }

   
  

}

?>