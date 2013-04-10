<?php

class Zend_View_Helper_LocalityList extends Zend_View_Helper_Abstract {

    public function localityList($name, $postedVars='', $cityId = '') {

        $str = '<select class="select02" id="'.$name.'" name="'.$name.'">';
    	
        //if city agnostic we do not neeed to shwo the locality list
        if($cityId != 'all') {
         $str .= '<option value="">Select one</option>';

         if($cityId != '') {
            $localityArr = $this->getLocalitiesFromDB($cityId);
             $str .= '<option value="0" '.(($postedVars == "0") ? 'selected="selected"' : '').'>All</option>';

             foreach ($localityArr as $key => $val) {
                 if($val['title']  == $postedVars) $selected = 'selected="selected"';
                 else $selected = '';

                $str .= '<option value="'.$val['title'].'" '.$selected.'>'.$val['title'].'</option>';

             }
         }

        }
        
        
         $str .= '</select>';

         return $str;


    }

    protected function getLocalitiesFromDB($cityId) {
        $sql = 'SELECT
                    title
                FROM
                    babel_localities
                WHERE cityid= "'.$cityId.'" ORDER BY title ASC';

        $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
        $objStmt->execute();
        $localities = $objStmt->fetchAll();

        return $localities;
    }

}

?>