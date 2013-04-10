<?php

/*
 * This script will index all the ads which has received hits for current day
 */


include('IndexingAbstract.php');



$handle = fopen($argv[1], "r");
if ($handle) {
    $obj = new Rabbitmq_Publisher_User("127.0.0.1","duplicate_users_x");
    while (($buffer = fgets($handle, 4096)) !== false) {
        $obj->publish(serialize(trim($buffer)));
    }
    if (!feof($handle)) {
        echo "Error: unexpected fgets() fail\n";
    }
    fclose($handle);
}