<?php

/*
 This will send out report as mail for:
 * -- core information of solr
 *      --> size of each core
 *      --> total records in each core
 *      -->   
 */

include(realpath(dirname(__FILE__))."/../../indexing_scripts/indexing_config.php");

$today = date("d-m-Y_H:i:s");
$past1 = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j")-1,date("Y"))));
$past2 = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j")-2,date("Y"))));
$now = strtotime(date('d-m-Y H:i:s',mktime(0,0,0,date("n"),date("j"),date("Y"))));

$params = "select?wt=json&rows=0";


$fileName = "System_Status_".$today.".html";
$filePath = INDEXING_LOG."/".$fileName;
$cores = array();

$mail = new Zend_Mail();


try {
    $obj = new Utility_SolrQueryAnalyzer(SOLR_META_QUERY_BASE."admin/cores?action=STATUS&wt=json",__FILE__.' at line '.__LINE__);
    $data = $obj->init();
    
    if(!empty($data)) {
        $xmlData = json_decode($data);
        
        $stories = $xmlData->status;
        $counter = 0;
        
        foreach ($stories as $story) { 
            $cores[$counter]["name"] = (string) $story->name;
            $cores[$counter]["size"] = (string) $story->index->size;
            $cores[$counter]["num_of_docs"] = (int) $story->index->numDocs;
            $url = SOLR_META_QUERY_BASE.$cores[$counter]["name"]."/".$params."&q=";
            $past1url="";
            $past2url="";
            switch($cores[$counter]["name"]) {
                case "ads":
                        $past1url = urlencode("remapped:[".$past1." TO ".$now."]");
                        $past2url = urlencode("remapped:[".$past2." TO ".$past1."]");
                    break;
                
                case "reply":                        
                        $past1url = urlencode("rpl_createdTime:[".$past1." TO ".$now."]");
                        $past2url = urlencode("rpl_createdTime:[".$past2." TO ".$past1."]");
                    break;
                case "alert":
                        $past1url = urlencode("creation_date:[".$past1." TO ".$now."]");
                        $past2url = urlencode("creation_date:[".$past2." TO ".$past1."]");
                    break;
                case "search":
                        $past1url = urlencode("search_date:[".$past1." TO ".$now."]");
                        $past2url = urlencode("search_date:[".$past2." TO ".$past1."]");
                    break;
                case "users":
                        $past1url = urlencode("last_updated_date:[".$past1." TO ".$now."]");
                        $past2url = urlencode("last_updated_date:[".$past2." TO ".$past1."]");
                    break;
                case "reply_with_ads":
                        $past1url = urlencode("rpl_createdTime:[".$past1." TO ".$now."]");
                        $past2url = urlencode("rpl_createdTime:[".$past2." TO ".$past1."]");
                    
                    
                    break;
                case "premiumads":
                        $past1url = urlencode("premiumads_ad_order_created_date:[".$past1." TO ".$now."]");
                        $past2url = urlencode("premiumads_ad_order_created_date:[".$past2." TO ".$past1."]");                        
                    break;
                
                case "bgs":
                        $past1url = urlencode("lead_date:[".date("Y-m-d\TH:i:s\Z",$past1)." TO ".date("Y-m-d\TH:i:s\Z",$now)."]");
                        $past2url = urlencode("lead_date:[".date("Y-m-d\TH:i:s\Z",$past2)." TO ".date("Y-m-d\TH:i:s\Z",$past1)."]");                        
                    break;
                
                default:
                    break;
            }
            
            try {
                $obj2 = new Utility_SolrQueryAnalyzer($url.$past1url,__FILE__.' at line '.__LINE__);

                $data2 = $obj2->init();

                if(!empty($data2)) {
                    $xmlData2 = json_decode($data2);
                    $cores[$counter]["count1"] = (int) $xmlData2->response->numFound;
                } 
                
                unset($obj2);unset($data2);unset($xmlData2);
                
                
                $obj2 = new Utility_SolrQueryAnalyzer($url.$past2url,__FILE__.' at line '.__LINE__);

                $data2 = $obj2->init();

                if(!empty($data2)) {
                    $xmlData2 = json_decode($data2);
                    $cores[$counter]["count2"] = (int) $xmlData2->response->numFound;
                } 
                
                
                
            
            } catch(Exception $e) {
                trigger_error($e->getMessage());
            }
            
            $counter++;
        }
        
                
        //write to file
        $h = fopen($filePath,"a+");
        //writing core data
        $text = preg_replace("/CORE_DATA/",createSolrStatsReport($cores),  createTemplate($cores));
        
        //writing count data
        
        
        
        fwrite($h,$text);
        fclose($h);
        
        //send email
        //create mime type
        $at = $mail->createAttachment(file_get_contents($filePath));
        $at->type        = Zend_Mime::TYPE_HTML;
        $at->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
        $at->encoding    = Zend_Mime::TYPE_HTML;
        $at->charset     = "UTF-8";
        $at->filename    = $fileName;
        //create mail

        $mail->setBodyHtml($text,"UTF-8");
        $mail->setFrom('vsingh@quikr.com', 'System');
        
        $mail->addCc("vsingh@quikr.com", "Vibhor Singh");
        if(APPLICATION_ENV == 'production') {
            $mail->addCc("rtitare@quikr.com", "Rahul Titare");
            $mail->addCc("ppatel@quikr.com", "Purvish Patel");
            $mail->addTo("stiwari@quikr.com", "Sudhir Tiwari");
            $mail->addTo("sumeer@quikr.com", "Sumeer Goyal");
        }
        $mail->setSubject('[Reporting Tool] - '.APPLICATION_ENV.' - System status report as of '.date("d-m-Y"));
        $mail->send();
        exit;
        
        //print_r($cores); exit;
    }
    
} catch (Exception $e) {
    trigger_error($e->getMessage());
}


function createSolrStatsReport($cores) {
    if(!empty($cores)) {
        $str = "<table border='1px solid black'>";
        $str .= "<tr><th>Core name</th><th>Docs indexed today </th><th>Docs indexed y'day</th><th>Total Docs in core</th><th>Size</th></tr>";
        foreach($cores as $k => $v) {
            $str .= "<tr><td align='center'>".$v["name"]."</td><td align='center'>".number_format($v["count1"])."</td><td align='center'>".number_format($v["count2"])."</td><td align='center'>".number_format($v["num_of_docs"])."</td><td align='center'>".$v["size"]."</td></tr>";
        }
        
        $str .= "</table>";
        return $str;
    }
    
}


function createTemplate($cores) {
    $str = "<html><head><title>Reporting Tool - Sytem Status - ".date("d-m-Y")."</title></head><body><div><h3>Reporting Tool - Indexing status</h3></div><div>CORE_DATA</div><br /><div>Below is the graph showing the counts of DB/Solr fetched during indexing <br />".createIndexingReport()."</div><p></p>If you are unable to view the report please download the attachment.</body></html>";
    return $str;
}

function createChart() {
   
    global $now;
    $db = Zend_Registry::get("authDbConnection");
    $select = $db->select();
    $select->from("data_history")->where("indexing_day='".date("Y-m-d",$now)."'");
    $stmt = $db->query($select);
    $result = $stmt->fetchAll();

    unset($select);
    //max solr_count
    $select = $db->select();
    $select->from(array("a"=>"data_history"),array("max_db_count" => "MAX(db_count)","max_solr_count" => "MAX(solr_count)"))->where("indexing_day='".date("Y-m-d",$now)."'");
    $stmt = $db->query($select);
    $countResult = $stmt->fetch();
    //print_r($result);print_r($countResult);
    $apiLink = "<img src=\"http://chart.googleapis.com/chart?";
    
    $barType="cht=bhg&chbh=a";
    $axis = "&chxt=x,y";
    $barSize="&chs=640x400";
    $barColors="&chco=4D89F9,C6D9FD";
    $chartTitle="&chtt=Reporting+Statistics+on+".date("Y-m-d",$now);
    $dbCount = "";
    $solrCount = "";
    $max = max(array($countResult["max_db_count"],$countResult["max_solr_count"]))+1000;
    $xAxisLabels = "&chxl=1:|";
    $dataStr = "&chd=t:";
    $axisRange = "&chxr=0,0,".$max.",200000";
    $scale = "&chds=0,".$max.",0,".$max;
    $legends = "&chdl=DB|Solr";
    foreach($result as $k => $v) {
        $xAxisLabels .= $v["core"]."_".$v["indexing_flag"]."|";
        $dbCount .= $v["db_count"].",";
        $solrCount .= $v["solr_count"].",";
                
    }
    $dataStr .= trim($dbCount, ",")."|".trim($solrCount, ",");
    $xAxisLabels = trim($xAxisLabels, "|");
    $finalLink = $apiLink.$barType.$axis.$barSize.$barColors.$xAxisLabels.$dataStr.$axisRange.$scale.$legends.$chartTitle."\" width=\"640\" height=\"220\" alt=\"Reporting Statistics\" />";
    return $finalLink;

}

function createIndexingReport() {
    global $now;
    $db = Zend_Registry::get("authDbConnection");
    $select = $db->select();
    $select->from("data_history")->where("indexing_day='".date("Y-m-d",$now)."'");
    $stmt = $db->query($select);
    $result = $stmt->fetchAll();
    $str = "<br /><table border='1px solid black'>";
    $str .= "<tr><th>Indexing script</th><th>Fetched from DB</th><th>Indexed in Solr</th></tr>";
    
    foreach($result as $k => $v) {
        $str .= "<tr><td align='center'>".$v["core"]."_".$v["indexing_flag"]."</td><td align='center'>".number_format($v["db_count"])."</td><td align='center'>".number_format($v["solr_count"])."</td></tr>";
    }
    $str .= "</table>";
    return $str;
    
}