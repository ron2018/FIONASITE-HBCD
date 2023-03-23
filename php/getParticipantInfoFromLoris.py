#!/usr/bin/env python3
"""

Get the data from Loris API for HBCD project


"""
import requests
import json
import argparse

def parse_arguments():
    parser = argparse.ArgumentParser(
        description="Query LORIS API to get the right participant's infomration")
    parser.add_argument('--id-triplet', help="id_triplet consists of TIOKH0015_273095_V02", required=True)
    parser.add_argument('--token', help="Token to UMN API", required=True)
    parser.add_argument('--fiona-id', help="Fiona site ID", required=True)
    parser.add_argument('--scann-date', help="scan date", required=True)
    return parser.parse_args()


args = parse_arguments()

headers = {
    'Content-Type': 'application/json',
    'X-API-KEY': args.token
}

print(args.id_triplet,args.fiona_id,args.scann_date)

data = {"id_triplet": args.id_triplet,
"fiona_id": args.fiona_id,
"scan_date": args.scann_date
}


response = requests.post('https://integration.hbcd.ahc.umn.edu/api/v1/fiona', headers=headers, data=json.dumps(data))
print(response)
print(response.json())

#response = {"sites":{"pscid":"TIUCS0016","candid":"977877","visit_label":"V02","gender":"M", "age":"003M","site":"UCS","scheduled_date":"20220916"}}
matched_list=[]

if (response.status_code == 500 ):
    errors = response.json()
    print(errors)
    if "error" in errors:
        errors["error"] ="Please Contact LORIS support: " +errors["error"]
    else:
        errors["errors"] ="Please Contact LORIS support: " + ' '.join(str(item) for item in errors["errors"])
    matched_list.append(errors)
if (response.status_code == 404 ):
    errors = response.json()
    print(errors)
    if "error" in errors:
        errors["error"] ="Please Contact LORIS support: " +errors["error"]
    else:
        errors["errors"] ="Please Contact LORIS support: " + ' '.join(str(item) for item in errors["errors"])

    matched_list.append(errors)


if (response.status_code == 200):
# get the study_id
   item = response.json()

#for item in response.json():
   print(item)
        
   print(item['pscId'], item['gender'], item['age'], item['visit'], item['anonymizedDOB'], item["dccId"]) 
    # print(scan_date, item["scheduled_date"])      
    #if scan_date ==  item["scheduled_date"]:
    #    print("scan date matched")
    #    dict = item
    #    matched_list.append(dict)
    #else: 
    #    str = f'No match on scan date: {scan_date}'
    #    dict = {"error": str}
    #    matched_list.append(dict)
#else:
#    str = f'No match on scan site: {scan_site} '
#    print(str)
    #dict = {"error": str}
   matched_list.append(item)
    
    
print(json.dumps(matched_list)) 
