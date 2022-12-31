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
    parser.add_argument('--sex', help="Sex data from Loris", required=True)
    parser.add_argument('--age', help="Age data from Loris", required=True)
    parser.add_argument('--filename', help="filename in quarention folder", required=True)
    return parser.parse_args()


args = parse_arguments()
print(args)

filename = shlex.quote(args.filename)
tripleId = shlex.quote(args.tripleId)
sex = shlex.quote(args.sex)
age = shlex.quote(args.age)
print(filename, tripleId,sex,age)

UIDs = filename.split("_")
SUID = UIDs[0]
SeUID = UIDs[1].replace(".tgz","")
print("SUID : ", SUID, " SeUID : ", SeUID)

try:
    proc = subprocess.run(["/bin/cp", "/data/quarantine/"+filename,  "/data/site/temp/"], shell=False)
    if proc.returncode < 0:
        print("Child was terminated by signal", -proc, file=sys.stderr)
    else:
        print("Child returned", proc, file=sys.stderr)
except OSError as e:
    print("Copy file x"/data/quarantine/"+filename failed:", e, file=sys.stderr)

try:
    proc = subprocess.run(["/bin/tar","-xvf", "/data/site/temp/"+filename, "-C", "/data/site/temp/"], shell=False)
    if proc.returncode < 0:
        print("Child was terminated by signal", -proc, file=sys.stderr)
    else:
        print("Child returned", proc, file=sys.stderr)
except OSError as e:
    print("Untar file /data/site/temp/"+filename + " failed:", e, file=sys.stderr)


if args.tripleId:
    
    try:
        command2 = 'dcmodify -i "(0010,0010)='+ tripleId + '" -nb ' + "/data/site/temp/" + SUID + "/" + SeUID + '/*'
        os.system(command2)
        command3 = 'dcmodify -i "(0010,0020)='+ tripleId + '" -nb ' + "/data/site/temp/" + SUID + "/" + SeUID + '/*'
        os.system(command3)
    except OSError as e:
        print("dcmodify PatientID or Name for /data/site/temp/"+filename + " failed:", e, file=sys.stderr)

    #proc = subprocess.run(["/usr/local/bin/dcmodify","-i '(0010,0010)="+ tripleId + "' -nb", "/data/site/temp/" + SUID + "/" + SeUID + "/*"], shell=True)
    #print(proc)
    #proc = subprocess.run(["/usr/local/bin/dcmodify","-i '(0010,0020)="+ tripleId + "' -nb", "/data/site/temp/" + SUID +  "/" + SeUID + "/*"], shell=True)
    #print(proc)
    #print("Anonymize Patient Name for this folder: %s", tripleId)
if args.age:
    try: 

        command4 = 'dcmodify -i "(0010,1010)='+ age + '" -nb ' + "/data/site/temp/" + SUID + "/" + SeUID + '/*'
        os.system(command4)
    except OSError as e:
        print("dcmodify PatientAge for /data/site/temp/"+filename + " failed:", e, file=sys.stderr)


    #proc = subprocess.run(["/usr/local/bin/dcmodify","-i","'(0010,1010)="+ age +"'", "-nb","/data/site/temp/" + SUID +  "/" + SeUID + "/*"], shell=True)
    print("Anonymize Patient Age for this folder: %s", age)
if args.sex:
    try:

        command5 = 'dcmodify -i "(0010,0040)='+ sex + '" -nb ' + "/data/site/temp/" + SUID + "/" + SeUID + '/*'
        os.system(command5)
    except OSError as e:
        print("dcmodify PatientSex for /data/site/temp/"+filename + " failed:", e, file=sys.stderr)
    #proc = subprocess.run(["/usr/local/bin/dcmodify","-i","'(0010,0040)="+ sex +"'" , "-nb", "/data/site/temp/" + SUID +  "/" + SeUID + "/*"], shell=False)
    print("Anonymize Patient Age for this folder: %s", sex)


# Change the json also.
try:

    with open("/data/site/temp/" + SUID + "/" + SeUID + ".json", "r") as json_file:
        json_data = json.load(json_file)
        json_data["PatientName"] = tripleId
        json_data["PatientID"] = tripleId
        json_data["PatientSex"] = sex
        json_data["PatientAge"] = age

    with open("/data/site/temp/" + SUID + "/" + SeUID + ".json", "w") as outfile:
        json.dump(json_data, outfile)
except ValueError:
    print("Modify json file for /data/site/temp/"+filename + " failed:", e, file=sys.stderr)


#command6 = "cd /data/site/temp/ && /bin/tar cvfz " + filename + " ./" + SUID + " && rm -rf ./" + SUID
#print(command6)
#os.system(command6)

#proc = subprocess.run(["/bin/mv", "/data/site/temp/"+filename,"/data/quarantine/"], shell=False)
#print(proc)

try:
    proc = subprocess.run(["/var/www/html/server/bin/tempFileToQuarentine.sh",SUID,SeUID], shell=False)
    if proc.returncode < 0:
        print("Child was terminated by signal", -proc, file=sys.stderr)
    else:
        print("Child returned", proc, file=sys.stderr)
except OSError as e:
    print("Copy file x"/data/quarantine/"+filename failed:", e, file=sys.stderr)
