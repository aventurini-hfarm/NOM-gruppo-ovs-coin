__author__ = 'vincenzosambucaro'
import os
import paramiko
import shutil
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
        #MULE OVS TEST
        ssh.connect('213.215.155.156', 22, username='mule', password='mule')
        #MULE OVS PRODUZIONE
        #ssh.connect('213.215.155.154', 22, username='everis', password='everisovs2016')

        stdin,stdout,stderr = ssh.exec_command("find /bus/mailboxes/demandwareprod/archive/in -cmin -5 |grep order")
        #stdin,stdout,stderr = ssh.exec_command("ls")
        lista_files = []
        for line in stdout.readlines():
            print (line.strip())
            lista_files.append(line.strip())

        return lista_files
    except paramiko.SSHException:
        print ("Connection Failed")
        quit()

def getOrdersFromRemoteServer(lista_files):
    ssh = paramiko.SSHClient()
    ssh.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    try:
        #MULE OVS TEST
        ssh.connect('213.215.155.156', 22, username='mule', password='mule')
        #MULE OVS PRODUZIONE
        #ssh.connect('213.215.155.154', 22, username='everis', password='everisovs2016')

        sftp = ssh.open_sftp()
        #sftp.chdir('/bus/mailboxes/demandwareprod/archive/in')
        for file in lista_files:
            print ("Processing file: ", file)
            nome_file = os.path.basename(file).strip('.backup')
            path_ordini = '/home/OrderManagement/testFiles/order_export/inbound/'
            path_locale = path_ordini + nome_file
            sftp.get(file,path_locale)
            print ("Retrieved: ", nome_file)

    except paramiko.SSHException:
        print ("Connection Failed")
        quit()



lista_files = getModifiedFiles()
getOrdersFromRemoteServer(lista_files)




