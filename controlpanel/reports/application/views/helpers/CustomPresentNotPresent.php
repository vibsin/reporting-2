<?php
class Zend_View_Helper_CustomPresentNotPresent extends Zend_View_Helper_Abstract {

    public function customPresentNotPresent($name, $postedVars=array(),$value) {
        $value1 = strtolower(str_replace(" ","_",trim($value[0])));
        $value2 = strtolower(str_replace(" ","_",trim($value[1])));
        $str = '<div class="chk_con">
                  <span class="chk">
                      <input type="checkbox" name="'.$name.'[]" value="'.$value1.'" '.(isset($postedVars) ? ((in_array($value1, $postedVars) == true) ? 'checked="checked"' : '') : '').' />
                   </span>
                  <span class="label">'.$value[0].'</span>
                </div>
                <div class="chk_con">
                  <span class="chk">
                        <input type="checkbox" name="'.$name.'[]" value="'.$value2.'" '.(isset($postedVars) ? ((in_array($value2, $postedVars) == true) ? 'checked="checked"' : ''): '').'/>
                  </span>
                  <span class="label">'.$value[1].'</span>
                </div>';
        return $str;
    }


}

?>