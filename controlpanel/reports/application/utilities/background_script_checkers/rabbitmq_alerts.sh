#! /bin/bash

if ps ax | grep -v grep | grep RabbitMQAlertsIndexing.php > /dev/null
then 
    echo "process running"
else 
    /usr/local/php/bin/php /home/reporting/controlpanel/reports/application/indexing_scripts/RabbitMQAlertsIndexing.php &
fi