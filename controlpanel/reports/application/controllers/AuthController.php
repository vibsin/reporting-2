<?php

class AuthController extends Zend_Controller_Action {


    public function init() {
        parent::init();

        $this->_helper->layout->setlayout('login_layout');
    }

    public function indexAction() {
        $request = $this->getRequest();
        $form  = new Forms_Login();
        $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector');
        
        if($request->isPost()) {
        
            if ($form->isValid($request->getPost())) {
                //var_dump($request); 
                
                $adapter = $this->_getAuthAdapter();
                
                $adapter->setIdentity(trim($this->getRequest()->getParam('username')));
                $adapter->setCredential(md5(trim($this->getRequest()->getParam('password')).ADMIN_PASSWORD_SALT));

                $auth    = Zend_Auth::getInstance(); 

                try {
                    $result  = $auth->authenticate($adapter); 
                    $msgs = $result->getMessages();
                    if($result->isValid()) {
                        $user = $adapter->getResultRowObject();
                        
                        $auth->getStorage()->write($user);
                        
                        //now update user's last login and no of logins
                        try {
                            $this->updateLoggedUserHistory($user->id);
                        } catch (Exception $e) {
                            echo $e;
                        }
                        //now get the home page for this user and redirect
                        $page = $this->getHomePageForAuthenticatedUser($user->allowed_section_ids);
                        
                        $redirector->gotoUrl(BASE_URL."/".$page["caption"]."/index");
                    } 
                    $this->view->msgs = $msgs[0];
                    
                } catch (Exception $e) {
                    echo $e; 
                }

            } else {
                $this->view->msgs = "Please correct below errors!";
            }
        } 

        $this->view->loginForm = $form;
        $this->renderScript('auth/login.phtml');
    }


    protected function updateLoggedUserHistory($userId) {
        $table = new Model_AdminUsers(array("db"=> Zend_Registry::get("authDbConnection")));
        
        $data = array(
            "last_login_time" =>  date("Y-m-d H:i:s"),
            "no_of_logins" => new Zend_Db_Expr("no_of_logins+1")
            );
        
        if($table->update($data, "id=".$userId) != 0)  return true;
        else throw new Exception("Error updating login history");
        
        
    }

    protected function _getAuthAdapter() {

        //$dbAdapter = Zend_Db_Table::getDefaultAdapter();
        $authAdapter = new Zend_Auth_Adapter_DbTable(Zend_Registry::get('authDbConnection'));
        
        
        $authAdapter->setTableName('admin')
            ->setIdentityColumn('username')
            ->setCredentialColumn('password');

        $sel =  $authAdapter->getDbSelect();

        return $authAdapter;
    }

    public function logoutAction() {
        Zend_Auth::getInstance()->clearIdentity();
        $this->_helper->redirector('index'); // back to login page
    }
    
    
    protected function getHomePageForAuthenticatedUser($ids) {
        $ids = explode(",",$ids);print_r($ids);
        $table = new Model_AdminUsers(array("db"=> Zend_Registry::get("authDbConnection")));
        $rows = $table->getSectionNameForId($ids[0]);
        
        return $rows;
    }
}

?>