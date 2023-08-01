#!/bin/bash
#
# For HBCD: GE k-space data from an MRI scanner sent to FIona as one tgz file.
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

#find ${kspaceDatLocations} -maxdepth 1 -type d -print0 | while read -d $'\0' fdir
#do

#  filedir=$(echo `basename "$fdir"`);
#  echo $filedir;

#  if [ ${filedir} = "kspace" ]; then
#        continue;
#  fi
#  if [ ${filedir} = "umn" ]; then
#        continue;
#  fi
#  if [ ${filedir} = "outbox" ]; then
#       continue;
#  fi
#  if [ ${filedir} = "badkspace" ]; then
#       continue;
#  fi
#  if [ ${filedir} = "processed" ]; then
#       continue;
#  fi

find ${kspaceDatLocations} -maxdepth 1 -type f -iname '*.tar.gz' -print0 | while read -d $'\0' filepath
do
    echo $filepath

    filename=$(echo `basename "$filepath"`);
    echo $filename;
    
    # untar the file
    #/usr/bin/tar xvf $filename

    tripleID=$(echo `basename $filename .tar.gz`)
    echo ${tripleID}
    cd /data/DAIC/
    suid=`ls ${filedir}*.tgz | head -1 | cut -d"_" -f5`
    

    echo ${suid}
    match=`ls /data/DAIC/${filedir}*.tgz | wc -l`

    if  [[ ${match} -gt 0 ]]; then


       cd ${kspaceDatLocations}/${tripleID}
       echo ${kspaceDatLocations}/${tripleID} 
       #tar the series folder into rawdata_suid_seuid_tar.gz file 
       # calulate the checksum
       find ./ -maxdepth 1 -type d -iname "Series*" -print0 | while read -d $'\0' fdir
       do
           filedir=$(echo `basename "$fdir"`);
           echo $filedir;

           if [ ${filename} = "." ]; then
              continue;
            fi  
       
           #loop all the SUID related json file in /data/DAIC/
           echo "*${suid}*.json"
           find /data/DAIC/ -maxdepth 1 -type f -iname *${suid}*.json -print0 | while read -d $'\0' jsonfilepath
           do
               jsonfile=$(echo `basename "$jsonfilepath"`);
               #echo $jsonfile;

               filesuid=`cat ${jsonfilepath} | jq .StudyInstanceUID`
               seuid=`cat ${jsonfilepath} | jq .SeriesInstanceUID`
               serialno=`cat ${jsonfilepath} | jq .SeriesNumber`
               #echo "${filesuid} ${seuid} ${serialno}"
  
               #replace the quota
               filesuid=`echo $filesuid | sed "s/\"//g"`
               seuid=`echo $seuid | sed  "s/\"//g" `
               serialno=`echo $serialno | sed  "s/\"//g" `
               fileno=`echo ${filedir} | sed "s/Series//g"` 
               #echo "${serialno} == ${fileno} "             
               if [[ ${serialno} == ${fileno} ]]; then
                 
                   newfile=rawdata_suid_${filesuid}_seuid_${seuid}_${filedir}
                   echo "/usr/bin/tar cvzf ${newfile}.tar.gz ./${filedir}"
                   #/usr/bin/tar cvzf ${newfile}.tar.gz ./${filedir}
                   break
               fi
           
           done
           # check if $filedir.tar.gz file exists 
           matchfile=`ls ./*${filedir}.tar.gz | wc -l`
           echo "matchfile == ${matchfile}"
           if [[ ${matchfile} -eq 0 ]]; then
               newfile=rawdata_suid_${suid}_${filedir}
                echo "/usr/bin/tar cvzf ${newfile}.tar.gz ./${filedir}"
                /usr/bin/tar cvzf ${newfile}.tar.gz ./${filedir}
           fi
           echo "Completed rename folder  : ${filedir} "
           
       done
       
 
    #rsync this folder
    { 
     if [[ ! -d ${kspaceDatLocations}/processed/${suid}/ ]]; then
        mkdir ${kspaceDatLocations}/processed/${suid}/
     fi
     /usr/bin/mv  ${kspaceDatLocations}/${tripleID}/*${suid}*.tar.gz ${kspaceDatLocations}/processed/${suid}/
      echo "/usr/bin/rsync -LptgoDv0 --no-R /data/site/kspace/processed/${suid}/* hbcd_${user}_fiona@${endpoint}:/home/hbcd_${user}_fiona/KSPACE/${tripleID}_KSPACE_${suid}/"
      /usr/bin/rsync -LptgoDv0 --no-R /data/site/kspace/processed/${suid}/*  hbcd_${user}_fiona@${endpoint}:/home/hbcd_${user}_fiona/KSPACE/${tripleID}_KSPACE_${suid}/
    } && {

     #  register the files to UMN
     echo "/usr/bin/python /var/www/html/server/bin/registerRawFileUpload.py --filename=${tripleID}_KSPACE_${suid} --token=$token --type=KSPACE "
     /usr/bin/python /var/www/html/server/bin/registerRawFileUpload.py --filename=${tripleID}_KSPACE_${suid} --token=$token --type=KSPACE >> $log 2>&1
     #clean up the folder
     #/usr/bin/rm -rf  ${filedir}
     /usr/bin/mv $filepath  ${kspaceDatLocations}/original/
     
    }
  fi
  if [[ ${match} -eq 0 ]]; then
     echo " NO match found, probably the DICOM data has not been processed"
     break;
  fi

done

