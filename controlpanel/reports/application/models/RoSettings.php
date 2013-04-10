<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class Model_RoSettings extends Zend_Db_Table_Abstract {
    public $_name = "ro_settings";
    public $_schema = "main"; //'ignored';
    
    public $_primary = "ro_user_id";
    
    public function init() {
        parent::init();
        $this->setDefaultAdapter(Zend_Registry::get("authDbConnection"));
        
    }
    
    /**
     *This will fetch the right value of any node in the table
     * @param type $nodeId 
     */
    public function getRightValueOfNode($nodeId) { 
        if(empty($nodeId) || is_null($nodeId)) { 
            throw new Zend_Db_Table_Exception("Node id cannot be empty");
        } 
        
        if(is_string($nodeId)) throw new Zend_Db_Table_Exception("Node id cannot be string");
        $select = new Zend_Db_Select(Zend_Registry::get("authDbConnection"));
        $select->from("ro_settings")->where("ro_user_id=?", $nodeId);
        
        //$sql = $select->__toString();
        //echo $sql."<br />";
        
        $stmt = $select->query();
        $result = $stmt->fetch();
        if(!empty ($result)) return $result;
        else return false;
    }
    
    
    public function updateLeftValuesBy($factor) { 
        if(empty($factor) || is_null($factor)) { 
            throw new Zend_Db_Table_Exception("Update arg must be positive number");
        }
        
        if(is_string($factor)) throw new Zend_Db_Table_Exception("Update arg cannot be string");
        
        $sql = "UPDATE 
                    ro_settings 
                SET 
                    lft = lft+2 
                WHERE 
                    lft > ".$factor;
        
        //echo $sql."<br />";
        
        $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('authDbConnection'), $sql);
        $objStmt->execute();
    }
    
    
    public function updateRightValuesBy($factor) {
        if(empty($factor) || is_null($factor)) { 
            throw new Zend_Db_Table_Exception("Update arg must be positive number");
        }
        
        if(is_string($factor)) throw new Zend_Db_Table_Exception("Update arg cannot be string");
        
        $sql = "UPDATE 
                    ro_settings 
                SET 
                    rgt = rgt+2 
                WHERE 
                    rgt > ".$factor;
        
        //echo $sql."<br />";
        
        $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('authDbConnection'), $sql);
        $objStmt->execute();
    }
    
    
    public function updateRightOfParent($parentId) {
        if(empty($parentId) || is_null($parentId)) { 
            throw new Zend_Db_Table_Exception("Parent arg must be positive number");
        }
        
        if(is_string($parentId)) throw new Zend_Db_Table_Exception("Parent arg cannot be string");
        
        
        $sql = "UPDATE 
                    ro_settings 
                SET 
                    rgt = rgt+2 
                WHERE 
                   ro_user_id  = ".$parentId;
        
        //echo $sql."<br />";
        
        $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('authDbConnection'), $sql);
        $objStmt->execute();
        
        
    }
    
    
    
    
    public function insertNewChildForParent($newId,$parentId) {
        
        if(empty($parentId) || is_null($parentId)) { 
            throw new Zend_Db_Table_Exception("Parent arg must be positive number");
        }
        
        if(is_string($parentId)) throw new Zend_Db_Table_Exception("Parent arg cannot be string");
        
        #1
        $parent = $this->getRightValueOfNode($parentId);
        $myright = $parent["rgt"];
        
        #2
        $this->updateLeftValuesBy((int) $myright);
        
        #3
        $this->updateRightValuesBy((int) $myright);
        
        #4
        $sql = "INSERT INTO ro_settings (ro_user_id,lft,rgt,created_time,modified_time) values 
            (".$newId.",".$myright.",".($myright+1).",'".date("Y-m-d H:i:s")."','".date("Y-m-d H:i:s")."')";
        
        //echo $sql."<br />";
        
        $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('authDbConnection'), $sql);
        $objStmt->execute();
        
        
        #5
        $this->updateRightOfParent($parentId);
    }
    
    
    function getParentsOfChild($childId) {
        $sql = "SELECT parent.ro_user_id, ad.username   
                FROM 
                    ro_settings AS child,
                    ro_settings AS parent JOIN admin AS ad ON parent.ro_user_id = ad.id    
                WHERE 
                    child.lft BETWEEN parent.lft AND parent.rgt AND 
                    child.ro_user_id = ".$childId." 
                ORDER BY child.lft";
        $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('authDbConnection'), $sql);
        $objStmt->execute();
        
        $result = $objStmt->fetchAll();
        if(!empty ($result)) return $result;
        else return false;
        
    }
    
    function getChildrenOfParent($parentId) {
        $sql = "SELECT 
                    child.ro_user_id, 
                    ad.username   
                FROM 
                    ro_settings AS parent,
                    ro_settings AS child JOIN admin AS ad ON child.ro_user_id = ad.id    
                WHERE 
                    child.lft BETWEEN parent.lft AND parent.rgt AND 
                    parent.ro_user_id = ".$parentId."  
                ORDER BY child.lft";
        $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('authDbConnection'), $sql);
        $objStmt->execute();
        
        $result = $objStmt->fetchAll();
        if(!empty ($result)) return $result;
        else return false;
        
    }
    
    public function getTree() {
        $sql = "SELECT 
                    ad.username, 
                    (COUNT(parent.ro_user_id)) AS depth
                FROM 
                        ro_settings AS parent,
                        ro_settings AS child JOIN admin AS ad ON child.ro_user_id = ad.id
                WHERE 
                        child.lft BETWEEN parent.lft AND parent.rgt
                GROUP BY 
                        ad.username
                ORDER BY 
                        child.lft";
        
        $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('authDbConnection'), $sql);
        $objStmt->execute();
        
        $result = $objStmt->fetchAll();
        if(!empty ($result)) return $result;
        else return false;
        
        
        
        
    }
    
    
    
    
    
    
}
