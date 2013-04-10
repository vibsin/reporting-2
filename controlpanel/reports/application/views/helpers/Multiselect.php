<?php

class Zend_View_Helper_Multiselect extends Zend_View_Helper_Abstract {

    public function multiselect($name, $postedVars='', $values = '',$param,$ad_status='') {
        $str = '';
        if($param == 'disable' && trim($ad_status[0])!='flag_and_delay')
        {
        $str .= '<select multiple="multiple" class="select04" name="'.$name.'[]" id="'.$name.'" disabled onclick="javascript:showFlagReason(this.value,this.id);">';
        }
        else
        {
        $str .= '<select multiple="multiple" class="select04" name="'.$name.'[]" id="'.$name.'" onclick="javascript:showFlagReason(this.value,this.id);">';    
        }
         foreach($values as $k=>$v)
         {
              $value = str_replace(" ","_",strtolower($v));
                if(is_array($postedVars) && in_array($value,$postedVars))
                {
                    $str .='<option value="'.$value.'"  selected="selected">'.$v.'</option>';
                }
                else
                {
                    $str .='<option value="'.$value.'">'.$v.'</option>';
                }
         }     
        $str .= '</select>';
        return $str;
    }
}

?>