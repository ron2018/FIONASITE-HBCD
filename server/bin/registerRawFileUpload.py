#!/usr/bin/env python3
"""

Register the uploaded rawdata to UMN Loris for HBCD project


"""
import requests
import json
import argparse

def parse_arguments():
    parser = argparse.ArgumentParser(
        description="Query LORIS API to get the right participant's infomration")
    parser.add_argument('--token', help="Token to UMN API", required=True)
    parser.add_argument('--filename', help="Raw File name stem", required=True)
    return parser.parse_args()


args = parse_arguments()

headers = {
    'Content-Type': 'application/json',
    'X-API-KEY': args.token
}

data = { "filename": args.filename+'.tar.gz' }

print(args.filename)

response = requests.post('https://integration.hbcd.ahc.umn.edu/api/v1/fiona/register/'+args.filename+'.tar.gz', headers=headers)
print('Called UMN API : https://integration.hbcd.ahc.umn.edu/v1/api/fiona/sftp/'+args.filename+'.tar.gz')
print(response.json())

matched_list=[]

if (response.status_code == 404 ):
    errors = response.json()
    print(errors)

    matched_list.append(errors)


if (response.status_code == 200):
# get the study_id
   item = response.json()
   print(item)
    
