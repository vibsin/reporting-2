<?php

class Model_Solr {
    
    public $usersummarize = array();
    public $max_results_per_page = MAX_RESULTS_PER_PAGE;
    public $user_summary_solar_limit = USER_SUMMARY_SOLR_LIMIT;
    public $solr_query = '';
    public $start = 0;
    
    public function querySolr($options){
		if(!$options["rows"])$options["rows"]=20;
		if(!$options["start"])$options["start"]=0;
		
		$options["debugQuery"]="false";
		$q=array();
		
		foreach($options as $key=>$value){
			$q[]=$key.'='.urlencode($value);
		}
		$this->solr_query= SOLR_META_QUERY_USERS.'select?'.join('&',$q);
		$data=file_get_contents($this->solr_query);
		
		if($data){
			return simplexml_load_string($data);		
		}	
		return $data;		
	}
        
       public function getUserDetailsFromSolr($params)
       {  error_reporting(1);
         if(!is_array($params) || count($params) < 0)
          {
              return false;
          }
          
          if(!is_array($params['user_columns']) || count($params['user_columns']) < 0)
           {           
              return 'InvalidColumn';
           }
               
           
          if($params['q'] == '')
           {               
              $params['q'] .= 'createdtime:['.trim(strtotime($params['user_filter_regdate_from'])).' TO '.trim(strtotime($params['user_filter_regdate_to'])).']';             
           }
           
           if($params['user_filter_lastlogin_from'] != '')
            {
               $from = date("Y-m-d",strtotime($params['user_filter_lastlogin_from']));
               $to = date("Y-m-d",strtotime($params['user_filter_lastlogin_to']));  
               $params['q'] .= '+ lastlogintime:['.trim($from).' TO '.trim($to).']';
            }
            
           $filter = array();
           if($params['user_filter_email_select_ece'] != '')
            {
              if($params['user_filter_email_select_ece'] == 'equals')
              {
               $params['q'] .= '+ basicinfo:(e\:'.trim($params[user_filter_email_text]).')';
              }
              
              if($params['user_filter_email_select_ece'] == 'contains')
              { 
                $params['q'] .= '+ basicinfo:(*'.trim($params[user_filter_email_text]).'*)';
              }
              
             if($params['user_filter_email_select_ece'] == 'excludes')
              { 
                $params['q'] .= '- (basicinfo:(e\:'.trim($params[user_filter_email_text]).'))';
              } 
            }
            
            if($params['user_filter_registered'] != '')
            {
               if($params['user_filter_registered'] == 'yes')
                {
                    $status = 1;
                }
                else
                {
                     $status = 0;
                }
              $params['q'] .= "+ basicinfo:(regusr\:".$status.")";
            }
            
            if($params['user_filter_bulk_upload'] != '')
            {
               if($params['user_filter_bulk_upload'] == 'yes')
                {
                    $bstatus = 1;
                }
                else
                {
                     $bstatus = 0;
                }
              $params['q'] .= "+ secondaryinfo:(bulk\:".$bstatus.")";
            }
            
           
            if($params['user_filter_firstname'][0]!= '')
            {               
                if($params['user_filter_firstname'][0] !='' && $params['user_filter_firstname'][1] !='')
                {
                 $filter['firstname'] = 'all';
                }
                else
                {
                 $filter['firstname'] = $params['user_filter_firstname'][0];
                }
            }
            
            
            if($params['user_filter_lastname'][0]!= '')
            {               
                if($params['user_filter_lastname'][0] !='' && $params['user_filter_lastname'][1] !='')
                {
                 $filter['lastname'] = 'all';
                }
                else
                {
                 $filter['lastname'] = $params['user_filter_lastname'][0];
                }
            }
            
            
            if($params['user_filter_mobile'][0]!= '')
            {
                if($params['user_filter_mobile'][0] !='' && $params['user_filter_mobile'][1] !='')
                {
                 $filter['mobile'] = 'all';
                }
                else
                {
                 $filter['mobile'] = $params['user_filter_mobile'][0];
                }
            }
            
             
            if($params['user_filter_city']!= '')
            {
                if($params['user_filter_city'] =='all')
                {
                 $filter['city'] = 'all';
                }
                elseif($params['user_filter_city'] =='none')
                {
                 $filter['city'] = 'none';
                }
                else
                {
                 $filter['city'] = $params['user_filter_city'];
                }
            }
        
            if($_POST['user_summarize_intervals_of'][0] != '')
            {
                $params['limit'] = $this->user_summary_solar_limit;
                $options = array('q'=> $params['q'],'fl'=>'id,createdtime,lastlogintime','start'=>$this->start,'rows'=> $params['limit']);
            }
            elseif($params['limit'] == '')
            {
                 if($params['export_csv'] == 'yes')
                 {
                   $params['limit'] = FILE_EXPORT_SOLR_LIMIT; 
                 }
                 else
                 {
                  $params['limit'] = $this->max_results_per_page;
                 }
                $options = array('q'=> $params['q'],'start'=>$this->start,'rows'=> $params['limit']);
            }
        
        $userXmlObj = $this->querySolr($options);
        
        if($_POST['user_summarize_intervals_of'][0] == '')
        {  
         $usersArrayObj = $this->getUserFromSolr($userXmlObj,$filter);
        }
        else
        { 
        $usersArrayObj = $this->getSummarizeUserFromSolr($userXmlObj,$filter);
        }

       if($_POST['show_summarize'] != 'on')
        {   
        if(is_array($usersArrayObj[items]))
         { 
            foreach($usersArrayObj[items] as $val)
            {
              $user_ids[] = $val->id;
              $email_ids[$val->id] = $val->email;
            }
            // concat user ids with OR to make a solr query
            if(is_array($user_ids))
            {
                $user_ids_with_or = implode(" OR ",$user_ids);
            }
                  
            if(is_array($user_ids))
              {
                foreach($user_ids as $k =>$v)
                 {
                    $all_user_replies = $this->getNumberOfRepliesByUser($user_ids_with_or);
                    $usersArrayObj['items'][$k]->reply_count = $all_user_replies[$v];
                    $all_user_ads = $this->getNumberAdsPostedByUser($user_ids_with_or);
                    $usersArrayObj['items'][$k]->ads_count = $all_user_ads[$v];
                    $all_user_alerts = $this->getNumberOfAlertsByUser($user_ids_with_or);
                    $usersArrayObj['items'][$k]->alert_count = $all_user_alerts[$v];
                 }  
              } 
              
             if(isset($_POST['user_filter_no_of_ads_range']) && $_POST['user_filter_no_of_ads_text'] !='')
              {
                   $filter_param = 'ads';
                   $filter['ad_range'] = trim($_POST['user_filter_no_of_ads_range']);
                   $filter['ad_value'] = trim($_POST['user_filter_no_of_ads_text']);
                   $usersArrayObj['items'] = $this->userAdReplyAlertCountFilteration($usersArrayObj['items'],$filter,$filter_param);
                   $usersArrayObj['count'] = count($usersArrayObj['items']);
               }
               
              if(isset($_POST['user_filter_no_of_replies_range']) && $_POST['user_filter_no_of_replies_text'] !='')
              {
                   $filter_param = 'reply';
                   $filter['reply_range'] = trim($_POST['user_filter_no_of_replies_range']);
                   $filter['reply_value'] = trim($_POST['user_filter_no_of_ads_text']);
                   $usersArrayObj['items'] = $this->userAdReplyAlertCountFilteration($usersArrayObj['items'],$filter,$filter_param);
                   $usersArrayObj['count'] = count($usersArrayObj['items']);
               }
               
              if(isset($_POST['user_filter_no_of_alerts_range']) && $_POST['user_filter_no_of_alerts_text'] !='')
              {
                   $filter_param = 'alert';
                   $filter['alert_range'] = trim($_POST['user_filter_no_of_alerts_range']);
                   $filter['alert_value'] = trim($_POST['user_filter_no_of_alerts_text']);
                   $usersArrayObj['items'] = $this->userAdReplyAlertCountFilteration($usersArrayObj['items'],$filter,$filter_param);
                   $usersArrayObj['count'] = count($usersArrayObj['items']);
               }
          }   
            
         }
         //echo "Initial".memory_get_usage();
         //print_r($usersArrayObj);
         if($params['export_csv'] == 'yes')
         {
            $str = '';
            $header_columns = $params['user_columns'];
            if($_POST[header_set] != 'yes')
            {
            $str .= 'SrNo.'.','.implode(",",$header_columns)."\n";
            }
            $length_col = count($header_columns);
            if(isset($_POST['counter']))
            {
                $f = $_POST['counter'];
            }
            else
            {
                $f=0;
            }
            foreach($usersArrayObj['items'] as $k_val=>$v_val)
            {
             $f++;
             $str .= $f.",";
             for($k=0; $k < $length_col;$k++)
             {
                 $c = trim($header_columns[$k]);
                 $str .= $v_val->$c.",";
                 if($k == ($length_col-1))
                 {
                    $str .= "\n";
                 }
                 
             }
            
            }
            $_POST['header_set'] = 'yes';
            $_POST['counter'] = $f;
            file_put_contents(BASE_PATH_CSV.'/report.csv',$str,FILE_APPEND);
            unset($usersArrayObj);
            unset($params);
            unset($str);
            flush();
            //echo "after".memory_get_usage();
            sleep(2);
            //$this->csvReportFacet($str);
            if(($this->start+FILE_EXPORT_SOLR_LIMIT) < FILE_EXPORT_LIMIT)
            {
                $this->start = $this->start+FILE_EXPORT_SOLR_LIMIT;
                //echo "now the start".$this->start;
                $this->getUserDetailsFromSolr($_POST);
            }
            else
            {
                // return $usersArrayObj;
                // echo "now the end".$this->start;
              //   exit;
            }
            
         }
         else
         {
            // print_r($usersArrayObj);
             return $usersArrayObj;
         }
         
         
       }
       
       public function getNumberOfRepliesByUser($user_ids)
       { 
        $params['facet_query'] = 'rpl_user_id:'.'('.trim($user_ids).')';
        $params['facet_limit'] = count(explode(" OR ",$user_ids));
        $params['facet_url'] = SOLR_META_QUERY_REPLIES.'select?';
        $original_user_ids = explode(" OR ",$user_ids);
        $xml=$this->getfacetResults($params);
        $facet_results = $xml->lst[1]->lst[1]->lst;
        $facet_search = array();
        for($x=0; $x < $facet_limit; $x++)
        {
             $id = $facet_results->int[$x]->attributes()->name;
             $val = $facet_results->int[$x];
             if(trim($id) != 0)
             {
              $facet_search[trim($id)] = trim($val);
             }
        }
        // get the original ids value
         foreach($original_user_ids as $f_id=>$f_val)
         {
           if($facet_search[$f_val] == 0)
           {
            $final_val[$f_val] = 0;
           }
           else
           {
            $final_val[$f_val] = $facet_search[$f_val];
           }
         }
         
         if(is_array($final_val))
         {
            return $final_val;  
         }
         else
         {
            return false;
         }        
		
       }
       
       /* common function which returns results from solr for facet search */
       public function getfacetResults($params)
       {
            $client = new Zend_Http_Client();
            $client->setUri($params['facet_url']);
            $client->setMethod(Zend_Http_Client::POST);
            $client->setHeaders('Content-Type','text/xml; charset=utf-8');
            $client->setHeaders('Content-Length',strlen($params['facet_query']));
            $client->setParameterPost('q', trim($params['facet_query']));
            $client->setParameterPost('fl', 'rpl_user_id');
            $client->setParameterPost('facet', 'true');
            $client->setParameterPost('facet.field', 'rpl_user_id');
            $client->setParameterPost('facet.limit', $params['facet_limit']);
            $responseXml = $client->request()->getBody();
            unset($client);
            $xml = simplexml_load_string($responseXml);
            if(is_array($xml))
            {
                return $xml;
            }
            else
            {
                return false;
            }
       }
       
       
       public function getNumberAdsPostedByUser($user_ids)
       {
	$params['facet_query'] = 'poster_id:'.'('.trim($user_ids).')';
        $params['facet_limit'] = count(explode(" OR ",$user_ids));
        $params['facet_url'] = SOLR_META_QUERY_ADS.'select?';
        $original_user_ids = explode(" OR ",$user_ids);
        $xml=$this->getfacetResults($params);
        $facet_results = $xml->lst[1]->lst[1]->lst;
        $facet_search = array();
        for($x=0; $x < $facet_limit; $x++)
        {
             $id = $facet_results->int[$x]->attributes()->name;
             $val = $facet_results->int[$x];
             if(trim($id) != 0)
             {
              $facet_search[trim($id)] = trim($val);
             }
        }
        // get the original ids value
         foreach($original_user_ids as $f_id=>$f_val)
         {
           if($facet_search[$f_val] == 0)
           {
            $final_val[$f_val] = 0;
           }
           else
           {
            $final_val[$f_val] = $facet_search[$f_val];
           }
         }
         
         if(is_array($final_val))
         {
            return $final_val;  
         }
         else
         {
            return false;
         }        
       }
      
       public function getNumberOfAlertsByUser($user_ids)
       {
	$params['facet_query'] = 'user_id:'.'('.trim($user_ids).')';
        $params['facet_limit'] = count(explode(" OR ",$user_ids));
        $params['facet_url'] = SOLR_META_QUERY_ALERTS.'select?';
        $original_user_ids = explode(" OR ",$user_ids);
        $xml=$this->getfacetResults($params);
        $facet_results = $xml->lst[1]->lst[1]->lst;
        $facet_search = array();
        for($x=0; $x < $facet_limit; $x++)
        {
             $id = $facet_results->int[$x]->attributes()->name;
             $val = $facet_results->int[$x];
             if(trim($id) != 0)
             {
              $facet_search[trim($id)] = trim($val);
             }
        }
        // get the original ids value
         foreach($original_user_ids as $f_id=>$f_val)
         {
           if($facet_search[$f_val] == 0)
           {
            $final_val[$f_val] = 0;
           }
           else
           {
            $final_val[$f_val] = $facet_search[$f_val];
           }
         }
         
         if(is_array($final_val))
         {
            return $final_val;  
         }
         else
         {
            return false;
         }        
       }
       
       public function getSummarizeUserFromSolr($xml,$filter){
          $i = 0;
          $results = array();
          foreach ($xml->result->doc as $story) {
              foreach ($story as $item) {
		$name = $item->attributes()->name;
		$value = (string)$item;
                switch($name){
		    case "id":
			    $this->usersummarize[$i]['id']=$value;
                            break;
                    case "createdtime":
			    $this->usersummarize[$i]['createdTime']=date("Y-m-d",$value);
			    break;
                    case "lastlogintime":
			    $this->usersummarize[$i]['lastlogintime']=date("Y-m-d",strtotime($value));
			    break;    
                        } // EOF switch                       
                 } // EOF foreach
                 $i++;
            } // EOF foreach($xml)
            if($this->usersummarize)
            {
                 $results['items'] = $this->usersummarize;
                return $results;
            }
            else
            {
                return false;
            }
            
       } // EOF function
       
       public function getUserFromSolr($xml,$filter){
		if(!$xml) return;
		$results = array();
		try{			
			$result = $xml->result;		
			$attrs = $result->attributes();
			$count = $result->attributes()->numFound;
			$results['count'] =  (string)$count;

			$users = array();
			foreach ($xml->result->doc as $story) {
				$user = new User();
				foreach ($story as $item) {
					$name = $item->attributes()->name;
					$value = (string)$item;
					switch($name){
						case "id":
							$user->id=$value;
							break;						
						case "createdtime":
							$user->createdTime=date("Y-m-d",$value);
							break;
						case "updatedtime":
							//$user->modifiedTime=$value;
							break;
                                                case "lastlogintime":
							$user->lastlogintime=date("Y-m-d",strtotime($value));
							break;
						case "basicinfo":
							preg_match('/e:(.*?)\|/',$value,$match);
							$user->email=$match[1];
							
							preg_match('/m:(\d+)\|/',$value,$match);
							$user->mobile=$match[1];
							
							preg_match('/nick:(.*?)\|/',$value,$match);
							//$user->nickname=$match[1];
							
							preg_match('/regusr:(\d)/',$value,$match);
							$user->registeredUser=$match[1];
							break;
						case "secondaryinfo":
							preg_match('/fn:(.*?)\|/',$value,$match);
							$user->firstName=$match[1];
							
							preg_match('/ln:(.*?)\|/',$value,$match);
							$user->lastName=$match[1];
							
							preg_match('/city:(.*?)\|/',$value,$match);
							$user->city=$match[1];
							
							preg_match('/cid:(\d+)\|/',$value,$match);
							//$user->manageAreaIds=$match[1];
							
							preg_match('/bulk:(\d)/',$value,$match);
							$user->bulkupload=$match[1];							
							break;					
						
					}									
				}
                                $users[] = $user; 
                        }
                          if($filter['firstname'] !='' || $filter['lastname'] != '' || $filter['mobile'] != '' || $filter['city'] !='')
                          {
                            //$userFn = $this->userFirstNamesFilteration($users,$filter);
                            //$userln = $this->userLastNamesFilteration($userFn,$filter);
                             //$userMob = $this->userMobileFilteration($userln,$filter);
                             //$userfinal = $this->userCityFilteration($userMob,$filter);
                           if($filter['firstname'] != '')
                            {
                            $userFn = $this->userFirstNamesFilteration($users,$filter);
                            }
                            else
                            {
                             $userFn = $users;   
                            }
                            if($filter['lastname'] != '')
                            {
                            $userln = $this->userLastNamesFilteration($userFn,$filter);
                            }
                            else
                            {
                            $userln =  $userFn;
                            }
                            if($filter['mobile'] != '')
                            {
                            $userMob = $this->userMobileFilteration($userln,$filter);
                            }
                            else
                            {
                             $userMob = $userln;   
                            }
                            if($filter['city'] != '')
                            {
                            $userfinal = $this->userCityFilteration($userMob,$filter);
                            }
                            else
                            {
                             $userfinal = $userMob;   
                            } 
			    $results['items'] = $userfinal;
                          }
                        else
                          {
                              $results['items'] = $users;
                          }
                         //$results['items'] = $users;
		} catch (LibException $e) {
			$results = false;
		}
		
		return $results;
       }
        
        public function userFirstNamesFilteration($userArray,$filter)
        {
            //print_r($userArray);
            if(is_array($userArray))
            {
            foreach($userArray as $k=>$v)
            {
                
                              if($filter['firstname'] == 'present')
                                {
                                 if($v->firstName != '')
                                 {
				  $users[] = $userArray[$k];
                                 }
                                }
                                elseif($filter['firstname'] == 'not_present')
                                {                               
                                  if($v->firstName == '')
                                  {                                   
				   $users[] = $userArray[$k];
                                  }   
                                }
                                else
                                {
                                   $users[] = $userArray[$k]; 
                                }                              
                
            }
            
            return $users;
            }
            
           
            
        }
        
        
        public function userLastNamesFilteration($userArray,$filter)
        {
            if(is_array($userArray))
            {
            foreach($userArray as $k=>$v)
            {
               
                              if($filter['lastname'] == 'present')
                                {
                                 if($v->lastName != '')
                                 {
				  $users[] = $userArray[$k];
                                 }
                                }
                                elseif($filter['lastname'] == 'not_present')
                                {                               
                                  if($v->lastName == '')
                                  {                                   
				   $users[] = $userArray[$k];
                                  }   
                                }
                                else
                                {
                                   $users[] = $userArray[$k]; 
                                }                              
                
            }
            return $users;
            }
            
        }
        
        
        public function userMobileFilteration($userArray,$filter)
        {
            if(is_array($userArray))
            {
            foreach($userArray as $k=>$v)
            {
                              if($filter['mobile'] == 'present')
                                {
                                 if($v->mobile != '')
                                 {
				  $users[] = $userArray[$k];
                                 }
                                }
                                elseif($filter['mobile'] == 'not_present')
                                {                               
                                  if($v->mobile == '')
                                  {                                   
				   $users[] = $userArray[$k];
                                  }   
                                }
                                else
                                {
                                   $users[] = $userArray[$k]; 
                                }                              
                
            }
            return $users;
            }
            
        }
        
        public function userCityFilteration($userArray,$filter)
        {
            if(is_array($userArray))
            {
                foreach($userArray as $k=>$v)
                 {
                               if($filter['city'] == 'none')
                               {
                                 if($v->city == '')
                                 {
				  $users[] = $userArray[$k];
                                 }
                                }
                                elseif($filter['city'] !='all' && $filter['city']!='')
                                {
                                  if(trim($v->city) == trim($filter['city']))
                                  {
				   $users[] = $userArray[$k];
                                  }   
                                }
                                else
                                {
                                   $users[] = $userArray[$k]; 
                                }
                 }               
            return $users;
            }
        }
        
        public function userAdReplyAlertCountFilteration($userArray,$filter,$param)
        {
            if(is_array($userArray))
            {
                if($param == 'ads')
                 {
                    $range = 'ad_range';
                    $value = 'ad_value';
                 }
                 elseif($param == 'reply')
                 {
                    $range = 'reply_range';
                    $value = 'reply_value';
                 }
                 elseif($param == 'alert')
                 {
                    $range = 'alert_range';
                    $value = 'alert_value';
                 }
 
                foreach($userArray as $k=>$v)
                 {
                                if($filter[$range] == 'equal')
                                {
                                 if($v->ads_count == $filter[$value] && $param =='ads')
                                 {
				  $users[] = $userArray[$k];
                                 }
                                 elseif($v->reply_count == $filter[$value] && $param =='reply')
                                 {
				  $users[] = $userArray[$k];
                                 }
                                 elseif($v->alert_count == $filter[$value] && $param =='alert')
                                 {
				  $users[] = $userArray[$k];
                                 }
                                }
                                elseif($filter[$range] == 'less')
                                {
                                 if($v->ads_count < $filter[$value] && $param =='ads')
                                 {
				  $users[] = $userArray[$k];
                                 }
                                 elseif($v->reply_count < $filter[$value] && $param =='reply')
                                 {
				  $users[] = $userArray[$k];
                                 }
                                 elseif($v->alert_count < $filter[$value] && $param =='alert')
                                 {
				  $users[] = $userArray[$k];
                                 }
                                }
                                elseif($filter[$range] == 'less_equal')
                                {
                                 if($v->ads_count <= $filter[$value] && $param =='ads')
                                 {
				  $users[] = $userArray[$k];
                                 }
                                 elseif($v->reply_count <= $filter[$value] && $param =='reply')
                                 {
				  $users[] = $userArray[$k];
                                 }
                                 elseif($v->alert_count <= $filter[$value] && $param =='alert')
                                 {
				  $users[] = $userArray[$k];
                                 }
                                }
                                elseif($filter[$range] == 'greater')
                                {
                                 if($v->ads_count > $filter[$value] && $param =='ads')
                                 {
				  $users[] = $userArray[$k];
                                 }
                                 elseif($v->reply_count > $filter[$value] && $param =='reply')
                                 {
				  $users[] = $userArray[$k];
                                 }
                                 elseif($v->alert_count > $filter[$value] && $param =='alert')
                                 {
				  $users[] = $userArray[$k];
                                 }
                                }
                                elseif($filter[$range] == 'greater_equal')
                                {
                                 if($v->ads_count >= $filter[$value] && $param =='ads')
                                 {
				  $users[] = $userArray[$k];
                                 }
                                 elseif($v->reply_count >= $filter[$value] && $param =='reply')
                                 {
				  $users[] = $userArray[$k];
                                 }
                                 elseif($v->alert_count >= $filter[$value] && $param =='alert')
                                 {
				  $users[] = $userArray[$k];
                                 }
                                }
                                elseif($filter[$range] == 'not_equal')
                                {
                                 if($v->ads_count != $filter[$value] && $param =='ads')
                                 {
				  $users[] = $userArray[$k];
                                 }
                                 elseif($v->reply_count != $filter[$value] && $param =='reply')
                                 {
				  $users[] = $userArray[$k];
                                 }
                                 elseif($v->alert_count != $filter[$value] && $param =='alert')
                                 {
				  $users[] = $userArray[$k];
                                 }
                                }
                                else
                                {
                                   $users[] = $userArray[$k]; 
                                }
                 }
                 
              return $users;
            }
        }
        
        public function getDailySummary($userArray, $startDate,$stopDate,$param)
        {
            //print_r($userArray);
           if(is_array($userArray))
            {
                $i = 1;
                $data = array();
                foreach($userArray as $key=>$val)
                {
                   if($param == 'user_reg_date')
                   {
                    if(key_exists(trim($val['createdTime']),$data))
                    {
                     $i = $data[trim($val['createdTime'])]+1;
                     $data[trim($val['createdTime'])] = $i;
                    }
                    else
                    {
                     $i =1;   
                     $data[trim($val['createdTime'])] = $i;
                    }
                   }
                   else
                   {
                    if(key_exists(trim($val['lastlogintime']),$data))
                    {
                     $i = $data[trim($val['lastlogintime'])]+1;
                     $data[trim($val['lastlogintime'])] = $i;
                    }
                    else
                    {
                     $i =1;   
                     $data[trim($val['lastlogintime'])] = $i;
                    }
                    
                   }
                }
                
            }
                
               //print_r($data);
              return $data;
        }
        
        public function getWeeklySummary($userArray='', $startDate,$stopDate,$param='')
        {                      
           $daily_counts = $this->getDailySummary($userArray,$startDate,$stopDate,$param);
           $startDate = date("Y-m-d",$startDate);
           $stopDate = date("Y-m-d",trim($stopDate));

           $day  = 0;
           $n = 0;
           $lastElement = count($daily_counts);
           $secondlastElement = $lastElement-1;
          foreach($daily_counts as $key=>$val)
          {
            $n++;
            $day++;           
            if($day < 8)
            {
          
                $val1 += $val;
                if($day == 7)
                {  //echo $key; exit;
                 $firstDate = $this->makeDate($key,6,'before');
                 $startDt =  $this->makedate($key,7,'before');
                 if($firstDate == $startDate)
                 {
                 $count_solr[$firstDate.' '.$key] = $val1;
                 }
                 else
                 {
                  $count_solr[$startDt.' '.$key] = $val1;
                  $secondlastDate = $this->makeDate($key,1,'after');
                 }
                }
                elseif($n == $lastElement)
                {
                 $count_solr[$secondlastDate.' '.$key] = $val1;
                }
            }
            else
            {
                $val1 = $val;
                $day =0;
            }
          }
          //  print_r($count_solr);
            return $count_solr;              
        }
    
    public function getMonthlySummary($userArray='', $startDate,$stopDate,$param='')
        {                      
                 $daily_counts = $this->getDailySummary($userArray,$startDate,$stopDate,$param);
                 $startDate = date("Y-m-d",$startDate);
                 $stopDate = date("Y-m-d",trim($stopDate));
 
           
           $day  = 0;
           $n = 0;
           $lastElement = count($daily_counts);
       
          foreach($daily_counts as $key=>$val)
          {
            $n++;
            $day++;
          
            if($day == 1)
              {
                $num_days_month = $this->getNumofDaysInMonth($key);
              }
            if($day < $num_days_month['num']+1)
            {
                //echo $key."*****".$val."<br>";
                $val1 += $val;
                if($day == $num_days_month['num'])
                {
                  //$startEndDate = $this->getStartEndDate($key,$num_days_month);     
                   $count_solr[$num_days_month['start'].'-'.$num_days_month['end']] = $val1;
                }
                elseif($n == $lastElement)
                {
                // $startEndDate1 = $this->getStartEndDate($key,$num_days_month);
                 $count_solr[$num_days_month['start'].'-'.$num_days_month['end']] = $val1;
                }   
            }
            else
            {
                $val1 = $val;           
                $day = 0;
            }
          }
         
            return $count_solr;              
        }
        
    
    public function getYearlySummary($userArray='', $startDate,$stopDate,$param='')
        {                      
                 $daily_counts = $this->getDailySummary($userArray,$startDate,$stopDate,$param);
                  //print_r($daily_counts);
                 $startDate = date("Y-m-d",$startDate);
                 $stopDate = date("Y-m-d",trim($stopDate));
               
           
           $day  = 0;
           $n = 0;
           $lastElement = count($daily_counts);
           
          foreach($daily_counts as $key=>$val)
          {
            $n++;
            $day++;
          
            if($day == 1)
              {
                // echo $key."<br />";
                $num_days_month = $this->getNumofDaysInYear($key);
              }
            if($day < $num_days_month['num']+1)
            {
                //echo $key."*****".$val."<br>";
                $val1 += $val;
                if($day == $num_days_month['num'])
                {
                  //$startEndDate = $this->getStartEndDate($key,$num_days_month);     
                   $count_solr[$num_days_month['start'].'-'.$num_days_month['end']] = $val1;
                }
                elseif($n == $lastElement)
                {
                // $startEndDate1 = $this->getStartEndDate($key,$num_days_month);
                   $count_solr[$num_days_month['start'].'-'.$num_days_month['end']] = $val1;
                }   
            }
            else
            {
                $val1 = $val;           
                $day = 0;
            }
          }
            
            return $count_solr;              
        }    
        
    public function csvReport($csv_data)
    {
      $filename = BASE_PATH_CSV.'/report.csv';
      file_put_contents($filename,$csv_data);
      header("Content-type: text/plain");
      header("Content-Disposition: attachment; filename=\"reports.csv\"");
      readfile($filename);
      exit;
    }
    
    public function csvSummarizeReport($csv_data)
    {
      $filename = BASE_PATH_CSV.'/report_summarize.csv';
      file_put_contents($filename,$csv_data);
      header("Content-type: text/plain");
      header("Content-Disposition: attachment; filename=\"report_summarize.csv\"");
      readfile($filename);
      exit;
    }
    
    public function makeDate($date,$days,$param)
    {
        if($param == 'before')
        {
         $new_date = date("Y-m-d",mktime(0,0,0,date("m",strtotime($date)), date("d",strtotime($date))-$days, date("Y",strtotime($date))));
        }
        else
        {
         $new_date = date("Y-m-d",mktime(0,0,0,date("m",strtotime($date)), date("d",strtotime($date))+$days, date("Y",strtotime($date))));    
        }
        return $new_date;
    }
    
    public function getNumofDaysInMonth($date)
    {
        $month = date("m",strtotime($date));
        $year = date("Y",strtotime($date));
        $day = date("d",strtotime($date));
        $num_days_month = cal_days_in_month(CAL_GREGORIAN, $month, $year) ;
        /*if($day != '01')
        {
            $first_day = $day;
            $startEndDate['num'] = ($num_days_month - $day) + 1;
        }
        else
        {
        $first_day =   $num_days_month - ($num_days_month-1);
        $startEndDate['num'] = $num_days_month;
        }*/
        $first_day =   $num_days_month - ($num_days_month-1);
        $startEndDate['num'] = $num_days_month;
        $startEndDate['start'] = $year.'-'.$month.'-'.$first_day;
        $startEndDate['end'] = $year.'-'.$month.'-'.$num_days_month;
        return $startEndDate;     
        //return $num;        
    }
    
    public function getNumofDaysInYear($date)
    {
        $year = date("Y",strtotime($date));
        $first_month = 1;
        $last_month = 12;
        $numOfDaysInyear = date("z", mktime(0,0,0,12,31,$year)) + 1;
        $last_days_month = cal_days_in_month(CAL_GREGORIAN, 12, $year) ;
        $first_day =   $numOfDaysInyear - ($numOfDaysInyear-1);
        $startEndDate['num'] = $numOfDaysInyear;
        $startEndDate['start'] = $year.'-'.$first_month.'-'.$first_day;
        $startEndDate['end'] = $year.'-'.$last_month.'-'.$last_days_month;
        return $startEndDate; 
        
    }
    
    
    public function post($domString='') {
        //$domString = 'id:1067982';
        //echo $domString;
        for($i=0;$i < 100000 ; $i++)
        {
             if($i==2)
             {
              $facet1[$i] = '620644';
             }
             else
             {
              $facet1[$i] = $domString[$i];
             }
        }
          $max_range = max($facet1);
          $min_range = min($facet1);
          $facet_limit = $max_range - $min_range;
        // print_r($facet1);
         //$facet1 = 'rpl_user_id:'.'('.implode(" OR ",$facet1).')';
         $facet1 = 'rpl_user_id:['.$min_range.' TO '.$max_range.']';
       /* for($n=1000;$n <=2000 ; $n++)
        {
            $facet2[$n] = $domString[$n];
        }
       */
        //print_r('pravin'.$facet2['1999']); exit;
       // $face = implode(" OR ",$facet2);
        //$facetrim = rtrim($face," OR ");
        //$facet2 = 'rpl_user_id:'.'('.$facetrim.')';
         
        $client = new Zend_Http_Client();
        $client->setUri('http://localhost:8983/solr/reply/select?');
        $client->setMethod(Zend_Http_Client::POST);
        $client->setHeaders('Content-Type','text/xml; charset=utf-8');
        $client->setHeaders('Content-Length',strlen($facet1));
        $client->setParameterPost('q', trim($facet1));
        $client->setParameterPost('fl', 'rpl_user_id');
        $client->setParameterPost('facet', 'true');
        $client->setParameterPost('facet.field', 'rpl_user_id');
        $client->setParameterPost('facet.limit', $facet_limit);
        //$client->setParameterPost('facet.query', trim($facet1));
        //$client->setParameterPost('facet.method', 'rpl_user_id');
        //$client->setParameterPost('facet.query', trim($facet2));
        $responseXml = $client->request()->getBody();
        unset($client);
        //$status = $this->parseSolrResponse($responseXml);
        echo "<pre>";
        print_r($responseXml); exit;
        return true;

    }
    
    public function csvReportFacet($csv_data)
    {
      $filename = BASE_PATH_CSV.'/report.csv';
      $handle = fopen($filename,'a');
      fwrite($handle,$csv_data);
      fclose($handle);
      unset($csv_data);
      flush();
     // header("Content-type: text/plain");
      //header("Content-Disposition: attachment; filename=\"reports.csv\"");
      //readfile($filename);
      //exit;
    }
    
    
   
    
}



?>