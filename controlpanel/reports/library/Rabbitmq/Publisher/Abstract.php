<?php

abstract class Rabbitmq_Publisher_Abstract implements Rabbitmq_Publisher_Interface {
	protected $ex = null;
        protected $exchange = null;
        
	protected $credentials = array (
	'host'		=>	STRING_RABBITMQ_HOST,
	'port'		=>	INT_RABBITMQ_PORT,
	'vhost'		=>	STRING_RABBITMQ_VHOST,
	'login'		=>	STRING_RABBITMQ_LOGIN,
	'password'	=>	STRING_RABBITMQ_PASSWORD
	);
        
        CONST EXCHANGE_TYPE =  AMQP_EX_TYPE_FANOUT;
        CONST RKEY = "*";
        
        public function __construct($host,$exchange) {
            $this->setHost($host);
            $this->setExchange($exchange);
            $this->setConnection();
        }
        
        protected function setConnection() {
            $conn = new AMQPConnection($this->credentials);
            $conn->connect();

            $this->ex = new AMQPExchange($conn);
            $this->ex->declare($this->exchange, self::EXCHANGE_TYPE); 
        }
        
	protected function setExchange($exchange) {
            if(null !== $exchange) {
                $this->exchange = $exchange;
            } else throw new Rabbitmq_Publisher_Excetion("Exchange cannot be null");
        }
        
        
        protected function setHost($host) {
            if(null !== $host) {
                $this->credentials["host"] = $host;
            } else throw new Rabbitmq_Publisher_Excetion("Host cannot be null");
        }
        
        public function publish($message,$rkey=  parent::RKEY) {
            $this->ex->publish(base64_encode($message), $rkey);
        }

}