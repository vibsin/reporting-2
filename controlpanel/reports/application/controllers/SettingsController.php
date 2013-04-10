<?php

class SettingsController extends Zend_Controller_Action {
    
    
    public function init() {
        parent::init();

    }
    
    public function indexAction() {
        
        //get our form
        $form = new Forms_CreateUserForm();
        
        //our table
        $table = new Model_AdminUsers(array("db"=> Zend_Registry::get("authDbConnection")));
        
        
        //get all the request paramters
        $request = $this->getRequest();
        
        //if delete request
        if($request->getUserParam('do') == "delete") { 
            try {
                $table->delete("id=".$request->getParam('id'));
            } catch (Exception $e) {
                echo $e->getMessage();
            }
            $this->view->msgs = "Record deleted successfully!";
        }
        
        
        
        //check if anything was posted
        
        if($request->isPost()) {
            //form was posted, now check for validity of form
            if($form->isValid($request->getPost())) {
                //form is valid, now create user
                
                
                
                $data = array(
                    "allowed_section_ids" => implode(',', $request->getParam('sections')),
                    "username"  => trim($request->getParam("username")),
                    "password"  => md5(trim($request->getParam("password").trim(ADMIN_PASSWORD_SALT))),
                    "salt"      => trim(ADMIN_PASSWORD_SALT),
                    "user_type" => trim($request->getParam('user_type')),
                    "created_time" => date("Y-m-d H:i:s"),
                    "modified_time" => date("Y-m-d H:i:s"),
                    "last_login_time" => null
                    );
                
                try {
                    //check for duplicate username
                    if(!$this->isDuplicateEntry(trim($request->getParam("username")))) {
                        
                        $lastInsertId = $table->insert($data);
                        if($request->getParam('user_type') == "ro") {
                            $parent = $request->getParam('ro_parent');
                            if(!empty($parent)) {
                                $m = new Model_RoSettings();
                                try {
                                    $m->insertNewChildForParent($lastInsertId, (int) $parent);
                                } catch (Zend_Db_Table_Exception $e) {
                                    //delete the inserted id
                                    $table->delete("id=".$lastInsertId);
                                    echo "<pre>".$e."</pre>";exit;
                                    
                                }
                            }
                        }
                        
                        
                        $this->view->msgs = "Record added successfully!";
                    } else {
                        $this->view->msgs = "Duplicate usernames not allowed!";
                    }
                } catch (Exception $e) {
                    echo $e->getMessage();
                }

                $form->reset();
                
            } else {
                //generally required fields are not set, hence we display a general message
                $this->view->msgs = "Please correct below errors!";
            }
        } 
        
        
        $this->view->userList = $table->fetchAll();
        $this->view->sectionList = $table->getSections();
        
        $this->view->createNewUserForm = $form;
        $this->renderScript("settings/accounts/index.phtml");
        
    }
    
    protected function isDuplicateEntry($username) {
        $table = new Model_AdminUsers(array("db"=> Zend_Registry::get("authDbConnection")));
        $select = $table->select()->where("username=?", $username)->query();
        $rows = $select->fetch();
        if(!empty($rows)) return true; //we have duplicate username
        else return false;
        
    }


    //ajax call
    public function createUserAction() {
        //don't render anything
        
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);

        
        
    }
    
}
