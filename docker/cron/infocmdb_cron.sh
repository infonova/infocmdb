#!/usr/bin/env bash

# check if lock File exists, if not create it and set trap on exit
 if { set -C; 2>/dev/null > /var/run/lock/infocmdb_cron; }; then
    trap "rm -f /var/run/lock/infocmdb_cron" EXIT
 else
    echo "lock file exists... exiting"
    exit
 fi

# env vars needed for cgi-fcgi
export REQUEST_METHOD="GET"
export SCRIPT_FILENAME="/app/public/cron.php"
export SCRIPT_NAME="/cron.php"

echo "starting infoCMDB cron runner..."

# first execution of cron starts endless loop of fastcgi calls to php-fpm
while true; do
    cgi-fcgi -bind -connect php:9000 1>/dev/null &
    sleep 10
done

#exit 0