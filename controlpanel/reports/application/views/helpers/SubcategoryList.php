<?php

class Zend_View_Helper_SubcategoryList extends Zend_View_Helper_Abstract {

    public function subcategoryList($name, $postedVars='', $metacatId = '', $cityId= '') {
         if($metacatId != '' && $cityId != '')
         {
          $catArr = $this->getSubcategoryFromDB($metacatId,$cityId);
         }

         $str = '<select class="select02" id="'.$name.'" name="'.$name.'" onchange="javascript:getAttributes(this.value)">';
			
         if($metacatId != 'all') { //if metacat is agnostic ,we need not show the subcat list
         
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
         }

         $str .= '</select>';

         return $str;


    }

    protected function getSubcategoryFromDB($metacatId,$cityId) {
        
    	if($metacatId == 'all') return;
    	
    	
        if(!empty($cityId)) {
        	if($cityId == 'all') {
	        	//global
	        	$sql = 'SELECT nod_name,node_id,nod_globalId FROM babel_node WHERE nod_areaid = 1 AND nod_globalpid='.$metacatId; 
	        } else {
	        	//city specific
	        	$sql = 'SELECT nod_name,node_id,nod_globalId FROM babel_node WHERE nod_level=101 AND nod_areaid = '.$cityId.' AND nod_pid = '.$metacatId; //echo $sql;
	        }

	$objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
        $objStmt->execute();
//        $metaId = $objStmt->fetchAll();
//
//        $sql2 = 'SELECT
//                    nod_name,node_id,nod_globalId
//                FROM
//                    babel_node
//                WHERE nod_level="101" AND nod_pid="'.$metaId['node_id'].'" AND nod_title!= "" ';
//
//        $objStmt2 = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql2);
//        $objStmt2->execute();
        $subCats = $objStmt->fetchAll();
        //print_r($subCats); exit;
        return $subCats;
        }
    }

}

?>