set echo on

REM
REM cct7_computers_part2.sql
REM

REM
REM Initialize with some default information before we populate what we can with 
REM the PL/SQL code below.
REM
update new_cct7_computers set
	last_record_update = SYSDATE,
	complex_partitions = 0,
	is_complex = 'N',
	is_ibm_supported = 'N',
	is_dmz = 'N',
	is_gold_server = 'N',
	is_service_guard = 'N',
	is_special_handling = 'N',
	timezone = 'GMT',
	osmaint_weekly = 'TUE,THU 02:00 180',
	osmaint_monthly = '2 SUN 00:00 240',
	osmaint_quarterly = 'FEB,MAY,AUG,NOV 3 SAT 22:00 1440',
	service_level_objective = 0,
	service_level_score = 0,
	csc_os_banners = 0,
	csc_pase_banners = 0,
	csc_dba_banners = 0,
	csc_fyi_banners = 0,
	disk_ecc_array_alloc_kb = 0,
	disk_ecc_array_used_kb = 0,
	disk_ecc_array_free_kb = 0,
	disk_ecc_local_alloc_kb = 0,
	disk_ecc_local_used_kb = 0,
	disk_ecc_local_free_kb = 0,
	app_server_assn_sox_critical = 0,
	db_server_assn_sox_critical = 0;

REM
REM computer_contract              VARCHAR2(80),
REM computer_contract_ref          VARCHAR2(20),
REM computer_contract_status       VARCHAR2(43),
REM computer_contract_status_type  VARCHAR2(80),
REM computer_contract_date         DATE,
REM
DECLARE
	v_server_lastid       number;
	v_ibm_contract        varchar2(80);
	v_ibm_ref             varchar2(20);
	v_qw_contract_status  varchar2(43);
	v_status              varchar2(80);
	v_include_date        date;

CURSOR c_contracts IS
	select
		server_lastid,
		ibm_contract,
		ibm_ref,
		qw_contract_status,
		status,
		include_date
	from
		AM_CONTRACTS
	where
		removed_date is null;

BEGIN
	OPEN c_contracts;
	LOOP
		FETCH c_contracts INTO
			v_server_lastid,
			v_ibm_contract,
			v_ibm_ref,
			v_qw_contract_status,
			v_status,
			v_include_date;

		EXIT WHEN c_contracts%NOTFOUND;

		update new_cct7_computers set
			contract = v_ibm_contract,
			contract_ref = v_ibm_ref,
			contract_status = v_qw_contract_status,
			contract_status_type = v_status,
			contract_date = v_include_date
			where lastid = v_server_lastid;

	END LOOP;

	CLOSE c_contracts;
END;
/

commit;
quit;
