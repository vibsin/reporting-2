#! /bin/sh

CSV_FOLDER=/home/reporting/controlpanel/reports/assets/csv

cd $CSV_FOLDER

find -type f -name '*.csv' -exec rm -f {} \;

find -type f -name '*.zip' -exec rm -f {} \;

exit 0
