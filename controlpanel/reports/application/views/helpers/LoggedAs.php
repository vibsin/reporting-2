<?php

class Zend_View_Helper_LoggedAs extends Zend_View_Helper_Abstract {


    public function loggedAs() {

        $auth = Zend_Auth::getInstance();

        if($auth->hasIdentity()) {
            $username = $auth->getIdentity()->username;
            $logoutUrl = $this->view->url(array(
                'controller' => 'auth',
                'action' => 'logout'
            ), null, true);

            return '<div class="login_status">Welcome "'.ucfirst(strtolower($username)).'",<br /> Last login: '.date("D, j M Y h:i:s A",  strtotime($auth->getIdentity()->last_login_time)).' <br /> <a href="'.$logoutUrl.'">Logout</a></div>';
        } else return '';


    }
}

?>