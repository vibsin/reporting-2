<?php
date_default_timezone_set("Asia/Calcutta");
// Define path to zend framework directory
//ini_set('display_errors',1);
define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../../application'));
   
    
defined('ZEND_LIBRARY_PATH') || define('ZEND_LIBRARY_PATH', realpath(dirname(__FILE__) . '/../../../../zend_library'));
   
    
defined('THIRD_LIBRARY_PATH') || define('THIRD_LIBRARY_PATH', APPLICATION_PATH. '/../library');


//define the configuration file path
defined('CONFIG_FILE_PATH') || define('CONFIG_FILE_PATH', APPLICATION_PATH . '/configs/application.ini');

//define which configuration section to load
defined('CONFIG_SECTION') || define('CONFIG_SECTION', 'backend');

set_include_path(implode(PATH_SEPARATOR, array(
    realpath(APPLICATION_PATH.'/indexing_scripts'),
    get_include_path(),
)));


// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(ZEND_LIBRARY_PATH),
    get_include_path(),
)));


set_include_path(implode(PATH_SEPARATOR, array(
    realpath(THIRD_LIBRARY_PATH),
    get_include_path(),
)));


//function shutdown() {
//	// get last error
//	$last_error = error_get_last ();
//	print_r($last_error);
//	// show user error message if any error happen
//	if (! is_null ( $last_error )) {
//		
//		// get error type
//		$errorType = $last_error ['type'];
//		$criticalError = array (E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_RECOVERABLE_ERROR );
//		
//		if (in_array ( $errorType, $criticalError )) {
//			// mail to admin
//			// mail('admin@example.com', ....);
//			//echo "Sorry, an unexpected error happen, please check later";exit;
//			
//
//			ErrorManager::Log ( E_ERROR, $last_error ['message'], $last_error ['file'], $last_error ['line'] );
//		}
//		
//	// print a proper message to output
//	//echo "Sorry, an unexpected error happen, please check later";
//	}
//}
//
//// register the shutdown function
//register_shutdown_function ( 'shutdown' );



/** Zend_Application */
/** You can use any of the below method to use ZF without MVC pattern*/

/************Method1 - using Zend_Application************/
//require_once 'Zend/Application.php';
//
//// Create application, bootstrap, and run
//$application = new Zend_Application(
//    CONFIG_SECTION,
//    CONFIG_FILE_PATH
//);
//$configurations = $application->getOptions();


/************Method2 - using Zend_Loader_Autoloader************/

//This will initiate the Zend library to load all the necessary classes
require_once 'Zend/Loader/Autoloader.php';
$loader = Zend_Loader_Autoloader::getInstance();

$loader->registerNamespace('Quikr_');
$loader->registerNamespace('Rabbitmq_');

$resourceLoader=new Zend_Loader_Autoloader_Resource(
			array(
                            'basePath'	 => APPLICATION_PATH,
			    'namespace'=> '')
		);

$resourceLoader->addResourceType('model','models','Model');
$resourceLoader->addResourceType('utility','utilities','Utility');

$config = new Zend_Config_Ini(CONFIG_FILE_PATH, CONFIG_SECTION);
//environment
defined('APPLICATION_ENV') || define('APPLICATION_ENV', $config->environment);

$params = array(
    'host'           => $config->resources->multidb->db1->host,
    'username'       => $config->resources->multidb->db1->username,
    'password'       => $config->resources->multidb->db1->password,
    'dbname'         => $config->resources->multidb->db1->dbname
);

//Initialize DB connection && store in registry
$db = Zend_Db::factory('Pdo_Mysql', $params);
try {
    //$db->getConnection();
    //Zend_Db_Table_Abstract::setDefaultAdapter($db);
    Zend_Registry::set('dbconnection', $db);
    
} catch (Excepetion $e) {
    echo 'Cannot connect to DB now'; exit;
}


//new db connection for Alerts coz table was moved in another Db
$alertsDB = Zend_Db::factory('Pdo_Mysql', array(
    'host'           => $config->resources->multidb->db2->host,
    'username'       => $config->resources->multidb->db2->username,
    'password'       => $config->resources->multidb->db2->password,
    'dbname'         => $config->resources->multidb->db2->dbname
));


try {
    //$alertsDB->getConnection();
    //Zend_Db_Table_Abstract::setDefaultAdapter($alertsDB);
    Zend_Registry::set('alertsdbconnection', $alertsDB);

} catch (Exception $e) {
    echo 'Cannot connect to alerts DB now:'.$e->getMessage(); exit;
}





//new db connection for  procedure as it was moved in another Db
$alertsDB = Zend_Db::factory('Pdo_Mysql', array(
    'host'           => $config->resources->multidb->db3->host,
    'username'       => $config->resources->multidb->db3->username,
    'password'       => $config->resources->multidb->db3->password,
    'dbname'         => $config->resources->multidb->db3->dbname
));


try {
    //$alertsDB->getConnection();
    //Zend_Db_Table_Abstract::setDefaultAdapter($alertsDB);
    Zend_Registry::set('procdbconnection', $alertsDB);

} catch (Exception $e) {
    echo 'Cannot connect to procedure DB now:'.$e->getMessage(); exit;
}


//auth DB--using sqlite
$authDB = Zend_Db::factory($config->resources->multidb->db4->adapter, 
        array("dbname"=>$config->resources->multidb->db4->dbname));

try {
    $authDB->getConnection();
    Zend_Registry::set('authDbConnection', $authDB);

} catch (Exception $e) {
    echo 'Cannot connect to DB now:'.$e->getMessage(); exit;
}


//new db connection for WRITE db
$writeDB = Zend_Db::factory('Pdo_Mysql', array(
    'host'           => $config->resources->multidb->db5->host,
    'username'       => $config->resources->multidb->db5->username,
    'password'       => $config->resources->multidb->db5->password,
    'dbname'         => $config->resources->multidb->db5->dbname
));


try {
    Zend_Registry::set('writedb', $writeDB);

} catch (Exception $e) {
    echo 'Cannot connect to write DB now:'.$e->getMessage(); exit;
}


//new db connection for ARCHIVE db
$archiveDB = Zend_Db::factory('Pdo_Mysql', array(
    'host'           => $config->resources->multidb->db6->host,
    'username'       => $config->resources->multidb->db6->username,
    'password'       => $config->resources->multidb->db6->password,
    'dbname'         => $config->resources->multidb->db6->dbname
));


try {
    Zend_Registry::set('archivedb', $archiveDB);

} catch (Exception $e) {
    echo 'Cannot connect to archive DB now:'.$e->getMessage(); exit;
}



//define app constants
define('SOLR_ALERTS_INDEXING_URL',$config->solr->indexing_url->alerts);
define('SOLR_SEARCH_INDEXING_URL',$config->solr->indexing_url->search);
define('SOLR_ADS_INDEXING_URL',$config->solr->indexing_url->ads);
define('SOLR_USER_INDEXING_URL',$config->solr->indexing_url->user);
define('SOLR_REPLY_INDEXING_URL',$config->solr->indexing_url->reply);
define('SOLR_REPLY_WITH_ADS_INDEXING_URL',$config->solr->indexing_url->reply_with_ads);
define('SOLR_PREMIUM_ADS_INDEXING_URL',$config->solr->indexing_url->premiumads);
define('SOLR_VD_INDEXING_URL',$config->solr->indexing_url->vd);
define('SOLR_BGS_INDEXING_URL',$config->solr->indexing_url->bgs);

define('SOLR_PATH', $config->solr->path);
define('SOLR_START_CMD', $config->solr->start_command);
define('SOLR_META_QUERY_BASE', $config->solr->query_url->base);
define('SOLR_INDEX_ERROR_LOG',$config->solr->indexing_error_logfile_path);
define('SOLR_META_QUERY_USERS', $config->solr->query_url->users);
define('SOLR_META_QUERY_ADS', $config->solr->query_url->ads);
define('SOLR_META_QUERY_REPLIES', $config->solr->query_url->replies);
define('SOLR_META_QUERY_ALERTS', $config->solr->query_url->alerts);
define('SOLR_META_QUERY_SEARCH', $config->solr->query_url->search);
define('SOLR_META_QUERY_REPLY_WITH_ADS',$config->solr->query_url->reply_with_ads);
define('SOLR_META_QUERY_PREMIUM_AD', $config->solr->query_url->premiumads);
define('SOLR_META_QUERY_VD', $config->solr->query_url->vd);
define('SOLR_META_QUERY_BGS', $config->solr->query_url->bgs);

defined('MAX_RESULTS_PER_PAGE') || define('MAX_RESULTS_PER_PAGE', $config->solr->max_records_per_page);

//define('QUIKR_RESOURCE_PATH', realpath(dirname(__FILE__) . '/../../../../'));
//define('ALLOWED_ATTRIBUTES', serialize(array($config->app->constants->allowed_attributes)));
Zend_Registry::set('ALLOWED_ATTRIBUTES', array('Brand_name','Year','No_of_Rooms','Type_of_land','Type_of_Job','You_are','Condition'));
define('REPORTING_MAX_RECORDS_FOR_INDEXING',$config->solr->max_records_for_indexing); //set this to 100000, this will fetch first 100000 records
define('REPORTING_LIMIT_FOR_INDEXING',$config->solr->max_limit_for_indexing); //set this to 1000, query will run in steps of 1000


//define which configuration section to load
defined('INDEXING_LOG') || define('INDEXING_LOG', $config->solr->indexing_logfile_path);
defined('PHP_MEMORY_LIMIT') || define('PHP_MEMORY_LIMIT', $config->php_ini->memory_limit);
defined('PHP_EXECUTABLE_PATH') || define('PHP_EXECUTABLE_PATH', $config->app->php_executable_path);
//includes
require_once(THIRD_LIBRARY_PATH.'/Quikr/SolrIndex.php');


ini_set('memory_limit', PHP_MEMORY_LIMIT);

define('SOLR_LOGGING_FROM_EMAIL_ID', $config->app->mail->from_email_id);
define('SOLR_LOGGING_FROM_EMAIL_NAME', $config->app->mail->from_email_name);

/* Rabbit MQ connection credentials */
define('STRING_RABBITMQ_HOST', $config->app->rmq->host);
define('INT_RABBITMQ_PORT', $config->app->rmq->port);
define('STRING_RABBITMQ_VHOST', $config->app->rmq->vhost);
define('STRING_RABBITMQ_LOGIN', $config->app->rmq->username);
define('STRING_RABBITMQ_PASSWORD', $config->app->rmq->password);

define('WURFL_BASE_PATH',$config->app->wurfl->path);

/**************DEFINE ALL CONSTANTS ABOVE THIS LINE***************/
//WURFL Settings start
$resourcesDir = WURFL_BASE_PATH.'/resources';
require_once WURFL_BASE_PATH.'/Application.php';

$persistenceDir = $resourcesDir.'/storage/persistence';
$cacheDir = $resourcesDir.'/storage/cache';

// Create WURFL Configuration
$wurflConfig = new WURFL_Configuration_InMemoryConfig();

// Set location of the WURFL File
$wurflConfig->wurflFile($resourcesDir.'/wurfl.zip');

// Set the match mode for the API ('performance' or 'accuracy')
$wurflConfig->matchMode('performance');

// Setup WURFL Persistence
$wurflConfig->persistence('file', array('dir' => $persistenceDir));

// Setup Caching
$wurflConfig->cache('file', array('dir' => $cacheDir, 'expiration' => 36000));

// Create a WURFL Manager Factory from the WURFL Configuration
$wurflManagerFactory = new WURFL_WURFLManagerFactory($wurflConfig);

// Create a WURFL Manager
/* @var $wurflManager WURFL_WURFLManager */
$wurflManager = $wurflManagerFactory->create();

Zend_Registry::set("WURFL_MNGR", $wurflManager);
//WURFL Settings ends

###################################################

//Redis Settings start
$redisOptions = array(
'namespace' => $config->redis->options->namespace,
'servers'   => array(
    array('host' => $config->redis->options->server->host, 
        'port' => $config->redis->options->server->port)

    )
);

require_once THIRD_LIBRARY_PATH.'/Rediska.php';
$rediska = new Rediska($redisOptions);
Zend_Registry::set('rediska', $rediska);
//Redis Settings end

//mail settings
defined('MAIL_SMTP_SERVER') || define('MAIL_SMTP_SERVER', $config->app->mail->smtp_server);
Zend_Mail::setDefaultTransport(new Zend_Mail_Transport_Smtp(MAIL_SMTP_SERVER));

?>
