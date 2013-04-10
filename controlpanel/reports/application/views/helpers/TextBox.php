<?php

class Zend_View_Helper_TextBox extends Zend_View_Helper_Abstract {

    public function textBox($name, $postedVars='') {

        $str = '<input
                type="text"
                name="'.$name.'"
                id="'.$name.'"
                class="input03"
                value="'.$postedVars.'"
                />';
        return trim($str);
        
    }
}

?>
