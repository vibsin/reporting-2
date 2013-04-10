<?php
class Zend_View_Helper_DateTo extends Zend_View_Helper_Abstract {


    public function dateTo($name, $postedVars='') {

        $dateVal = '';

        if(!empty ($postedVars)) {
            if($postedVars == 'null') {
                $dateVal = '';
            } else {
                $dateVal = $postedVars;
            }
        }
        else {
            //$dateVal = date('d-m-Y',mktime(0, 0, 0, date('m'), date('d'),date('Y')));
        }

        $jsStr = '<script type="text/javascript">
                    jQuery(document).ready(function() {jQuery("#'.$name.'").datepicker({
					inline: true,
                                        dateFormat: "dd-mm-yy"
				});

    });
                  </script>';
        $str = '<span>To</span><input type="text" id="'.$name.'" name="'.$name.'"
            value="'.$dateVal.'" class="input01" />';

        return $jsStr.$str;

    }
}

?>