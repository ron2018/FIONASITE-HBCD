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
    others = []
    t1_runcounter = 1
    t2_runcounter = 1
    t1_nd_runcounter = 1
    t2_nd_runcounter = 1
    dti_runcounter = 1
    rsfmri_runcounter = 1
    tfmri_runcounter = 1
    mri_runcounter = 1
    fmri_fm_runcounter = 1
    dmri_fm_runcounter = 1
    qmri_runcounter = 1
    bmri_runcounter = 1

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
                            dict[data["ClassifyType"][2] + '_run_' + str(t1_nd_runcounter)] = copy.deepcopy(dict2)
                            t1_nd_runcounter = t1_nd_runcounter + 1
                        else:
                            dict[data["ClassifyType"][2] + '_run_' + str(t1_runcounter)] = copy.deepcopy(dict2)
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
                            dict[data["ClassifyType"][2] + '_run_' + str(t2_nd_runcounter)] = copy.deepcopy(dict2)
                        else:
                            t2_runcounter = t2_runcounter + 1
                            dict[data["ClassifyType"][2] + '_run_' + str(t2_runcounter)] = copy.deepcopy(dict2)

                elif 'HBCD-dMRI' in data["ClassifyType"][2]:
                    if int(filecounts["HBCD-dMRI"]) > int(data["NumFiles"]):
                            # it is incomplete series
                            print("DMRI Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0
                    dict[data["ClassifyType"][2] + '_run_' + str(dti_runcounter)] = copy.deepcopy(dict2)
                    dti_runcounter = dti_runcounter + 1
                    
                elif 'HBCD-fMRI' in data["ClassifyType"][2]:
                    if int(filecounts["HBCD-fMRI"]) > int(data["NumFiles"]):
                            # it is incomplete series
                            print("restfMRI Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0

                    dict[data["ClassifyType"][2] + '_run_' + str(rsfmri_runcounter)] = copy.deepcopy(dict2)
                    rsfmri_runcounter = rsfmri_runcounter + 1
                elif 'qMRI' in data["ClassifyType"][2]:
                    if int(filecounts["qMRI"]) > int(data["NumFiles"]):
                            # it is incomplete series
                            print("qMRI Set the dict2[status] = 0")
                            dict2["status"] = 0
                            compliance_found = 0

                    dict[data["ClassifyType"][2] + '_run_' + str(qmri_runcounter)] = copy.deepcopy(dict2)
                    qmri_runcounter = qmri_runcounter + 1
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
           
            logging.debug(dict)

    if compliance_found:
        dict["status"] = "1"
        dict["shortmessage"] = "C"
        dict["message"] = "The Serie is ready to send to HDCC. One session based acquisition."
    else:
        dict["status"] = "0"
        dict["shortmessage"] = "N"
        dict["message"] = "There is no completed T1 or T2 Series, Please re-send them from original sources"
    dict["AdditionalSeries"] = others

    #dict = dict(sorted(dict, key=lambda k: k.get('SeriesTime')))


    with open(os.path.join("/data/site/output/scp_"+SUID, "series_compliance/compliance_output.json"), "w") as write_file:
        json.dump(dict, write_file, indent = 4, sort_keys=True) 
