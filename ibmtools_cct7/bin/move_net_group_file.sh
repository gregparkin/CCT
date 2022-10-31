#!/usr/bin/ksh
#


ftp="/ibm/ftp/net/cct_net_members.csv";
weekday="/ibm/cct6/net/`date +'%A'`"; 

date

if [ -f $ftp ] 
then
	echo -e "Moving file\n"
	mv $ftp $weekday 
	chown gparkin:dcfull $weekday
else
	echo -e "FTP file $ftp file not available today.\n"
fi
