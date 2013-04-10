<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

interface Rabbitmq_Publisher_Interface {
    public function publish($message,$rkey);
}
