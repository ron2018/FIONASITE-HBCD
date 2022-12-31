#!/bin/bash

SUID="$1"
SeUID="$2"

if [ -z "$SUID" ] || [ -z "$SUID" ]; then
   echo " Need SUID and SeUID to continue! "
else
   cd /data/site/temp/

   /bin/tar cvfz  $1_$2.tgz ./$1 
   echo " Data from temp to Quarantine is completed! "
   /bin/rm -rf $1
   /bin/mv /data/site/temp/$1_$2.tgz /data/quarantine/
   echo " Data from temp to Quarantine is completed! "

fi

