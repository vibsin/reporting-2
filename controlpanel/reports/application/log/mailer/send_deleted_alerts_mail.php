<?php
include(realpath(dirname(__FILE__))."/../../indexing_scripts/indexing_config.php");
/**
 *Following script will send email with log attached 
 * The attached email consists of the deleted alerts 
 */
$file = shell_exec("cd ".INDEXING_LOG."; ls -t *.text | head -1");
$latestFile = INDEXING_LOG."/".trim($file);
//$latestFile = "/home/quikr/Documents/mylog.log";
//echo $latestFile;
//mail object
$mail = new Zend_Mail();

//create mime type
$at = $mail->createAttachment(file_get_contents($latestFile));
$at->type        = 'text/plain';
$at->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
$at->encoding    = Zend_Mime::TYPE_TEXT;
$at->charset     = "UTF-8";
$at->filename    = $file;
//create mail
  
$mail->setBodyText('Deleted Alerts Report, PFA');
$mail->setFrom('vsingh@quikr.com', 'System');
$mail->addTo('vsingh@quikr.com', 'Quikr');
$mail->setSubject('[Reporting Tool]: Alerts delete report');
$mail->send();