<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
//include QUIKR_RESOURCE_PATH.'/includes/init.php';
//include QUIKR_RESOURCE_PATH.'/model/Event.class.php';
//include QUIKR_RESOURCE_PATH.'/model/Cache.class.php';
//include QUIKR_RESOURCE_PATH.'/model/Data.class.php';
//include QUIKR_RESOURCE_PATH.'/util/Attribute.class.php';

class AjaxrequestsController extends Zend_Controller_Action {

    public $objModel;
    
    public function init() {
        if($this->getRequest()->isXmlHttpRequest()) {
                $this->_helper->viewRenderer->setNoRender();
                $this->_helper->layout->disableLayout();
        }

        $this->objModel = new Model_Selectlist();
    }



    
    public function getlocalitiesAction() {
        if($this->getRequest()->isPost()) {
            $cityId = $this->getRequest()->getParam('city_id');
        } else $cityId = '';

        $localities = $this->objModel->getLocalityList($cityId);

        if(!empty($localities)) {

            $this->view->localities = $localities;
            $this->view->sectionName = $this->getRequest()->getParam('section_name');
            $this->renderScript('ajax_calls/locality_list.phtml');
        }
        else return false;
    }


    public function getmetacategoriesAction() {
        if($this->getRequest()->isPost()) {
            $cityId = $this->getRequest()->getParam('city_id');
        } else $cityId = '';
        
        if($cityId != '') {
        	$metacats = $this->objModel->getMetaCategoryList($cityId);
		
	        if(!empty($metacats)) {
	
	            $this->view->metacats = $metacats;
	            $this->view->sectionName = $this->getRequest()->getParam('section_name');
	            if($cityId == 'all') $this->view->isAgnostic = true;
	            else $this->view->isAgnostic = false;
	            $this->renderScript('ajax_calls/metacat_list.phtml');
	        }
        }
        else return false;
    }

    public function getsubcategoriesAction() {
         if($this->getRequest()->isPost()) {
            $metaId = $this->getRequest()->getParam('metacat_id');
            $cityId = $this->getRequest()->getParam('city_id');
            if($cityId == '') {
                echo '|FALSE|Please select City'; exit;
            }

        } else $metaId = '';
		
        if($metaId != 'all') { //if all metacat is selected we need not show teh subcategories
	        if($cityId == 'all' || $cityId == '') {
	            //global
	            $sql = 'SELECT nod_name,node_id,nod_globalId FROM babel_node WHERE nod_areaid = 1 AND nod_globalpid='.$metaId; 
	        } else {
	            //city specific
	            $sql = 'SELECT nod_name,node_id,nod_globalId FROM babel_node WHERE nod_level=101 AND nod_areaid = '.$cityId.' AND nod_pid = '.$metaId; //echo $sql;
	        } 
	        $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
	        $objStmt->execute();
	        $subcats = $objStmt->fetchAll();
			//echo $sql;
	        //print_r($subcats); exit;
	        //echo $sql; exit;
	
	        //$subcats = $this->objModel->getSubCategoryList($metaId['node_id']);
	
	        if(!empty($subcats)) {
	
	            $this->view->subcats = $subcats;
	            $this->view->sectionName = $this->getRequest()->getParam('section_name');
	            if($cityId == 'all') $this->view->isAgnostic = true;
	            else $this->view->isAgnostic = false;
	            $this->renderScript('ajax_calls/subcat_list.phtml');
	        }
        }
        else return false;
    }


    public function getattributesAction() {
        if($this->getRequest()->isPost()) {
            $subcatId = $this->getRequest()->getParam('subcat_id');
            $cityId = $this->getRequest()->getParam('city_id');
        }
        
        if($subcatId != 'all' && $subcatId != '') { //for all selection we need to return attrubutes
        
	        if($cityId == 'all') {
	        	$sql = 'SELECT text0 FROM babel_meta WHERE globalcatid = "'.$subcatId.'"';
	        } else {
	        	$sql = 'SELECT text0 FROM babel_meta WHERE globalcatid = (SELECT a.nod_globalId FROM babel_node as a WHERE a.node_id = "'.$subcatId.'")';
	        }
	
	        $objStmt = new Zend_Db_Statement_Pdo(Zend_Registry::get('dbconnection'), $sql);
	        $objStmt->execute();
	        $attrString = $objStmt->fetch(); 
	        $xmlArr = simplexml_load_string($attrString['text0']);
	        $allowedAttributes = Zend_Registry::get('ALLOWED_ATTRIBUTES');
	
	        foreach($xmlArr as $key => $val) { 
	            if(in_array($val->name, $allowedAttributes)) {
	                $attributesArr[] = $val;
	            }
	        }
	
	        $this->view->attributesArr = $attributesArr;
	        //print_r($this->view->attributesArr);
	        $this->renderScript('ajax_calls/attributes.phtml');
	
	        
	//         $attribute = new Attribute();
	//        $grabber = new AttributeGrabber($attribute->loadByCatid($subcatId));
	//        print_r($grabber->metas()); exit;
	        
	        
	    }
    }
}
?>