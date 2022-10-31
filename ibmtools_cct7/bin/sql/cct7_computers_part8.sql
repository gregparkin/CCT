set echo on

REM
REM cct7_computers_part8.sql
REM

REM
REM disk_ecc_array_alloc_kb NUMBER,
REM disk_ecc_array_used_kb  NUMBER,
REM disk_ecc_array_free_kb  NUMBER,
REM disk_ecc_local_alloc_kb NUMBER,
REM disk_ecc_local_used_kb  NUMBER,
REM disk_ecc_local_free_kb  NUMBER,
REM backup_format           VARCHAR2(10),
REM backup_nodename         VARCHAR2(1000),
REM backup_program          VARCHAR2(80),
REM backup_server           VARCHAR2(1000),
REM netbackup               VARCHAR2(80)
REM
DECLARE
	v_lastid                  NUMBER;
	v_disk_ecc_array_alloc_kb NUMBER;
	v_disk_ecc_array_used_kb  NUMBER;
	v_disk_ecc_array_free_kb  NUMBER;
	v_disk_ecc_local_alloc_kb NUMBER;
	v_disk_ecc_local_used_kb  NUMBER;
	v_disk_ecc_local_free_kb  NUMBER;
	v_backup_format           VARCHAR2(10);
	v_backup_nodename         VARCHAR2(1000);
	v_backup_program          VARCHAR2(80);
	v_backup_server           VARCHAR2(1000);
	v_netbackup               VARCHAR2(80);

CURSOR c_data_collect IS
	select
		lastid,
		disk_ecc_array_alloc_kb,
		disk_ecc_array_used_kb,
		disk_ecc_array_free_kb,
		disk_ecc_local_alloc_kb,
		disk_ecc_local_used_kb,
		disk_ecc_local_free_kb,
		backup_format,
		backup_nodename,
		backup_program,
		backup_server,
		netbackup
	from
		orion.host;

BEGIN
	OPEN c_data_collect;
	LOOP
    FETCH c_data_collect INTO 
			v_lastid,
			v_disk_ecc_array_alloc_kb,
			v_disk_ecc_array_used_kb,
			v_disk_ecc_array_free_kb,
			v_disk_ecc_local_alloc_kb,
			v_disk_ecc_local_used_kb,
			v_disk_ecc_local_free_kb,
			v_backup_format,
			v_backup_nodename,
			v_backup_program,
			v_backup_server,
			v_netbackup;

    EXIT WHEN c_data_collect%NOTFOUND;

		update new_cct7_computers set
					disk_ecc_array_alloc_kb = v_disk_ecc_array_alloc_kb,
					disk_ecc_array_used_kb  = v_disk_ecc_array_used_kb,
					disk_ecc_array_free_kb  = v_disk_ecc_array_free_kb,
					disk_ecc_local_alloc_kb = v_disk_ecc_local_alloc_kb,
					disk_ecc_local_used_kb  = v_disk_ecc_local_used_kb,
					disk_ecc_local_free_kb  = v_disk_ecc_local_free_kb,
					backup_format           = v_backup_format,
					backup_nodename         = v_backup_nodename,
					backup_program          = v_backup_program,
					backup_server           = v_backup_server,
					netbackup               = v_netbackup
			where
				lastid = v_lastid;

  END LOOP;

  CLOSE c_data_collect;
END;
/

COMMIT;
quit;
