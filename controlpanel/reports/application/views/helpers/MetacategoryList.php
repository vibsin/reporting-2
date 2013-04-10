<?php

class Zend_View_Helper_MetacategoryList extends Zend_View_Helper_Abstract {

    public function metacategoryList($name, $postedVars='', $cityId = '') {
	
        $catArr = $this->getMetacategoryFromDB($cityId);

         $str = '<select class="select02" id="'.$name.'" name="'.$name.'" onchange="javascript:getSubcategories(this.value)">';

         $str .= '<option value="">Select one</option>';
	    
	 if(!empty($catArr)) {   
	    $str .= '<option value="all" '.(($postedVars == "all") ? 'selected="selected"' : '').'>All</option>';
   
	    foreach ($catArr as $key => $val) {
		   
		   $value = '';
		   if($cityId == 'all') { 
			   $value = $val['nod_globalId'];
		   } else {
			   $value = $val['node_id'];
		   }
		   
		if($value  == $postedVars) $selected = 'selected="selected"';
		else $selected = '';
   
		$str .= '<option value="'.$value.'" '.$selected.'>'.$val['nod_name'].'</option>';
   
	    }
	 }

         $str .= '</select>';

         return $str;


    }

    protected function getMetacategoryFromDB($cityId) {
        //if($cityId == 'all' || $cityId == '') {
	if($cityId == 'all') {
            $sql = 'SELECT nod_name,node_id, nod_globalId FROM babel_node WHERE nod_level="100" AND nod_areaid="1" AND nod_title!="" AND nod_enable!=0';
        } else {
            $sql = ' SELECT nod_name,node_id, nod_globalId FROM babel_node WHERE nod_level="100" AND nod_pid="'.$cityId.'" AND nod_title!="" AND nod_enable!=0';
        }
        $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
        $objStmt->execute();
        $cities = $objStmt->fetchAll();

        return $cities;
    }

}

?>