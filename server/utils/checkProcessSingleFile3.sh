#!/bin/bash

# 
# Check if the process is running but the pipe does not exist. Remove the program if that is the case.
#
# /usr/bin/python3 /var/www/html/server/bin/processSingleFile3.py start
# Run this as a cron job for the processing user, instead of the above line.
#

me=$(whoami)
if [[ "$me" != "processing" ]]; then
   echo "Error: checkProcessSingleFile3.sh should only be run by the processing user, not by $me"
   exit -1
fi

project="$1"
if [ "$project" == "HBCD" ]; then
  project=""
fi

startString="/var/www/html/server/bin/processSingleFile3.py start"
if [ ! -z "$project" ]; then
    startString="/var/www/html/server/bin/processSingleFile3.py start $project"
fi

pid=`pgrep -f "/usr/bin/python3 ${startString}\s*\$"`
RETVAL=$?
if [ "$RETVAL" == "0" ]; then
    # processSingleFile3 is running right now, does the pipe exists?
    pipename="/tmp/.processSingleFilePipe${project}"
    if [ ! -p "$pipename" ]; then
	echo "`date`: Error, processSingleFile3 is running for project \"$project\" but pipe \"$pipename\" does not exist. Restart now..."
	# we should stop processSingleFile3 and restart it again
	/usr/bin/python3 /var/www/html/server/bin/processSingleFile3.py stop "${project}"
	/usr/bin/python3 /var/www/html/server/bin/processSingleFile3.py start "${project}"
    fi
    # find out if the correct user is running processSingleFile3
    # echo "ps -o user= --pid \"${pid}\""
    owner=`ps -o user= --pid "${pid}"`
    if [ "${owner}" != "processing" ]; then
	echo "`date`: Error, user \"${owner}\" is running processSingleFile3, should be run by processing user only."
    fi
else
    # restart if its not running already
    echo "`date`: Warning, starting processSingleFile3 for project \"${project}\" again, its not running already."
    /usr/bin/python3 /var/www/html/server/bin/processSingleFile3.py stop "${project}"
    /usr/bin/python3 /var/www/html/server/bin/processSingleFile3.py start "${project}"
fi
