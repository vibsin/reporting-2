<?php
class Zend_View_Helper_Citylistnames extends Zend_View_Helper_Abstract {

    public function citylistnames($name, $postedVars='') {

        $cityArr = $this->getCityFromDB();

         $str = '<select class="select02" id="'.$name.'" name="'.$name.'" onchange="javascript:getMetacategories(this.value);javascript:getLocalities(this.value)">';

         $str .= '<option value="">Select one</option>';

        $str .= '<option value="none" '.(($postedVars == "none") ? 'selected="selected"' : '').'>None</option>';
        $str .= '<option value="all" '.(($postedVars == "all") ? 'selected="selected"' : '').'>All</option>';
        
         foreach ($cityArr as $key => $val) {
             if($val['area_id']  == $postedVars) $selected = 'selected="selected"';
             else $selected = '';

             $str .= '<option value="'.$val['area_name'].'" '.$selected.'>'.$val['area_name'].'</option>';

         }

         $str .= '</select>';

         return $str;

        
    }

    protected function getCityFromDB() {
        
        $sql = 'SELECT
                    area_name,
                    area_id
                FROM
                    babel_area 
                WHERE area_id != 1';

        $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
        $objStmt->execute();
        return $objStmt->fetchAll();
    }
    
    
    

}

?>
