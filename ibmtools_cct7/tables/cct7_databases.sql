WHENEVER SQLERROR CONTINUE
set echo on

REM
REM See: /ibm/cct7/bin/sql/cct7_databases.sql
REM

create table cct7_databases
(
 COMPUTER_LASTID                                    NUMBER(10)         NOT NULL,
 COMPUTER_HOSTNAME                                  VARCHAR2(255 CHAR),
 COMPUTER_ASSET_TAG                                 VARCHAR2(80 CHAR),
 COMPUTER_SERIAL_NO                                 VARCHAR2(128 CHAR),
 DATABASE_LASTID                                    NUMBER(10)         NOT NULL,
 DATABASE_ASSET_TAG                                 VARCHAR2(80 CHAR),
 DATABASE_SEASSIGNMENT                              NUMBER(5)          NOT NULL,
 DATABASE_NAME                                      VARCHAR2(128 CHAR),
 DATABASE_CONNECTION_TYPE                           VARCHAR2(80 CHAR),
 DATABASE_FAILOVER_PRIORITY                         VARCHAR2(81 CHAR),
 DATABASE_INSTANCE                                  VARCHAR2(128 CHAR),
 DATABASE_SERVICE                                   VARCHAR2(128 CHAR),
 DATABASE_TYPE                                      VARCHAR2(43 CHAR),
 DATABASE_VERSION                                   VARCHAR2(32 CHAR),
 DATABASE_MDL_POC                                   VARCHAR2(20 CHAR),
 DATABASE_PATCH_INSTALL                             VARCHAR2(43 CHAR),
 DATABASE_PATCH_LEVEL                               VARCHAR2(32 CHAR),
 DATABASE_FAMILY_GROUP_KEY                          NUMBER(10)         NOT NULL,
 DATABASE_COMMENT                                   VARCHAR2(4000),
 DATABASE_MANAGER_CUID                              VARCHAR2(20 CHAR),
 DATABASE_PROD_MANAGER_CUID                         VARCHAR2(20 CHAR),
 DATABASE_ARRANGEMENT                               VARCHAR2(81 CHAR),
 DATABASE_NON_STANDARD_SUPPORT                      VARCHAR2(81 CHAR),
 DATABASE_ASSIGN_CHANGE_DATE                        DATE,
 NO_OWNING_APPL_REASON                              VARCHAR2(81 CHAR),
 ASSET_SYSTEM                                       VARCHAR2(40 CHAR)
);

create index idx_cct7_databases1 on cct7_databases ( computer_lastid );
create index idx_cct7_databases2 on cct7_databases ( computer_hostname );
create index idx_cct7_databases3 on cct7_databases ( computer_asset_tag );
create index idx_cct7_databases4 on cct7_databases ( computer_serial_no );
create index idx_cct7_databases5 on cct7_databases ( database_name );

insert into cct7_databases select * from cct6_databases;

select count(*) as cct7_databases from cct7_databases;

commit;
