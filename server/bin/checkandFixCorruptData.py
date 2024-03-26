import json
import shutil
import os
import subprocess
import time
import requests
import pandas as pd
import numpy as np
import pydicom
from datetime import datetime

__author__ = "Ron Yang"
__organization__ = "Center for Multimodal Imaging and Genetics (CMIG), UC San Diego"
__credits__ = ['Ron Yang']
__license__ = "GPL"
__since__ = "03/21/2024"
__status__ = "Development"

""" This program will check the tgz file in /data/DAIC folder and identify the 
    corrupted files and check past weeks data only and if SUID data is corrupted
    Then, put the SUID into repacking
"""

#pydicom.config.convert_wrong_length_to_UN = True

dir_path = '/data/DAIC'
temp = '/data/site/temp/'



#Start time of the script
time_string = time.strftime("%m/%d/%Y, %H:%M:%S", time.localtime())
print('### Start Program checkandFixCorruptData.py at {0} ###'.format(time_string))

newSiteID = {"VAT":"VTC", "OSU":"OKH", "EUY":"EMO", "UNM":"UMX"}

# Copy file into /data/site/temp/check_SUID foler unwarp there and check corruption.
def copy_check_data(filename, hbcd_id, SUID,SeUID, temp):

    #print(f"Copying: {path} to ({temp}/{hbcd_id})")
    src = os.path.join(dir_path,filename)
    dst = os.path.join(temp, hbcd_id)
    if not os.path.exists(dst):
       print("make Dir: {dst}") 
       os.mkdir(dst)

    shutil.copyfile(src,os.path.join(dst, filename))

    # Extract every .tgz file in the current directory after copied, extracted folder should be SUID

    #print(f"Extracting: {os.path.join(dst,filename)} ")
    proc = subprocess.run(["/bin/tar","-xf", dst + "/" + filename, "-C", dst + "/"], shell=False)
    if proc.returncode != 0:
         error_msg = f"ERROR: Extracting {filename} failed (SUID: {SUID})."
         return error_msg

    for dcmfile in os.listdir(os.path.join(dst,SUID,SeUID)):
        try:
            dataset = pydicom.dcmread(os.path.join(dst,SUID, SeUID, dcmfile))
            print(dataset.PatientName, dataset.PatientID, dataset.PatientAge, dataset.PatientSex)
        except:
            # if any read file error, the DICOM file is not good, remove it.
            print(" Bad Dicom file:",os.path.join(dst,SUID, SeUID,dcmfile))
            error_msg = " Bad Dicom file:" + os.path.join(dir_path,dcmfile) 
            return error_msg

    return "OK"

#loop through SUID Folder
repacked = []

for path in os.listdir(dir_path):
   #print(f"Checking dcms in series folder: {path}")
   if os.path.isfile(os.path.join(dir_path,path)):
       tgzfiletimestamp = ''
       if 'tgz' in path:
           tgzfiletimestamp = datetime.fromtimestamp(os.path.getmtime(os.path.join(dir_path,path)))
           #print(datetime.strftime(tgzfiletimestamp, "%Y/%m/%d"))
           #print(datetime.today().strftime("%Y/%m/%d"))
           if (datetime.today() - tgzfiletimestamp).days > 7 :
               continue;
           else:
               
               #get hbcd_id, SUID, SeUID
               fileElems = path.split('_')
               hbcd_id = fileElems[0] + "_" +  fileElems[1] + "_" +  fileElems[2]
               session = fileElems[3]
               SUID = fileElems[4]
               SeUID = fileElems[5][:-4]
               
               if hbcd_id in repacked:
                   continue
               #if hbcd_id != 'CHUCS0050_699557_V02':
               #    continue

               print(hbcd_id, session, SUID, SeUID)
               #Check corruption
               checkResult = ''
               checkResult = copy_check_data(path,hbcd_id, SUID,SeUID, temp)
     
               #Put into repack if there is an corruption data
               if checkResult != 'OK':
                   #open file /var/www/html/php/repush.jobs and add the SUID into it
                   with open('/var/www/html/php/repush.jobs', 'a+') as outfile:
                        print(f"{SUID} HBCD")
                        outfile.write(f"{SUID} HBCD")
                        outfile.close

                   print("Found file corruption for ", path)
                   repacked.append(hbcd_id)
                   

    
#remove all temp files in /data/site/temp
#get site ID:
try:
   os.delete("/data/site/temp/CH*")
catch:
   print("delete temp data error out")
   continue;

try:
   os.delete("/data/site/temp/PH*")
catch:
   continue;

print(repacked)


#End time of the script
time_string = time.strftime("%m/%d/%Y, %H:%M:%S", time.localtime())
print('### End Program checkandFixCorruptData.py at {0} ###'.format(time_string))
