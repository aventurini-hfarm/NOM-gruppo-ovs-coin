__author__ = 'vincenzosambucaro'
import os
import paramiko
import shutil
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

        ssh.connect('130.211.59.246', username='vincenzosambucaro')
        sftp = ssh.open_sftp()
        for f in files:
            sftp.put(localDirectory+"/"+f, remotePath+"/"+f)

            print "Trasferito: ",f
    except paramiko.SSHException:
        print ("Connection Failed")
        quit()



lista_files = getLocalFiles('.')

print lista_files

transferToRemoteServer('/tmp',lista_files,'.')

#shutil.move('/tmp/prova.txt','./ciao.txt')
shutil.move('./ciao.txt', '/tmp/prova.txt')

