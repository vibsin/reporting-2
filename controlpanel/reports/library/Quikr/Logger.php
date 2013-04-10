<?php
class Quikr_Logger{
	private static $instance;

	private $logFile;
	private $errorFile;
	private $defaultPath;
	private $missingOrderIds;
	
	private function __construct() {
            $this->defaultPath = SOLR_INDEX_ERROR_LOG;
            
		if (!is_dir($this->defaultPath)){
			mkdir($this->defaultPath,0777);
		}
		$this->logFile = $this->defaultPath.'/logger.txt';
		$this->errorFile = $this->defaultPath.'/error.txt';
		$this->missingOrderIds=$this->defaultPath.'/Premium_missing_ads'.date('d-m-Y-h-i-s').'.txt';
	}

	/**
	 * Singleton getInstance
	 *
	 * @return Quikr_Logger
	 */
	public static function getInstance() {
		if (!self::$instance instanceof self) {
			self::$instance = new self;
		}

		return self::$instance;
	}

/**
 * Log informative messages in file
 *
 * @param string $message
 */
	public function logInfo($message){

		$logger = new Zend_Log();

		$writer = new Zend_Log_Writer_Stream($this->logFile);
		$format = 'LOG:'. date(' Y-m-d H:i:s')." %message%" . PHP_EOL;
		$formatter = new Zend_Log_Formatter_Simple($format);
		$writer->setFormatter($formatter);
		$logger->addWriter($writer);
		$logger->log($message, Zend_Log::INFO);
	}


/**
 * Log error messages in file
 *
 * @param string $message
 */
	public function logError($message){

		$logger = new Zend_Log();

		$writer = new Zend_Log_Writer_Stream($this->errorFile);
		$format = 'LOG:'. date(' Y-m-d H:i:s')." %message%" . PHP_EOL;
		$formatter = new Zend_Log_Formatter_Simple($format);
		$writer->setFormatter($formatter);
		$logger->addWriter($writer);

		$logger->log($message, Zend_Log::INFO);
	}

/**
 * Log any messages onto stdout
 *
 * @param string $message
 */
	public function log($message){
		$logger = new Zend_Log();

		$writer = new Zend_Log_Writer_Stream('php://output');
		$format = 'LOG:'. date(' Y-m-d H:i:s')." %message%" . PHP_EOL;
		$formatter = new Zend_Log_Formatter_Simple($format);
		$writer->setFormatter($formatter);
		$logger->addWriter($writer);

		$logger->log($message,  Zend_Log::INFO );
	}
	
	/**
	 * Log Missing Ads which are not present in Ads Solr core
	 *
	 * @param string $message
	 */
	
	public function logMissingAds($message){
		
		$logger = new Zend_Log();

		$writer = new Zend_Log_Writer_Stream($this->missingOrderIds);
		$format = 'LOG:'. date(' Y-m-d H:i:s')." %message%" . PHP_EOL;
		$formatter = new Zend_Log_Formatter_Simple($format);
		$writer->setFormatter($formatter);
		$logger->addWriter($writer);

		$logger->log($message, Zend_Log::INFO);
		
	}

}
?>