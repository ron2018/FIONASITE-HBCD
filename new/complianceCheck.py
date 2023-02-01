"""
 Check the protocol compliance for HBCD data in /data/quanrentine folder
 Ron Yang 09/2022

"""

import sys, os, time, atexit, stat, tempfile, copy, traceback
import pydicom, json, re,  logging.handlers, threading, string
import json
import logging as log
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
    parser.add_argument('--project', help="Project Name")
    parser.add_argument('--verbose', '-v', action='store_true',
                        help="Display / save logged DEBUG-level messages.")
    return parser.parse_args()

def getSeriesFileCount(filename):
  # return file series count and update filename with new filecont
  UIDs = filename.split("_")
  SUID = UIDs[0]

  SeUID = UIDs[1].replace(".json","")
  print("SUID : ", SUID, " SeUID : ", SeUID)
  dir_path = r'/data/site/raw/' + SUID + r'/' + SeUID 
  count = len([entry for entry in os.listdir(dir_path) if os.path.isfile(os.path.join(dir_path, entry))])
  print("Dir_Path" + dir_path + "file count " + str(count))
  
  return count
 

# take SUID as first parameter, project name as optional second parameter
if __name__ == "__main__":
    projname = ''

    args = parse_arguments()
    if args.verbose:
        log.getLogger().setLevel(log.DEBUG)

    SUID = args.suid
    projname = args.project



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

        logging.info("DEBUG: complianceSetting.json: ", complianceSetting[SCANNERTYPE]["T1"])
        logging.debug(json.dumps(filecounts))

    datadir = '/data/quarantine/'    
    dict = {}
    
    #read the json files in /data/quantine folder with all SUID name in it
    compliance_found = 0
    t1_block = {}
    t2_block = {}
    others = []
    dti_block = {}
    mid_fmri_block = {}
    sst_fmri_block = {}
    nback_fmri_block = {}
    rsfmri_block = {}
    fmAPfmri_block = {}
    fmPAfmri_block = {}

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
    midmri_runcounter = 1
    fmAPdti_runcounter = 1 
    fmPAdti_runcounter = 1 
    fmAPfmri_runcounter = 1 
    fmPAfmri_runcounter = 1 
    nBackfmri_runcounter = 1 
    for filename in os.listdir(datadir):
        if SUID in filename and '.json' in filename and filename != 'scp_'+SUID+'.json':
            print(filename)
            logging.info(filename)

            # read each json file
            with open(os.path.join(datadir,filename), 'r') as f:
                data = json.load(f)
                
            logging.debug(data)

            dict["PatientID"] = data["PatientID"]
            dict["PatientName"] = data["PatientName"]
            dict["StudyDate"] = data["StudyDate"]
            dict["PatientID"] = data["PatientID"]
            dict["StudyInstanceUID"] = data["StudyInstanceUID"]
            dict["Manufacturer"] = data["Manufacturer"]
            dict["ManufacturerModelName"] = data["ManufacturerModelName"] 
            dict["SeriesTime"] = data["SeriesTime"]
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
            dict2["message"] = 'ABCD ' + data["SeriesDescription"] + ' was found'
            #file size and path
            dict3 = {}
            dict4 = {}
            list3 = []
            dict3["path"] = os.path.join(datadir,filename)
            dict3["size"] = os.path.getsize(os.path.join(datadir,filename))
            list3.append(dict3)
            dict2["file"] = list3
            
            if len(data["ClassifyType"]) > 2:
                logging.info(data["ClassifyType"][2])
                #depending on serise to imcrement the run counter
                if 'T1' in data["ClassifyType"][2]:
                    if ('T1'in data["ClassifyType"][2]):
                        # check if any T1 Norm has enough files.
                        logging.debug (str(filecounts["T1"]) + " T1 Norm slice in DICOM vs number of Files " + str(data["NumFiles"]))
                        if int(filecounts["T1"])  > int(data["NumFiles"]):
                            # it is incomplete series
                            print("T1 Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0
                        if ('T1-ND'in data["ClassifyType"][2]):
                            t1_block[data["ClassifyType"][2] + '_run_' + str(t1_nd_runcounter)] = copy.deepcopy(dict2)
                            t1_nd_runcounter = t1_nd_runcounter + 1
                        else:
                            t1_block[data["ClassifyType"][2] + '_run_' + str(t1_runcounter)] = copy.deepcopy(dict2)
                            t1_runcounter = t1_runcounter + 1
   
                elif 'T2' in data["ClassifyType"][2]:
                    if ('T2' in data["ClassifyType"][2]):
                        # check if any T1 Norm has enough files.
                        logging.debug (str(filecounts["T2"]) + " T2 Norm slice in DICOM vs number of Files " + str(data["NumFiles"]))
                        if int(filecounts["T2"]) > int(data["NumFiles"]):
                            # it is incomplete series
                            print("T2 Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0
                        if ('T1-ND'in data["ClassifyType"][2]):
                            t2_nd_runcounter = t2_nd_runcounter + 1
                            t2_block[data["ClassifyType"][2] + '_run_' + str(t2_nd_runcounter)] = copy.deepcopy(dict2)
                        else:
                            t2_runcounter = t2_runcounter + 1
                            t2_block[data["ClassifyType"][2] + '_run_' + str(t2_runcounter)] = copy.deepcopy(dict2)
                elif 'ABCD-DTI' in data["ClassifyType"][2]:
                    if int(filecounts["ABCD-DTI"]) > int(data["NumFiles"]):
                            # it is incomplete series
                            print("ABCD-DTI Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0
                    dti_block[data["ClassifyType"][2] + '_run_' + str(dti_runcounter)] = copy.deepcopy(dict2)
                    dti_runcounter = dti_runcounter + 1
   
                    
                elif 'ABCD-rsfMRI' in data["ClassifyType"][2]:
                    if int(filecounts["ABCD-rsfMRI"]) > int(data["NumFiles"]):
                            # it is incomplete series
                            print("restfMRI Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0

                    rsfmri_block[data["ClassifyType"][2] + '_run_' + str(rsfmri_runcounter)] = copy.deepcopy(dict2)
                    rsfmri_runcounter = rsfmri_runcounter + 1

                elif 'ABCD-nBack-fMRI' in data["ClassifyType"][2]:
                    if int(filecounts["ABCD-nBack-fMRI"]) > int(data["NumFiles"]):
                            # it is incomplete series
                            print("nBackfMRI Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0

                    nback_fmri_block[data["ClassifyType"][2] + '_run_' + str(nBackfmri_runcounter)] = copy.deepcopy(dict2)
                    nBackfmri_runcounter = nBackfmri_runcounter + 1

                elif 'ABCD-SST-fMRI' in data["ClassifyType"][2]:
                    if int(filecounts["ABCD-SST-fMRI"]) > int(data["NumFiles"]):
                            # it is incomplete series
                            print("SST_fMRI Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0

                    sst_fmri_block[data["ClassifyType"][2] + '_run_' + str(sstfmri_runcounter)] = copy.deepcopy(dict2)
                    sstfmri_runcounter = sstfmri_runcounter + 1

                elif 'ABCD-MID-fMRI' in data["ClassifyType"][2]:
                    if int(filecounts["ABCD-MID-fMRI"]) > int(data["NumFiles"]):
                            # it is incomplete series
                            print("midfMRI Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0

                    mid_fmri_block[data["ClassifyType"][2] + '_run_' + str(midfmri_runcounter)] = copy.deepcopy(dict2)
                    midmri_runcounter = midfmri_runcounter + 1

                #Field Map
                elif  'ABCD-Diffusion-FM-PA' in data["ClassifyType"][2] :
                    if int(filecounts["ABCD-Diffusion-FM-PA"]) > int(data["NumFiles"]):
                            # it is incomplete series
                            print("ABCD-Diffusion-FM-PA Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0
                    dti_block[data["ClassifyType"][2] + '_run_' + str(fmPAdti_runcounter)] = copy.deepcopy(dict2)
                    fmPAdti_runcounter = fmPAdti_runcounter + 1
                #Field Map
                elif  'ABCD-Diffusion-FM-AP' in data["ClassifyType"][2] :
                    if int(filecounts["ABCD-Diffusion-FM-AP"]) > int(data["NumFiles"]):
                            # it is incomplete series
                            print("ABCD-Diffusion-FM-PA Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0
                    dti_block[data["ClassifyType"][2] + '_run_' + str(fmAPdti_runcounter)] = copy.deepcopy(dict2)
                    fmAPdti_runcounter = fmAPdti_runcounter + 1

                elif 'ABCD-fMRI-FM-PA' in data["ClassifyType"][2]:
                    if int(filecounts["ABCD-fMRI-FM-PA"]) > int(data["NumFiles"]):
                            # it is incomplete series
                            print("restfMRI Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0

                    fmPAfmri_block[data["ClassifyType"][2] + '_run_' + str(fmPAfmri_runcounter)] = copy.deepcopy(dict2)
                    fmPAfmri_runcounter = fmPAfmri_runcounter + 1
                elif 'ABCD-fMRI-FM-AP' in data["ClassifyType"][2]:
                    if int(filecounts["ABCD-fMRI-FM-AP"]) > int(data["NumFiles"]):
                            # it is incomplete series
                            print("restfMRI Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0

                    fmAPfmri_block[data["ClassifyType"][2] + '_run_' + str(fmAPfmri_runcounter)] = copy.deepcopy(dict2)
                    fmAPfmri_runcounter = fmAPfmri_runcounter + 1
                elif 'FM-fMRI' in data["ClassifyType"][2]:
                    logging.debug(str(filecounts["FM-fMRI"]) + "<should be : actual is > " +  str(data["NumFiles"]))
                    if int(filecounts["FM-fMRI"]) > int(data["NumFiles"]):
                            # it is incomplete series
                            print("fMRI-FM Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0

                    dict[data["ClassifyType"][2] + '_run_' + str(fmri_fm_runcounter)] = copy.deepcopy(dict2)
                    fmri_fm_runcounter = fmri_fm_runcounter + 1
                elif 'B1' in data["ClassifyType"][2]:
                    if int(filecounts["B1"]) > int(data["NumFiles"]):
                            # it is incomplete series
                            print("bMRI Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0

                    dict[data["ClassifyType"][2] + '_run_' + str(bmri_runcounter)] = copy.deepcopy(dict2)
                    bmri_runcounter = bmri_runcounter + 1
                else:
                    others.append(copy.deepcopy(dict2))
            else:
                others.append(copy.deepcopy(dict2))

            dict["ManufacturerModelName"] = data["ManufacturerModelName"]
            #print(data["SeriesNumber"])
            

    if compliance_found:
        dict["status"] = "1"
        dict["shortmessage"] = "C"
        dict["message"] = "The Serie is ready to send to DAIRC. One session based acquisition."
    else:
        dict["status"] = "0"
        dict["shortmessage"] = "N"
        dict["message"] = "Some series is not compliant, Please re-send them from original sources"

    t1_block["message"] = "Compliant ABCD-T1 series was found"
    t2_block["message"] = "Compliant ABCD-T2 series was found"
    t1_block["status"] =1
    t2_block["status"] = 1

    if dti_runcounter >0 and  fmPAdti_runcounter > 0 and fmAPdti_runcounter: 
         dti_block["status"] = 1
         dti_block["message"] = "Compliant ABCD-DTI component was found"
    else: 
         dti_block["status"] = 0
         dti_block["message"] = "Compliant ABCD-DTI component was not found, A compliant DTI component should include a both AP and PA DTI field map followed by the DTI acquisition"

    print(midfmri_runcounter, fmAPfmri_runcounter, fmPAfmri_runcounter)
    if midfmri_runcounter > 0 and  fmAPfmri_runcounter > 0  and fmPAfmri_runcounter > 0: 
         mid_fmri_block["status"] = 1
         mid_fmri_block["message"] = "Compliant ABCD MID fMRI task was found."
    else: 
         mid_fmri_block["status"] = 0
         mid_fmri_block["message"] = "Compliant ABCD MID fMRI task  component was not found, A compliant MID fMRI component should include a MID fMRI field map followed by the MID fMRI acquisition"

    if sstfmri_runcounter > 0 and  fmAPfmri_runcounter > 0  and fmPAfmri_runcounter > 0: 
         sst_fmri_block["status"] = 1
         sst_fmri_block["message"] = "Compliant ABCD SST fMRI task was found."
         
    else: 
         dti_block["status"] = 0
         dti_block["message"] = "Compliant ABCD SST fMRI component was not found, A compliant SST fMRI should include a SST fMRI field map followed by the SST fMRI acquisition"

    if nBackfmri_runcounter > 0 and  fmAPfmri_runcounter > 0  and fmPAfmri_runcounter > 0: 
         nback_fmri_block["status"] = 1
         nback_fmri_block["message"] = "Compliant nBack fMRI task was found."
    else: 
         nback_fmri_block["status"] = 0
         nback_fmri_block["message"] = "Compliant nBack fMRI task was not found. A compliant nBack fMRI task component should include a fMRI field map followed by the nBack fMRI task acquisition"

    if rsfmri_runcounter > 0 and  fmAPfmri_runcounter > 0  and fmPAfmri_runcounter > 0: 
         rsfmri_block["status"] = 1
         rsfmri_block["message"] = "Compliant rest fMRI task was found."
    else: 
         rsfmri_block["status"] = 0
         rsfmri_block["message"] = "Compliant rest fMRI task was not found. A compliant rest fMRI task component should include a fMRI field map followed by the rest fMRI task acquisition"

    fmAPfmri_block["message"] = "fMRI FieldMap AP was found."
    fmPAfmri_block["message"] = "fMRI FieldMap PA was found."

    dict["T1"] = t1_block
    dict["T2"] = t2_block
    dict["DTI_Block"] = dti_block
    dict["MID_fMRI_Block"] = mid_fmri_block
    dict["SST_fMRI_Block"] = sst_fmri_block
    dict["nBack_fMRI_Block"] = nback_fmri_block
    dict["rsfMRI_Block"] = rsfmri_block
    dict["fMRI_FieldMap_AP_Block"] = fmAPfmri_block
    dict["fMRI_FieldMap_PA_Block"] = fmPAfmri_block

    dict["AdditionalSeries"] = others


    #dict = dict(sorted(dict, key=lambda k: k.get('SeriesTime')))


    with open(os.path.join("/data/site/output/scp_"+SUID, "series_compliance/compliance_output.json"), "w") as write_file:
        json.dump(dict, write_file, indent = 4) 
    with open(os.path.join("/data/quarantine/","scp_"+SUID +  ".json"), "w") as write_suid_file:
        json.dump(dict, write_suid_file, indent = 4)
