"""

Modify DICOMS with data from LORIS for HBCD project
If there is a mismatch on the tripleID

"""
import subprocess
import json
import argparse
import shlex
import os,sys
import json

def parse_arguments():
    parser = argparse.ArgumentParser(
        description="Query LORIS API to get the right participant's infomration")
    parser.add_argument('--tripleId', help="should be triplet consists of TIOKH0015_273095_V02", required=True)
    parser.add_argument('--run', help="should be Session name", required=True)
    parser.add_argument('--sex', help="Sex data from Loris", required=True)
    parser.add_argument('--age', help="Age data from Loris", required=True)
    parser.add_argument('--dob', help="de-idenfied DOB  data from Loris", required=True)
    parser.add_argument('--filename', help="filename in quarention folder", required=True)
    return parser.parse_args()


args = parse_arguments()
#print(args)

filename = shlex.quote(args.filename)
tripleId = shlex.quote(args.tripleId)
sex = shlex.quote(args.sex)
age = shlex.quote(args.age)
dob = shlex.quote(args.dob)
run = shlex.quote(args.run)

#print(filename, tripleId,sex,age)

UIDs = filename.split("_")
SUID = UIDs[0]
SeUID = UIDs[1].replace(".tgz","")
#print("SUID : ", SUID, " SeUID : ", SeUID)
dob = dob.replace('-', '')

#get fiona ID

# try to read the config file from this machine
configFilename = '/data/config/config.json'
settings = {}
with open(configFilename,'r') as f:
    settings = json.load(f)

FIONAID = settings["FIONAID"]
print("Fiona ID: ", FIONAID)

try:
    proc = subprocess.run(["/bin/cp", "/data/quarantine/"+filename+".tgz",  "/data/site/temp/"], shell=False)
    if proc.returncode < 0:
        print("Child was terminated by signal", -proc, file=sys.stderr)
    else:
        print("Child returned", proc, file=sys.stderr)
except OSError as e:
    print("Copy file /data/quarantine/"+filename+".tgz failed:", e, file=sys.stderr)

try:
    proc = subprocess.run(["/bin/tar","-xvf", "/data/site/temp/"+filename+".tgz", "-C", "/data/site/temp/"], shell=False)
    if proc.returncode < 0:
        print("Child was terminated by signal", -proc, file=sys.stderr)
    else:
        print("Child returned", proc, file=sys.stderr)
except OSError as e:
    print("Untar file /data/site/temp/"+filename + ".tgz failed:", e, file=sys.stderr)


if tripleId:
    
    try:
        command2 = 'dcmodify -i "(0010,0010)='+ tripleId + '" -nb ' + "/data/site/temp/" + SUID + "/" + SeUID + '/*'
        os.system(command2)
        command3 = 'dcmodify -i "(0010,0020)='+ tripleId + '" -nb ' + "/data/site/temp/" + SUID + "/" + SeUID + '/*'
        os.system(command3)
        print ("De-identify Patient Name for this folder: %s", tripleId)
        command3 = 'dcmodify -i "(0012,0030)='+ FIONAID + '" -nb ' + "/data/site/temp/" + SUID + "/" + SeUID + '/*'
        os.system(command3)
        print ("De-identify Patient Name for this folder: %s", tripleId)

    except OSError as e:
        print("dcmodify PatientID or Name for /data/site/temp/"+filename + " failed:", e, file=sys.stderr)

    #proc = subprocess.run(["/usr/local/bin/dcmodify","-i '(0010,0010)="+ tripleId + "' -nb", "/data/site/temp/" + SUID + "/" + SeUID + "/*"], shell=True)
    #print(proc)
    #proc = subprocess.run(["/usr/local/bin/dcmodify","-i '(0010,0020)="+ tripleId + "' -nb", "/data/site/temp/" + SUID +  "/" + SeUID + "/*"], shell=True)
    #print(proc)
    #print("Anonymize Patient Name for this folder: %s", tripleId)
if age:
    try: 

        command4 = 'dcmodify -i "(0010,1010)='+ age + '" -nb ' + "/data/site/temp/" + SUID + "/" + SeUID + '/*'
        os.system(command4)
    except OSError as e:
        print("dcmodify PatientAge for /data/site/temp/"+filename + " failed:", e, file=sys.stderr)


    #proc = subprocess.run(["/usr/local/bin/dcmodify","-i","'(0010,1010)="+ age +"'", "-nb","/data/site/temp/" + SUID +  "/" + SeUID + "/*"], shell=True)
    #print("Anonymize Patient Age for this folder: %s", age)
if sex:
    try:

        command5 = 'dcmodify -i "(0010,0040)='+ sex + '" -nb ' + "/data/site/temp/" + SUID + "/" + SeUID + '/*'
        os.system(command5)
    except OSError as e:
        print("dcmodify PatientSex for /data/site/temp/"+filename + " failed:", e, file=sys.stderr)
    #proc = subprocess.run(["/usr/local/bin/dcmodify","-i","'(0010,0040)="+ sex +"'" , "-nb", "/data/site/temp/" + SUID +  "/" + SeUID + "/*"], shell=False)
if dob:
    try:

        command6 = 'dcmodify -i "(0010,0030)='+ dob + '" -nb ' + "/data/site/temp/" + SUID + "/" + SeUID + '/*'
        os.system(command6)
    except OSError as e:
        print("dcmodify DOB for /data/site/temp/"+filename + " failed:", e, file=sys.stderr)


# Change the json also.
try:

    with open("/data/site/temp/" + SUID + "/" + SeUID + ".json", "r") as json_file:
        json_data = json.load(json_file)
        json_data["PatientName"] = tripleId
        json_data["PatientID"] = tripleId
        json_data["PatientSex"] = sex
        json_data["PatientAge"] = age

    with open("/data/site/temp/" + SUID + "/" + SeUID + ".json", "w") as outfile:
        json.dump(json_data, outfile, indent=4)
except ValueError:
    print("Modify json file for /data/site/temp/"+filename + " failed:", e, file=sys.stderr)



try:
    proc = subprocess.run(["/var/www/html/server/bin/tempFileToQuarentine.sh",SUID,SeUID,tripleId+"_"+run], shell=False)
    if proc.returncode < 0:
        print("Child was terminated by signal", -proc, file=sys.stderr)
    else:
        print("Child returned", proc, file=sys.stderr)
except OSError as e:
    print("Copy file x"/data/quarantine/"+filename failed:", e, file=sys.stderr)
