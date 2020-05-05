#!/bin/bash
cron_file=/app/crontab
# check and start cron if not running
if [ `ps -ef | grep cron| wc -l` -eq 0 ]; then
cron &
fi

crontab -l > $cron_file
echo "$@" >> $cron_file
crontab $cron_file
sleep 100000000
