<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


class Model_VdSolr {
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
    public $solrUrl         = SOLR_META_QUERY_VD;
    public $finalUrl        = '';
    public $queryArray      = array();

    public $columnsToShow = array();

    public $totalRecordsFound = '';
    public $records = '';
    public $separator = "|";
    public $sectionName = 'vdu';

    public function  __construct($postedParams) {
        $this->post = $postedParams;
    }

	
public function getSingleFieldFromVdu($fieldToReturn,$vduId) {
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
                    'q'         => 'id:'.$vduId
            );

             $solrVarsStr = '';
             foreach($solrVars as $key => $val) {
                 $solrVarsStr .= $key.'='.trim($val).'&';
             }

             //this is final query
             $this->finalUrl =  rtrim($this->solrUrl.'select?'.$solrVarsStr,'&');
             //echo $this->finalUrl;exit;
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



    
    
    
    

    
}