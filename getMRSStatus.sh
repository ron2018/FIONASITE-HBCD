#!/bin/bash

/bin/echo "MRSEXPECT" > /data/site/mrs/umn/mrsexpect.csv
/bin/grep mrs-ISTHMUS /data/DAIC/CH*json | cut -d'_' -f5 | sort | uniq >> /data/site/mrs/umn/mrsexpect.csv


echo "KSPACESENT" > /data/site/kspace/processed/kspacesent.csv
grep rawdata /data/quarantine/scp*json | cut -d'_' -f2 | cut -d' ' -f1 | cut -d':' -f1 | sed 's/.json//' | sort | uniq >> /data/site/kspace/processed/kspacesent.csv
