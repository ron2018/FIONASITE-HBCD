"""
 Check the protocol compliance for HBCD data in /data/quanrentine folder
 read /data/quarantine, /data/outbox, /data/DAIC for overall data situation.
 Ron Yang 09/2022

"""

import sys, os, time, atexit, stat, tempfile, copy, traceback, shutil
import pydicom, json, re,  logging.handlers, threading, string
import json
import logging as log
from datetime import datetime
import argparse
from pydicom.filereader import InvalidDicomError

# LOGGING
ROOT_PATH = os.path.join(os.path.dirname(os.path.realpath(__file__)))
log.basicConfig(filename=os.path.join(ROOT_PATH,'../logs',os.path.basename(__file__) + '.log'),
                format='%(asctime)s  %(levelname)10s  %(message)s', level=log.INFO)


def parse_arguments():
    parser = argparse.ArgumentParser(
        description="Processes EPrime MID Data for Release")
    parser.add_argument('--suid', help="SUID", required=True)
    parser.add_argument('--verbose', '-v', action='store_true',
                        help="Display / save logged DEBUG-level messages.")
    return parser.parse_args()

def getSeriesFileCount(filename):
  # return file series count and update filename with new filecont
  UIDs = filename.split("_")
  if "PI" in UIDs[0]:
      SUID = UIDs[4]
      SeUID = UIDs[5].replace(".json","")
  else:
    SUID = UIDs[0]
    SeUID = UIDs[1].replace(".json","")
  print("SUID : ", SUID, " SeUID : ", SeUID)
  dir_path = r'/data/site/raw/' + SUID + r'/' + SeUID 
  count = len([entry for entry in os.listdir(dir_path) if os.path.isfile(os.path.join(dir_path, entry))])
  print("Dir_Path" + dir_path + "file count " + str(count))
  
  return count

def getKSpaceFilePath(StudyInstanceUID, SeriesInstanceUID,scannerType):
    if scannerType == "SIEMENS":  
         filename = "/data/site/kspace/outbox/"+ StudyInstanceUID + "/rawdata_suid_" + StudyInstanceUID + "_seuid_" + SeriesInstanceUID + ".dat"
    elif scannerType == "PHILIPS":  
         filename = "/data/site/kspace/outbox/"+ StudyInstanceUID + "/rawdata_suid_" + StudyInstanceUID + "_seuid_" + SeriesInstanceUID + ".zip"
    else:    
         filename = "/data/site/kspace/outbox/"+ StudyInstanceUID + "/rawdata_suid_" + StudyInstanceUID + "_seuid_" + SeriesInstanceUID + ".tgz"
    if os.path.isfile(filename):
        return filename
    else:
        return "NotFound.txt"

def getKSpaceFileSize(StudyInstanceUID, SeriesInstanceUID, scannerType):
    if scannerType == "SIEMENS":  
         filename = "/data/site/kspace/outbox/"+ StudyInstanceUID + "/rawdata_suid_" + StudyInstanceUID + "_seuid_" + SeriesInstanceUID + ".dat"
    elif scannerType == "PHILIPS":  
         filename = "/data/site/kspace/outbox/"+ StudyInstanceUID + "/rawdata_suid_" + StudyInstanceUID + "_seuid_" + SeriesInstanceUID + ".zip"
    else:    
         filename = "/data/site/kspace/outbox/"+ StudyInstanceUID + "/rawdata_suid_" + StudyInstanceUID + "_seuid_" + SeriesInstanceUID + ".tgz"
    if os.path.isfile(filename): 
        return os.path.getsize(filename)
    else:
        return 0
def getMRSFilePath(tripleID):
    filename1 = "/data/site/mrs/umn/"+ tripleID + "_MRS.zip"
    filename2 = "/data/site/mrs/umn/"+ tripleID + "_MRS.tar.gz"
    filename3 = "/data/site/mrs/"+ tripleID + "_MRS.zip"
    filename4 = "/data/site/mrs/"+ tripleID + "_MRS.tar.gz"
    if os.path.isfile(filename1):
        return filename1
    elif os.path.isfile(filename2):
        return filename2
    elif os.path.isfile(filename3):
        return filename3
    elif os.path.isfile(filename4):
        return filename4
    else:
        return "NotFound.txt"

def getMRSFileSize(tripleID):
    filename1 = "/data/site/mrs/umn/"+ tripleID + "_MRS.zip"
    filename2 = "/data/site/mrs/umn/"+ tripleID + "_MRS.tar.gz"
    filename3 = "/data/site/mrs/"+ tripleID + "_MRS.zip"
    filename4 = "/data/site/mrs/"+ tripleID + "_MRS.tar.gz"
    if os.path.isfile(filename1):
        return os.path.getsize(filename1)
    elif os.path.isfile(filename2):
        return os.path.getsize(filename2)
    elif os.path.isfile(filename3):
        return os.path.getsize(filename3)
    elif os.path.isfile(filename4):
        return os.path.getsize(filename4)
    else:
        return 0

 

# take SUID as first parameter, project name as optional second parameter
if __name__ == "__main__":
    projname = ''

    args = parse_arguments()
    if args.verbose:
        log.getLogger().setLevel(log.DEBUG)

    SUID = args.suid



    # try to read the config file from this machine
    configFilename = '/data/config/config.json'
    settings = {}
    with open(configFilename,'r') as f:
        settings = json.load(f)


    with open('/var/www/html/server/bin/compliances.json','r') as f:
        complianceSetting = json.load(f)

    SCANNERTYPE = settings["SCANNERTYPE"]
    DICOMFORMAT = settings["DICOMFORMAT"]

    if DICOMFORMAT:
        filecounts = complianceSetting[SCANNERTYPE + "-" + DICOMFORMAT]
    else:
        filecounts = complianceSetting[SCANNERTYPE]

        #logging.info("DEBUG: complianceSetting.json: ", complianceSetting[SCANNERTYPE]["T1"])
        logging.debug(json.dumps(filecounts))

    # read the existing json if it is existed
    if os.path.exists(os.path.join("/data/site/output/scp_"+SUID, "series_compliance/compliance_output.json")): 
        #Save a copy
        currentTime = datetime.now().strftime("%d-%m-%y-%H-%M-%S")
        shutil.move(os.path.join("/data/site/output/scp_"+SUID, "series_compliance/compliance_output.json"),
                os.path.join("/data/site/output/scp_"+SUID, "series_compliance/compliance_output" + currentTime + ".json"))


    datadir = '/data/quarantine/'    
    dict = {}
    t1_block = {}
    t2_block = {}
    dti_block = {}
    rsfmri_block = {}
    fmAPfmri_block = {}
    qmri_block = {}
    b1_block = {}
    fmPAfmri_block = {}
    others = []
    mrs_block = {}

    #read the json files in /data/quantine folder with all SUID name in it
    compliance_found = 1

    t1_runcounter = 1
    t2_runcounter = 1
    t1_nd_runcounter = 1
    t2_nd_runcounter = 1
    dti_runcounter = 1
    rsfmri_runcounter = 1
    tfmri_runcounter = 1
    midfmri_runcounter = 1
    fmri_fm_runcounter = 1
    dmri_fm_runcounter = 1
    qmri_runcounter = 1
    bmri_runcounter = 1
    sstfmri_runcounter = 1 
    qmri_runcounter = 1
    fmAPdti_runcounter = 1 
    fmPAdti_runcounter = 1 
    fmAPfmri_runcounter = 1 
    fmPAfmri_runcounter = 1 
    b1_runcounter = 1 
    mrs_runcounter = 1 

    SeUIDs = []

    print("SUID = ",SUID)

    for filename in os.listdir(datadir):
        if SUID in filename and '.json' in filename and filename != 'scp_'+SUID+'.json':
            print(filename)
            logging.info(filename)

            # read each json file
            with open(os.path.join(datadir,filename), 'r') as f:
                data = json.load(f)
                
            logging.debug(data)
            suid = data["StudyInstanceUID"]
            seuid = data["SeriesInstanceUID"]

            print(suid,"_", seuid)
            dict["PatientID"] = data["PatientID"]
            dict["PatientName"] = data["PatientName"]
            dict["StudyDate"] = data["StudyDate"]
            dict["PatientID"] = data["PatientID"]
            dict["StudyInstanceUID"] = data["StudyInstanceUID"]
            dict["Manufacturer"] = data["Manufacturer"]
            dict["ManufacturerModelName"] = data["ManufacturerModelName"] 
            
            SeUIDs.append(seuid);

            # re-calculate number of file in the series
             
            data["NumFiles"] = getSeriesFileCount(filename)
            with open(os.path.join(datadir,filename), "w") as write_qjson_file:
                json.dump(data, write_qjson_file, indent = 4, sort_keys=True)

            dict2 = {}
            if len(data["ClassifyType"]) > 2:
                dict2["status"] = 1
                compliance_found = 1
            else:
                dict2["status"] = 0
            dict2["SeriesNumber"] = data["SeriesNumber"]
            dict2["SeriesInstanceUID"] = data["SeriesInstanceUID"]
            dict2["message"] = 'HBCD ' + data["SeriesDescription"] + ' was found'
            #file size and path
            dict3 = {}
            dict4 = {}
            list3 = []
            dict3["path"] = os.path.join(datadir,filename)
            dict3["size"] = os.path.getsize(os.path.join(datadir,filename))
            list3.append(dict3)
            dict2["file"] = list3

            kspace = {}
            kspacelist= []
            
            if len(data["ClassifyType"]) > 2:
                logging.info(data["ClassifyType"][2])
                #depending on serise to imcrement the run counter
                if 'T1' in data["ClassifyType"][2]:
                    # check if any T1 Norm has enough files.
                    logging.debug (str(filecounts["T1"]) + " T1 Norm slice in DICOM vs number of Files " + str(data["NumFiles"]))
                    if int(filecounts["T1"])  > int(data["NumFiles"]):
                        # it is incomplete series
                        print("T1 Set the dict2[status] = 0")
                        dict2["status"] = 0
                        compliance_found = 0

                    t1_block[data["ClassifyType"][2] + '_run_' + str(t1_runcounter)] = copy.deepcopy(dict2)
 
                    # check the kspace data
                    kspace["path"] = getKSpaceFilePath(suid, seuid, SCANNERTYPE )
                    kspace["size"] = getKSpaceFileSize(suid, seuid, SCANNERTYPE )
                    if kspace["size"] > 0:  
                        dict4["status"] = 1
                        dict4["message"] = "Kspace found"
                    else:
                        dict4["status"] = 2
                        dict4["message"] = "Kspace not found"
                    kspacelist.append(kspace)
                    dict4["file"] = kspacelist
                    if "ND" not in data["SeriesDescription"]:
                        t1_block[data["ClassifyType"][2] + '_run_' + str(t1_runcounter)+"_KSPACE"] = copy.deepcopy(dict4) 
                    t1_runcounter = t1_runcounter + 1
                                       
   
                elif ('T2' in data["ClassifyType"][2]):
                    # check if any T1 Norm has enough files.
                    logging.debug (str(filecounts["T2"]) + " T2 Norm slice in DICOM vs number of Files " + str(data["NumFiles"]))
                    if int(filecounts["T2"]) > int(data["NumFiles"]):
                        # it is incomplete series
                        print("T2 Set the dict2[status] = 0")
                        dict2["status"] = 0
                        compliance_found = 0
                    t2_block[data["ClassifyType"][2] + '_run_' + str(t2_nd_runcounter)] = copy.deepcopy(dict2)
                    # check the kspace datadd
                    kspace["path"] = getKSpaceFilePath(suid, seuid, SCANNERTYPE)
                    kspace["size"] = getKSpaceFileSize(suid, seuid, SCANNERTYPE )
                    if kspace["size"] > 0:
                        dict4["status"] = 1 
                        dict4["message"] = "Kspace data found" 
                    else:
                        dict4["message"] = "Kspace data Not found" 
                        dict4["status"] = 2
                    kspacelist.append(kspace)
                    dict4["file"] = kspacelist    
                    if "ND" not in data["SeriesDescription"]:
                        t2_block[data["ClassifyType"][2] + '_run_' + str(t2_nd_runcounter)+"_KSPACE"] = copy.deepcopy(dict4) 
                    t2_nd_runcounter = t2_nd_runcounter + 1

                elif 'HBCD-dMRI' in data["ClassifyType"][2]:
                    if int(filecounts["HBCD-dMRI"]) > int(data["NumFiles"]):
                            # it is incomplete series
                            print("HBCD-dMRI Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0
                    dti_block[data["ClassifyType"][2] + '_run_' + str(dti_runcounter)] = copy.deepcopy(dict2)
                    # check the kspace datadd
                    kspace["path"] = getKSpaceFilePath(suid, seuid, SCANNERTYPE)
                    kspace["size"] = getKSpaceFileSize(suid, seuid, SCANNERTYPE )
                    if kspace["size"] :
                        dict4["status"] = 1 
                        dict4["message"] = "Kspace data found" 
                    else:
                        dict4["message"] = "Kspace data Not found" 
                        dict4["status"] = 2
                    kspacelist.append(kspace)
                    dict4["file"] = kspacelist    
                    dti_block[data["ClassifyType"][2] + '_run_' + str(dti_runcounter)+"_KSPACE"] = copy.deepcopy(dict4) 
                    dti_runcounter = dti_runcounter + 1
   
                    
                elif 'HBCD-fMRI' in data["ClassifyType"][2]:
                    if int(filecounts["HBCD-fMRI"]) > int(data["NumFiles"]):
                            # it is incomplete series
                            print("restfMRI Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0

                    rsfmri_block[data["ClassifyType"][2] + '_run_' + str(rsfmri_runcounter)] = copy.deepcopy(dict2)
                    # check the kspace datadd
                    kspace["path"] = getKSpaceFilePath(suid, seuid, SCANNERTYPE)
                    kspace["size"] = getKSpaceFileSize(suid, seuid, SCANNERTYPE )
                    if kspace["size"] > 0 :
                        dict4["status"] = 1 
                        dict4["message"] = "Kspace data found" 
                    else:
                        dict4["message"] = "Kspace data Not found" 
                        dict4["status"] = 2
                    kspacelist.append(kspace)
                    dict4["file"] = kspacelist    
                    rsfmri_block[data["ClassifyType"][2] + '_run_' + str(rsfmri_runcounter)+"_KSPACE"] = copy.deepcopy(dict4) 
                    rsfmri_runcounter = rsfmri_runcounter + 1
                
                elif 'qMRI' in data["ClassifyType"][2]:
                    if int(filecounts["qMRI"]) > int(data["NumFiles"]):
                            # it is incomplete series
                            print("qMRI Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0

                    qmri_block[data["ClassifyType"][2] + '_run_' + str(qmri_runcounter)] = copy.deepcopy(dict2)
                    # check the kspace datadd
                    kspace["path"] = getKSpaceFilePath(suid, seuid, SCANNERTYPE)
                    kspace["size"] = getKSpaceFileSize(suid, seuid, SCANNERTYPE )
                    if kspace["size"] > 0:
                        dict4["status"] = 1 
                        dict4["message"] = "Kspace data found" 
                    else:
                        dict4["message"] = "Kspace data Not found" 
                        dict4["status"] = 2
                    kspacelist.append(kspace)
                    dict4["file"] = kspacelist    
                    qmri_block[data["ClassifyType"][2] + '_run_' + str(qmri_runcounter)+"_KSPACE"] = copy.deepcopy(dict4) 
                    qmri_runcounter = qmri_runcounter + 1

                elif 'B1' in data["ClassifyType"][2]:
                    if int(filecounts["B1"]) > int(data["NumFiles"]):
                            # it is incomplete series
                            print("bMRI Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0

                    b1_block[data["ClassifyType"][2] + '_run_' + str(bmri_runcounter)] = copy.deepcopy(dict2)
                    # check the kspace datadd
                    kspace["path"] = getKSpaceFilePath(suid, seuid, SCANNERTYPE)
                    kspace["size"] = getKSpaceFileSize(suid, seuid, SCANNERTYPE )
                    if kspace["size"] > 0 :
                        dict4["status"] = 1 
                        dict4["message"] = "Kspace data found" 
                    else:
                        dict4["message"] = "Kspace data Not found" 
                        dict4["status"] = 2
                    kspacelist.append(kspace)
                    dict4["file"] = kspacelist    
                    b1_block[data["ClassifyType"][2] + '_run_' + str(bmri_runcounter)+"_KSPACE"] = copy.deepcopy(dict4) 
                    bmri_runcounter = bmri_runcounter + 1

                #Field Map

                elif 'HBCD-FM-fMRI-PA' in data["ClassifyType"][2]:
                    if int(filecounts["FM-fMRI"]) > int(data["NumFiles"]):
                            # it is incomplete series
                            print("restfMRI Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0

                    fmPAfmri_block[data["ClassifyType"][2] + '_run_' + str(fmPAfmri_runcounter)] = copy.deepcopy(dict2)
                    # check the kspace datadd
                    kspace["path"] = getKSpaceFilePath(suid, seuid, SCANNERTYPE)
                    kspace["size"] = getKSpaceFileSize(suid, seuid, SCANNERTYPE )
                    if kspace["size"] > 0  :
                        dict4["status"] = 1 
                        dict4["message"] = "Kspace data found" 
                    else:
                        dict4["message"] = "Kspace data Not found" 
                        dict4["status"] = 2
                    kspacelist.append(kspace)
                    dict4["file"] = kspacelist    
                    fmPAfmri_block[data["ClassifyType"][2] + '_run_' + str(fmPAfmri_runcounter)+"_KSPACE"] = copy.deepcopy(dict4) 
                    fmPAfmri_runcounter = fmPAfmri_runcounter + 1
                elif 'HBCD-FM-fMRI-AP' in data["ClassifyType"][2]:
                    if int(filecounts["FM-fMRI"]) > int(data["NumFiles"]):
                            # it is incomplete series
                            print("restfMRI Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0

                    fmAPfmri_block[data["ClassifyType"][2] + '_run_' + str(fmAPfmri_runcounter)] = copy.deepcopy(dict2)
                    # check the kspace datadd
                    kspace["path"] = getKSpaceFilePath(suid, seuid, SCANNERTYPE)
                    kspace["size"] = getKSpaceFileSize(suid, seuid, SCANNERTYPE )
                    if kspace["size"] > 0 :
                        dict4["status"] = 1 
                        dict4["message"] = "Kspace data found" 
                    else:
                        dict4["message"] = "Kspace data Not found" 
                        dict4["status"] = 2
                    kspacelist.append(kspace)
                    dict4["file"] = kspacelist    
                    fmAPfmri_block[data["ClassifyType"][2] + '_run_' + str(fmAPfmri_runcounter)+"_KSPACE"] = copy.deepcopy(dict4) 
                    fmAPfmri_runcounter = fmAPfmri_runcounter + 1
                elif 'SVS-loc' in data["ClassifyType"][2]:
                    dict[data["ClassifyType"][2] + '_run_' + str(mrs_runcounter)] = copy.deepcopy(dict2)
                    # check the kspace datadd
                    mrs["path"] = getMRSFilePath(data["PatientID"])
                    mrs["size"] = getMRSFileSize(data["PatientID"])
                    if mrs["size"] > 0 :
                        dict4["status"] = 1 
                        dict4["message"] = "MRS data found" 
                    else:
                        dict4["message"] = "MRS data Not found" 
                        dict4["status"] = 2
                    mrslist.append(mrs)
                    dict4["file"] = mrslist    
                    mrs_block[data["ClassifyType"][2] + '_run_' + str(mrs_runcounter)+"_MRS"] = copy.deepcopy(dict4) 
                    mrs_runcounter = bmri_runcounter + 1
                else:
                    others.append(copy.deepcopy(dict2))
            else:
                others.append(copy.deepcopy(dict2))

            dict["ManufacturerModelName"] = data["ManufacturerModelName"]


    # check the /data/DAIC folder if any series in /data/quarentine and also in /data/DAIC folder use the data in /data/quarentine
    datadir = "/data/DAIC/"

    for filename in os.listdir(datadir):
        if SUID in filename and '.json' in filename and filename != 'scp_'+SUID+'.json':
            print(filename)
            logging.info(filename)

            # read each json file
            with open(os.path.join(datadir,filename), 'r') as f:
                data = json.load(f)
                
            logging.debug(data)
            suid = data["StudyInstanceUID"]
            seuid = data["SeriesInstanceUID"]

            #ignore the data if it is in /data/quarantine folder
            if seuid in SeUIDs:
                continue

            print(suid,"_", seuid)
            dict["PatientID"] = data["PatientID"]
            dict["PatientName"] = data["PatientName"]
            dict["StudyDate"] = data["StudyDate"]
            dict["PatientID"] = data["PatientID"]
            dict["StudyInstanceUID"] = data["StudyInstanceUID"]
            dict["Manufacturer"] = data["Manufacturer"]
            dict["ManufacturerModelName"] = data["ManufacturerModelName"] 
            # re-calculate number of file in the series
            data["NumFiles"] = getSeriesFileCount(filename)
            dict2 = {}
            if len(data["ClassifyType"]) > 2:
                dict2["status"] = 1
                compliance_found = 1
            else:
                dict2["status"] = 0
            dict2["SeriesNumber"] = data["SeriesNumber"]
            dict2["SeriesInstanceUID"] = data["SeriesInstanceUID"]
            dict2["message"] = 'HBCD ' + data["SeriesDescription"] + ' was found'
            #file size and path
            dict3 = {}
            dict4 = {}
            list3 = []
            dict3["path"] = os.path.join(datadir,filename)
            dict3["size"] = os.path.getsize(os.path.join(datadir,filename))
            list3.append(dict3)
            dict2["file"] = list3

            kspace = {}
            kspacelist = []
            mrs = {}
            mrslist = []
            
            if len(data["ClassifyType"]) > 2:
                logging.info(data["ClassifyType"][2])
                print(data["ClassifyType"][2])
                #depending on serise to imcrement the run counter
                if 'T1' in data["ClassifyType"][2]:
                    # check if any T1 Norm has enough files.
                    logging.debug (str(filecounts["T1"]) + " T1 Norm slice in DICOM vs number of Files " + str(data["NumFiles"]))
                    if int(filecounts["T1"])  > int(data["NumFiles"]):
                        # it is incomplete series
                        print("T1 Set the dict2[status] = 0")
                        dict2["status"] = 0
                        compliance_found = 0

                    t1_block[data["ClassifyType"][2] + '_run_' + str(t1_runcounter)] = copy.deepcopy(dict2)
 
                    # check the kspace datadd
                    kspace["path"] = getKSpaceFilePath(suid, seuid, SCANNERTYPE)
                    kspace["size"] = getKSpaceFileSize(suid, seuid, SCANNERTYPE )
                    if kspace["size"] > 0 :
                        dict4["status"] = 1 
                        dict4["message"] = "Kspace data found" 
                    else:
                        dict4["message"] = "Kspace data Not found" 
                        dict4["status"] = 2
                    kspacelist.append(kspace)
                    dict4["file"] = kspacelist    
                    if "ND" not in data["SeriesDescription"]:
                        t1_block[data["ClassifyType"][2] + '_run_' + str(t1_runcounter)+"_KSPACE"] = copy.deepcopy(dict4) 
                    t1_runcounter = t1_runcounter + 1
                                       
   
                elif 'T2' in data["ClassifyType"][2]:
                    # check if any T1 Norm has enough files.
                    logging.debug (str(filecounts["T2"]) + " T2 Norm slice in DICOM vs number of Files " + str(data["NumFiles"]))
                    if int(filecounts["T2"]) > int(data["NumFiles"]):
                        # it is incomplete series
                        print("T2 Set the dict2[status] = 0")
                        dict2["status"] = 0
                        compliance_found = 0
                    t2_block[data["ClassifyType"][2] + '_run_' + str(t2_nd_runcounter)] = copy.deepcopy(dict2)

                    # check the kspace data
                    kspace["path"] = getKSpaceFilePath(suid, seuid, SCANNERTYPE )
                    kspace["size"] = getKSpaceFileSize(suid, seuid, SCANNERTYPE )
                    if kspace["size"] > 0 :
                        dict4["status"] = 1 
                        dict4["message"] = "Kspace data found" 
                    else:
                        dict4["message"] = "Kspace data Not found" 
                        dict4["status"] = 2
                    kspacelist.append(kspace)
                    dict4["file"] = kspacelist    
                    if "ND" not in data["SeriesDescription"]:
                        t2_block[data["ClassifyType"][2] + '_run_' + str(t2_runcounter)+"_KSPACE"] = copy.deepcopy(dict4) 
                    t2_nd_runcounter = t2_nd_runcounter + 1
                                       
   
                elif 'HBCD-dMRI' in data["ClassifyType"][2]:
                    if int(filecounts["HBCD-dMRI"]) > int(data["NumFiles"]):
                            # it is incomplete series
                            print("HBCD-dMRI Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0
                    dti_block[data["ClassifyType"][2] + '_run_' + str(dti_runcounter)] = copy.deepcopy(dict2)
   
                    # check the kspace data
                    kspace["path"] = getKSpaceFilePath(suid, seuid, SCANNERTYPE )
                    kspace["size"] = getKSpaceFileSize(suid, seuid, SCANNERTYPE )
                    if kspace["size"] > 0 :
                        dict4["status"] = 1 
                        dict4["message"] = "Kspace data found" 
                    else:
                        dict4["message"] = "Kspace data Not found" 
                        dict4["status"] = 2
                    kspacelist.append(kspace)
                    dict4["file"] = kspacelist    
                    dti_block[data["ClassifyType"][2] + '_run_' + str(dti_runcounter)+"_KSPACE"] = copy.deepcopy(dict4) 
                                       
                    dti_runcounter = dti_runcounter + 1
   
                    
                elif 'HBCD-fMRI' in data["ClassifyType"][2]:
                    if int(filecounts["HBCD-fMRI"]) > int(data["NumFiles"]):
                            # it is incomplete series
                            print("restfMRI Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0

                    rsfmri_block[data["ClassifyType"][2] + '_run_' + str(rsfmri_runcounter)] = copy.deepcopy(dict2)
                    # check the kspace data
                    kspace["path"] = getKSpaceFilePath(suid, seuid, SCANNERTYPE )
                    kspace["size"] = getKSpaceFileSize(suid, seuid, SCANNERTYPE )
                    if kspace["size"] > 0 :
                        dict4["status"] = 1 
                        dict4["message"] = "Kspace data found" 
                    else:
                        dict4["message"] = "Kspace data Not found" 
                        dict4["status"] = 2
                    kspacelist.append(kspace)
                    dict4["file"] = kspacelist    
                    rsfmri_block[data["ClassifyType"][2] + '_run_' + str(rsfmri_runcounter)+"_KSPACE"] = copy.deepcopy(dict4) 
                    rsfmri_runcounter = rsfmri_runcounter + 1
                
                elif 'qMRI' in data["ClassifyType"][2]:
                    if int(filecounts["qMRI"]) > int(data["NumFiles"]):
                            # it is incomplete series
                            print("qMRI Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0

                    qmri_block[data["ClassifyType"][2] + '_run_' + str(qmri_runcounter)] = copy.deepcopy(dict2)
                    # check the kspace data
                    kspace["path"] = getKSpaceFilePath(suid, seuid, SCANNERTYPE )
                    kspace["size"] = getKSpaceFileSize(suid, seuid, SCANNERTYPE )
                    if kspace["size"] > 0 :
                        dict4["status"] = 1 
                        dict4["message"] = "Kspace data found" 
                    else:
                        dict4["message"] = "Kspace data Not found" 
                        dict4["status"] = 2
                    kspacelist.append(kspace)
                    dict4["file"] = kspacelist    
                    qmri_block[data["ClassifyType"][2] + '_run_' + str(qmri_runcounter)+"_KSPACE"] = copy.deepcopy(dict4) 
                    qmri_runcounter = qmri_runcounter + 1

                elif 'HBCD-FM-fMRI-PA' in data["ClassifyType"][2]:
                    if int(filecounts["FM-fMRI"]) > int(data["NumFiles"]):
                            # it is incomplete series
                            print("restfMRI Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0

                    fmPAfmri_block[data["ClassifyType"][2] + '_run_' + str(fmPAfmri_runcounter)] = copy.deepcopy(dict2)
                    # check the kspace data
                    kspace["path"] = getKSpaceFilePath(suid, seuid, SCANNERTYPE )
                    kspace["size"] = getKSpaceFileSize(suid, seuid, SCANNERTYPE )
                    if kspace["size"] > 0 :
                        dict4["status"] = 1 
                        dict4["message"] = "Kspace data found" 
                    else:
                        dict4["message"] = "Kspace data Not found" 
                        dict4["status"] = 2
                    kspacelist.append(kspace)
                    dict4["file"] = kspacelist    
                    fmPAfmri_block[data["ClassifyType"][2] + '_run_' + str(fmPAfmri_runcounter)+"_KSPACE"] = copy.deepcopy(dict4) 
                    fmPAfmri_runcounter = fmPAfmri_runcounter + 1
                elif 'HBCD-FM-fMRI-AP' in data["ClassifyType"][2]:
                    if int(filecounts["FM-fMRI"]) > int(data["NumFiles"]):
                            # it is incomplete series
                            print("restfMRI Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0

                    fmAPfmri_block[data["ClassifyType"][2] + '_run_' + str(fmAPfmri_runcounter)] = copy.deepcopy(dict2)
                    # check the kspace data
                    kspace["path"] = getKSpaceFilePath(suid, seuid, SCANNERTYPE )
                    kspace["size"] = getKSpaceFileSize(suid, seuid, SCANNERTYPE )
                    if kspace["size"] > 0 :
                        dict4["status"] = 1 
                        dict4["message"] = "Kspace data found" 
                    else:
                        dict4["message"] = "Kspace data Not found" 
                        dict4["status"] = 2
                    kspacelist.append(kspace)
                    dict4["file"] = kspacelist    
                    fmAPfmri_block[data["ClassifyType"][2] + '_run_' + str(fmAPfmri_runcounter)+"_KSPACE"] = copy.deepcopy(dict4) 
                    fmAPfmri_runcounter = fmAPfmri_runcounter + 1
                elif 'B1' in data["ClassifyType"][2]:
                    if int(filecounts["B1"]) > int(data["NumFiles"]):
                            # it is incomplete series
                            print("bMRI Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0

                    b1_block[data["ClassifyType"][2] + '_run_' + str(bmri_runcounter)] = copy.deepcopy(dict2)
                    # check the kspace data
                    kspace["path"] = getKSpaceFilePath(suid, seuid, SCANNERTYPE )
                    kspace["size"] = getKSpaceFileSize(suid, seuid, SCANNERTYPE )
                    if kspace["size"] > 0 :
                        dict4["status"] = 1 
                        dict4["message"] = "Kspace data found" 
                    else:
                        dict4["message"] = "Kspace data Not found" 
                        dict4["status"] = 2
                    kspacelist.append(kspace)
                    dict4["file"] = kspacelist    
                    b1_block[data["ClassifyType"][2] + '_run_' + str(bmri_runcounter)+"_KSPACE"] = copy.deepcopy(dict4) 
                    bmri_runcounter = bmri_runcounter + 1
                elif 'SVS_loc' in data["ClassifyType"][2]:
                    mrs_block[data["ClassifyType"][2] + '_run_' + str(mrs_runcounter)] = copy.deepcopy(dict2)
                    # check the kspace datadd
                    mrs["path"] = getMRSFilePath(data["PatientID"])
                    mrs["size"] = getMRSFileSize(data["PatientID"])
                    print("MRS file path :", mrs["path"])
                    print("MRS file size :", mrs["size"])
                    if mrs["size"] > 0 :
                        dict4["status"] = 1 
                        dict4["message"] = "MRS raw data found" 
                    else:
                        dict4["message"] = "MRS raw data Not found" 
                        dict4["status"] = 2
                    mrslist.append(mrs)
                    dict4["file"] = mrslist    
                    mrs_block[data["ClassifyType"][2] + '_run_' + str(mrs_runcounter)+"_MRS"] = copy.deepcopy(dict4) 
                    mrs_runcounter = bmri_runcounter + 1
                else:
                    others.append(copy.deepcopy(dict2))
            else:
                others.append(copy.deepcopy(dict2))

            dict["ManufacturerModelName"] = data["ManufacturerModelName"]
            
            

    if compliance_found:
        dict["status"] = "1"
        dict["shortmessage"] = "C"
        dict["message"] = "The Serie is ready to send to DAIRC. One session based acquisition."
    else:
        dict["status"] = "0"
        dict["shortmessage"] = "N"
        dict["message"] = "Some series is not complete, Please re-send them from original sources"




    if len(t1_block) > 2 :
        t1_block["message"] = "HBCD-T1 series was found"
        t1_block["status"] =1
       
    else:
        t1_block["message"] = "HBCD-T1 series was not found"
        t1_block["status"] = 0 
    if len(t2_block) > 2 :
        t2_block["status"] = 1
        t2_block["message"] = "HBCD-T2 series was found"
    else:
        t2_block["status"] = 0
        t2_block["message"] = "HBCD-T2 series was not found"

    if dti_runcounter >0 and  fmPAdti_runcounter > 0 and fmAPdti_runcounter: 
         dti_block["status"] = 1
         dti_block["message"] = " HBCD-dMRI component was found"
    else: 
         dti_block["status"] = 0
         dti_block["message"] = " HBCD-dMRI component was not found, A  DTI component should include a both AP and PA dMRI field map followed by the dMRI acquisition"

    print(midfmri_runcounter, fmAPfmri_runcounter, fmPAfmri_runcounter)

    if rsfmri_runcounter >0 and  rsfmri_runcounter > 0 and rsfmri_runcounter:
         rsfmri_block["status"] = 1
         rsfmri_block["message"] = " rest fMRI task was found."
    else: 
         rsfmri_block["status"] = 0
         rsfmri_block["message"] = " rest fMRI task was not found. A  rest fMRI task component should include a fMRI field map followed by the rest fMRI task acquisition"

    if fmAPfmri_block:
        fmAPfmri_block["message"] = "fMRI FieldMap AP was found."
    else:
        fmAPfmri_block["message"] = "fMRI FieldMap AP was Not found."
    if fmAPfmri_block:
        fmPAfmri_block["message"] = "fMRI FieldMap PA was found."
    else: 
        fmPAfmri_block["message"] = "fMRI FieldMap PA was Not found."

    if qmri_block:
        qmri_block["message"] = "qMRI was found."
    else:
        qmri_block["message"] = "qMRI was Not found."
    if b1_block:
        b1_block["message"] = "B1 was found."
    else: 
        b1_block["message"] = "B1 was Not found."
    if mrs_block:
        mrs_block["message"] = "MRS was found."
    else: 
        mrs_block["message"] = "MRS was Not found."

    dict["T1"] = t1_block
    dict["T2"] = t2_block
    dict["dMRI_Block"] = dti_block
    dict["rsfMRI_Block"] = rsfmri_block
    dict["fMRI_FieldMap_AP_Block"] = fmAPfmri_block
    dict["fMRI_FieldMap_PA_Block"] = fmPAfmri_block
    dict["qMRI_Block"] = qmri_block
    dict["B1_block"] = b1_block 
    dict["AdditionalSeries"] = others
    dict["MRS"] = mrs_block

    #dict = dict(sorted(dict, key=lambda k: k.get('SeriesTime')))


    with open(os.path.join("/data/site/output/scp_"+SUID, "series_compliance/compliance_output.json"), "w") as write_file:
        json.dump(dict, write_file, indent = 4) 
    with open(os.path.join("/data/quarantine/","scp_"+SUID +  ".json"), "w") as write_suid_file:
        json.dump(dict, write_suid_file, indent = 4)
