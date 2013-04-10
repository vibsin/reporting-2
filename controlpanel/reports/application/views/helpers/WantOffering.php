<?php
class Zend_View_Helper_WantOffering extends Zend_View_Helper_Abstract {

    public function wantOffering($name, $postedVars=array()) {
        $str = '<div class="chk_con">
                  <span class="chk">
                      <input type="checkbox" name="'.$name.'[]" value="want" '.(!empty($postedVars) ? ((in_array('want', $postedVars) == true) ? 'checked="checked"' : ''):'').' />
                   </span>
                  <span class="label">Want</span>
                </div>
                <div class="chk_con">
                  <span class="chk">
                        <input type="checkbox" name="'.$name.'[]" value="offering" '.(!empty($postedVars) ? ((in_array('offering', $postedVars) == true) ? 'checked="checked"' : ''):'').' />
                  </span>
                  <span class="label">Offering</span>
                </div>';
        return $str;
    }


}