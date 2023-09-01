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

echo "starting sendMRSPhilips.sh *** "
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
find ${mrsDatLocations} -maxdepth 1 -type d -print0 | while read -d $'\0' fdir
do

  echo $fdir
  filedir=$(echo `basename "$fdir"`);
  echo $filedir;

  if [ ${filedir} == "mrs" ]; then
	continue;
  fi
  if [ ${filedir} == "umn" ]; then
	continue;
  fi
  if [ ${filedir} == "badmrs" ]; then
       echo "By pass badmrs";
       continue;
  fi
   
  # if folder files have not been changed.
 
  # need to get suid
  suid=$(echo ${filedir} | cut -d'_' -f2)
  echo "SUID = ${suid}"

  cd $fdir
  find ./ -maxdepth 1 -type f -iname '*.zip' -print0 | while read -d $'\0' datfile
  do
     
     echo $datfile
     datafile=$(basename $datfile) 

     if [ "$(( $(date +"%s") - $(stat -c "%Y" "$datfile") ))" -lt "$oldtime" ]; then
        echo "`date`: too young $datfile"
        continue
     fi

     tripleID=`cat /data/quarantine/scp_${suid}.json | jq -r ".PatientName"`
     echo $tripleID
     if [ -z "$tripleID" ]; then
         match=0
     else
         match=`ls /data/DAIC/$tripleID*.tgz | wc -l`
     fi
     echo $match
     filename=${tripleID}_MRS

     echo ${filename}

     if [[ ${match} -gt 0 ]]; then
         # triple ID are correct and should send the MRS datat to UMN
         #register the files to UMN
         {       

		 tar cvfz /data/site/mrs/${filename}.tar.gz *
		 /usr/bin/md5sum  /data/site/mrs/${filename}.tar.gz >  /data/site/mrs/${filename}.md5sum; 
		 echo "/usr/bin/rsync -LptgoDv0 --no-R /data/site/mrs/${filename}.* hbcd_${user}_fiona@${endpoint}:/home/hbcd_${user}_fiona/MRS/"
                 /usr/bin/rsync -LptgoDv0 --no-R /data/site/mrs/${filename}.* hbcd_${user}_fiona@${endpoint}:/home/hbcd_${user}_fiona/MRS/   

         } &&
         {
         echo "/usr/bin/python /var/www/html/server/bin/registerRawFileUpload.py --filename=${filename}.tar.gz --token=$token --type=MRS"

         /usr/bin/python /var/www/html/server/bin/registerRawFileUpload.py --filename=${filename}.tar.gz --token=$token --type=MRS >> $log 2>&1
         /bin/mv  /data/site/mrs/${filedir} /data/site/mrs/umn/ || error_exit
         /bin/mv  /data/site/mrs/${filename}.*  /data/site/mrs/umn/  || error_exit
	 echo "Touch /var/www/html/php/request_compliance_check/${suid}"
         touch /var/www/html/php/request_compliance_check/$suid
	 break;

         }
     fi
     if [[ ${match} -eq 0 ]]; then
        echo " NO match found, probably the DICOM data has not been processed"
	break;
     fi
  done     
done
