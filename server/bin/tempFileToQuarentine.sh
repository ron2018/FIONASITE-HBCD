#!/bin/bash

SUID="$1"
SeUID="$2"
tripleID="$3"

if [ -z "$SUID" ] || [ -z "$SUID" ]; then
   echo " Need SUID and SeUID to continue! "
else
   cd /data/site/temp/
   /bin/cp ./$1/$2.json $3_$1_$2.json
   
   /bin/tar cvfz  $3_$1_$2.tgz ./$1/$2 ./$1/$2.json 
   echo " Data from temp to Quarantine is completed! "
   md5sum $3_$1_$2.tgz > $3_$1_$2.md5sum

   /bin/mv -f /data/site/temp/$3_$1_$2.* /data/outbox/
   /bin/rm -f /data/quarantine/$1_$2*
   /bin/rm -rf $1_$2
   /bin/rm -f $1_$2.tgz
   echo " Data from temp to Quarantine is completed! "

fi

