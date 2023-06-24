#!/bin/bash
#
# For HBCD: MRS data will be sftp by site to the /data/site/mrs folder
# with Pilot data name convention: PIUMNXXXX_XXXXXX_V0X_MRS.zip or tar.gz 
# We will validate the name against the data in /data/DAIC folder 
# If the tripleID is validated, then we will send the data to UMN and register the data.
# If the site has name the MRS file incorrectly, we will expand the raw filw and identify the SUID, 
# thenm identify the tripleID from /data/DAIC  
#
# This script should be run by user processing (creates mount point).
#
# 1 2 * * * /var/www/html/server/bin/sendsMRS.sh >>/var/www/html/server/logs/sendMRS.sh.log 2>&1
#

# A .zip or .tar.gz file needs to be at least that many seconds old (modification time) before it will be copied
oldtime=15

echo "starting sendMRS.sh *** "
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

token=`cat /data/config/config.json | jq -r ".CONNECTION"`
if [ "$token" == "" ]; then
   echo "CONNECTION string is missing from /data/config/config.json file"
   exit
fi

echo $token
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

log=${SERVERDIR}/logs/sendMRS.log
if [ ! -f "$log" ]; then
   touch "$log"
fi

mrsDatLocations="/data/site/mrs/"
cd  $mrsDatLocations

#
# now go through all the files on the /data/site/mrs/ folder
#
#find ${mrsDatLocations} -maxdepth 1 -type d -print0 | while read -d $'\0' fdir
#do

#  filedir=$(echo `basename "$fdir"`);
#  echo $filedir;

#  if [ ${filedir} = "umn" ]; then
#	continue;
#  fi
#  if [ ${filedir} = "badmrs" ]; then
#       continue;
#  fi
   
  # if folder files have not been changed.

  #rename the dat file to rawdata_suid_$suid_seuid_$seuid.dat
find ./ -maxdepth 1  -type f -iname '*.zip' -print0 | while read -d $'\0' datfile
do
     
     echo $datfile
     datafile=$(basename $datfile) 

     if [ "$(( $(date +"%s") - $(stat -c "%Y" "$datfile") ))" -lt "$oldtime" ]; then
        echo "`date`: too young $datfile"
        continue
     fi

     mrsID=$(echo $datafile | cut -d"." -f1)
     tripleID=${mrsID:0:20}

     echo $tripleID
     match=`ls /data/DAIC/$tripleID*.tgz | wc -l`

     echo $match
     suid=`ls /data/DAIC/${tripleID}*.tgz | head -1 | cut -d"_" -f5`
     #suid=`ls /data/DAIC/PIUCS0023_128918_V02*.tgz | head -1 | cut -d"_" -f5`
     echo $suid
     touch /var/www/html/php/request_compliance_check/$suid

     if [[ ${match} -gt 0 ]]; then
         # triple ID are correct and should send the MRS datat to UMN
         #register the files to UMN
         {  echo "/usr/bin/rsync -LptgoDv0 --no-R /data/site/mrs/${tripleID}_MRS* hbcd_${user}_fiona@${endpoint}:/home/hbcd_${user}_fiona/MRS/"
		 /usr/bin/md5sum  $datfile  >  /data/site/mrs/${mrsID}.md5sum; 
               /usr/bin/rsync -LptgoDv0 --no-R /data/site/mrs/$tripleID_MRS* hbcd_${user}_fiona@${endpoint}:/home/hbcd_${user}_fiona/MRS/   
         } &&
         {
         echo "/usr/bin/python /var/www/html/server/bin/registerRawFileUpload.py --filename=$datafile --token=$token --type=MRS"

         /usr/bin/python /var/www/html/server/bin/registerRawFileUpload.py --filename=$datafile --token=$token --type=MRS >> $log 2>&1
         /bin/mv  ${tripleID}_MRS* /data/site/mrs/umn/ || error_exit
         }
     fi

done
  # for site to use .tar.gz extension

find ./ -maxdepth 1 -type f -iname '*.tar.gz' -print0 | while read -d $'\0' datfile
do
     echo $datfile
      
     datafile=$(basename $datfile)
  
     if [ "$(( $(date +"%s") - $(stat -c "%Y" "$datfile") ))" -lt "$oldtime" ]; then
        echo "`date`: too young $datfile"
        continue
     fi

     mrsID=$(echo $datafile | cut -d"." -f1)
     tripleID=${mrsID:0:20}

     echo $tripleID
     match=`ls /data/DAIC/$tripleID*.tgz | wc -l`

     echo $match
     suid=`ls /data/DAIC/${tripleID}*.tgz | head -1 | cut -d"_" -f5`
     echo $suid
     touch /var/www/html/php/request_compliance_check/$suid

     if [[ ${match} -gt 0 ]]; then
         # triple ID are correct and should send the MRS datat to UMN
         #register the files to UMN

         {  echo "/usr/bin/rsync -LptgoDv0 --no-R /data/site/mrs/${tripleID}_MRS* hbcd_${user}_fiona@${endpoint}:/home/hbcd_${user}_fiona/MRS/"

		 /usr/bin/md5sum  $datfile  >  /data/site/mrs/${mrsID}.md5sum; 
		 /usr/bin/rsync -LptgoDv0 --no-R /data/site/mrs/${tripleID}_MRS* hbcd_${user}_fiona@${endpoint}:/home/hbcd_${user}_fiona/MRS/   
         } && {
         echo "/usr/bin/python /var/www/html/server/bin/registerRawFileUpload.py --filename=$datafile --token=$token --type=MRS"

         /usr/bin/python /var/www/html/server/bin/registerRawFileUpload.py --filename=$datafile --token=$token --type=MRS >> $log 2>&1
         /bin/mv  ${tripleID}_MRS* /data/site/mrs/umn || error_exit
        }
     fi

done
