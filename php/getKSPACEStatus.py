#!/usr/bin/env python3
"""

Get the data from Loris API for HBCD project


"""
import os
import json
import argparse
import numpy as np
import pandas as pd

# read the distinct HBCDID from /data/DAIC folder
# read the folder name on /data/site/kspace
# Compare the data and out put into json files.


def get_unique_prefix_identifiers(folder_path, n):
    """
    Extracts unique identifiers from the first 'n' characters of filenames
    in a given folder.

    Args:
        folder_path (str): The path to the folder to scan.
        n (int): The number of characters from the beginning of the filename
                 to consider as the identifier.

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
            if filename.startswith('CH'):
                unique_identifiers.add(identifiers[0] + "_" + identifiers[1] + "_" + identifiers[2] + "_" + identifiers[4])
    return sorted(list(unique_identifiers)) # Return as sorted list for consistency


dicomlist = get_unique_prefix_identifiers("/data/DAIC", 20)

#print(len(dicomlist))

sendList = pd.read_csv("/data/site/kspace/processed/kspacesent.csv" )
#print(sendList["KSPACESENT"].tolist())


all_list = []
all_list =  [{"DICOM without KSPACE data":item} for item in dicomlist if not item.split("_")[3] in sendList["KSPACESENT"].tolist()]
#print(len(all_list))

#print(all_list)

def get_ch_GE_from_directory(directory_path):
    if not os.path.isdir(directory_path):
        print(f"Error: Directory '{directory_path}' not found.")
        return []

    ch_folders = []
    for entry_name in os.listdir(directory_path):
        full_path = os.path.join(directory_path, entry_name)
        # Check if it's a directory AND if its name starts with 'CH' and end with .tgz
        if os.path.isfile(full_path) and (entry_name.startswith('CH') or entry_name.endswith('tgz')):
            ch_folders.append(entry_name)

    return sorted(ch_folders) # Return a sorted list for consistent output

unprocessedKspace = get_ch_GE_from_directory("/data/site/kspace")

print(unprocessedKspace)

#processedList = get_ch_folders_from_directory("/data/site/kspace/processed")

#print(processedList)

[all_list.append({"Problematic KSPACE data":item}) for item in unprocessedKspace]         

#   matched_list.append(item)
print(json.dumps(all_list)) 
