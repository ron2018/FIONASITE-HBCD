#!/bin/bash
#
# For HBCD: GE k-space data from an MRI scanner is stored as .tgz with json and md5sum  files and name convention is following:
# 
#  SUID_1.2.840.113619.6.495.256392911284210468483920599390767769027_subjid_test_ex6139_se3_01-10-2023_174656.tgz
#  and 
#  SUID_1.2.840.113619.6.495.256392911284210468483920599390767769027_subjid_test_ex6139_se3_01-10-2023_174656.json
#  json file contains the SUID and SeUID
# 
# This script tries to rename and pack the files as following: 
# for series:  rawdata_SUID_suid_SeUID_seuid.tgz
# 
# This script should be run by user processing (creates mount point).
#
# 1 2 * * * /var/www/html/server/bin/sendGEKSPACE.sh >> /var/www/html/server/logs/sendGEKSPACE.sh.log 2>&1
#

# A .dat file needs to be at least that many seconds old (modification time) before it will be copied
oldtime=15

echo "starting sendGEKSPACE.sh *** "
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

kspaceDatLocations="/data/site/kspace/"
cd  $kspaceDatLocations

#
# now go through all the files on the /data/site/kspace/ folder
#

find ${kspaceDatLocations} -maxdepth 1 -type d -print0 | while read -d $'\0' fdir
do

  filedir=$(echo `basename "$fdir"`);
  echo $filedir;

  if [ ${filedir} = "kspace" ]; then
        continue;
  fi
  if [ ${filedir} = "umn" ]; then
        continue;
  fi
  if [ ${filedir} = "outbox" ]; then
       continue;
  fi
  if [ ${filedir} = "badkspace" ]; then
       continue;
  fi
  if [ ${filedir} = "processed" ]; then
       continue;
  fi

  cd /data/DAIC/
  suid=`ls ${filedir}*.tgz | head -1 | cut -d"_" -f5`
  echo ${suid}
  match=`ls /data/DAIC/${filedir}*.tgz | wc -l`
  tripleID=${filedir}

  if  [[ ${match} -gt 0 ]]; then


     cd ${kspaceDatLocations}/${filedir}
     echo ${kspaceDatLocations}/${filedir} 
     #rename the zip file to rawdata_suid_$suid_seuid_$seuid.zip
     find ./ -maxdepth 1 -type f -iname '*.tgz' -print0 | while read -d $'\0' filepath
     do
        echo $filepath;

        filename=$(echo `basename "$filepath"`);
        echo $filename;
 
  
   
        # if folder files have not been changed.
       if [ "$(( $(date +"%s") - $(stat -c "%Y" "$filename") ))" -lt "$oldtime" ]; then
          echo "`date`: too young $datfile"
          continue
       fi
  
       filestem=$(echo `basename $filename .tgz`);
       
       
       filesuid=`cat ${filestem}.json | jq .StudyInstanceUID`;
  
       echo $filesuid;

       seuid=`cat ${filestem}.json | jq .SeriesInstanceUID`
       echo $seuid;
  
       #replace the quota
       filesuid=`echo $filesuid | sed "s/\"//g"`;
       seuid=`echo $seuid | sed  "s/\"//g" `;
       
       newfile=rawdata_suid_${filesuid}_seuid_${seuid}
       echo $newfile
       /bin/mv  ${filestem}.tgz ${newfile}.tgz 
       /bin/mv  ${filestem}.json ${newfile}.json 
       /bin/mv  ${filestem}.md5sum ${newfile}.md5sum 
       /bin/mv  ${filestem}.log ${newfile}.log 
       echo "Completed rename files  : rawdata_suid_${suid}*.zip"

    done

    echo "${suid} ${tripleID}"
 
    #rsync this folder
    { 
      echo "/usr/bin/rsync -LptgoDv0 --no-R /data/site/kspace/${filedir}/*${suid}* hbcd_${user}_fiona@${endpoint}:/home/hbcd_${user}_fiona/KSPACE/${tripleID}_KSPACE/"
      #/usr/bin/rsync -LptgoDv0 --no-R /data/site/kspace/${filedir}/*${suid}*  hbcd_${user}_fiona@${endpoint}:/home/hbcd_${user}_fiona/KSPACE/${tripleID}_KSPACE/
    } && {

     #  register the files to UMN
     echo "/usr/bin/python /var/www/html/server/bin/registerRawFileUpload.py --filename=${tripleID}_KSPACE_${suid} --token=$token --type=KSPACE "
     /usr/bin/python /var/www/html/server/bin/registerRawFileUpload.py --filename=${tripleID}_KSPACE_${suid} --token=$token --type=KSPACE >> $log 2>&1
     #clean up the folder
     if [[ ! -d ${kspaceDatLocations}/processed/${suid}/ ]]; then
        mkdir ${kspaceDatLocations}/processed/${suid}/
     fi
     /usr/bin/mv  ${kspaceDatLocations}/${filedir}/*${suid}* ${kspaceDatLocations}/processed/${suid}/
     if [[ -d ${kspaceDatLocations}/baskspace/${filedir}/ ]]; then
        rm -rf ${kspaceDatLocations}/badkspace/${filedir}/
     fi
     /usr/bin/mv -f  ${kspaceDatLocations}/${filedira} ${kspaceDatLocations}/badkspace/
     
    }
  fi
  if [[ ${match} -eq 0 ]]; then
     echo " NO match found, probably the DICOM data has not been processed"
     break;
  fi

done

