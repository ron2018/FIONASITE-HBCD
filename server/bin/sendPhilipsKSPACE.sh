#!/bin/bash
#
# For HBCD: Philips k-space data from an MRI scanner is stored as .zip files and name convention is following:
# scanDate_SUID as top folder, which contains SeUID.zip and Extra_files and others.
# 
# This script tries to rename and pack the files as following: 
# for series:  rawdata_SUID_suid_SeUID_seuid.zip
# compress Extra_files as rawdata_SUID_suid_Extra_files.zip
# Compress Text files as rawdata_SUID_suid_Text_files.zip
# 
# Fast compression is enabled if you have pigz installed.
#
# This script should be run by user processing (creates mount point).
#
# 1 2 * * * /var/www/html/server/bin/sendPhilipsKSPACE.sh >> /var/www/html/server/logs/sendPhilipsKSPACE.sh.log 2>&1
#

# A .dat file needs to be at least that many seconds old (modification time) before it will be copied
oldtime=15

echo "starting sendPhilipsKSPACE.sh *** "
#
# check the user account, this script should run as root
#
if [[ $USER !=  "processing" ]]; then
   echo "This script must be run by the processing user"
   exit 1
fi

SERVERDIR=`dirname "$(readlink -f "$0")"`/../
user=`cat /data/config/config.json | jq -r ".SERVERUSER"`

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
# Make sure this script runs only once (each .zip file is about 30G).
# We will wait until one run is done before we attempt the next.
#
thisscript=`basename "$0"`
for pid in $(/usr/sbin/pidof -x "$thisscript"); do
    if [ $pid != $$ ]; then
        echo "[$(date)] : ${thisscript} : Process is already running with PID $pid"
        exit 1
    fi
done

# given error message, and exiting with an error code.
function error_exit {
    echo
    echo "$@"
    exit 1
}


log=${SERVERDIR}/logs/sendKspaceFiles.log
if [ ! -f "$log" ]; then
   touch "$log"
fi

kspaceDatLocations="/data/site/kspace"
cd  $kspaceDatLocations

#
# now go through all the files on the /data/site/kspace/ folder
#
find ${kspaceDatLocations} -type f -name *.tgz -print0 | while read -d $'\0' file
do

  # only look at files that are at least oldtime seconds old
  if [ "$(( $(date +"%s") - $(stat -c "%Y" "$file") ))" -lt "$oldtime" ]; then
        echo "`date`: too young $file"
        continue
  fi

  echo $file
  tar -xf $file

  # pattern is : 20221213_1.3.46.670589.11.45002.5.0.15852.2022121307120758000.tgz
  # tar the file
  filedir=$(echo `basename "$file"` | sed "s/.tgz//")
  
  echo $filedir
   
  suid=$(echo ${filedir} | cut -d'_' -f2)
  
  echo $suid

  cd $filedir
  echo ${kspaceDatLocations}/${filedir} 
  #rename the zip file to rawdata_suid_$suid_seuid_$seuid.zip
  find ./ -type f -iname '*.zip' -print0 | while read -d $'\0' zipfile
  do
     echo $zipfile
     newfile=$(echo `basename "$zipfile"` | sed "s/.zip//")
     echo $newfile
     mv $zipfile ../outbox/rawdata_suid_${suid}_seuid_${newfile}.zip || error_exit
  done

  # tar all text file into a zip   rawdata_suid_$suid_text.zip
  /usr/bin/zip ../outbox/rawdata_suid_${suid}_text.zip *.txt || error_exit

  # tar all text file into a zip   rawdata_suid_$suid_text.zip
  /usr/bin/zip ../outbox/rawdata_suid_${suid}_extra.zip ./Extra_files/* || error_exit
  
  cd /data/site/kspace/outbox/

  #clean up the folder
  rm -rf  ${kspaceDatLocations}/${filedir}

  echo "Before rsyc: rawdata_suid_${suid}*.zip"

  #rsync this files
  /usr/bin/rsync -LptgoDv0 --no-R /data/site/kspace/outbox/rawdata_suid_${suid}*.zip hbcd_${user}_fiona@${endpoint}:/home/hbcd_${user}_fiona 
  #register the files to UMN
  /usr/bin/python /var/www/html/server/bin/registerRawFileUpload.py --filename=rawdata_suid_${suid} --token=$token >> $log 2>&1

done

