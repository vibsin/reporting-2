<?php
class Zend_View_Helper_QuantityRange extends Zend_View_Helper_Abstract {

    public function quantityRange($name, $postedVars='') {

        $str = '<select class="select01" id="'.$name.'" name="'.$name.'">';
        $str .= '<option value="" '.(($postedVars == "") ? 'selected="selected"' : '').'>Select one</option>';
        $str .= '<option value="less" '.(($postedVars == "less") ? 'selected="selected"' : '').'><</option>';
        $str .= '<option value="less_equal" '.(($postedVars == "less_equal") ? 'selected="selected"' : '').'><=</option>';
        $str .= '<option value="greater" '.(($postedVars == "greater") ? 'selected="selected"' : '').'>></option>';
        $str .= '<option value="greater_equal" '.(($postedVars == "greater_equal") ? 'selected="selected"' : '').'>>=</option>';
        $str .= '<option value="equal" '.(($postedVars == "equal") ? 'selected="selected"' : '').'>=</option>';
        $str .= '<option value="not_equal" '.(($postedVars == "not_equal") ? 'selected="selected"' : '').'>!=</option>';
        $str .= '</select>';

        return $str;
        
    }

}

?>