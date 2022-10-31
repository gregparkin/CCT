#!/bin/bash
#
# post_install.sh
#
# AUTHOR: Greg Parkin
#
# This script is used to move files uploaded from my workstation into /opt/ibmtools/www/cct
# to /opt/ibmtools/cct7 that are for automation (/opt/ibmtools/www/cct7/ibm_cct7, etc.)
#
# When PHP scripts are copied from DOS (Windows) to Unix (Linux) you need to remove
# the special characters that DOS puts in a file (\r). To do this you need to run
# dos2unix to convert the files over you will get a Zend Exception error when you
# try and run the scripts. If you are root on Linux and cannot find dos2unix you can
# do this command to retrieve it: yum install dos2unix
#

umask 0

cd /opt/ibmtools/cct7

mkdir -p bin counters debug email etc logs tables

chmod 2755 bin counters debug email etc logs tables

cp -R /opt/ibmtools/www/cct7/ibmtools_cct7/bin bin/
cp -R /opt/ibmtools/www/cct7/ibmtools_cct7/bin tables/
cp /opt/ibmtools/www/cct7/send_notifications.php bin/

cd /opt/ibmtools/www/cct7
rm -f counters debug email 

ln -s /opt/ibmtools/cct7/counters counters
ln -s /opt/ibmtools/cct7/debug debug
ln -s /opt/ibmtools/cct7/email email

cd /opt/ibmtools/cct7/bin

for a in `ls *.php`
do
	dos2unix $a
done

chmod +x /opt/ibmtools/cct7/bin/*.php
chmod +x /opt/ibmtools/cct7/bin/*.exe

