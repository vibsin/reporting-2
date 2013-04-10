<?php
//shell_exec('cd /opt/apache-solr-1.4.1/example; java -jar start.jar & > /dev/null');
$response =  shell_exec('ps aux|grep java;');

$pattern = '/-Djetty.port=8983/';
preg_match($pattern,$response,$matches);

if(empty($matches[0])) {
    shell_exec('cd /opt/apache-solr-1.4.1/example/; java -Djetty.port=8983 -jar start.jar &');
}

