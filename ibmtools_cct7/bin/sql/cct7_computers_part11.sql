set echo on

REM
REM cct7_computers_part11.sql
REM

REM
REM Remove the backup copies of asset and computers
REM
exec drop_table_if_exist('cct7_computers_backup');

REM
REM From here on down we want to terminate the script if the following
REM two SQLPL code blocks RAISE_APPLICATION_ERROR
REM
REM whenever sqlerror exit sql.sqlcode

REM
REM Copy the new computers_today to computers if computers_today exists and
REM has more than 1 record. Otherwise, keep the original computers.
REM
DECLARE
	v_count NUMBER;
	v_rows  NUMBER;

CURSOR c_xx1 IS
	select
		count(*)
	from
		dba_tables
	where
		lower(table_name) = 'computers_today';

CURSOR c_xx2 IS
	select
		count(*)
	from
		computers_today;

BEGIN
	OPEN c_xx1;
	FETCH c_xx1 INTO v_count;
	dbms_output.put_line(v_count);
	CLOSE c_xx1;

	if v_count > 0 then
		OPEN c_xx2;
		FETCH c_xx2 INTO v_rows;
		CLOSE c_xx2;
		if v_rows > 0 then
			dbms_output.put_line('computers_today exists and has more than 1 rows');
			execute immediate 'rename cct7_computers to cct7_computers_backup';
			execute immediate 'rename computers_today to cct7_computers';
		end if;
	end if;
END;
/

REM
REM Drop the indexes from cct7_computers_backup
REM
exec drop_index_if_exist('idx_cct7_computers_1');
exec drop_index_if_exist('idx_cct7_computers_2');
exec drop_index_if_exist('idx_cct7_computers_3');
exec drop_index_if_exist('idx_cct7_computers_4');
exec drop_index_if_exist('idx_cct7_computers_5');
exec drop_index_if_exist('idx_cct7_computers_6');
exec drop_index_if_exist('idx_cct7_computers_7');
exec drop_index_if_exist('idx_cct7_computers_8');
exec drop_index_if_exist('idx_cct7_computers_9');
exec drop_index_if_exist('idx_cct7_computers_10');

REM
REM Create new indexes for cct7_computers
REM
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

REM
REM Cleanup temp files
REM

exec drop_table_if_exist('new_cct7_computers');
exec drop_table_if_exist('AM_COMPUTERS');
exec drop_table_if_exist('AM_CONTRACTS');
exec drop_table_if_exist('AM_AP');
exec drop_table_if_exist('AM_A7');
exec drop_table_if_exist('AM_C9');
exec drop_table_if_exist('AM_PR');

commit;
select count(*) as record_count from cct7_computers;
quit;

