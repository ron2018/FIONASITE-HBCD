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
    parser.add_argument('--type', help="Raw File name type", required=True)

    return parser.parse_args()


args = parse_arguments()

headers = {
    'Content-Type': 'application/json',
    'X-API-KEY': args.token
}

data = { "filename": args.filename }

print(args.filename)
if args.type == 'MRI':
    URL='https://integration.hbcd.ahc.umn.edu/api/v1/fiona/register/mri/'
elif args.type == 'KSPACE':
    URL='https://integration.hbcd.ahc.umn.edu/api/v1/fiona/register/kspace/'
elif args.type == 'MRS':
    URL='https://integration.hbcd.ahc.umn.edu/api/v1/fiona/register/mrs/'

print(URL)
response = requests.post(URL+args.filename, headers=headers)
print('Called UMN API : {URL} and Filename: {args.filename}')
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
   matched_list.append(item)

print(matched_list)
