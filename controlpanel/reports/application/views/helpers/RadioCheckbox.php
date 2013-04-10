<?php
class Zend_View_Helper_RadioCheckbox extends Zend_View_Helper_Abstract {

    public function radioCheckbox($name,$postedVars,$value,$type) {
        $str = "";
        $cap = "";
        if($type == "checkbox") $cap = "[]";
        foreach($value as $k => $v) {
            $checked= "";
            if($type == "radio") {
                $checked = (isset($postedVars) ? ($v == $postedVars ? 'checked="checked"' : '') : '');
            } else if($type == "checkbox") {
                $checked = (isset($postedVars) ? (in_array($v,$postedVars) ? 'checked="checked"' : '') : '');
            } else $checked = "";
            
        $str .= '<div class="chk_con">
                  <span class="chk">
                      <input type="'.$type.'" name="'.$name.$cap.'" value="'.$v.'" '.$checked.' />
                   </span>
                  <span class="label">'.$v.'</span>
                </div>';
        }
        return $str;
    }


}

?>
