<?php
// Define path to application directory
//ini_set('display_errors',1);
date_default_timezone_set("Asia/Calcutta");
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/application'));


// Define path to zend framework directory
defined('ZEND_LIBRARY_PATH')
    || define('ZEND_LIBRARY_PATH', realpath(dirname(__FILE__) . '/../../zend_library'));

defined('THIRD_LIBRARY_PATH')
    || define('THIRD_LIBRARY_PATH', APPLICATION_PATH. '/../library');


//define the configuration file path
defined('CONFIG_FILE_PATH') || define('CONFIG_FILE_PATH', APPLICATION_PATH . '/configs/application.ini');

//define which configuration section to load
defined('CONFIG_SECTION') || define('CONFIG_SECTION', 'frontend');

// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(ZEND_LIBRARY_PATH),
    get_include_path(),
)));


set_include_path(implode(PATH_SEPARATOR, array(
    realpath(THIRD_LIBRARY_PATH),
    get_include_path(),
)));


/** Zend_Application */
require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
    CONFIG_SECTION,
    CONFIG_FILE_PATH
);
$configurations = $application->getOptions();

//******************redis integration
//$options = array(
//    'namespace' => 'Application_',
//    'servers'   => array(
//       array('host' => '127.0.0.1', 'port' => 6379)
//       
//    )
//);
//
//require_once THIRD_LIBRARY_PATH.'/Rediska.php';
//$rediska = new Rediska($options);
//
//$key = new Rediska_Key('keyName');
//$key->setValue('value');
//echo $key->getValue();exit;


//base path of the app
defined('BASE_PATH') || define('BASE_PATH', $_SERVER['DOCUMENT_ROOT'].$configurations['paths']['base']);
// Define base url
defined('BASE_URL') || define('BASE_URL', 'http://'.$_SERVER['SERVER_NAME'].':'.$configurations['app']['port'].$configurations['urls']['base']);

//environment
defined('APPLICATION_ENV') || define('APPLICATION_ENV', $configurations['environment']);


try {
    Zend_Session::start();
    
    Zend_Controller_Front::getInstance()->throwExceptions();
    Zend_Controller_Front::getInstance()->setControllerDirectory($configurations['resources']['frontController']['controllerDirectory']);
    
    $application->bootstrap()->run();
}
catch (Exception $e) {
    echo $e->getMessage(); exit;
}