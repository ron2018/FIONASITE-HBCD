#!/bin/bash

# Example crontab entry that starts this script once a day
#
# This script is designed to send compressed data files with raw DICOM data
# to the UMN endpoint using rsync and register the files to UMN API. All data in the /data/site/rawdata/ directory will
# be packed as triblet_id_MRI_SUID.tar.tgz into /data/site/rawdata/outbox and rsyn to UMN
# Sctipt takes three parameters $1 tripleId, $2 SUID $3 run
# and called at the end of the 

me=$(whoami)
if [[ "$me" != "processing" ]]; then
   echo "Error: sendFiles should only be run by the processing user, not by $me"
   exit -1
fi

SUID=""
tripleId=""
run=""

if [ "$#" -eq 3 ]; then
  tripleId="$1"
  SUID=$2
  run=$3
fi

if [ "$tripleId" == "" ]; then
   echo "packRawDicomFiles.sh needs three parameters : tripletID, SUID and Run"
   exit
fi
echo "TripleID: $tripleId Run: $run and SUID: $SUID"


SERVERDIR=`dirname "$(readlink -f "$0")"`/../
log=${SERVERDIR}/logs/packRawDicomFiles.log
user=`cat /data/config/config.json | jq -r ".SERVERUSER"`
# directory storing the files that are ok to send
pfiles=/data/site/rawdata/outbox
pfolder=/data/site/rawdata/


echo "`date`: Pack and send this SUID ${SUID}" >> $log


endpoint=`cat /data/config/config.json | jq -r ".UMNSERVER"`
if [ "$project" != "" ]; then
    endpoint=`cat /data/config/config.json | jq -r ".SITES.${project}.UMNSERVER"`
fi
echo "Endpoint selected: $endpoint"

token==`cat /data/config/config.json | jq -r ".CONNECTION"`
if [ "$token" == "" ]; then
   echo "CONNECTION string is missing from /data/config/config.json file"
   exit
fi
echo "Toekn Used : $token"
#
# connect to  hbcdsftp.ahc.umn.edu  using keyless ssh access
#

# pack the data in /data/site/rawdata/scp_ folders into outbox  
# and calculate  md5sums for the files.
 
filename="scp_${SUID}"
echo $filename
cd $pfolder

find ${filename} -type f -print > tempfile
tar cvfz ${pfiles}/${tripleId}_MRI_${SUID}.tar.gz  -T ./tempfile
sf="${tripleId}_MRI_${SUID}.md5sum"
echo $sf
md5c=`/usr/bin/md5sum -b ${pfiles}/${tripleId}_MRI_${SUID}.tar.gz`
echo $md5c > ${pfiles}/${tripleId}_MRI_${SUID}.md5sum
 
     
  #done
  #rsync this file and md5sum file
  #/usr/bin/rsync -LptgoDv0 --no-R ${pfiles}/${tripleId}_MRI_${SUID}.* hbcd_${user}_fiona@${endpoint}:/home/hbcd_${user}_fiona
  #move the rsync file from outbox to UMN folder
  #mv ${pfiles}/${tripleId}_MRI_${SUID}.tar.gz ${pfolder}/umn/
   
/usr/bin/python /var/www/html/server/bin/registerRawFileUpload.py --filename=${tripleId}_MRI_${SUID} --token=$token >> $log 2>&1 
  
echo "`date`: Complete pack and rsync this SUID ${filename}" >> $log
