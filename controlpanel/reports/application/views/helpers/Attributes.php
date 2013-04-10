<?php

class Zend_View_Helper_Attributes extends Zend_View_Helper_Abstract {

    public function attributesTemp($name, $postedVars='', $values='', $subcatId,$cityId) {
    	//print_r($postedVars);
      if($subcatId == "all")
      {
        return;
      }
      $str = '';  
      //$str .= '<div id='.$name.'>';
      if($subcatId != '') {  
        $attributes = $this->getAttributesFromDB($subcatId,$cityId);
        $x = 1;
        foreach ($attributes as $key => $val) { 
           if(($x%2)==0)
           {
             $class = 'even';
           }
           else
           {
             $class = 'odd';
           }
            $x++;
            
            if(trim($val->caption) == '')
            {
                $val->caption = 'Type_of_job';
            }
            $str .= "<li class=\"$class\">";
            $str .= "<input type=\"hidden\" name=\"attribute_subcat\" id=\"attribute_subcat\" value=\"set\"/>"; 
            $str .= "<label>".str_replace('_',' ',$val->caption)."</label>";
            $str .= "<div class=\"field\">";
            $str .= "<select id=\"$val->caption\" name=\"".trim(str_replace(' ','_',$val->caption)).'[]'."\" multiple=\"multiple\" class=\"select04\">";

            $options = explode(',', $val->values);
            foreach($options as $k => $v) {
            $str .= "<option value=\"$v\">".$v."</option>";
            }
        }
            $str .= "</select></div></li>";
        } // EO foreach 
        //$str .= '</div>';
        return $str;
    }
	
    //use this function for attributes
    public function attributes($name, $postedVars='', $values='',$classFlag = 0) {
    	
    	$str = '';
    	if(($classFlag % 2)==0) $class = 'even';
       	else $class = 'odd';
	    
          
    	
    	$str .= '<li class="'.$class.'">';
        $str .= "<input type=\"hidden\" name=\"attribute_subcat\" id=\"attribute_subcat\" value=\"set\"/>";
    	$str .= '<label>'.ucfirst(str_replace('_',' ',$name)).'</label>';
    	$str .= '<div class="field">';
    	$str .= '<select id="'.$name.'" name="'.$name.'[]" class="select04" multiple="multiple">';
    	
    	$options = explode(',',$values);
    	foreach($options as $k => $v) {
    		$sel = '';
    		if(is_array($postedVars) && in_array($v,$postedVars)) $sel = 'selected="selected"';
    		
    		$str .= '<option value="'.$v.'" '.$sel.'>'.$v.'</option>';
    	}
    	
    	$str .= '</select></div></li>';
    	
    	//echo $str;
    	return trim($str);
    }
    
    
    
    //not used
    protected function getAttributesFromDB($subcatId,$cityId) {
               if($cityId == 'all') {
	          $sql = 'SELECT text0 FROM babel_meta WHERE globalcatid = "'.$subcatId.'"';
	        } else {
	          $sql = 'SELECT text0 FROM babel_meta WHERE globalcatid = (SELECT a.nod_globalId FROM babel_node as a WHERE a.node_id = "'.$subcatId.'")'; 
	        }

        $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
        $objStmt->execute();
        $attrString = $objStmt->fetch(); 
        $xmlArr = simplexml_load_string($attrString['text0']);
        $allowedAttributes = Zend_Registry::get('ALLOWED_ATTRIBUTES');
        foreach($xmlArr as $key => $val) { 
            if(in_array($val->name, $allowedAttributes)) {
                $attributesArr[] = $val;
            }
        }
        return $attributesArr;

    }

}

?>