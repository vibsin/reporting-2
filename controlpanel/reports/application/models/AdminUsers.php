<?php

class Model_AdminUsers extends Zend_Db_Table_Abstract {
    
    public $_name = "admin";
    public $_schema = "main"; //'ignored';
    
    public $_primary = "id";
    
    public function getSections() {
        //fetch sections from DB first
        $select = new Zend_Db_Select(Zend_Registry::get("authDbConnection"));
        $select->from("sections");
        
        $stmt = $select->query();
        $result = $stmt->fetchAll();
        $sections = array();
     
        if(!empty($result)) {
            foreach($result as $k => $v) {
                $sections[$v["id"]] = ucwords(strtolower($v["caption"]));
            }
            return $sections;
        } else            throw new Exception("Cannot fetch sections data. Error:".$stmt->errorInfo());
    }
    
    
    public function getSectionNameForId($sectionId) {
        $select = new Zend_Db_Select(Zend_Registry::get("authDbConnection"));
        $select->from("sections")->where("id=?", $sectionId);
        $stmt = $select->query();
        $result = $stmt->fetch(); 
        return $result;
    }
    
    public function getRoTypeUsers() {
        $select = new Zend_Db_Select(Zend_Registry::get("authDbConnection"));
        $select->from("admin")->where("user_type=?", "ro");
        
        //$sql = $select->__toString();
        //echo $sql."<br />";
        
        $stmt = $select->query();
        $result = $stmt->fetchAll();
        if(!empty ($result)) return $result;
        else return false;
        
    }
    
}
