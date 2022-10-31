REM
REM See: /opt/ibmtools/cct7/bin/sql/cct7_computers_part[1-11]
REM

WHENEVER SQLERROR CONTINUE
set echo on

create table cct7_computers
(
 COMPUTER_LASTID                                    NUMBER(10) NOT NULL,
 COMPUTER_LAST_UPDATE                               DATE,
 COMPUTER_INSTALL_DATE                              DATE,
 COMPUTER_SYSTEMNAME                                VARCHAR2(255 CHAR),
 COMPUTER_HOSTNAME                                  VARCHAR2(255 CHAR),
 COMPUTER_OPERATING_SYSTEM                          VARCHAR2(80 CHAR),
 COMPUTER_OS_LITE                                   VARCHAR2(20),
 COMPUTER_STATUS                                    VARCHAR2(80 CHAR),
 COMPUTER_STATUS_DESCRIPTION                        VARCHAR2(40),
 COMPUTER_DESCRIPTION                               VARCHAR2(255 CHAR),
 COMPUTER_NATURE                                    VARCHAR2(80),
 COMPUTER_PLATFORM                                  VARCHAR2(43 CHAR),
 COMPUTER_TYPE                                      VARCHAR2(80 CHAR),
 COMPUTER_CLLI                                      VARCHAR2(30 CHAR),
 COMPUTER_CLLI_FULLNAME                             VARCHAR2(511 CHAR),
 COMPUTER_TIMEZONE                                  VARCHAR2(25),
 COMPUTER_BUILDING                                  VARCHAR2(100 CHAR),
 COMPUTER_ADDRESS                                   VARCHAR2(100 CHAR),
 COMPUTER_CITY                                      VARCHAR2(80 CHAR),
 COMPUTER_STATE                                     VARCHAR2(80 CHAR),
 COMPUTER_FLOOR_ROOM                                VARCHAR2(32 CHAR),
 COMPUTER_GRID_LOCATION                             VARCHAR2(32 CHAR),
 COMPUTER_LEASE_PURCHASE                            CHAR(1),
 COMPUTER_SERIAL_NO                                 VARCHAR2(128 CHAR),
 COMPUTER_ASSET_TAG                                 VARCHAR2(80 CHAR),
 COMPUTER_MODEL_CATEGORY                            VARCHAR2(511 CHAR),
 COMPUTER_MODEL_NO                                  VARCHAR2(80 CHAR),
 COMPUTER_MODEL                                     VARCHAR2(128 CHAR),
 COMPUTER_MODEL_MFG                                 VARCHAR2(128 CHAR),
 COMPUTER_CPU_TYPE                                  VARCHAR2(80 CHAR),
 COMPUTER_CPU_COUNT                                 NUMBER(10) NOT NULL,
 COMPUTER_CPU_SPEED                                 NUMBER(10) NOT NULL,
 COMPUTER_MEMORY_MB                                 NUMBER(10) NOT NULL,
 COMPUTER_IP_ADDRESS                                VARCHAR2(64 CHAR),
 COMPUTER_DOMAIN                                    VARCHAR2(40 CHAR),
 COMPUTER_HOSTNAME_DOMAIN                           VARCHAR2(255 CHAR),
 COMPUTER_DMZ                                       VARCHAR2(1),
 COMPUTER_EWEBARS_TITLE                             VARCHAR2(100),
 COMPUTER_EWEBARS_STATUS                            VARCHAR2(80),
 COMPUTER_BACKUP_FORMAT                             VARCHAR2(10),
 COMPUTER_BACKUP_NODENAME                           VARCHAR2(1000),
 COMPUTER_BACKUP_PROGRAM                            VARCHAR2(80),
 COMPUTER_BACKUP_SERVER                             VARCHAR2(1000),
 COMPUTER_NETBACKUP                                 VARCHAR2(80),
 COMPUTER_COMPLEX                                   VARCHAR2(1),
 COMPUTER_COMPLEX_LASTID                            NUMBER(10) NOT NULL,
 COMPUTER_COMPLEX_NAME                              VARCHAR2(255 CHAR),
 COMPUTER_COMPLEX_PARENT_NAME                       VARCHAR2(255 CHAR),
 COMPUTER_COMPLEX_CHILD_NAMES                       VARCHAR2(4000),
 COMPUTER_COMPLEX_PARTITIONS                        NUMBER,
 COMPUTER_SERVICE_GUARD                             CHAR(1),
 COMPUTER_OS_GROUP_CONTACT                          VARCHAR2(20),
 COMPUTER_CIO_GROUP                                 VARCHAR2(100),
 COMPUTER_MANAGING_GROUP                            VARCHAR2(40),
 COMPUTER_CONTRACT                                  VARCHAR2(80),
 COMPUTER_CONTRACT_REF                              VARCHAR2(20),
 COMPUTER_CONTRACT_STATUS                           VARCHAR2(43),
 COMPUTER_CONTRACT_STATUS_TYPE                      VARCHAR2(80),
 COMPUTER_CONTRACT_DATE                             DATE,
 COMPUTER_IBM_SUPPORTED                             VARCHAR2(1),
 COMPUTER_GOLD_SERVER                               VARCHAR2(1),
 COMPUTER_SLEVEL_OBJECTIVE                          NUMBER(5,2),
 COMPUTER_SLEVEL_SCORE                              NUMBER(4,2),
 COMPUTER_SLEVEL_COLORS                             VARCHAR2(80),
 COMPUTER_SPECIAL_HANDLING                          VARCHAR2(1),
 COMPUTER_APPLICATIONS                              VARCHAR2(4000),
 COMPUTER_OSMAINT_WEEKLY                            VARCHAR2(4000),
 COMPUTER_OSMAINT_MONTHLY                           VARCHAR2(4000),
 COMPUTER_OSMAINT_QUARTERLY                         VARCHAR2(4000),
 COMPUTER_CSC_OS_BANNERS                            NUMBER,
 COMPUTER_CSC_PASE_BANNERS                          NUMBER,
 COMPUTER_CSC_DBA_BANNERS                           NUMBER,
 COMPUTER_CSC_FYI_BANNERS                           NUMBER,
 COMPUTER_DISK_ARRAY_ALLOC_KB                       NUMBER,
 COMPUTER_DISK_ARRAY_USED_KB                        NUMBER,
 COMPUTER_DISK_ARRAY_FREE_KB                        NUMBER,
 COMPUTER_DISK_LOCAL_ALLOC_KB                       NUMBER,
 COMPUTER_DISK_LOCAL_USED_KB                        NUMBER,
 COMPUTER_DISK_LOCAL_FREE_KB                        NUMBER,
 APP_SERVER_ASSN_SOX_CRITICAL                       NUMBER,
 DB_SERVER_ASSN_SOX_CRITICAL                        NUMBER
);

create index idx_cct7_computers_1  on cct7_computers ( computer_lastid );
create index idx_cct7_computers_2  on cct7_computers ( computer_hostname );
create index idx_cct7_computers_3  on cct7_computers ( computer_contract );
create index idx_cct7_computers_4  on cct7_computers ( computer_ip_address );
create index idx_cct7_computers_5  on cct7_computers ( computer_complex_lastid );
create index idx_cct7_computers_6  on cct7_computers ( computer_clli_fullname );
create index idx_cct7_computers_7  on cct7_computers ( computer_state );
create index idx_cct7_computers_8  on cct7_computers ( computer_clli );
create index idx_cct7_computers_9  on cct7_computers ( computer_operating_system );
create index idx_cct7_computers_10 on cct7_computers ( computer_os_group_contact );

COMMENT ON TABLE cct7_computers IS 'Computer Information from: Asset Center, CSC and Data Collect';

comment on column cct7_computers.computer_lastid               IS '233494988';
comment on column cct7_computers.computer_last_update          IS '15-FEB-13';
comment on column cct7_computers.computer_install_date         IS '30-JUN-08';
comment on column cct7_computers.computer_systemname           IS 'HVDNP16E';
comment on column cct7_computers.computer_hostname             IS 'hvdnp16e';
comment on column cct7_computers.computer_operating_system     IS 'HP-UX B.11.31';
comment on column cct7_computers.computer_os_lite              IS 'HPUX';
comment on column cct7_computers.computer_status               IS 'PRODUCTION';
comment on column cct7_computers.computer_status_description   IS 'In Use';
comment on column cct7_computers.computer_description          IS 'Locally Administered MAC Address ia64 hp server Integrity Virtual Machine';
comment on column cct7_computers.computer_nature               IS 'SUPPORT';
comment on column cct7_computers.computer_platform             IS 'MIDRANGE';
comment on column cct7_computers.computer_type                 IS 'SERVER';
comment on column cct7_computers.computer_clli                 IS 'DNVRCODP';
comment on column cct7_computers.computer_clli_fullname        IS '/USA/CO/DENVER/DNVCODP/';
comment on column cct7_computers.computer_timezone             IS 'MST';
comment on column cct7_computers.computer_building             IS 'DENVER BUILDING LOC';
comment on column cct7_computers.computer_address              IS '5325 ZUNI ST';
comment on column cct7_computers.computer_city                 IS 'DENVER';
comment on column cct7_computers.computer_state                IS 'CO';
comment on column cct7_computers.computer_floor_room           IS '2';
comment on column cct7_computers.computer_grid_location        IS 'D-14';
comment on column cct7_computers.computer_lease_purchase       IS '0';
comment on column cct7_computers.computer_serial_no            IS 'USE7507MWL';
comment on column cct7_computers.computer_asset_tag            IS 'SYSGEN0787081606';
comment on column cct7_computers.computer_model_category       IS '/HARDWARE/COMPUTERS/VPAR/';
comment on column cct7_computers.computer_model_no             IS 'M389973';
comment on column cct7_computers.computer_model                IS 'HP VIRTUAL MACHINE';
comment on column cct7_computers.computer_model_mfg            IS 'HEWLETT PACKARD';
comment on column cct7_computers.computer_cpu_type             IS 'Itanium 9100 Series';
comment on column cct7_computers.computer_cpu_count            IS '0';
comment on column cct7_computers.computer_cpu_speed            IS '1670';
comment on column cct7_computers.computer_memory_mb            IS '4089';
comment on column cct7_computers.computer_ip_address           IS '151.119.98.174';
comment on column cct7_computers.computer_domain               IS 'qintra.com';
comment on column cct7_computers.computer_hostname_domain      IS 'hvdnp16e.qintra.com';
comment on column cct7_computers.computer_dmz                  IS 'N';
comment on column cct7_computers.computer_ewebars_title        IS 'IBM-UNIX SUPPORT';
comment on column cct7_computers.computer_ewebars_status       IS 'SUPPORT';
comment on column cct7_computers.computer_backup_format        IS 'TSM';
comment on column cct7_computers.computer_backup_nodename      IS 'ahvdnp16eh';
comment on column cct7_computers.computer_backup_program       IS 'TSM';
comment on column cct7_computers.computer_backup_server        IS 'aidnb07g.qintra.com';
comment on column cct7_computers.computer_netbackup            IS '(null)';
comment on column cct7_computers.computer_complex              IS 'N';
comment on column cct7_computers.computer_complex_lastid       IS '225693687';
comment on column cct7_computers.computer_complex_name         IS 'hhdnp85d';
comment on column cct7_computers.computer_complex_parent_name  IS 'hcdnx11a';
comment on column cct7_computers.computer_complex_child_names  IS '(null)';
comment on column cct7_computers.computer_complex_partitions   IS '0';
comment on column cct7_computers.computer_service_guard        IS 'N';
comment on column cct7_computers.computer_os_group_contact     IS 'mits-all';
comment on column cct7_computers.computer_cio_group            IS 'IBM-UNIX SUPPORT';
comment on column cct7_computers.computer_managing_group       IS 'IBM-UNIX';
comment on column cct7_computers.computer_contract             IS 'IGS FULL CONTRACT UNIX-PROD';
comment on column cct7_computers.computer_contract_ref         IS 'C028602';
comment on column cct7_computers.computer_contract_status      IS '(null)';
comment on column cct7_computers.computer_contract_status_type IS 'SERVER';
comment on column cct7_computers.computer_contract_date        IS '01-OCT-12';
comment on column cct7_computers.computer_ibm_supported        IS 'Y';
comment on column cct7_computers.computer_gold_server          IS 'Y';
comment on column cct7_computers.computer_slevel_objective     IS '98';
comment on column cct7_computers.computer_slevel_score         IS '44.3';
comment on column cct7_computers.computer_slevel_colors        IS 'GOLD';
comment on column cct7_computers.computer_special_handling     IS 'N';
comment on column cct7_computers.computer_applications         IS 'NBA';
comment on column cct7_computers.computer_osmaint_weekly       IS 'MON,TUE,WED,THU,FRI,SAT,SUN 2200 480';
comment on column cct7_computers.computer_osmaint_monthly      IS '3 SUN 01:00 240';
comment on column cct7_computers.computer_osmaint_quarterly    IS 'FEB,MAY,AUG,NOV 3 FRI 22:00 720';
comment on column cct7_computers.computer_csc_os_banners       IS '1';
comment on column cct7_computers.computer_csc_pase_banners     IS '1';
comment on column cct7_computers.computer_csc_dba_banners      IS '0';
comment on column cct7_computers.computer_csc_fyi_banners      IS '0';
comment on column cct7_computers.computer_disk_array_alloc_kb  IS '0';
comment on column cct7_computers.computer_disk_array_used_kb   IS '0';
comment on column cct7_computers.computer_disk_array_free_kb   IS '0';
comment on column cct7_computers.computer_disk_local_alloc_kb  IS '0';
comment on column cct7_computers.computer_disk_local_used_kb   IS '0';
comment on column cct7_computers.computer_disk_local_free_kb   IS '0';
comment on column cct7_computers.app_server_assn_sox_critical  IS 'SOX application critical flag';
comment on column cct7_computers.db_server_assn_sox_critical   IS 'SOX database critical flag';

insert into cct7_computers select * from cct6_computers;

select count(*) as cct7_computers from cct7_computers;

commit;
