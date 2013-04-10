<?php
class Zend_View_Helper_Summarize extends Zend_View_Helper_Abstract {


    public function summarize($name, $value, $postedVars='') {
        $str = '<input type="radio" value="'.$value.'" name="'.$name.'[]"
            '.(!empty($postedVars) ? ((in_array($value, $postedVars) == true) ? 'checked="checked"' : '') : '').' />';

        return $str;

    }
}