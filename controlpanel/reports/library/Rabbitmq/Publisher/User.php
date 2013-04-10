<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class Rabbitmq_Publisher_User extends Rabbitmq_Publisher_Abstract {
    
    public function publish($message,$rkey=  parent::RKEY) {
        parent::publish($message, $rkey);
    }
}
