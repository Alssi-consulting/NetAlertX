#!/usr/bin/env python
# test script by running:
# tbc

import os
import pathlib
import argparse
import subprocess
import sys
import hashlib
import csv
import sqlite3
import re
from io import StringIO
from datetime import datetime

# Register NetAlertX directories
INSTALL_PATH="/app"
sys.path.extend([f"{INSTALL_PATH}/front/plugins", f"{INSTALL_PATH}/server"])

from plugin_helper import Plugin_Object, Plugin_Objects, decodeBase64
from logger import mylog, Logger, append_line_to_file
from helper import timeNowTZ, get_setting_value
from const import logPath, applicationPath, fullDbPath
from database import DB
from device import Device_obj
import conf
from pytz import timezone

# Make sure the TIMEZONE for logging is correct
conf.tz = timezone(get_setting_value('TIMEZONE'))

# Make sure log level is initialized correctly
Logger(get_setting_value('LOG_LEVEL'))

pluginName = 'ICMP'

LOG_PATH = logPath + '/plugins'
LOG_FILE = os.path.join(LOG_PATH, f'script.{pluginName}.log')
RESULT_FILE = os.path.join(LOG_PATH, f'last_result.{pluginName}.log')



def main():

    mylog('verbose', [f'[{pluginName}] In script'])     


    timeout = get_setting_value('ICMP_RUN_TIMEOUT')
    args = get_setting_value('ICMP_ARGS')
 
    # Create a database connection
    db = DB()  # instance of class DB
    db.open()

    # Initialize the Plugin obj output file
    plugin_objects = Plugin_Objects(RESULT_FILE)

    # Create a Device_obj instance
    device_handler = Device_obj(db)

    # Retrieve devices
    all_devices = device_handler.getAll()

    mylog('verbose', [f'[{pluginName}] Devices to PING: {len(all_devices)}'])   

    for device in all_devices:
        is_online, output = execute_scan(device['devLastIP'], timeout, args)

        mylog('verbose', [f'[{pluginName}] ip: "{device['devLastIP']}" is_online: "{is_online}"'])

        if is_online:
            plugin_objects.add_object(
            # "MAC", "IP", "Name", "Output"
            primaryId   = device['devMac'],
            secondaryId = device['devLastIP'],
            watched1    = device['devName'],
            watched2    = output.replace('\n',''),
            watched3    = '',
            watched4    = '',
            extra       = '',
            foreignKey  = device['devMac'])

    plugin_objects.write_result_file()
    
    
    mylog('verbose', [f'[{pluginName}] Script finished'])   
    
    return 0

#===============================================================================
# Execute scan
#===============================================================================
def execute_scan (ip, timeout, args):
    """
    Execute the ICMP command on IP.
    """
    
    icmp_args = ['ping'] + args.split() + [ip]

    # Execute command
    output = ""

    try:
        # try runnning a subprocess with a forced (timeout)  in case the subprocess hangs
        output = subprocess.check_output (icmp_args, universal_newlines=True,  stderr=subprocess.STDOUT, timeout=(timeout), text=True)

        mylog('verbose', [f'[{pluginName}] DEBUG OUTPUT : {output}'])

        # Parse output using case-insensitive regular expressions
        #Synology-NAS:/# ping -i 0.5 -c 3 -W 8 -w 9 192.168.1.82
        # PING 192.168.1.82 (192.168.1.82): 56 data bytes
        # 64 bytes from 192.168.1.82: seq=0 ttl=64 time=0.080 ms
        # 64 bytes from 192.168.1.82: seq=1 ttl=64 time=0.081 ms
        # 64 bytes from 192.168.1.82: seq=2 ttl=64 time=0.089 ms

        # --- 192.168.1.82 ping statistics ---
        # 3 packets transmitted, 3 packets received, 0% packet loss
        # round-trip min/avg/max = 0.080/0.083/0.089 ms
        # Synology-NAS:/# ping -i 0.5 -c 3 -W 8 -w 9 192.168.1.82a
        # ping: bad address '192.168.1.82a'
        # Synology-NAS:/# ping -i 0.5 -c 3 -W 8 -w 9 192.168.1.92
        # PING 192.168.1.92 (192.168.1.92): 56 data bytes

        # --- 192.168.1.92 ping statistics ---
        # 3 packets transmitted, 0 packets received, 100% packet loss

        # TODO: parse output and return True if online, False if Offline (100% packet loss, bad address) 
        is_online = True

        # Check for 0% packet loss in the output
        if re.search(r"0% packet loss", output, re.IGNORECASE):
            is_online = True
        elif re.search(r"bad address", output, re.IGNORECASE):
            is_online = False
        elif re.search(r"100% packet loss", output, re.IGNORECASE):
            is_online = False

        return is_online, output

    except subprocess.CalledProcessError as e:
        # An error occurred, handle it
        mylog('verbose', [f'[{pluginName}] ⚠ ERROR - check logs']) 
        mylog('verbose', [f'[{pluginName}]', e.output])

        return False, output       
        
    except subprocess.TimeoutExpired as timeErr:
        mylog('verbose', [f'[{pluginName}] TIMEOUT - the process forcefully terminated as timeout reached']) 
        return False, output    

    return False, output   
          
    
    

#===============================================================================
# BEGIN
#===============================================================================
if __name__ == '__main__':
    main()