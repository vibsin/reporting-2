<?php
class Zend_View_Helper_Subscribe extends Zend_View_Helper_Abstract {

    public function subscribe($name, $postedVars=array()) {
        $str = '<div class="chk_con">
                  <span class="chk">
                      <input type="checkbox" name="'.$name.'[]" value="0" '.(!empty($postedVars) ? ((in_array('0', $postedVars) == true) ? 'checked="checked"' : ''):'').' />
                   </span>
                  <span class="label">Active</span>
                </div>
                <div class="chk_con">
                  <span class="chk">
                        <input type="checkbox" name="'.$name.'[]" value="2" '.(!empty($postedVars) ? ((in_array('2', $postedVars) == true) ? 'checked="checked"' : ''):'').' />
                  </span>
                  <span class="label">Unsubscribe</span>
                </div>';
        return $str;
    }


}