<?php
class Zend_View_Helper_YesNo extends Zend_View_Helper_Abstract {


    public function yesNo($name, $postedVars='') {


        $str = '<span class="radio"><input type="radio" value="yes" name="'.$name.'"
            '.(('yes'== $postedVars) ? 'checked="checked"' : '').' />Yes</span>
                <span class="radio"><input type="radio" value="no" name="'.$name.'"
                '.(('no' == $postedVars) ? 'checked="checked"' : '').'/>No</span>';

        return $str;
        

    }
}

?>
