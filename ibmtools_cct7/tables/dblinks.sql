REM
REM dblinks.sql 
REM
REM Host: lxomp11m
REM
REM Date: Wed Sep 23 16:53:50 CDT 2015
REM
REM BY: gparkin
REM

WHENEVER SQLERROR CONTINUE;

drop database link ibmtoolp;
create database link ibmtoolp
connect to cct identified by candy4Kids
using 'ibmtoolp';

drop database link ibmtoolt;
create database link ibmtoolt
connect to cct identified by candy4Kids
using 'ibmtoolt';

drop database link asset_center;
create database link asset_center
connect to cctadm identified by B00mbadaboom
using 'itast';

drop database link cct_prod;
create database link cct_prod
connect to cct identified by candy4Kids
using 'orion';

drop database link cct_test;
create database link cct_test
connect to cct identified by candy4Kids
using 'thor';

drop database link cct;
create database link cct
connect to cct identified by candy4Kids
using 'orion';

drop database link csc_cct_web;
create database link csc_cct_web
connect to cct_web identified by hotRod4me
using 'CSC';

drop database link itast;
create database link itast
connect to qwestibm identified by B00mbadaboom
using 'itast';

drop database link mits;
create database link mits
connect to uss identified by ussadm
using 'MITS';

drop database link orion;
create database link orion
connect to orion identified by candy4Kids
using 'orion';

drop database link remedy_im2;
create database link remedy_im2
connect to gparkin identified by chcort60
using 'REMEDY_IM2';

drop database link remedy_prod;
create database link remedy_prod
connect to gparkin identified by Timbuck2
using 'remedy_prod';

drop database link srm_cdc;
create database link srm_cdc
connect to stsadmin identified by st0rage
using 'srm_cdc';

drop database link srm_ddc;
create database link srm_ddc
connect to stsadmin identified by st0rage
using 'srm_ddc';

drop database link srm_gdc;
create database link srm_gdc
connect to stsadmin identified by stsadmin
using 'srm_gdc';

drop database link srm_mdc;
create database link srm_mdc
connect to stsadmin identified by st0rage
using 'srm_mdc';

drop database link srm_odc;
create database link srm_odc
connect to stsadmin identified by st0rage
using 'srm_odc';

drop database link sts_cdc;
create database link sts_cdc
connect to stsview identified by sts
using 'sts_cdc';

drop database link sts_ddc;
create database link sts_ddc
connect to stsview identified by sts
using 'sts_ddc';

drop database link sts_gdc;
create database link sts_gdc
connect to stsview identified by sts
using 'sts_gdc';

drop database link sts_mdc;
create database link sts_mdc
connect to stsview identified by sts
using 'sts_mdc';

drop database link sts_odc;
create database link sts_odc
connect to stsview identified by sts
using 'sts_odc';

drop database link thor;
create database link thor
connect to thor identified by candy4Kids
using 'thor';

drop database link tla;
create database link tla
connect to orion identified by belt
using 'tla';

drop database link tla_prod;
create database link tla_prod
connect to orion identified by belt
using 'tla_prod';

drop database link tla_test;
create database link tla_test
connect to orion identified by belt
using 'tla_test';

drop database link tss;
create database link tss
connect to httpd identified by httpd98
using 'tss';

drop database link uss;
create database link uss
connect to uss identified by ussadm
using 'USS';
