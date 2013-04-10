#!/bin/sh

PATH=$PATH:/usr/sbin/

date +"[%Y-%m-%d %H:%M:%S]"

declare -a name
declare -a inst
name[0]="controlpanel/reports/application/indexing_scripts/RabbitMQIndexing.php 127.0.0.1"
inst[0]=10
name[1]="controlpanel/reports/application/indexing_scripts/RabbitMQIndexing.php 127.0.0.1"
inst[1]=5
name[2]="controlpanel/reports/application/indexing_scripts/RabbitMQUserIndexing.php 127.0.0.1"
inst[2]=5
name[3]="controlpanel/reports/application/indexing_scripts/RabbitMQUserIndexing.php 127.0.0.1"
inst[3]=5
name[4]="controlpanel/reports/application/indexing_scripts/RabbitMQAlertsIndexing.php 127.0.0.1"
inst[4]=0
name[5]="controlpanel/reports/application/indexing_scripts/RabbitMQArchiveAdsIndexing.php 127.0.0.1"
inst[5]=5


for i in "${!name[@]}";
do
	cnsmrname=${name[$i]};
	instances=${inst[$i]};
	echo "Ensuring that $instances $cnsmrname consumers are running";
	(cd /home/reporting
	cpid=`ps -ef | grep "$cnsmrname" | grep -v 'grep' | awk '{print $2}'`;
	count=`echo $cpid | wc -w`;
	if [ "$count" -le "$instances" ];
	then
		new_consumers=$((instances-count));
		for ((k=0; k<$new_consumers; k++))
		do
			nohup /usr/local/php/bin/php $cnsmrname &
		done
	else
		extra_consumers=$((count-instances));
		for pid in $cpid;
		do
			kill -9 $pid;
			extra_consumers=$((extra_consumers-1));
			if [ "$extra_consumers" -eq "0" ];
			then
				break;
			fi
		done
	fi
	)
done

