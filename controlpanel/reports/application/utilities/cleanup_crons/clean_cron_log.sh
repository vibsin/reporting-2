#!/bin/sh
#script to delete cron files created before one day

LOG_FOLDER=/home/reporting/controlpanel/reports/application/log/cron
LOG_BKUP_FOLDER=/home/reporting/controlpanel/reports/application/log/log_bkup
LOG_TAR_FILE=`date +"%m-%d-%y"`_log.tgz
cd $LOG_FOLDER
find -type f -name '*.*' -mtime -1 | sed 's/.*\.\/\(.*\)/\1/g'| xargs tar czf $LOG_TAR_FILE;
mv $LOG_FOLDER/$LOG_TAR_FILE $LOG_BKUP_FOLDER/$LOG_TAR_FILE;
find -type f -name '*.*' -mtime -1 -exec rm {} \;

exit 0;

