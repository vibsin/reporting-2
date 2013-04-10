<?php

//config file to be used for crons
define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../../../application'));
define('BASE_PATH', realpath(dirname(__FILE__) . '/../../../'));   
    
defined('ZEND_LIBRARY_PATH') || define('ZEND_LIBRARY_PATH', realpath(dirname(__FILE__) . '/../../../../../zend_library'));
   
    
defined('THIRD_LIBRARY_PATH') || define('THIRD_LIBRARY_PATH', APPLICATION_PATH. '/../../library');


//define the configuration file path
defined('CONFIG_FILE_PATH') || define('CONFIG_FILE_PATH', APPLICATION_PATH . '/configs/application.ini');

//define which configuration section to load
defined('CONFIG_SECTION') || define('CONFIG_SECTION', 'clean_up');



// Ensure library/ is on include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(ZEND_LIBRARY_PATH),
    get_include_path(),
)));



//This will initiate the Zend library to load all the necessary classes
require_once 'Zend/Loader/Autoloader.php';
$loader = Zend_Loader_Autoloader::getInstance();


$config = new Zend_Config_Ini(CONFIG_FILE_PATH, CONFIG_SECTION);

//csv file path
define('BASE_PATH_CSV',$config->paths->csv_files);
//indexing cron log path
define('INDEXING_LOG',$config->solr->indexing_logfile_path);

