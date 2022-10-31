#!/bin/ksh
#
# Start /u/gparkin/lenovo/send_cct_to_lenovo.sh first!
#

mkfifo import.pipe

netcat lxomp47x.corp.intranet 4000 >import.pipe &

cat <<! >parfile_cct.dat
USERID=cct/candy4Kids
BUFFER=8192000
FILE=import.pipe
FROMUSER=cct
TOUSER=cct
LOG=receive_cct.log
STATISTICS=NONE
COMMIT=Y
IGNORE=Y
!

export NLS_LANG=AMERICAN_AMERICA.WE8ISO8859P1

imp parfile=parfile_cct.dat

