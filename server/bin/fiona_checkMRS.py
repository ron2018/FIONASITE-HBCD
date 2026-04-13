#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Created on Wed Jul 30 2025
Updated on Fri Jan 30 2026
Updated on Mon Feb 02 2026
Updated on Mon Feb 16 2026

@author: Parekh, Pravesh [JCVI, pparekh@jcvi.org]
"""

# To do list
# * figure out how to handle multiple data types in the same folder
# * figure out what to do if more than one set of .data files exist (which ones map to which?)
# * figure out how to deal with multiple zip files within a single input folder
# * improve robustness of picking the centers from Philips data/list format
# * handle multiple Philips data files
# * improve robustness of checking whether complete NIfTI set is present

# =============================================================================
# Documentation of all exit codes
# =============================================================================
# General exit codes
# ===================
#  0:   normal exit

# File format related exit codes
# ==============================
# -11:  no relevant MRS files found
# -12:  multiple MRS file formats found
# -13:  corresponding SDAT file missing
# -14:  corresponding list file missing
# -15:  corresponding data file missing
# -16:  corresponding sin  file missing
# -17:  corresponding json file missing
# -18:  corresponding txt  file missing
# -19:  corresponding dcm  folder missing
# -20:  both data and list files are missing (when .raw file is present)
# -21:  multiple Philips data files found
# -22:  multiple matching text/JSON/sin files found

# Archive related exit codes
# ==========================
# -31:  multiple archive files within a folder
# -32:  empty archive
# -33:  any general unzipping error
# -34:  zip bomb

# NIfTI related exit codes
# ========================
# -41:  no NIfTI files were generated
# -42:  one or more NIfTI header is invalid
# -43:  "main" NIfTI file not found
# -44:  "short TE" NIfTI file not found
# -45:  "ref" NIfTI file not found
# -46:  one or more combinations of NIfTI files not present

# =============================================================================

# =============================================================================
# Update notes: 30-Jan-2026 / 02-Feb-2026 / 16-Feb-2026
# Allowing multiple scans to be sent
# Allowing localisers to be sent
# Checking for file names
# Exit code -12 is now used for scenarios where more than one file format is found
# Updated exit codes
# Delete output directory if it already exists prior to unarchiving


# First, import any modules that we may need
import os
import numpy
import warnings
import re
import subprocess
import glob
import nibabel
import datetime
import shutil
import pandas as pd

# =============================================================================
# Global variables that can be shared across
# This section also serves as configurable information
# =============================================================================
dir_dcmtk    = "/usr/bin/"
pathSpec2nii = "/home/processing/.local/bin"
dir_Logs     = "/home/processing/logs_MRSCheck"
logName      = []
warn_msgs    = []
fname_dl     = []
fname_data   = []
fname_list   = []
fname_SPAR   = []
fname_SDAT   = []
exitCode     = 0


# Function that recursively checks for archives and unzips, etc.
def archiveModule(inputArg, outDir):
    global exitCode, logName
    count_zip = 0
    
    while count_zip < 3:
        fList = getArchiveFile(inputArg)
        
        # If multiple zip files found, exit
        if exitCode == -31:
            return ""
        
        # If no archives found, possibly a case of unzipped files in a folder; stop
        if not(fList):
            if os.path.isdir(inputArg):
                return inputArg
            else:
                # No archives found
                return ""
        else:
            # Archive found: attempt to unzip and make command
            # while fList:
            outDir      = doUnarchive(fList, outDir)
            if exitCode != 0:
                return ""
            else:
                # fList       = getArchiveFile(outDir)
                count_zip   = count_zip + 1;
                tmpOut      = outDir + "_output"
                inputArg    = outDir
                outDir      = tmpOut

    # Exit condition                    
    if count_zip > 3:
        with open(logName) as f:
            print("Possible zip bomb; aborting", file = f)
        warn_msgs.append("Possible zip bomb; aborting")
        exitCode = -34
        return ""


# Function to unzip/unarchive
def doUnarchive(inFile, outDir):
    global logName, exitCode
    
    # Back up output directory location
    tmpBack = outDir
    
    # Make output directory
    prepOutDir(outDir)
    
    # Make a list of directory content
    currList = os.listdir(outDir)
    
    if isinstance(inFile, list):
        inFile = inFile[0]
        
    if ".zip" in inFile:
        command  = "unzip " + inFile + " -d " + outDir
    else:
        command = "tar -xvzf " + inFile + " -C " + outDir
        
    with open(logName, "a") as f:
        print("Attempting to unarchive " + inFile + "\n", file = f)
        print("Command: " + command + "\n", file = f)
    
        try:
            f.flush()
            unzip_res = subprocess.run(command, shell = True, capture_output = True)
        except:
            warn_msgs.append("One or more error during unarchiving")
            exitCode = -33;
        
        print("Stdout: \n", file = f)
        print(unzip_res.stdout, file = f)
        print("\nStdErr: \n", file = f)
        print(unzip_res.stderr, file = f)
    
        # Updated outDir
        newList = os.listdir(outDir)
        newList = list(set(newList) - set(currList))
    
        # If the length of newList is 0, nothing was generated; nothing to do
        if len(newList) == 0:
            print("No files found inside the archive; aborting", file = f)
            warn_msgs.append("No files found inside the archive; aborting")
            exitCode = -32
            return outDir
        else:
            # If the length of newList is 1, then check if a folder
            # If a folder, this is the new outDir;
            if len(newList) == 1:
                if os.path.isdir(os.path.join(outDir, newList[0])):
                    outDir = os.path.join(outDir, newList[0])
                else:
                    # A file was generated, move it into a folder
                    a, b = os.path.splitext(os.path.basename(newList[0]))
                    tmpOut = os.path.join(tmpBack, a)
                    prepOutDir(tmpOut)
                    command = "mv " + os.path.join(tmpBack, newList[0]) + " " + tmpOut
                    f.flush()
                    subprocess.run(command, shell = True)
                    outDir = tmpOut
            else:
                # More than one file + folder was created: move them all
                a, b = os.path.splitext(os.path.basename(inFile))
                tmpOut = os.path.join(tmpBack, a)
                prepOutDir(tmpOut)
                for files in newList:
                    if os.path.isdir(files):
                        command = "mv -r " + os.path.join(tmpBack, files) + " " + tmpOut
                    else:
                        command = "mv " + os.path.join(tmpBack, files) + " " + tmpOut
                    f.flush()
                    subprocess.run(command, shell = True)
                outDir = tmpOut
        return outDir


# This function searches for a zip or a tar file
def getArchiveFile(inputArg):
    global exitCode
    
    # If input is a directory, search recursively; else return as such
    if os.path.isdir(inputArg):
        zip_files = glob.glob(os.path.join(inputArg, "**", "*.zip"),  recursive=True)
        tar_files = glob.glob(os.path.join(inputArg, "**", "*.tar*"), recursive=True) + \
                    glob.glob(os.path.join(inputArg, "**", "*.tgz*"), recursive=True)
    
        # Ideally only one of these should exist
        fileList = []
        fileList.extend(zip_files)
        fileList.extend(tar_files)
    
        if len(fileList) > 1:
            warn_msgs.append("More than one archive found in the folder; aborting")
            exitCode = -31
            return []
        else:
            return fileList
    else:
        if ".zip" in inputArg or ".tar" in inputArg or ".tgz" in inputArg:
            fList = inputArg
            return fList
        else:
            return []

# Function to prepare output directory
def prepOutDir(outDir, delete=True):
    # Make output directory if it does not exist
    if not(os.path.exists(outDir)):
        os.mkdir(outDir)
    else:
        if delete:
            shutil.rmtree(outDir, ignore_errors=True)
            os.mkdir(outDir)

            
# Function that parses a directory, makes a list of files, decides modality, 
# and generates the spec2nii command to be run
def makeCommand(inDir, outDir):
    global warn_msgs, fname_dl, fname_data, fname_list, fname_SPAR, fname_SDAT, pathSpec2nii, exitCode
    
    # Relevant extensions are: .dat, .ima, .SPAR, .data, .dcm, and .7
    list_dat  = glob.glob(os.path.join(inDir, "*.dat"))
    list_ima  = glob.glob(os.path.join(inDir, "*.ima"))
    list_SPAR = glob.glob(os.path.join(inDir, "*.[sS][pP][aA][rR]"))
    list_data = glob.glob(os.path.join(inDir, "*.data"))
    list_dcm  = glob.glob(os.path.join(inDir, "*.dcm"))
    list_7    = glob.glob(os.path.join(inDir, "*.7"))
    list_raw  = glob.glob(os.path.join(inDir, "*.raw"))
    
    # Check completeness of some files
    toDelete = []
    if list_SPAR:
        for idx, files in enumerate(list_SPAR):
            if not(check_Philips_SPAR(inDir, (files.replace(".spar", "")).replace(".SPAR", ""))):
                toDelete.append(idx)
    list_SPAR = list(set(list_SPAR) - set(toDelete))
    
    toDelete = []
    if list_data:
        for idx, files in enumerate(list_data):
            if not(check_Philips_data(inDir, (files.replace(".data", "")))):
                toDelete.append(idx)
                if exitCode != 0:
                    return ""
    list_data = list(set(list_data) - set(toDelete))
    
    temp = len(list_dat) + len(list_ima) + len(list_SPAR) + len(list_data) + len(list_dcm) + len(list_7)
    command = ""
    if temp == 0:
        # Is this a scenario with .raw file present but no .list/.data?
        if list_raw:
            warn_msgs.append("No .data/.list file present but .raw file present")
            exitCode = -20
            return command
        else:
            # No relevant MRS data found
            warn_msgs.append("No relevant MRS data type found")
            exitCode = -11
            return command
    else:
        # Check if more than one imaging format is found as opposed to more 
        # than one file of the same format; for example, are there .7 and .twix 
        # files present at the same time? Shouldn't be allowed. If, on the other 
        # hand, say multiple .7 files are present, this is allowed
        tmp_listAll = [list_dat, list_ima, list_SPAR, list_data, list_dcm, list_7]
        listCount   = 0
        idx = []
        for ii, item in enumerate(tmp_listAll):
            if item != []:
                listCount = listCount + 1 
        if listCount > 1:
            # Not handled
            warn_msgs.append("Multiple MRS file formats found; not currently handled")
            exitCode = -12
        else:
            # Currently, not able to handle multiple Philips data format
            if len(list_data) > 1:
                warn_msgs.append("Multiple Philips data files found; not currently handled")
                exitCode = -21
            else:
                # Make commands
                command = []
                if list_dat:
                    for img in list_dat:
                        command.append("twix -e image " + img) 
                    exitCode = 0
                else:
                    if list_ima:
                        for img in list_ima:
                            command.append("dicom " + img)
                        exitCode = 0
                    else:
                        if list_SPAR:
                            for ii, img in enumerate(list_SPAR):
                                command.append("philips " + fname_SDAT[ii] + " " + img)
                            exitCode = 0
                        else:
                            if len(list_data) == 1:
                                command.append("philips_dl " + fname_data + " " + fname_list + " " + fname_dl)
                                exitCode = 0
                            else:
                                if list_dcm:
                                    for img in list_dcm:
                                        command.append("philips_dcm " + img)
                                    exitCode = 0
                                else:
                                    if list_7:
                                        for img in list_7:
                                            command.append("ge " + img)
                                        exitCode = 0
    return command


# Function to prefix spec2nii execution to command
def prefix_spec2nii(command):
    global pathSpec2nii
    command = os.path.join(pathSpec2nii, "spec2nii") + " " + command
    return command


# Function to suffix output directory to spec2nii command
def suffix_spec2nii(command, outDir):
    command = command + " -o " + outDir
    return command


# Check completeness of Philips SPAR/SDAT files
def check_Philips_SPAR(inDir, baseName):
    global warn_msgs, fname_SPAR, fname_SDAT, exitCode
    exist_SDAT = os.path.exists(os.path.join(inDir, baseName + ".SDAT"))
        
    if not(exist_SDAT):
        warn_msgs.append(baseName + ": SDAT file missing")
        output = False
        exitCode = -13
        
    return output

# Check completeness of Philips data/list files
def check_Philips_data(inDir, baseName):
    global warn_msgs, fname_data, fname_list, exitCode
    output = True
    
    fname_data = baseName + ".data"
    fname_list = baseName + ".list"
    
    # Make sure list and data files have the same basenames
    exist_list = os.path.exists(fname_list)
    exist_data = os.path.exists(fname_data)
    
    # # Check if this is a case that both list and data files are missing
    # if not(exist_list) and not(exist_data):
    #     if ".raw" in baseName:
    #         warn_msgs.append(baseName + ": both data and list files are missing; raw file found")
    #         exitCode = -20
    #         output = False
    #         return output
    
    if not(exist_list):
        warn_msgs.append(baseName + ": list file missing")
        output = False
        exitCode = -14
        return output
        
    if not(exist_data):
        warn_msgs.append(baseName + ": data file missing")
        output = False
        exitCode = -15
        return output
    
    # Now, look for .sin and .json files; additionally look for .dcm folder
    list_sin  = glob.glob(os.path.join(inDir, "*.sin"))
    list_json = glob.glob(os.path.join(inDir, "*.json"))
    
    # Remove any coil survey, B0, or SENSE files
    coilFiles_sin  = []
    coilFiles_json = []
    # coilFiles_txt  = []
    for files in range(0, len(list_sin)):
        if "CoilSurvey" in list_sin[files] or "B0" in list_sin[files] or "Sense" in list_sin[files]:
            coilFiles_sin.append(list_sin[files])

    for files in range(0, len(list_json)):            
        if "CoilSurvey" in list_json[files] or "B0" in list_json[files] or "Sense" in list_json[files]:
            coilFiles_json.append(list_json[files])
        
    list_sin  = list(set(list_sin)  - set(coilFiles_sin))
    list_json = list(set(list_json) - set(coilFiles_json))
    
    # At least one sin, json, and txt file should exist
    if not(list_sin):
        warn_msgs.append("No sin file found")
        exitCode = -16
        output = False
        return output
    
    if not(list_json):
        warn_msgs.append("No json file found")
        output = False
        exitCode = -17
        return output
    
    # For every sin file, check if lab and raw files exist
    for files in list_sin:
        if not(os.path.exists(files.replace(".sin", ".lab"))):
            warn_msgs.append(files.replace(".sin", ".lab") + " does not exist")
        if not(os.path.exists(files.replace(".sin", ".raw"))):
            warn_msgs.append(files.replace(".sin", ".raw") + " does not exist")

    # For every JSON file, check if txt file exists
    list_txt = []
    for files in list_json:
        if not(os.path.exists(files.replace(".json", ".txt"))):
            warn_msgs.append(files.replace(".json", ".txt") + " does not exist")
            output = False
            exitCode = -18
            return output
        else:
            list_txt.append(files.replace(".json", ".txt"))
        
    # Ensure that the dcm folder exists as well
    dcmFolder = os.path.join(inDir, "dcm")
    if not(os.path.exists(dcmFolder)):
        warn_msgs.append("dcm folder not found")
        output = False
        exitCode = -19
    else:
        # Check that there are files inside the dcm folder
        list_dcm = os.listdir(dcmFolder)
        hiddenFiles = []
        for f in range(0, len(list_dcm)):
            if list_dcm[f].startswith("."):
                hiddenFiles.append(list_dcm[f])

        list_dcm = list(set(list_dcm) - set(hiddenFiles))
        if not(list_dcm):
            warn_msgs.append("dcm folder is empty")
            output = False
            exitCode = -19
        
    # Do we have more than one file? Not sure how to proceed, if so
    if len(list_txt) != 1 or len(list_json) != 1 or len(list_sin) != 1:
            warnings.warn("Incorrect number of files exist")
            output = False
            exitCode = -22
            return output
        
    # Now generate dcm file name: updates global variable    
    handle_philips_dl(inDir, list_sin[0], list_txt[0], dcmFolder)
    
    return output
    

# Define a function that handles the philips_dl case
# Adapted from: https://github.com/DCAN-Labs/hbcd_mrs_to_nii_conversion/blob/main/spec2nii_HBCD_batch.sh
# Requires dcmtk to be installed
# Philips data/list/dcmtxtdump following Sandeeps description
def handle_philips_dl(inDir, sinFile, txtFile, dcmFolder):
    # We need the list file, data file, and the DICOM file containing MRS info
    # The data and list files already exist; the DICOM file needs to be generated
    # A text file containing DICOM info should exist but may not have the same file name as fname
    # We also need the JSON file - should be the same file name as the text file
    # There should also be a sin file - but should ignore the coil survey scan
    # Finally, looks like we also need the dcm folder
    global fname_dl
    
    # Center positions in the sin file
    centerSin = returnCenter_sin(sinFile)
    
    # Center positions in the text file
    centerTxt = returnCenter_txt(txtFile)
    
    # Do the centers match?
    # if all(numpy.isclose(centerSin, centerTxt)):
    if all(centerSin.round(2) - centerTxt.round(2) == 0):
        fname_dl = dumpInfo_txt(txtFile)
        if os.path.exists(fname_dl):
            return(fname_dl)
    else:
        # Make a list of all dcm files
        dcmFileList = os.listdir(dcmFolder)
        
        # Sin file order is ap rl fh and dcm is ap fh rl switching it (hopefully works all the time)
        for dcmFile in dcmFileList:
            centerTxt, newName = returnCenter_dcm(dcmFolder, dcmFile)
            if all(centerSin.round(2) - centerTxt.round(2) == 0):    
                fname_dl = dumpInfo_txt(newName)
                if os.path.exists(fname_dl):
                    break

def returnCenter_sin(sinFile):
    # with open(os.path.join(inDir, sinFile)) as f:
    with open(sinFile) as f:
        for lineNumber, lineContent in enumerate(f):
            found = re.search("loc_ap_rl_fh_offcentres", lineContent)
            if found:
                temp = lineContent.split(": ")
                temp = (((temp[len(temp)-1]).replace("\n", "")).replace("  ", "")).split(" ")
                centerSin = (numpy.array(temp)).astype(float)
                break
    return centerSin

def returnCenter_txt(txtFile):
    # Using the solution from https://stackoverflow.com/a/4703409 instead of the previous "\d+"
    # with open(os.path.join(inDir, txtFile)) as f:
    with open(txtFile) as f:
        for lineNumber, lineContent in enumerate(f):
            found = re.search("(2005,105a)", lineContent)
            if found:
                temp = re.findall(r"[-+]?(?:\d*\.*\d+)", lineContent.replace("(2005,105a)", ""))
                centerTxt_ap = (numpy.array(temp[0])).astype(float)
                break
            
    # with open(os.path.join(inDir, txtFile)) as f:
    with open(txtFile) as f:
        for lineNumber, lineContent in enumerate(f):
            found = re.search("(2005,105b)", lineContent)
            if found:
                temp = re.findall(r"[-+]?(?:\d*\.*\d+)", lineContent.replace("(2005,105b)", ""))
                centerTxt_fh = (numpy.array(temp[0])).astype(float)
                break

    # with open(os.path.join(inDir, txtFile)) as f:
    with open(txtFile) as f:
        for lineNumber, lineContent in enumerate(f):
            found = re.search("(2005,105c)", lineContent)
            if found:
                temp = re.findall(r"[-+]?(?:\d*\.*\d+)", lineContent.replace("(2005,105c)", ""))
                centerTxt_rl = (numpy.array(temp[0])).astype(float)
                break
    
    # Put centers together
    centerTxt = numpy.array([centerTxt_ap, centerTxt_fh, centerTxt_rl])
    return centerTxt

def returnCenter_dcm(dcmFolder, dcmFile):
    global dir_dcmtk
    
    # Temporary output file name
    outFile = os.path.join(dcmFolder, dcmFile + "_dump.txt")

    # Dump DICOM header
    command = os.path.join(dir_dcmtk, "dcmdump") + " " + os.path.join(dcmFolder, dcmFile) + " > " + outFile
    subprocess.run(command, shell = True)
    
    # Get center
    # with open(os.path.join(dcmFolder, outFile)) as f:
    with open(outFile) as f:
        for lineNumber, lineContent in enumerate(f):
            found = re.search("(2005,105a)", lineContent)
            if found:
                temp = re.findall(r"[-+]?(?:\d*\.*\d+)", lineContent.replace("(2005,105a) ", ""))
                centerTxt_ap = (numpy.array(temp[0])).astype(float)
                break
            
    # with open(os.path.join(dcmFolder, outFile)) as f:
    with open(outFile) as f:
        for lineNumber, lineContent in enumerate(f):
            found = re.search("(2005,105b)", lineContent)
            if found:
                temp = re.findall(r"[-+]?(?:\d*\.*\d+)", lineContent.replace("(2005,105b) ", ""))
                centerTxt_fh = (numpy.array(temp[0])).astype(float)
                break

    # with open(os.path.join(dcmFolder, outFile)) as f:
    with open(outFile) as f:
        for lineNumber, lineContent in enumerate(f):
            found = re.search("(2005,105c)", lineContent)
            if found:
                temp = re.findall(r"[-+]?(?:\d*\.*\d+)", lineContent.replace("(2005,105c) ", ""))
                centerTxt_rl = (numpy.array(temp[0])).astype(float)
                break
    
    # Put centers together
    centerTxt = numpy.array([centerTxt_ap, centerTxt_rl, centerTxt_fh])
    return centerTxt, outFile

def dumpInfo_txt(txtFile):
    global dir_dcmtk
    # with open(os.path.join(inDir, txtFile), "a") as file:
    with open(txtFile, "a") as file:
        file.write("\n(0008,0080) LO [HBCD site]                              #  30, 1 InstitutionName\n")
        file.write("(0018,1030) LO [WIP HYPER]                              #  10, 1 ProtocolName")

    # Dump it as a dcm file
    inFile  = txtFile # os.path.join(inDir, txtFile)
    outFile = txtFile.replace(".txt", ".dcm") # os.path.join(inDir, txtFile.replace(".txt", ".dcm"))
    command = os.path.join(dir_dcmtk, "dump2dcm") + " " + inFile + " " + outFile
    subprocess.run(command, shell = True)

    # Were we successful?
    if os.path.exists(outFile):
        return outFile
    else:
        warn_msgs.append("Could not create dcm file")
        return ""
    
def checkNIfTI(workDir):
    # This module checks if the generated NIfTI images are valid
    # https://medium.com/@ashkanpakzad/writing-a-nifti-nii-reader-in-python-3865895d4c06
    # https://github.com/wtclarke/nifti_mrs_python_example/tree/86a305f28a45f0d07aab29f52daf3a5d880438d8
    # Will assume that if we can read the header, a valid NIfTI has been generated
    global exitCode, logName
    
    listFiles = glob.glob(os.path.join(workDir, "*.nii*"))
    if not(listFiles):
        exitCode = -41
        return
    else:
        validHeader = [False] * len(listFiles)
        
        with open(logName, "a") as f:
            for idx, files in enumerate(listFiles):
                try:
                    img = nibabel.load(files)
                    print("Image Header: \n", file = f)
                    print(img.header, file = f)
                    validHeader[idx] = True
                except:
                    validHeader[idx] = False
                    warn_msgs.append(files, ": invalid header")
                    exitCode = -42


def checkNIfTInames(workDir):
    # This module checks if the following three sets of NIfTI files are generated:
    # "*edited*:    corresponds to the main scan
    # "*short*te*": corresponds to the short TE scan
    # "*ref*":      corresponds to reference scan(s)
    # The above is quite simplistic and will fail most of the time; using logic from:
    # https://github.com/DCAN-Labs/hbcd_mrs_to_nii_conversion/blob/main/spec2nii_HBCD_batch.sh#L499
    # Do we need to additionally make sure we are not double counting the same file?
    # Don't think this is particularly robust and clearly misses out on cases (use protocol name?)
    
    global warn_msgs, exitCode
        
    # Configuring case-insensitive patterns
    pat_hyper   = "*[hH][yY][pP][eE][rR]*"
    pat_isthmus = "*[iI][sS][tT][hH][mM][uU][sS]*"
    pat_edited  = "*[eE][dD][iI][tT][eE][dD]*"
    pat_ref     = "*[rR][eE][fF]*"
    pat_shortTE = "*[sS][hH][oO][rR][tT]*[tT][eE]"
    pat_nii     = ".nii*"
    
    # Now look for "ref" scans:
    list_ref = list(set(glob.glob(os.path.join(workDir, pat_hyper + pat_ref + pat_nii))   + \
                        glob.glob(os.path.join(workDir, pat_isthmus + pat_ref + pat_nii)) + \
                        glob.glob(os.path.join(workDir, pat_edited + pat_ref + pat_nii))))
    
    # Now look for "TE" scans:
    list_TE  = list(set(glob.glob(os.path.join(workDir, pat_hyper + pat_shortTE + pat_nii))   + \
                        glob.glob(os.path.join(workDir, pat_isthmus + pat_shortTE + pat_nii)) + \
                        glob.glob(os.path.join(workDir, pat_edited + pat_shortTE + pat_nii))))
               
    # Now look for "main" scans:
    list_main = list(set(glob.glob(os.path.join(workDir, pat_hyper + pat_nii))   + \
                         glob.glob(os.path.join(workDir, pat_isthmus + pat_nii)) + \
                         glob.glob(os.path.join(workDir, pat_edited + pat_nii))))
    
    if list_main:
        hasMainFile = True
    else:
        hasMainFile = False
        
    if list_TE:
        hasTEFile = True
    else:
        hasTEFile = False
        
    if list_ref:
        hasRefFile = True
    else:
        hasRefFile = False
        
    tmp_sum = hasMainFile + hasTEFile + hasRefFile
    if tmp_sum == 3:
        return
    else:
        if tmp_sum == 2:
            if not(hasMainFile):
                exitCode = -43
            else:
                if not(hasTEFile):
                    exitCode = -44
                else:
                    exitCode = -45
        else:
            exitCode = -46

    
# Main module
def main(inputArg, outDir):
    
    global warn_msgs, dir_Logs, logName, exitCode
    
    # Always start with zero exit status
    exitCode = 0
    
    # Keep a copy of input argument to generate final exit message
    backup_inputArg = os.path.basename(inputArg)
    
    # Generate a name for the log file
    logName = os.path.join(dir_Logs, "Log-" 
                           + os.path.basename(inputArg) 
                           + "-" + str(datetime.datetime.now().strftime("%Y%m%d%H%M%S"))
                           + ".txt")
    
    prepOutDir(dir_Logs, False)
    
    # Start logging
    with open(logName, "w") as f:
        txt = "Log started: " + str(datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S") + "\n")
        print(txt, file = f)
        print("=" * (len(txt)-2), file = f)
        
        # Make a note of input parameters
        print("Input string: " + inputArg +  "\n", file = f)
        print("Output directory: " + outDir, "\n", file = f)
    
        # First pass, try to make command
        command = makeCommand(inputArg, outDir)
        
        # If the exitCode was -11, reset it to zero
        if exitCode == -11:
            exitCode = 0
        
        # If failed to make command, check for archives
        if not(command):
            returned_inutArg = archiveModule(inputArg, outDir)
            
            if exitCode == 0:
                # Updated output directory
                outDir = returned_inutArg + "_output"
                prepOutDir(outDir)
            
                # If returned_inputArg is empty, failed to generate command and failed to find archive; stop
                if returned_inutArg:
                    # Attempt to make the command again
                    command = makeCommand(returned_inutArg, outDir)
                    
                    if command:
                        for cmd in command:
                            command_toRun = prefix_spec2nii(cmd)
                            command_toRun = suffix_spec2nii(command_toRun, outDir)
                            print("Command generated: \n", file = f)
                            print(command_toRun, file = f)
                            f.flush()                    
                            cmd_res = subprocess.run(command_toRun, shell=True, capture_output = True)
                            
                            print("Stdout: \n",   file = f)
                            print(cmd_res.stdout, file = f)
                            print("\nStderr: \n", file = f)
                            print(cmd_res.stderr, file = f)
                            
                        # Now check if the NIfTI files are valid
                        checkNIfTI(outDir)
                        
                        # Now check if the NIfTI file set is complete
                        if exitCode == 0:
                            checkNIfTInames(outDir)
                else:
                    if exitCode == 0:
                        # No MRS data found
                        warn_msgs.append("No relevant MRS data found")
                        exitCode = -11
        else:
            for cmd in command:
                command_toRun = prefix_spec2nii(cmd)
                command_toRun = suffix_spec2nii(command_toRun, outDir)
                print("Command generated: \n", file = f)
                print(command_toRun, file = f)
                f.flush()                    
                cmd_res = subprocess.run(command_toRun, shell=True, capture_output = True)
                
                print("Stdout: \n",   file = f)
                print(cmd_res.stdout, file = f)
                print("\nStderr: \n", file = f)
                print(cmd_res.stderr, file = f)
                
            # Now check if the NIfTI files are valid
            checkNIfTI(outDir)
            
            if exitCode == 0:
                # Now check if the NIfTI file set is complete
                checkNIfTInames(outDir)
                    
    if exitCode == 0:
        print(backup_inputArg + ": Exited normally")
        return backup_inputArg, exitCode
    else:
        print(backup_inputArg + ": Exited with code: ", + exitCode)
        return backup_inputArg, exitCode

    
# =============================================================================
# # This part is for testing; comment this out and invoke the main function 
# from command line by passing in the input directory/file that needs to be 
# parsed and full path to a temporary output directory
# =============================================================================
dirSource   = "/data/site/mrs/"
dirOutput   = "/home/processing/output_spec2nii"

# clean dirOutput otherwise program will hung on unzip
# shutil.rmtree(dirOutput, ignore_errors=True)
# os.makedirs(dirOutput)


# only work for zip file in Siemens.
listContent = [f for f in os.listdir(dirSource)
         if os.path.isfile(os.path.join(dirSource, f)) and "CH" in f and ".zip" in f]


allData = []
individual = {}


for ff in listContent:
    basename, errCode = main(os.path.join(dirSource, ff), os.path.join(dirOutput, os.path.basename(ff)))
    individual = { "hbcd_id": basename.replace("_MRS.zip", "").replace("_mrs.zip", ""), "code": errCode}
    print(basename.replace("_MRS.zip", "").replace("_mrs.zip", ""), errCode)
    allData.append(individual)

if not allData:
    individual = { "hbcd_id":"" , "code":0}
    allData.append(individual)     

result = pd.DataFrame(allData)
result = result[result["hbcd_id"].str.strip() != ""]
print (result)
result.to_csv("/data/site/mrs/umn/mrs_status.csv", index=False)