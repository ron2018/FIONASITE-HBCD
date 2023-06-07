#!/bin/bash

# Example crontab entry that starts this script once a day
# 1 1 * * * /usr/bin/nice -n 3 /var/www/html/server/bin/sendFiles.sh
# Add the above line to your machine using:
#   > crontab -e
#
# This script is designed to send compressed raw dicom data files
# to the UMN endpoint using rsync and register the files to UMN API. All data in the /data/site/rawdata/ directory will
# be packed as triblet_id_MRI_SUID.tar.tgz into /data/site/rawdata/outbox and rsyn to UMN
# Sctipt reads the suidlists.csv in /data/site/rawdata/ and send the SUIDs in the file.
# After sending, update the sent list.
# 

me=$(whoami)
if [[ "$me" != "processing" ]]; then
   echo "Error: sendFiles should only be run by the processing user, not by $me"
   exit -1
fi


SERVERDIR=`dirname "$(readlink -f "$0")"`/../
log=${SERVERDIR}/logs/sendRawFile${project}.log
user=`cat /data/config/config.json | jq -r ".SERVERUSER"`
# directory storing the files that are ok to send
pfiles=/data${project}/site/rawdata/outbox
pfolder=/data${project}/site/rawdata/

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
#
# connect to  hbcdsftp.ahc.umn.edu  using keyless ssh access
#


cd $pfolder
grep -avxFf sentlists.csv suidlists.csv > suidsdiff.csv

# given error message, and exiting with an error code.
function error_exit {
    echo
    echo "$@"
    exit 1
}


sendFile () {

   while IFS="," read -r tripleId SUID run
   do

      echo "tripleId: $tripleId, SUID: $SUID run: $run"
      if [ "$tripleId" == "" ]; then
         echo "sendRawFile needs two parameters : tripletID and SUID"
         exit
      fi
   
      echo "`date`: Pack and send this SUID ${SUID}" >> $log

      # pack the data in /data/site/rawdata/scp_ folders into outbox  
      # and calculate  md5sums for the files.
 
      filename="scp_${SUID}"
      echo $filename
      cd $pfolder

      find ${filename} -type f -print > tempfile || error_exit 
      
      tar cfz ${pfiles}/${tripleId}_MRI_${SUID}_${run}.tar.gz  -T ./tempfile || error_exit
      md5c=`/usr/bin/md5sum -b ${pfiles}/${tripleId}_MRI_${SUID}_${run}.tar.gz`
      echo $md5c > ${pfiles}/${tripleId}_MRI_${SUID}_${run}.md5sum
 
     
      #rsync this file and md5sum file
     { /usr/bin/rsync -LptgoDv0 --no-R ${pfiles}/${tripleId}_MRI_${SUID}_${run}.* hbcd_${user}_fiona@${endpoint}:/home/hbcd_${user}_fiona || error_exit
      #move the rsync file from outbox to UMN folder 
     } && {
      mv ${pfiles}/${tripleId}_MRI_${SUID}_${run}.* ${pfolder}/umn/
    } && {
      /usr/bin/python /var/www/html/server/bin/registerRawFileUpload.py --filename=${tripleId}_MRI_${SUID}_${run}.tar.gz --token=$token --type=MRI  >> $log 2>&1 
   }
   done < suidsdiff.csv
   
   cat suidsdiff.csv >> sentlists.csv

   echo "`date`: Complete pack and rsync this SUID ${filename}" >> $log
  

}

# The following section takes care of not starting this script more than once 
# in a row. If for example it takes too long to run a single iteration this 
# will ensure that no second call is executed prematurely.
(
  flock -n 9 || exit 1
  # command executed under lock
  sendFile
) 9>${SERVERDIR}/.pids/sendRawFiles.lock
