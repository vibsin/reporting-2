<?php

/*
 * this will run once a week (on every sunday 12 pm noon ) to optimize the indexes of all cores
 * 
 * in crontab put this
 * 
 * 0 12 * * 0 /usr/local/php/bin/php /home/reporting/controlpanel/reports/application/indexing_scripts/OptimizeSolr.php
 * 
 */

include('IndexingAbstract.php');



$objSolr = new Quikr_SolrIndex();

//for search
$objSolr->indexingUrl = SOLR_SEARCH_INDEXING_URL;
$objSolr->optimizeIndexes();


//for users
$objSolr->indexingUrl = SOLR_USER_INDEXING_URL;
$objSolr->optimizeIndexes();


//for alerts
$objSolr->indexingUrl = SOLR_ALERTS_INDEXING_URL;
$objSolr->optimizeIndexes();

//for ads
$objSolr->indexingUrl = SOLR_ADS_INDEXING_URL;
$objSolr->optimizeIndexes();


//for reply
$objSolr->indexingUrl = SOLR_REPLY_INDEXING_URL;
$objSolr->optimizeIndexes();


//for reply wid ads
$objSolr->indexingUrl = SOLR_REPLY_WITH_ADS_INDEXING_URL;
$objSolr->optimizeIndexes();