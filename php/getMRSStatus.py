#!/usr/bin/env python3
"""

Get the data from Loris API for HBCD project


"""
import os
import json
import argparse
import numpy as np
import pandas as pd
import datetime

# read the distinct HBCDID from /data/DAIC folder
# read the folder name on /data/site/mrs
# Compare the data and out put into json files.


def get_unique_prefix_identifiers(folder_path, n, expectmrs):
    """

    Returns:
        list: A list of unique identifiers found.
    """

    if not os.path.isdir(folder_path):
        print(f"Error: Folder '{folder_path}' not found.")
        return []

    unique_identifiers = set()

    for filename in os.listdir(folder_path):
        full_path = os.path.join(folder_path, filename)
        if os.path.isfile(full_path):
            # Ensure 'n' doesn't exceed the filename length
            identifier = filename[:n]
            identifiers = filename.split("_")

            if (filename.startswith('CH') or filename.startswith('HP')) and (identifiers[4] in expectmrs):
                #unique_identifiers.add(identifiers[0] + "_" + identifiers[1] + "_" + identifiers[2] + "_" + identifiers[4])
                unique_identifiers.add(identifiers[0] + "_" + identifiers[1] + "_" + identifiers[2] )
    return sorted(list(unique_identifiers)) # Return as sorted list for consistency



#get study without scan MRS, there is no MRS localizer
#/data/site/mrs/umn/mrssent.csv contains DICOM with localizer
expectList = pd.read_csv("/data/site/mrs/umn/mrsexpect.csv" )
print(len(expectList))
#print(expectList)
dicomlist = get_unique_prefix_identifiers("/data/DAIC", 20, expectList["MRSEXPECT"].tolist())
print(len(dicomlist))

#read /data/site/mrs/umn folder to get the sendlist

sendlist = []

folder_path = '/data/site/mrs/umn'
unique_identifiers = set()

for filename in os.listdir(folder_path):
        full_path = os.path.join(folder_path, filename)
        if os.path.isfile(full_path):
            #print(filename)
            identifiers = filename.split("_")
            #print(len(identifiers))

            if (filename.startswith('CH') or filename.startswith('HP')) and len(identifiers) >  3 and filename.endswith("gz"):
               # print(filename)
               # unique_identifiers.add(identifiers[0] + "_" + identifiers[1] + "_" + identifiers[2] + "_" + identifiers[4].replace(".tar.gz", ""))
               unique_identifiers.add(identifiers[0] + "_" + identifiers[1] + "_" + identifiers[2])

sendlist = sorted(list(unique_identifiers))
print(len(sendlist))
set1 = set(dicomlist)
set2 = set(sendlist)

difference_set = set1.difference(set2)


all_list =  [{"DICOM without MRS data":item} for item in list(difference_set)]
print(len(all_list))

#print(all_list)

def get_ch_GE_from_directory(directory_path):

    
    time_threshold = datetime.datetime.now() - datetime.timedelta(days = 3)

    if not os.path.isdir(directory_path):
        print(f"Error: Directory '{directory_path}' not found.")
        return []

    ch_folders = []
    for entry_name in os.listdir(directory_path):
        full_path = os.path.join(directory_path, entry_name)
        # Check if it's a directory AND if its name starts with 'CH' and end with .tgz
        if (os.path.isfile(full_path) and entry_name.endswith('tar.gz')):
            #filter files with 3 days newer
#            try:
                # Get the last modification time of the file
#                modification_time_epoch = os.path.getmtime(full_path)
#                modification_time = datetime.datetime.fromtimestamp(modification_time_epoch)

#                # Compare the file's modification time with the threshold
#                if modification_time < time_threshold:
#                    ch_folders.append(entry_name)
#            except FileNotFoundError:
                # The file might have been deleted between listing and getting the timestamp
#                print(f"Warning: File '{entry_name}' not found during processing.")
           ch_folders.append(entry_name)

    return sorted(ch_folders) # Return a sorted list for consistent output

unprocessedMRS = get_ch_GE_from_directory("/data/site/mrs")

print(unprocessedMRS)


[all_list.append({"Problematic MRS data":item}) for item in unprocessedMRS]         

#   matched_list.append(item)
print(json.dumps(all_list)) 
