#!/bin/bash

# For HBCD: Siemens k-space data from an MRI scanner is stored as .dat files and name convention is following:
# Seriese description is at the end of filename:  meas_MID00258_FID00293_T2w_SPACE.dat 
#   meas_MIDXXXXX_FIDXXXXX_PROTOCOL_NAME.dat
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

echo "starting sendSiemensKSPACE.sh *** "
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
  #tar -xf $file
  # pattern is : 20221213_1.3.46.670589.11.45002.5.0.15852.2022121307120758000.tgz
  # tar the file
  #filedir=$(echo `basename "$file"` | sed "s/.tgz//")

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
   
  # if folder files have not been changed.

  tripleID="undefined"
  suid="undefined"


  cd $fdir
  #rename the dat file to rawdata_suid_$suid_seuid_$seuid.dat
  find ./ -type f -iname '*.dat' -print0 | while read -d $'\0' datfile
  do
    # echo $datfile
      

     if [ "$(( $(date +"%s") - $(stat -c "%Y" "$datfile") ))" -lt "$oldtime" ]; then
        echo "`date`: too young $datfile"
        continue
     fi
   
     suid=$(grep -a -m 1 StudyInstanceUID ${datfile} | cut -d"\"" -f4)
     echo $suid

     fname=${suid}*.json
     seuid='undefined'

     find "/data/quarantine/" -type f -iname $fname -print0 | while read -d $'\0' jsonfile
     do
	 if [[ ${tripleId} = "undefined" ]]; then
	    tripleId=`cat $jsonfile | jq -r ".PatientName"`;
	 fi

         private0021_1106=`cat $jsonfile | jq -r ".Private0021_1106"`;
         seriesDecr=`cat $jsonfile | jq -r ".SeriesDescription" | sed -r 's/-/_/g'`;
	 #echo $jsonfile
	 #echo "In quarantine $private0021_1106 $seriesDecr "
         if [ ! -n "${private0021_1106}" ]; then
            pattern=`printf "MID%05.0f" ${private0021_1106}`
         fi

         #echo $pattern

	 if [[ $datfile =~ ${pattern} ]]; then
		if [[ $datfile =~ ${seriesDecr} ]]; then
		    seuid=`cat $jsonfile | jq -r ".SeriesInstanceUID"`
                    echo "Found SeUID : ${seuid}"
                    newfile="rawdata_suid_${suid}_seuid_${seuid}.dat"
                    echo $newfile
                    /bin/mv  $datfile $newfile || error_exit 
                    break
	        fi
	 fi

     done

     fname=*${suid}*.json
     # check /data/DAIC if the data is not in /data/quarantine
     find "/data/DAIC/"  -type f -iname $fname -print0 | while read -d $'\0' jsonfile
     do
          # echo "In /data/DAIC/ : ${jsonfile}"
           if [[ ${tripleId} = "undefined" ]]; then
              tripleId=`cat $jsonfile | jq -r ".PatientName"`;
           fi
           seriesDecr=`cat $jsonfile | jq -r ".SeriesDescription" | sed -r 's/-/_/g'`;

           private0021_1106=`cat $jsonfile | jq -r ".Private0021_1106"`;
	  # echo "In DAIC $private0021_1106 $seriesDecr "
           if [ ! -n "${private0021_1106}" ]; then
               pattern=`printf "MID%05.0f" ${private0021_1106}`
           fi
           pattern=`printf "MID%05.0f" ${private0021_1106}`

         #  echo $pattern
         #  echo $tripleId

           if [[ $datfile =~ ${pattern} ]]; then
	       if [[ $datfile =~ ${seriesDecr} ]]; then
                    seuid=`cat $jsonfile | jq -r ".SeriesInstanceUID"`;
                    echo "Found SeUID : ${seuid}"
                    newfile="rawdata_suid_${suid}_seuid_${seuid}.dat"
                    echo $newfile
                    /bin/mv -f  $datfile $newfile || error_exit
                    break
                fi
	   fi
     done
    # echo "${seuid}"
    # echo "${datfile}"

  done
  
  #process unmatched files
  find ./ -type f -iname 'meas*.dat' -print0 | while read -d $'\0' datfile
  do
     suid=$(grep -a -m 1 StudyInstanceUID ${datfile} | cut -d"\"" -f4)
     filename=$(echo `basename "$datfile"`)
     newfile="rawdata_suid_${suid}_${filename}"
     echo "Could not find the Seuid: ${newfile} ${suid}"
     /bin/mv -f $datfile $newfile || error_exit
  done


  #rsync this files
  tripleID=${filedir}
  #echo ${tripleID}
  #get SUID from file
  suid=$(ls /data/site/kspace/${filedir}/rawdata_suid_* | head -1 | cut -d"_" -f5)
  echo $suid
  echo "Before rsyc: rawdata_suid_${suid}*.dat"

  { echo "/usr/bin/rsync -v --no-R /data/site/kspace/${filedir}/ hbcd_${user}_fiona@${endpoint}:/home/hbcd_${user}_fiona/KSPACE/${filedir}_KSPACE_${suid}/"
  cd /data/site/kspace/${filedir}
  for a in `ls -1 *.dat`; do id=$(sed 's/\.dat//g'  <<< $a);  md5sum  $a >  $id.md5sum; done

  /usr/bin/rsync -v /data/site/kspace/${filedir}/ hbcd_${user}_fiona@${endpoint}:/home/hbcd_${user}_fiona/KSPACE/${filedir}_KSPACE_${suid}/ 
  } &&  {
	  #register the files to UMN
  echo "/usr/bin/python /var/www/html/server/bin/registerRawFileUpload.py --filename=${tripleID}_KSPACE_${suid} --token=$token --type=KSPACE "

  /usr/bin/python /var/www/html/server/bin/registerRawFileUpload.py --filename=${tripleID}_KSPACE_${suid} --token=$token --type=KSPACE >> $log 2>&1
  mv -f /data/site/kspace/${filedir} /data/site/kspace/processed/
  mv -f /data/site/kspace/processed/${filedir} /data/site/kspace/processed/${suid}

  }

done
