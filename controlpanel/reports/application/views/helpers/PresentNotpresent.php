<?php
class Zend_View_Helper_PresentNotpresent extends Zend_View_Helper_Abstract {

    public function presentNotpresent($name, $postedVars=array()) {
        $str = '<div class="chk_con">
                  <span class="chk">
                      <input type="checkbox" name="'.$name.'[]" value="present" '.(is_array($postedVars) ? ((in_array('present', $postedVars) == true) ? 'checked="checked"' : '') : '').' />
                   </span>
                  <span class="label">Present</span>
                </div>
                <div class="chk_con">
                  <span class="chk">
                        <input type="checkbox" name="'.$name.'[]" value="not_present" '.(is_array($postedVars) ? ((in_array('not_present', $postedVars) == true) ? 'checked="checked"' : '') : '').'/>
                  </span>
                  <span class="label">Not Present</span>
                </div>';
        return $str;
    }


}

?>
