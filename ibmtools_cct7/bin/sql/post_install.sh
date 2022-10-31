#!/bin/bash
#
# post_install.sh
#
# AUTHOR: Greg Parkin
#
# This script is used to move files uploaded from my workstation into /ibm/www/cct
# to /ibm/cct6 that are for automation (/ibm/www/cct6/ibm_cct6, etc.)
#
# When PHP scripts are copied from DOS (Windows) to Unix (Linux) you need to remove
# the special characters that DOS puts in a file (\r). To do this you need to run
# dos2unix to convert the files over you will get a Zend Exception error when you
# try and run the scripts. If you are root on Linux and cannot find dos2unix you can
# do this command to retrieve it: yum install dos2unix
#

umask 0

if [ -d "/ibm/www/cct6/ibm_cct6" ]
then
  rm -rf /ibm/www/cct6/ibm_cct6/cct_csc
  rm -rf /ibm/www/cct6/ibm_cct6/counters
  rm -rf /ibm/www/cct6/ibm_cct6/debug
  rm -rf /ibm/www/cct6/debug
  echo -e "Copying /ibm/www/cct6/ibm_cct6 to /ibm/cct6"
  cp -R /ibm/www/cct6/ibm_cct6/* /ibm/cct6/
  cd /ibm/cct6/bin
  for a in `ls *.php`
  do
    dos2unix $a
  done
  chmod +x /ibm/cct6/bin/*.php
  chmod +x /ibm/cct6/bin/*.exe
  if [ -f "/ibm/www/cct6/ibm_cct6/etc/cct.txt" ]
  then
    dos2unix /ibm/cct6/etc/cct.txt
    chgrp ibmtools /ibm/cct6/etc/cct.txt
    chmod 644 /ibm/cct6/etc/cct.txt
  fi
  if [ -f "/ibm/www/cct6/ibm_cct6/bin/post_install.sh" ]
  then
    dos2unix /ibm/cct6/bin/post_install.sh
    chmod +x /ibm/cct6/bin/post_install.sh
  fi
  rm -rf /ibm/www/cct6/ibm_cct6
else
  echo -e "Nothing to do!"
fi
