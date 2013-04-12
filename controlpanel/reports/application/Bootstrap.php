<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

    private $config;

    protected function _initAutoload(){
            $autoloader=Zend_Loader_Autoloader::getInstance();

            $autoloader->registerNamespace('Quikr_');
            $autoloader->registerNamespace('Rabbitmq_');
            $autoloader->registerNamespace('Docs_');
            $autoloader->registerNamespace('WURFL_');
            //register our extended classes
            $autoloader->registerNamespace('Extended_');

            $this->config = new Zend_Config_Ini(CONFIG_FILE_PATH, CONFIG_SECTION);
            
            $params = array(
                'host'           => $this->config->resources->multidb->db1->host,
                'username'       => $this->config->resources->multidb->db1->username,
                'password'       => $this->config->resources->multidb->db1->password,
                'dbname'         => $this->config->resources->multidb->db1->dbname
            );

            //by vibhor
            $db = Zend_Db::factory('Pdo_Mysql', $params);
            try {
                $db->getConnection();
                Zend_Db_Table_Abstract::setDefaultAdapter($db);
                Zend_Registry::set('dbconnection', $db);
                
            } catch (Exception $e) {
                echo 'Cannot connect to DB now:'.$e->getMessage(); exit;
            }
            
            
            //auth DB--using sqlite
            $authDB = Zend_Db::factory($this->config->resources->multidb->db4->adapter, 
                    array("dbname"=>$this->config->resources->multidb->db4->dbname));
            
            try {
                $authDB->getConnection();
                Zend_Registry::set('authDbConnection', $authDB);
                
            } catch (Exception $e) {
                echo 'Cannot connect to DB now:'.$e->getMessage(); exit;
            }
            
            $resourceLoader=new Zend_Loader_Autoloader_Resource(
			array(
                            'basePath'	 => APPLICATION_PATH,
			    'namespace'=> '')
		);
            
            
            //go on adding your resources
            $resourceLoader->addResourceType('model','models','Model');
            $resourceLoader->addResourceType('forms','forms','Forms');
            $resourceLoader->addResourceType('utility','utilities','Utility');
            
            //add ACL plugin 
            $frontController = Zend_Controller_Front::getInstance();
            $frontController->registerPlugin(new Extended_Auth_Login());
            
            return $autoloader;
	}

        public function _initSetVarConstants() {

            defined('QUIKR_PAGE_TITLE') || define('QUIKR_PAGE_TITLE', $this->config->page->title);
	    define('USER_SUMMARY_SOLR_LIMIT', $this->config->solr->user_summary_limit);
	    define('USER_SOLR_LIMIT', $this->config->solr->user_limit);
	    define('FILE_EXPORT_MAX_LIMIT', $this->config->solr->export->max_limit);
	    define('FILE_EXPORT_SOLR_LIMIT', $this->config->solr->export->limit);
            defined('MAX_SOLR_RESULTS') || define('MAX_SOLR_RESULTS', $this->config->solr->max_results);
            defined('MAX_RESULTS_PER_PAGE') || define('MAX_RESULTS_PER_PAGE', $this->config->solr->max_records_per_page);

            Zend_Registry::set('ALLOWED_ATTRIBUTES', array('Brand_name','Year','No_of_Rooms','Type_of_land','Type_of_Job','You_are','Condition'));

            defined('TO_DATE_INCREMENT') || define('TO_DATE_INCREMENT', $this->config->app->constants->to_date_increment);
            
            //email
            define('SOLR_LOGGING_FROM_EMAIL_ID', $this->config->app->mail->from_email_id);
            define('SOLR_LOGGING_FROM_EMAIL_NAME', $this->config->app->mail->from_email_name);
            define('ADMIN_PASSWORD_SALT', $this->config->app->password->salt);
            defined('PHP_EXECUTABLE_PATH') || define('PHP_EXECUTABLE_PATH', $this->config->app->php_executable_path);
            defined('CSV_FILE_MODE') || define('CSV_FILE_MODE', $this->config->app->csv_file_mode);
            
            defined('MAIL_SMTP_SERVER') || define('MAIL_SMTP_SERVER', $this->config->app->mail->smtp_server);
            Zend_Mail::setDefaultTransport(new Zend_Mail_Transport_Smtp(MAIL_SMTP_SERVER));
            
            //GA
            if($this->config->app->ga->status == "1") {
                defined("GA_ACCOUNT_STATUS") || define("GA_ACCOUNT_STATUS","1");
                if($this->config->environment == "production") {
                    defined("GA_ACCOUNT") || define("GA_ACCOUNT",$this->config->app->ga->production);
                } else {
                    defined("GA_ACCOUNT") || define("GA_ACCOUNT",$this->config->app->ga->development);
                }
            } else {
                defined("GA_ACCOUNT_STATUS") || define("GA_ACCOUNT_STATUS","0");
            }
            
            define('SOLR_LOGGING_TO_EMAIL_ID', $this->config->app->mail->solr_query_analyzer->to_email_id);
            define('SOLR_LOGGING_TO_EMAIL_NAME', $this->config->app->mail->solr_query_analyzer->to_email_id);
            
        }


        /**
         * Here we set all the MSGs (error/information/others) for the frontend application
         */
        public function _initSetMsgConstants() {
            define('USER_NO_RECORD_FOUND', $this->config->error->msgs->no_record_found);
            define('INVALID_DATE_ERROR', $this->config->error->msgs->invalid_date);
	    define('NO_SUMMARY_FOUND', $this->config->error->msgs->future_date);
            define('ERROR_SUMMARY_SELECT', $this->config->error->msgs->no_record_found_summary);
            define('FUTURE_DATE_ERROR', $this->config->error->msgs->no_selection);
        }

        /**
         * This function will set all the paths relative to the application folder. It will also set the URLs of JS/CSS/SOLR
         */

        protected function _initSetPathConstants() {
   
            defined('MODEL_PATH') || define('MODEL_PATH', $this->config->paths->models);

            // path where generated CSV files will be stored
	    defined('BASE_PATH_CSV') || define('BASE_PATH_CSV',$this->config->paths->csv_files);

            //JS path
            defined('JS_URL') || define('JS_URL',$this->config->urls->js);

            //CSS path
            defined('CSS_URL') || define('CSS_URL',$this->config->urls->css);
            
            //IMAGES path
            defined('IMAGES_URL') || define('IMAGES_URL',$this->config->urls->images);


            define('SOLR_PATH', $this->config->solr->path);
            define('SOLR_PORT', $this->config->solr->port);
            define('SOLR_START_CMD', $this->config->solr->start_command);
            define('SOLR_META_QUERY_BASE', $this->config->solr->query_url->base);
            define('SOLR_INDEX_ERROR_LOG',$this->config->solr->indexing_error_logfile_path);
	    define('SOLR_META_QUERY_USERS', $this->config->solr->query_url->users);
	    define('SOLR_META_QUERY_ADS', $this->config->solr->query_url->ads);
            define('SOLR_META_QUERY_SLAVE_ADS', $this->config->solr->query_url->slave->ads);
	    define('SOLR_META_QUERY_REPLIES', $this->config->solr->query_url->replies);
            define('SOLR_META_QUERY_REPLY_WITH_ADS', $this->config->solr->query_url->reply_with_ads);
	    define('SOLR_META_QUERY_ALERTS', $this->config->solr->query_url->alerts);
	    define('SOLR_META_QUERY_SEARCH', $this->config->solr->query_url->search);
	    
	    define('SOLR_META_QUERY_PREMIUM_AD', $this->config->solr->query_url->premiumads);
            define('SOLR_META_QUERY_VD', $this->config->solr->query_url->vd);
            
            define('SOLR_META_QUERY_BGS', $this->config->solr->query_url->bgs);
            
            /* Rabbit MQ connection credentials */
            define('STRING_RABBITMQ_HOST', $this->config->app->rmq->host);
            define('INT_RABBITMQ_PORT', $this->config->app->rmq->port);
            define('STRING_RABBITMQ_VHOST', $this->config->app->rmq->vhost);
            define('STRING_RABBITMQ_LOGIN', $this->config->app->rmq->username);
            define('STRING_RABBITMQ_PASSWORD', $this->config->app->rmq->password);
            
            define('WURFL_BASE_PATH',$this->config->app->wurfl->path); 
        }

        protected function _initView() {
            $view = new Zend_View();

            $view->addHelperPath(ZEND_LIBRARY_PATH.'/Zend/View/Helper');
            

            $view->baseUrl = Zend_Controller_Front::getInstance()->getBaseUrl();

            $layout = new Zend_Layout(array(
                      'layoutPath' => APPLICATION_PATH.'/layouts/scripts',
                      'layout' => 'default'
                      ),true);
            $view = $layout->getView();


            $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
            $viewRenderer->setView($view);
            return $view;
        }
        
        /**
         * This will enable caching function 
         */
        protected function _initCache() {
            $this->config = new Zend_Config_Ini(CONFIG_FILE_PATH, CONFIG_SECTION);
            if($this->config->is_caching_allowed == true) {
                
                $frontendOptions = array(
                                        "lifetime" => 24*60*60,//"300",
                                        "automatic_serialization" => true
                    );
                
                $backendOptions = array(
                    "cache_dir" => $this->config->paths->cache
                );

                $cache = Zend_Cache::factory("Core", "File",$frontendOptions,$backendOptions);
                $cache->clean(Zend_Cache::CLEANING_MODE_OLD);
                Zend_Registry::set("reporting_cache", $cache);
            }
            
        }
        protected function _initWURFLSettings() {
            //$wurflDir = THIRD_LIBRARY_PATH."/WURFL";
            $resourcesDir = WURFL_BASE_PATH.'/resources';
	    require_once WURFL_BASE_PATH.'/Application.php';
            
            $persistenceDir = $resourcesDir.'/storage/persistence';
            $cacheDir = $resourcesDir.'/storage/cache';

            // Create WURFL Configuration
            $wurflConfig = new WURFL_Configuration_InMemoryConfig();

            // Set location of the WURFL File
            $wurflConfig->wurflFile($resourcesDir.'/wurfl.zip');

            // Set the match mode for the API ('performance' or 'accuracy')
            $wurflConfig->matchMode('accuracy');

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
            //for test uncomment below code
            //$requestingDevice = $wurflManager->getDeviceForUserAgent("Mozilla/5.0 (Windows NT 5.1; rv:12.0) Gecko/20100101 Firefox/12.0");
            //$is_wireless = ($requestingDevice->getCapability('is_wireless_device') == 'true');
            //echo $is_wireless;
        }
        
        protected function _initRedis() {
            /*******redis integration****/
            $redisOptions = array(
            'namespace' => $this->config->redis->options->namespace,
            'servers'   => array(
                array('host' => $this->config->redis->options->server->host, 
                    'port' => $this->config->redis->options->server->port)

                )
            );

            require_once THIRD_LIBRARY_PATH.'/Rediska.php';
            $rediska = new Rediska($redisOptions);
            Zend_Registry::set('rediska', $rediska);
        }
        
        
        
}
