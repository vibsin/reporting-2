<?php

class Zend_View_Helper_EqualsContainsExcludes extends Zend_View_Helper_Abstract {


    public function equalsContainsExcludes($name, $postedVars='') {

        $str = '<select class="select01" id="'.$name.'" name="'.$name.'">';
        $str .= '<option value="none" '.(($postedVars == "") ? 'selected="selected"' : '').'>Select one</option>';
        $str .= '<option value="equals" '.(($postedVars == "equals") ? 'selected="selected"' : '').'>Equals</option>';
        $str .= '<option value="contains" '.(($postedVars == "contains") ? 'selected="selected"' : '').'>Contains</option>';
        $str .= '<option value="excludes" '.(($postedVars == "excludes") ? 'selected="selected"' : '').'>Excludes</option>';
        $str .= '</select>';

        return $str;
    }
}

?>
