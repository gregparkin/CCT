set echo on

REM
REM cct7_computers_part10.sql
REM

select count(*) as total from new_cct7_computers;
select count(*) as weekly from new_cct7_computers where osmaint_weekly is null;
select count(*) as monthly from new_cct7_computers where osmaint_monthly is null;
select count(*) as quarterly from new_cct7_computers where osmaint_quarterly is null;

exec drop_table_if_exist('computers_today');

create table computers_today as
	select
		lastid                       as computer_lastid,
		last_record_update           as computer_last_update,
		install_date                 as computer_install_date,
		lower(systemname)            as computer_systemname,
		lower(hostname)              as computer_hostname,
		operating_system             as computer_operating_system,
		os_lite                      as computer_os_lite,
		status                       as computer_status,
		status_description           as computer_status_description,
		description                  as computer_description,
		nature                       as computer_nature,
		platform                     as computer_platform,
		type                         as computer_type,
		clli                         as computer_clli,
		clli_fullname                as computer_clli_fullname,
		timezone                     as computer_timezone,
		building                     as computer_building,
		address                      as computer_address,
		city                         as computer_city,
		state                        as computer_state,
		floor_room                   as computer_floor_room,
		grid_location                as computer_grid_location,
		lease_purchase               as computer_lease_purchase,
		serial_no                    as computer_serial_no,
		asset_tag                    as computer_asset_tag,
		model_category               as computer_model_category,
		model_no                     as computer_model_no,
		model                        as computer_model,
		model_mfg                    as computer_model_mfg,
		cpu_type                     as computer_cpu_type,
		cpu_count                    as computer_cpu_count,
		cpu_speed                    as computer_cpu_speed,
		memory_mb                    as computer_memory_mb,
		ip_address                   as computer_ip_address,
		lower(domain)                as computer_domain,
		lower(hostname_domain)       as computer_hostname_domain,
		is_dmz                       as computer_dmz,
		ewebars_title                as computer_ewebars_title,
		ewebars_status               as computer_ewebars_status,
		backup_format                as computer_backup_format,
		lower(backup_nodename)       as computer_backup_nodename,
		backup_program               as computer_backup_program,
		lower(backup_server)         as computer_backup_server,
		netbackup                    as computer_netbackup,
		is_complex                   as computer_complex,
		complex_lastid               as computer_complex_lastid,
		lower(complex_name)          as computer_complex_name,
		lower(complex_parent_name)   as computer_complex_parent_name,
		lower(complex_child_names)   as computer_complex_child_names,
		complex_partitions           as computer_complex_partitions,
		is_service_guard             as computer_service_guard,
		os_group_contact             as computer_os_group_contact,
		cio_group                    as computer_cio_group,
		managing_group               as computer_managing_group,
		contract                     as computer_contract,
		contract_ref                 as computer_contract_ref,
		contract_status              as computer_contract_status,
		contract_status_type         as computer_contract_status_type,
		contract_date                as computer_contract_date,
		is_ibm_supported             as computer_ibm_supported,
		is_gold_server               as computer_gold_server,
		service_level_objective      as computer_slevel_objective,
		service_level_score          as computer_slevel_score,
		service_level_colors         as computer_slevel_colors,
		is_special_handling          as computer_special_handling,
		applications                 as computer_applications,
		osmaint_weekly               as computer_osmaint_weekly,
		osmaint_monthly              as computer_osmaint_monthly,
		osmaint_quarterly            as computer_osmaint_quarterly,
		csc_os_banners               as computer_csc_os_banners,
		csc_pase_banners             as computer_csc_pase_banners,
		csc_dba_banners              as computer_csc_dba_banners,
		csc_fyi_banners              as computer_csc_fyi_banners,
		disk_ecc_array_alloc_kb      as computer_disk_array_alloc_kb,
		disk_ecc_array_used_kb       as computer_disk_array_used_kb,
		disk_ecc_array_free_kb       as computer_disk_array_free_kb,
		disk_ecc_local_alloc_kb      as computer_disk_local_alloc_kb,
		disk_ecc_local_used_kb       as computer_disk_local_used_kb,
		disk_ecc_local_free_kb       as computer_disk_local_free_kb,
		app_server_assn_sox_critical as app_server_assn_sox_critical,
		db_server_assn_sox_critical  as db_server_assn_sox_critical
	from
		new_cct7_computers;

COMMIT;

COMMENT ON TABLE computers_today IS 'Computer Information from: Asset Center, CSC and Data Collect';

comment on column computers_today.computer_lastid               IS '233494988';
comment on column computers_today.computer_last_update          IS '15-FEB-13';
comment on column computers_today.computer_install_date         IS '30-JUN-08';
comment on column computers_today.computer_systemname           IS 'HVDNP16E';
comment on column computers_today.computer_hostname             IS 'hvdnp16e';
comment on column computers_today.computer_operating_system     IS 'HP-UX B.11.31';
comment on column computers_today.computer_os_lite              IS 'HPUX';
comment on column computers_today.computer_status               IS 'PRODUCTION';
comment on column computers_today.computer_status_description   IS 'In Use';
comment on column computers_today.computer_description          IS 'Locally Administered MAC Address ia64 hp server Integrity Virtual Machine';
comment on column computers_today.computer_nature               IS 'SUPPORT';
comment on column computers_today.computer_platform             IS 'MIDRANGE';
comment on column computers_today.computer_type                 IS 'SERVER';
comment on column computers_today.computer_clli                 IS 'DNVRCODP';
comment on column computers_today.computer_clli_fullname        IS '/USA/CO/DENVER/DNVCODP/';
comment on column computers_today.computer_timezone             IS 'MST';
comment on column computers_today.computer_building             IS 'DENVER BUILDING LOC';
comment on column computers_today.computer_address              IS '5325 ZUNI ST';
comment on column computers_today.computer_city                 IS 'DENVER';
comment on column computers_today.computer_state                IS 'CO';
comment on column computers_today.computer_floor_room           IS '2';
comment on column computers_today.computer_grid_location        IS 'D-14';
comment on column computers_today.computer_lease_purchase       IS '0';
comment on column computers_today.computer_serial_no            IS 'USE7507MWL';
comment on column computers_today.computer_asset_tag            IS 'SYSGEN0787081606';
comment on column computers_today.computer_model_category       IS '/HARDWARE/COMPUTERS/VPAR/';
comment on column computers_today.computer_model_no             IS 'M389973';
comment on column computers_today.computer_model                IS 'HP VIRTUAL MACHINE';
comment on column computers_today.computer_model_mfg            IS 'HEWLETT PACKARD';
comment on column computers_today.computer_cpu_type             IS 'Itanium 9100 Series';
comment on column computers_today.computer_cpu_count            IS '0';
comment on column computers_today.computer_cpu_speed            IS '1670';
comment on column computers_today.computer_memory_mb            IS '4089';
comment on column computers_today.computer_ip_address           IS '151.119.98.174';
comment on column computers_today.computer_domain               IS 'qintra.com';
comment on column computers_today.computer_hostname_domain      IS 'hvdnp16e.qintra.com';
comment on column computers_today.computer_dmz                  IS 'N';
comment on column computers_today.computer_ewebars_title        IS 'IBM-UNIX SUPPORT';
comment on column computers_today.computer_ewebars_status       IS 'SUPPORT';
comment on column computers_today.computer_backup_format        IS 'TSM';
comment on column computers_today.computer_backup_nodename      IS 'ahvdnp16eh';
comment on column computers_today.computer_backup_program       IS 'TSM';
comment on column computers_today.computer_backup_server        IS 'aidnb07g.qintra.com';
comment on column computers_today.computer_netbackup            IS '(null)';
comment on column computers_today.computer_complex              IS 'N';
comment on column computers_today.computer_complex_lastid       IS '225693687';
comment on column computers_today.computer_complex_name         IS 'hhdnp85d';
comment on column computers_today.computer_complex_parent_name  IS 'hcdnx11a';
comment on column computers_today.computer_complex_child_names  IS '(null)';
comment on column computers_today.computer_complex_partitions   IS '0';
comment on column computers_today.computer_service_guard        IS 'N';
comment on column computers_today.computer_os_group_contact     IS 'mits-all';
comment on column computers_today.computer_cio_group            IS 'IBM-UNIX SUPPORT';
comment on column computers_today.computer_managing_group       IS 'IBM-UNIX';
comment on column computers_today.computer_contract             IS 'IGS FULL CONTRACT UNIX-PROD';
comment on column computers_today.computer_contract_ref         IS 'C028602';
comment on column computers_today.computer_contract_status      IS '(null)';
comment on column computers_today.computer_contract_status_type IS 'SERVER';
comment on column computers_today.computer_contract_date        IS '01-OCT-12';
comment on column computers_today.computer_ibm_supported        IS 'Y';
comment on column computers_today.computer_gold_server          IS 'Y';
comment on column computers_today.computer_slevel_objective     IS '98';
comment on column computers_today.computer_slevel_score         IS '44.3';
comment on column computers_today.computer_slevel_colors        IS 'GOLD';
comment on column computers_today.computer_special_handling     IS 'N';
comment on column computers_today.computer_applications         IS 'NBA';
comment on column computers_today.computer_osmaint_weekly       IS 'MON,TUE,WED,THU,FRI,SAT,SUN 2200 480';
comment on column computers_today.computer_osmaint_monthly      IS '3 SUN 01:00 240';
comment on column computers_today.computer_osmaint_quarterly    IS 'FEB,MAY,AUG,NOV 3 FRI 22:00 720';
comment on column computers_today.computer_csc_os_banners       IS '1';
comment on column computers_today.computer_csc_pase_banners     IS '1';
comment on column computers_today.computer_csc_dba_banners      IS '0';
comment on column computers_today.computer_csc_fyi_banners      IS '0';
comment on column computers_today.computer_disk_array_alloc_kb  IS '0';
comment on column computers_today.computer_disk_array_used_kb   IS '0';
comment on column computers_today.computer_disk_array_free_kb   IS '0';
comment on column computers_today.computer_disk_local_alloc_kb  IS '0';
comment on column computers_today.computer_disk_local_used_kb   IS '0';
comment on column computers_today.computer_disk_local_free_kb   IS '0';
comment on column computers_today.app_server_assn_sox_critical  IS 'SOX application critical flag';
comment on column computers_today.db_server_assn_sox_critical   IS 'SOX database critical flag';

REM FIX for MITS-ALL

update computers_today set
  computer_cio_group='IBM-UNIX SUPPORT',
  computer_managing_group='IBM-UNIX',
  computer_os_group_contact='mits-all'
where computer_os_group_contact != 'sm-unix' and (computer_cio_group = 'IBM-UNIX SUPPORT' or computer_cio_group = 'MITS-ALL');

update computers_today set
  computer_cio_group='SERVER-MANAGEMENT-UNIX',
  computer_managing_group='SMU'
where computer_os_group_contact = 'sm-unix';

update computers_today set computer_last_update = SYSDATE;

commit;
quit;
