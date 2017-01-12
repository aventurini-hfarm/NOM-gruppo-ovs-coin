__author__ = 'vincenzosambucaro'
import os
import paramiko
import shutil
import datetime
from MuleConfig import host, port, username, password

# returns a list of names (with extension, without full path) of all files
def getRemoteFiles(directory):
    # in folder path
    files = []
    for name in os.listdir(directory):
        if os.path.isfile(os.path.join(directory, name)):
            files.append(name)

    return files


def getModifiedFiles():
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        ssh.connect(host, port, username, password)

        data_esecuzione= datetime.datetime.now().strftime("%Y%m%d")
        print ('Data esecuzione: ', data_esecuzione)
        #stdin,stdout,stderr = ssh.exec_command("find /bus/mailboxes/demandwarestag/archive/in -cmin -120 |grep catalog|grep "+data_esecuzione)
        stdin,stdout,stderr = ssh.exec_command("ls /bus/mailboxes/nom/out/catalog*")
        lista_files = []
        for line in stdout.readlines():
            print (line.strip())
            lista_files.append(line.strip())

        return lista_files
    except paramiko.SSHException:
        print ("Connection Failed")
        quit()

def getFilesFromRemoteServer(lista_files):
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        ssh.connect(host, port, username, password)

        sftp = ssh.open_sftp()
        #sftp.chdir('/bus/mailboxes/demandwareprod/archive/in')
        for file in lista_files:
            print ("Processing file: ", file)
            #nome_file = os.path.basename(file).strip('.backup')
            nome_file = os.path.basename(file)
            path_ordini = '/home/OrderManagement/testFiles/catalog_export/inbound/'
            path_locale = path_ordini + nome_file
            sftp.get(file,path_locale)
            print ("Retrieved: ", nome_file)

    except paramiko.SSHException:
        print ("Connection Failed")
        quit()


def removeRemoteFiles(lista_files):
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        ssh.connect(host, port, username, password)

        for file in lista_files:
            print ("Removing file: ", file)
            stdin,stdout,stderr = ssh.exec_command("rm "+ file)

        return lista_files
    except paramiko.SSHException:
        print ("Connection Failed")
        quit()

lista_files = getModifiedFiles()
getFilesFromRemoteServer(lista_files)
removeRemoteFiles(lista_files)



