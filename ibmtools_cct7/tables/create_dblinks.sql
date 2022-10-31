
REM
REM NOTE: You cannot do a desc command on a dblink in SQL Developer
REM
REM select * from user_db_links;
REM

drop database link csc;

drop database link itast;
drop database link mnet;
drop database link remedy_prod;

REM create database link csc
REM connect to csc
REM identified by snorkey
REM using 'CSC_CLUSTER';

create database link csc_cct_web
connect to cct_web
identified by candy4Kids
using 'CSC_CLUSTER';

desc csc.v_acsys_assign@csc_cct_web

select count(*) from csc.v_acsys_assign@csc_cct_web;

-- itast|itastdb.qintra.com:1535|itast|qwestibm|candy4Kids|Oracle

create database link itast
connect to QWESTIBM
identified by candy4Kids
using 'ITAST';

create database link mnet
connect to USSDB
identified by Gr87$xy9
using 'MNET';

select count(*) from ussdb_mnet@mnet;

create database link remedy_prod
connect to GPARKIN
identified by Timbuck2
using 'REMEDY_PROD';

quit;

SQL> select db_link ||','|| username ||','|| host from user_db_links;

quit;

DB_LINK||','||USERNAME||','||HOST
--------------------------------------------------------------------------------
EMS,EMSARCH,CSC_CLUSTER
GPARKIN,PCRM,PCRM
ITAST,QWESTIBM,ITAST
MITS,USS,mits

MNET,USSDB,mnet

mnet =
USSDB/LZ3\$7U9@(DESCRIPTION=(SDU = 8192)(TDU = 8192)(LOAD_BALANCE=ON)(FAILOVER=ON)(ADDRESS = (PROTOCOL=TCP)(HOST=mnetdb.uswc.uswest.com)(PORT=1521))(ADDRESS = (PROTOCOL=TCP)(HOST=mnetdb.uswc.uswest.com)(PORT=1526))(CONNECT_DATA = (SERVICE_NAME = mnet.world))
	
	mnet =
  (DESCRIPTION =
    (SDU = 8192)(TDU = 8192)
    (LOAD_BALANCE=ON)(FAILOVER=ON)
    (ADDRESS = (PROTOCOL=TCP)(HOST=mnetdb.uswc.uswest.com)(PORT=1521))
    (ADDRESS = (PROTOCOL=TCP)(HOST=mnetdb.uswc.uswest.com)(PORT=1526))
    (CONNECT_DATA = (SERVICE_NAME = mnet.world))


REMEDY_DEV,GPARKIN,REMEDY_DEV
REMEDY_PROD,GPARKIN,REMEDY_PROD
REMEDY_TEST,GPARKIN,REMEDY_TEST
SRM_CDC,STSADMIN,SRM_CDC
SRM_DDC,STSADMIN,SRM_DDC
SRM_ODC,STSADMIN,SRM_ODC

DB_LINK||','||USERNAME||','||HOST
--------------------------------------------------------------------------------
STS_CDC,STSVIEW,STS_CDC
STS_DDC,STSVIEW,STS_DDC
STS_ODC,STSVIEW,STS_ODC
ITASTUGD,QWESTIBM,ITASTUGD
TTEAM_RPT,TTEAM_RPT,TTEAM_RPT
THOR,THOR,thor
ASSET_CENTER5,CSCADM,itast
CSC,CSC,CSC_CLUSTER
CSCDEV,USS,CSCDEV
REMEDYCM,NAREVAL,remedy_prod
ASSET_CENTER,CCTADM,itast

DB_LINK||','||USERNAME||','||HOST
--------------------------------------------------------------------------------
MNETDBD1,USSDB,mnetdbd1
SRM_GDC,STSADMIN,SRM_GDC
STS_GDC,STSVIEW,STS_GDC
REMEDY_IM2,DASHBOARD,remedy_im2

26 rows selected.

SQL>


mnetdbd1 =
  (DESCRIPTION =
    (ADDRESS = (PROTOCOL=TCP)(HOST=lxdnt04g.qintra.com)(PORT=1521))
    (ADDRESS = (PROTOCOL=TCP)(HOST=lxdnt04g.qintra.com)(PORT=1526))
    (CONNECT_DATA = (SERVICE_NAME = mnetdbd1.world))
  )
  