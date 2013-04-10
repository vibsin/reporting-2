<?php
/* 
 * This class will be used as plugin component
 * This will analyze the Solr queries (GET/POST) >>> compute the severity of the load >>> enter the statictics into DB
 *
 * This class uses curl to get/post request from Solr. Please check server configurations for Curl
 *
 * A configurable variable ENABLE_SOLR_LOGGER is defined in config.ini.php file to set a flag for using this class.
 * check usage on model\Category.class.php line 567
 *
 *
 * TODO:
 *
 * 1) Change the order of parameter in constructor -- done
 * 2) change the object call in affected file ---  done
 * 2) Change logic in constructor for checking METHOD - done
 * 3) two step process to check if Solr is working:--done

 */

class Utility_SolrQueryAnalyzer {


    protected $isTestingEnabled = false;
    /**
     * This will hold the curl reponse
     * @var <xml>
     */
    protected $curlResponse;

    /**
     * The solr query url to be fetched. provide this in constructor
     * @var <string>
     */
    protected $url;

    /**
     * The curl array in curl_getinfo()
     * @var <array>
     */
    protected $curlInfo;

    /**
     * Handle for curl
     * @var <resource>
     */
    protected $curlHandle;

    /**
     * Default method for curl request
     * @var <string>
     */
    protected $method;

    /**
     * THe time when execution of this script began
     * @var <date time>
     */
    protected $masterStartTime;

    /**
     * The time when execution ended
     * @var <date time>
     */
    protected $masterEndTime;

    /**
     * The optimal number of seconds the query must respond within.
     * @var <int/float>
     */
    protected $desiredResponseTimeLimit = 30;

    /**
     * The severity levels (HIGH,NORMAL,LOW)
     * @var <string>
     */
    protected $severityLevel;

    /**
     * The formatted report of the query response in HTML
     * @var <str>
     */
    protected $reportStr;

    /**
     * The fields from curl info taht we want in report
     * @var <array>
     */
    protected $requiredOpts = array('url','http_code','total_time','size_download','speed_download','size_upload','speed_upload');


    /**
     * The script file which invokes this class
     * @var <string>
     */
    protected $scriptFilePath;

    const HIGH_SEVERITY = 'High';
    const NORMAL_SEVERITY = 'Normal';
    const LOW_SEVERITY = 'Low';
    const BYTES_TO_MB_FACTOR = 1048576; //conversion factor from bytes to MB
    const PRECISION = 2; 
    const TD_STYLE = 'text-align:center;';
    const TH_STYLE = 'background:#9FC253;';
    
	/**
	 * BEGIN_LOG_WHEN_SEVERITY_LEVEL will determine at what severity level the loggin process should begin
	 * currently set to HIGH_SEVERITY, you can add multiple option in the array
	 *
	 */
    protected $allowedSeverityLevelsForLogging = array(self::HIGH_SEVERITY);
    
    public $writeToFile = false;
    protected $outFile;
    
    
    public $isLoggingEnabled = false;

    /**
     * The following are mandatory
     * @param <string> $url
     * @param <string> $method
     * @param <string> $scriptFilePath
     */
    public function  __construct($url,$scriptFilePath,$method='GET') {
        if(empty($url) && strlen($url) == 0) {
            throw new Exception('No url provided in constructor:'.$url);
        } else {
            $this->url = trim($url);
        }

        if(!empty($scriptFilePath)) {
            $fileLocation = trim(preg_replace('/at (.*)/', '', $scriptFilePath));
            if(file_exists($fileLocation)) {
                $this->scriptFilePath = $scriptFilePath;
            } else {
                throw new Exception('No such file exists:'.$fileLocation);
            }
        } else {
            throw new Exception('No script file provided in constructor:'.$scriptFilePath);
        }

        if(!in_array($method, array('GET','POST'))) {
            throw new Exception('The specified HTTP method is incorrect:'.$method);
        } else {
            $this->method = trim($method);
        }
        
        $this->outFile = "/tmp/curl_out".microtime(true).".txt";

        //$this->pingSolr();
        
        //$this->isTestingEnabled = true;
        
    }


    /**
     * call this in constructor before going further
     * Ping the solr server to check if its working
     * if(wroking) {
     *  if (working but no response)
     * }
     */
    protected function pingSolr() {

        /**
         * first clean the url :
         * e.g. http://192.168.1.7:8983/solr/ads/select?q=%28status...
            becomes http://127.0.0.1:8983/solr/ads/select?q=*:*&rows=0&start=0
         *
         * We will this url to ping and check
         *
         * 24-06-2011 --vibhor
         * removed earlier preg_replace('/q=(.*)/','', $this->url) as not all query string started from 'q=...'
         * e.g.http://192.168.1.7:8983/solr/ads/select?fq=%7B%21tag....check fq=..here
         * 
         * The new pattern will replace evrything in query string
         */

        $url = preg_replace('/\?(.*)/','', $this->url).'?q=*:*&rows=0&start=0'; 
        $ch = curl_init($url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch,CURLOPT_HEADER, true);

        $result = curl_exec($ch);
        $info = curl_getinfo($ch);

        /**
         * curl will always return an array, so we chekc if http code is not 0,
         * if 0 then curl was not able to communicate i.e. server is down
         */

        if(!empty($info) && $info['http_code'] != '0') {

         /**
         * now we check if we are getting 200 http code which means server is running
          * 
         */
            if($info['http_code'] == '200') {

             /**
             * if Solr is running but not sending any response..hung up..showing blank page
             */
                if(!empty($result)) { 
                    return true;
                } else {
                    throw new Exception("Solr is running but not responding <hr>".$result."</hr>");
                }
            } else {
                throw new Exception("Solr server problem: http code=". $info['http_code'].'<hr>'.$result.'</hr>');
            }

        } else {
            throw new Exception("Solr server not running. Url:". $url);
        }


    }





    /**
     * The entry point of this class. After the class vars are set in constrcutor, this method is called which
     * -- set the minimum required curl options
     * -- sends requests
     * -- parses the response
     * -- checks severity level of the response time
     * -- sends email if severity level is HIGH
     * -- adds the statistics in DB
     * @return <xml>
     */
    public function init() {

        $this->masterStartTime = date('Y-m-d H:i:s');
        $this->curlHandle = curl_init(); //echo '1';
        $this->setCurlOpts();//echo '2';
        $this->sendRequest();//echo '3';
        $this->parseCurlResponse();//echo '4';
        $this->closeCurl();//echo '5';
        
        //init logging
        /**
         * sending email and adding to DB functions are called in initLogs() now
         */
        if($this->isLoggingEnabled) {
            $this->initLogs();
        }
        
        //finally return result
        if($this->writeToFile == true) { 
            return $this->getLineCountFromOutputFile();
        } else { 
            return $this->getQueryResult();
        }
    }


    public function  __destruct() {
        ;
    }


    /**
     * This function will set the curl options to GET/POST any request. For now,
     * only POST method is not entirely implemented (raw post, post fields).
     */
    protected function setCurlOpts() {
        /**
         * CURLOPT_FORBID_REUSE 	TRUE to force the connection to explicitly close when it has finished processing, and not be pooled for reuse.
         * CURLOPT_FRESH_CONNECT 	TRUE to force the use of a new connection instead of a cached one.
         * CURLOPT_CONNECTTIMEOUT 	The number of seconds to wait while trying to connect. Use 0 to wait indefinitely.
         * CURLOPT_TIMEOUT 	        The maximum number of seconds to allow cURL functions to execute.
         */

        if ($this->method == "POST") {
                $url = preg_replace ( '/\?(.*)/', '', $this->url );
                $data = preg_replace ( '/(.*)\?/', '', $this->url );
                //set the URL of the curl request
                curl_setopt ( $this->curlHandle, CURLOPT_URL, $url );
                //set the method
                curl_setopt ( $this->curlHandle, CURLOPT_POST, 1 );
                curl_setopt ( $this->curlHandle, CURLOPT_POSTFIELDS, $data );
        } else {
                //set the URL of the curl request
                curl_setopt ( $this->curlHandle, CURLOPT_URL, $this->url );
                //set the method
                curl_setopt ( $this->curlHandle, CURLOPT_HTTPGET, $this->url );
        }


        //set whether to allow Header info in response
        curl_setopt($this->curlHandle,CURLOPT_HEADER, false);

        //allow to return the output as string
        curl_setopt($this->curlHandle,CURLOPT_RETURNTRANSFER, 1);

        //The maximum number of miliseconds to allow cURL functions to execute.
        curl_setopt($this->curlHandle,CURLOPT_TIMEOUT_MS, 360000);

        curl_setopt($this->curlHandle, CURLOPT_CONNECTTIMEOUT_MS, 360000);

        curl_setopt($this->curlHandle,CURLOPT_FORBID_REUSE, true);
        
        if($this->writeToFile == true) { //echo $this->outFile;exit;
            $fp = fopen($this->outFile,"w");
            curl_setopt($this->curlHandle,CURLOPT_FILE, $fp);
        }
    }



    /**
     * Send a request to Solr server
     */
    protected  function sendRequest() {
        if($this->writeToFile == true) {
            curl_exec($this->curlHandle);
            $this->masterEndTime = date('Y-m-d H:i:s');
        } else {
            $result = curl_exec($this->curlHandle);
            $this->curlResponse = $result;
            if(isset ($this->curlResponse)) {
                $this->masterEndTime = date('Y-m-d H:i:s');
            } else {
                throw new Exception('No response retured from query');
            }
        }
    }

    
    /**
     * Parse the reponse of the solr query and set our class var
     */
    protected function parseCurlResponse() {
        $this->curlInfo = curl_getinfo($this->curlHandle);
        //print_r($this->curlInfo);
        if($this->curlInfo['http_code'] == '200') {
            return;
        } else {
            throw new Exception("Request Query failed with HTTP code:".$this->curlInfo['http_code']);
        }
    }

    /**
     * Shutdown curl handle
     */
    protected function closeCurl() {
        curl_close($this->curlHandle);        
    }

    /**
     * return reponse on success
     */
    protected function getQueryResult() {
        if(!empty($this->curlResponse)) {//exit;
            //print_r($this->curlResponse);
            return $this->curlResponse;
        } else {
            throw new Exception("Curl response not set");
        }
    }

    /**
     * Start logging 
     */
    protected function initLogs() {
    
        if(!empty($this->curlInfo)) {
            $lCurlInfo = $this->curlInfo;

            //compute the severity of the response
            $this->computeSeverity();
            //only when severity is HIGH start sending email process
            if(in_array($this->severityLevel,$this->allowedSeverityLevelsForLogging)) { 
                $this->addToDB();
                
                $this->prepareReportColumns(array_keys($lCurlInfo), $lCurlInfo);
                
                $this->sendMail();
                
                
            }
        } else {
            throw new Exception("Curl information is not set");
        }

    }


    /**
     * Format the response of curl in HTML
     * @param <type> $colsArr
     * @param <type> $dataArr
     */
    protected function prepareReportColumns($colsArr, $dataArr) {
        if(!empty($colsArr)) {
            $str = '<table border="1px" style="padding:1px;" cellpadding="2px"><tr>';
            $str .= '<th style="'.self::TH_STYLE.'">Severity Level</th>';
            $str .= '<th style="'.self::TH_STYLE.'">Calling script</th>';
            $str .= '<th style="'.self::TH_STYLE.'">Entry time</th>';
            $str .= '<th style="'.self::TH_STYLE.'">Complete time</th>';

            foreach($colsArr as $key => $val) {
                if(in_array($val, $this->requiredOpts)) {
                    switch($val) {
                    case 'size_download':
                    case 'speed_download':
                    case 'size_upload':
                    case 'speed_upload':
                        $str .= '<th style="'.self::TH_STYLE.'">'.ucfirst(str_replace('_',' ',strtolower($val))).' (in MB)</th>';
                        break;

                    default:
                        $str .= '<th style="'.self::TH_STYLE.'">'.ucfirst(str_replace('_',' ',strtolower($val))).'</th>';
                        break;
                    }


                    //$str .= '<th style="background:#FF9900;">'.ucfirst(str_replace('_',' ',strtolower($val))).'</th>';
                }
            }
            

            $str .= '</tr><tr>';
            //$this->computeSeverity($dataArr['total_time']);
            $str .= '<td style="'.self::TD_STYLE.'">'.$this->severityLevel.'</td>';
            
            //adding the IP address also
            $str .= '<td style="'.self::TD_STYLE.'">'.$this->scriptFilePath.'<br /> Client IP:'.$_SERVER['REMOTE_ADDR'].' <br />Server IP:'.$_SERVER['SERVER_ADDR'].'</td>';
            $str .= '<td style="'.self::TD_STYLE.'">'.$this->masterStartTime.'</td>';
            $str .= '<td style="'.self::TD_STYLE.'">'.$this->masterEndTime.'</td>';

            if(!empty($dataArr)) {
                foreach($dataArr as $key => $val) {
                    if(in_array($key, $this->requiredOpts)) {
                        switch($key) {
                        case 'size_download':
                        case 'speed_download':
                        case 'size_upload':
                        case 'speed_upload':
                            $str .= '<td style="'.self::TD_STYLE.'">'.$this->convertBytesToMB($val).'</td>';
                            break;

                        default:
                            $str .= '<td style="text-align:center">'.$val.'</td>';
                            break;
                        }
                    }
                }
            } else {
                throw new Exception('Empty data array provided');
            }

            $str .= '</tr></table>';

            $this->reportStr .= $str; //echo $this->reportStr;

        } else {
            throw new Exception("Empty columns provided");
        }

    }

    /**
     *
     * @param <type> $totalTime
     */
    protected function computeSeverity() {
        $totalTime = $this->curlInfo['total_time'];
        if(isset ($totalTime)) {
            if($totalTime > $this->desiredResponseTimeLimit) {
                $this->severityLevel = self::HIGH_SEVERITY;
            } else if($totalTime <= $this->desiredResponseTimeLimit &&
                    $totalTime > ($this->desiredResponseTimeLimit/2)) {
                $this->severityLevel = self::NORMAL_SEVERITY;
            } else {
                $this->severityLevel = self::LOW_SEVERITY;
            }
        } else {
            throw new Exception('Total time not set');
        }

    }
    
    //update with Zend_mail --done
    
    protected function sendMail() {        
        //using Zend_mail
        $mail = new Zend_Mail();
        $mail->setBodyHtml($this->reportStr);
        $mail->setFrom(SOLR_LOGGING_FROM_EMAIL_ID, SOLR_LOGGING_FROM_EMAIL_NAME);
        $mail->addTo(SOLR_LOGGING_TO_EMAIL_ID, SOLR_LOGGING_TO_EMAIL_NAME);
        $mail->setSubject("[Reporting Tool]: ".  APPLICATION_ENV." - Solr query report - ".date("d-F-Y").'-'.  uniqid());
        $mail->send();
        
    }


    protected function addToDB() {
       
        
        $sql = 'INSERT INTO solr_query_log (severity_level,calling_script,stack_trace,php_mem_usage,php_peak_usage,entry_time,complete_time,
            url,http_code,total_time,size_download,speed_download,size_upload,speed_upload)
            VALUES (:severity_level,:calling_script,:stack_trace,:php_mem_usage,:php_peak_usage,:entry_time,:complete_time,
            :url,:http_code,:total_time,:size_download,:speed_download,:size_upload,:speed_upload)';
        
        $r = new Zend_Db_Statement_Pdo(Zend_Registry::get('authDbConnection'), $sql);
        

        //$r = $objStmt->prepare($sql);
        $s = ""; //json_encode(debug_backtrace());
        $r->bindParam(':severity_level',$this->severityLevel);
        $r->bindParam(':calling_script',$this->scriptFilePath);
        $r->bindParam(':stack_trace', $s);
        $r->bindParam(':php_mem_usage',$this->convertBytesToMB(memory_get_usage()));
        $r->bindParam(':php_peak_usage',$this->convertBytesToMB(memory_get_peak_usage()));
        $r->bindParam(':entry_time',$this->masterStartTime);
        $r->bindParam(':complete_time',$this->masterEndTime);
        $r->bindParam(':url',$this->curlInfo['url']);
        $r->bindParam(':http_code',$this->curlInfo['http_code']);
        $r->bindParam(':total_time',$this->curlInfo['total_time']);
        $r->bindParam(':size_download',$this->convertBytesToMB($this->curlInfo['size_download']));
        $r->bindParam(':speed_download',$this->convertBytesToMB($this->curlInfo['speed_download']));
        $r->bindParam(':size_upload',$this->convertBytesToMB($this->curlInfo['size_upload']));
        $r->bindParam(':speed_upload',$this->convertBytesToMB($this->curlInfo['speed_upload']));    
        
        if(!$r->execute()) {
            throw new Exception("Unable to insert record in DB");
        }
        

    }


    protected function convertBytesToMB($val) {
        return number_format(($val/self::BYTES_TO_MB_FACTOR),self::PRECISION);
    }

    protected function checkResources() {

    }

    protected function timeNow() {
        return time();
    }

    protected function mailFormat() {
        
    }
    
    protected function getLineCountFromOutputFile() {
        $cmd = "cat ".$this->outFile." | wc -l;";
        $count = shell_exec($cmd) - 26;
        unlink($this->outFile);
        return (int) $count;
        //if($count == "") return 0; else return $count-26;
    }
    
    

    
    

}