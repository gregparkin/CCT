set echo on

REM
REM In the event that the extract is bad, this script will restore the backup copy.
REM

exec drop_table_if_exist('cct7_csc');

rename cct7_csc_backup to cct7_csc;

quit;
