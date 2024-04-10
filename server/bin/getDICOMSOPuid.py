import os
import pandas as pd
import pydicom# pydicom is using the gdcm package for decompression

def clean_text(string):
# clean and standardize text descriptions, which makes searching files easier
    forbidden_symbols=["*", ".", ",", "\"", "\\", "/", "|", "[", "]", ":", ";", " "]
    for symbol in forbidden_symbols:
        string=string.replace(symbol, "_") # replace everything with an underscore
    return string.lower()

# user specified parameters
src='/data/DAIC/'
dst='/data/site/raw/'

print('reading file list...')

dicomdata = {}
dicomList = []
checkedList = []
site_id = ''
for root, dirs, files in os.walk(src):
   for file in files: 
       print (os.path.join(root, file))
       hbcd_id = ''
       suid = ''
       if ".tgz" in file:
           elements = file.split("_")
           hbcd_id = elements[0] + "_" +  elements[1] + "_" + elements[2]
           site_id = elements[0][2:5]
           suid = elements[4]
           print(site_id, hbcd_id, suid)
           if hbcd_id in checkedList:
               continue
           else:
               # get files from archives
               for rawroot, rawdirs, jsonfiles in os.walk(dst + "/" + suid):
                   print(rawroot, rawdirs)
                   for dcmdir in rawdirs:
                       for dcmfile in os.listdir( dst + "/" + suid + "/" + dcmdir):
                           dicomdata = {"hbcd_id": hbcd_id, "StudyInstanceUID":suid, "SeriesInstanceUID":dcmdir, "SOPInstanceUID": dcmfile}
                           print (dicomdata)
                           dicomList.append(dicomdata)

               checkedList.append(hbcd_id)
dicomDataPD = pd.DataFrame(dicomList)
print(dicomDataPD.shape)
dicomDataPD.to_csv(src + "/" +  site_id + "_" + "FIONA.csv", mode="+w", header=True)
 
print('done.')


