<?php
class Zend_View_Helper_PresentNotpresentradio extends Zend_View_Helper_Abstract {


    public function presentNotpresentradio($name, $postedVars='') {


        $str = '<span class="radio"><input type="radio" value="present" name="'.$name.'"
            '.(('present'== $postedVars) ? 'checked="checked"' : '').' />Present</span>
                <span class="radio"><input type="radio" value="not_present" name="'.$name.'"
                '.(('not_present' == $postedVars) ? 'checked="checked"' : '').'/>Not present</span>';

        return $str;
        

    }
}

?>
