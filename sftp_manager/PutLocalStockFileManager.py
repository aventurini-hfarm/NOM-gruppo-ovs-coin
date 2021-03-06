__author__ = 'vincenzosambucaro'
import os
import paramiko
import shutil
import datetime
from MuleConfig import host, port, username, password

# returns a list of names (with extension, without full path) of all files
def getLocalFiles(directory):
    # in folder path
    files = []
    for name in os.listdir(directory):
        if os.path.isfile(os.path.join(directory, name)):
            files.append(name)

    return files

def transferToRemoteServer(remotePath, files, localDirectory):
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        ssh.connect(host, port, username, password)

        sftp = ssh.open_sftp()
        for f in files:
            sftp.put(localDirectory+"/"+f, remotePath+"/"+f)
            print ("Trasferito: ",f)

    except paramiko.SSHException:
        print ("Connection Failed")
        quit()

def transferToRemoteServerBackup(remotePath, files, localDirectory):
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        ssh.connect(host, port, username, password)

        sftp = ssh.open_sftp()
        for f in files:
            data_esecuzione= datetime.datetime.now().strftime("%Y%m%d%H%M%S")
            sftp.put(localDirectory+"/"+f, remotePath+"/"+data_esecuzione+"-"+f+".backup")
            print ("Trasferito: ",f)

    except paramiko.SSHException:
        print ("Connection Failed")
        quit()

def move_files(localDirectory, files, archiveDirectory):
    for f in files:
        shutil.move(localDirectory+"/"+f,archiveDirectory+'/'+f)

    return



local_dir = '/home/OrderManagement/testFiles/stock_export/outbound'
remote_path = '/bus/mailboxes/nom/in'
remote_path_archive = '/bus/mailboxes/nom/archive/in'
archive_dir = '/home/OrderManagement/testFiles/stock_export/archive'
lista_files = getLocalFiles(local_dir)


transferToRemoteServer(remote_path,lista_files,local_dir)
#transferToRemoteServerBackup(remote_path_archive,lista_files,local_dir)
move_files(local_dir, lista_files, archive_dir)


