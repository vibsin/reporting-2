<?php
class Model_Selectlist {


    public function getLocalityList($cityId) {
        $sql = 'SELECT
                    title
                FROM
                    babel_localities
                WHERE cityid= "'.$cityId.'" 
                ORDER BY title ASC';
        
        $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
        $objStmt->execute();
        $localities = $objStmt->fetchAll();
        
        return $localities;
       
    }
    
    public function getMetaCategoryList($cityId) {

        if($cityId == '' || $cityId == 'all') {
            $sql = 'SELECT nod_name,node_id, nod_globalId FROM babel_node WHERE nod_level="100" AND nod_areaid="1" AND nod_title!="" AND nod_enable!=0';
        } else {
            $sql = ' SELECT nod_name,node_id, nod_globalId FROM babel_node WHERE nod_level="100" AND nod_pid="'.$cityId.'" AND nod_title!="" AND nod_enable!=0';
        }

        $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
        $objStmt->execute();
        $cities = $objStmt->fetchAll();

        return $cities;
    }


    
    public function getSubCategoryList($metacatId) {

        
        $sql = 'SELECT 
                    nod_name,node_id,nod_globalId
                FROM
                    babel_node
                WHERE nod_level="101" AND nod_pid="'.$metacatId.'" AND nod_title!= "" '; 
         
        $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
        $objStmt->execute();
        $subCats = $objStmt->fetchAll();

        return $subCats;
    }


    public function getAttributeList() {
        
    }
    
}
?>
