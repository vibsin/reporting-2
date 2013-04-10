<?php

class Extended_Auth_Login extends Zend_Controller_Plugin_Abstract {
    public function postDispatch(Zend_Controller_Request_Abstract $request) {
        parent::postDispatch($request);
        
        //
        $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector');
        
        
        if($request->getControllerName() != "auth") {
            $auth = Zend_Auth::getInstance();
            if(!$auth->hasIdentity()) {
                $redirector->gotoUrl(BASE_URL.'/auth/index');
            } 
        } 
    }
    
    public function preDispatch(Zend_Controller_Request_Abstract $request) {
        parent::preDispatch($request);
        
        
    }

}
