<?php

class Rabbitmq_Consumer {

    private $mq;
    private $credentials = array(
        'host' => STRING_RABBITMQ_HOST,
        'port' => INT_RABBITMQ_PORT,
        'vhost' => STRING_RABBITMQ_VHOST,
        'login' => STRING_RABBITMQ_LOGIN,
        'password' => STRING_RABBITMQ_PASSWORD
    );

    public function __construct($queue, $exchange, $host=STRING_RABBITMQ_HOST,$rkey = "*") {
        if((isset($host))) {
            $this->credentials["host"] = $host;
        }
        
        $conn = new AMQPConnection($this->credentials);
        $conn->connect();
        
        $this->mq = new AMQPQueue($conn);
        $this->mq->declare($queue);
        
        $ex = new AMQPExchange($conn);
        $ex->declare($exchange, AMQP_EX_TYPE_FANOUT);
        
        $ex->bind($queue, $rkey);
        }

    public function get() {
        return $this->mq->get();
    }

    public function ack($delivery_tag) {
        return $this->mq->ack($delivery_tag);
    }

}