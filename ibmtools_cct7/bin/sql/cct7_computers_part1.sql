set echo on

REM
REM cct7_computers_part1.sql
REM

exec drop_table_if_exist('new_cct7_computers');

exec drop_table_if_exist('AM_COMPUTERS');
exec drop_table_if_exist('AM_CONTRACTS');
exec drop_table_if_exist('AM_AP');
exec drop_table_if_exist('AM_A7');
exec drop_table_if_exist('AM_C9');
exec drop_table_if_exist('AM_PR');

create table AM_COMPUTERS                as select * from cs_qwestibm_computers@itast;
create table AM_CONTRACTS                as select * from cs_qwestibm_igscontracts@itast;

create table AM_AP                       as select * from amAstProjDesc@itast;
create table AM_A7                       as select * from amAstCntrDesc@itast;
create table AM_C9                       as select * from amContract@itast;
create table AM_PR                       as select * from amProject@itast;

create index idx_AM_COMPUTERS_1 on AM_COMPUTERS ( a1_lastid );

create index idx_AM_CONTRACTS_1 on AM_CONTRACTS ( removed_date );

create index idx_AM_AP_1        on AM_AP        ( dremoved, lastid, lprojid );
create index idx_AM_AP_2        on AM_AP        ( dremoved, lprojid );

create index idx_AM_A7_1        on AM_A7        ( lastid );
create index idx_AM_A7_2        on AM_A7        ( lastid, dplannedremov );

create index idx_AM_C9_1        on AM_C9        ( nature, status );

create index idx_AM_PR_1        on AM_PR        ( lprojid, status );
create index idx_AM_PR_2        on AM_PR        ( lprojid, title );

create table new_cct7_computers as
  select
		a1_lastid             AS lastid,
		a1_dinstall           AS install_date,
		a1_field1             AS systemname,
		c_name                AS hostname,
		a1_serialno           AS serial_no,
		a1_assettag           AS asset_tag,
		a1_status             AS status,
		CASE a1_seacqumethod
			WHEN 0 THEN 'N'
			ELSE        'Y'
		END                   AS lease_purchase,
		p_status_description  AS status_description,
		p_qw_platform         AS platform,
		c_computerdesc        AS description,
		c_computertype        AS type,
		c_cputype             AS cpu_type,
		c_lcpunumber          AS cpu_count,
		c_lcpuspeedmhz        AS cpu_speed,
		c_lmemorysizemb       AS memory_mb,
		c_operatingsystem     AS operating_system,
		c_tcpipaddress        AS ip_address,
		c_tcpipdomain         AS domain,
		c_tcpiphostname       AS hostname_domain,
		m_barcode             AS model_no,
		m_name                AS model,
		b_name                AS model_mfg,
		l6_name               AS clli,
		l6_fullname           AS clli_fullname,
		l6_address1           AS building,
		l6_address2           AS address,
		l6_city               AS city,
		l6_state              AS state,
		p_qw_floor_room       AS floor_room,
		p_qw_grid_loc         AS grid_location,
		model_category        AS model_category,
		parent_lastid         AS complex_lastid,
		parent_name           AS complex_name,
		parent_parent_name    AS complex_parent_name,
		child_computers       AS complex_child_names,
		CASE m_name
			WHEN 'SERVICE GUARD CLUSTER SERVER' THEN 'Y'
			ELSE 'N'
		END                   AS is_service_guard
	from
		AM_COMPUTERS;

alter table new_cct7_computers add (
	last_record_update            DATE,
	os_group_contact              VARCHAR2(20),
	complex_partitions            NUMBER,
	contract                      VARCHAR2(80),
	contract_ref                  VARCHAR2(20),
	contract_status               VARCHAR2(43),
	contract_status_type          VARCHAR2(80),
	contract_date                 DATE,
	os_lite                       VARCHAR2(20),
	is_gold_server                VARCHAR2(1),
	service_level_objective       NUMBER(5,2),
	service_level_score           NUMBER(4,2),
	service_level_colors          VARCHAR2(80),
	is_special_handling           VARCHAR2(1),
	timezone                      VARCHAR2(25),
	is_dmz                        VARCHAR2(1),
	applications                  VARCHAR2(4000),
	osmaint_weekly                VARCHAR2(4000),
	osmaint_monthly               VARCHAR2(4000),
	osmaint_quarterly             VARCHAR2(4000),
	ewebars_title                 VARCHAR2(100),
	ewebars_status                VARCHAR2(80),
	cio_group                     VARCHAR2(100),
	managing_group                VARCHAR2(40),
	nature                        VARCHAR2(80),
	is_complex                    VARCHAR2(1),
	is_ibm_supported              VARCHAR2(1),
	csc_os_banners                NUMBER,
	csc_pase_banners              NUMBER,
	csc_dba_banners               NUMBER,
	csc_fyi_banners               NUMBER,
	disk_ecc_array_alloc_kb       NUMBER,
	disk_ecc_array_used_kb        NUMBER,
	disk_ecc_array_free_kb        NUMBER,
	disk_ecc_local_alloc_kb       NUMBER,
	disk_ecc_local_used_kb        NUMBER,
	disk_ecc_local_free_kb        NUMBER,
	backup_format                 VARCHAR2(10),
	backup_nodename               VARCHAR2(1000),
	backup_program                VARCHAR2(80),
	backup_server                 VARCHAR2(1000),
	netbackup                     VARCHAR2(80),
	app_server_assn_sox_critical  NUMBER,
	db_server_assn_sox_critical   NUMBER
);

create index idx_new_cct7_computers_1 on new_cct7_computers ( lastid );
create index idx_new_cct7_computers_2 on new_cct7_computers ( hostname );
create index idx_new_cct7_computers_3 on new_cct7_computers ( contract );
create index idx_new_cct7_computers_4 on new_cct7_computers ( ip_address );
create index idx_new_cct7_computers_5 on new_cct7_computers ( complex_lastid );
create index idx_new_cct7_computers_6 on new_cct7_computers ( clli_fullname );
create index idx_new_cct7_computers_7 on new_cct7_computers ( state );
create index idx_new_cct7_computers_8 on new_cct7_computers ( clli );
create index idx_new_cct7_computers_9 on new_cct7_computers ( operating_system );
create index idx_new_cct7_computers_10 on new_cct7_computers ( os_group_contact );

commit;
quit;
