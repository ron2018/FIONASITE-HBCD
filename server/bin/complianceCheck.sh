#!/bin/bash

#
# run protocol compliance check
#
# This script will be called by incrond, install by adding to processing users incron
#   /var/www/html/php/request_compliance_check IN_CREATE,IN_MOVED_TO /var/www/html/server/bin/complianceCheck.sh $#
#

if [ "$#" -ne 1 ]; then
   echo "Usage: scp_study instance uid>"
   exit;
fi

# we can have a project at the end of the touch file, lets see if there is an underscore in the filename
PROJECT=""
SDIR=""
if [[ $1 == *"_"* ]]; then
    PROJECT=`echo "$1" | cut -d'_' -f2-`
    SDIR=`echo "$1" | cut -d'_' -f1`
    SDIR=scp_${SDIR}
else 
    SDIR=scp_$1
fi

SERVERDIR=`dirname "$(readlink -f "$0")"`/../
log=${SERVERDIR}/logs/compliance_check.log
echo "`date`: compliance check started ${SDIR}" >> $log


d=/data${PROJECT}/site/output/${SDIR}/series_compliance
mkdir -p ${d}
SSDIR=${SDIR:4}
# remove the input file (for next time)
echo "`date`: remove the input file /var/www/html/php/request_compliance_check/$1" >> $log
rm -f "/var/www/html/php/request_compliance_check/$1"

echo "`date`: protocol compliance check (python complianceCheck.py --suid=${SDIR} >> $log &)" >> $log
python complianceCheck.py --suid=${SDIR}

echo "`date`: compliance check finished for ${SDIR} with \"$id\"" >> $log
