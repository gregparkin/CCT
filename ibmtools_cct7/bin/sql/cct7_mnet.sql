set echo on

REM
REM This will die in step 9 if cct7_mnet does not already exist. So create a dummy table if you need too.
REM

REM
REM cct7_mnet.sql
REM

REM
REM 1. Drop tables and indexes if they exist
REM
exec drop_table_if_exist('new_cct7_mnet');
exec drop_table_if_exist('cct7_mnet_backup');

exec drop_index_if_exist('idx_cct7_mnet_1');
exec drop_index_if_exist('idx_cct7_mnet_2');
exec drop_index_if_exist('idx_cct7_mnet_3');
exec drop_index_if_exist('idx_cct7_mnet_4');
exec drop_index_if_exist('idx_cct7_mnet_5');

REM
REM 2. Exit SQL script from on down if we encounter any errors
REM
whenever sqlerror exit sql.sqlcode

REM
REM 3. Create new_cct7_mnet and copy v_new_cct7_mnet into it.
REM
create table new_cct7_mnet as 
	select 
		m.mnet_id,
		m.cuid,
		m.wrkstn_login,
		m.last_name,
		m.first_name,
		m.nick_name,
		m.middle,
		replace(d.DUMMY,'X','FULLNAME') as fullname,
		m.job_title,
		m.smtp_addr,
		m.work_phone,
		m.pager,
		m.street,
		m.city,
		m.state,
		m.country_code,
		m.dept_name,
		m.company_name,
		m.tier1,
		m.tier2,
		m.tier3,
		m.status,
		m.change_date,
		m.mgr_cuid,
		m.outsrc_mgr_cuid
	from 
		ussdb_mnet@mnet m,
		dual d;

REM
REM 4. Rename the column names to change v_asset to new_cct7_mnet.
REM
alter table new_cct7_mnet rename column cuid            to mnet_cuid;
alter table new_cct7_mnet rename column wrkstn_login    to mnet_workstation_login;
alter table new_cct7_mnet rename column last_name       to mnet_last_name;
alter table new_cct7_mnet rename column first_name      to mnet_first_name;
alter table new_cct7_mnet rename column nick_name       to mnet_nick_name;
alter table new_cct7_mnet rename column middle          to mnet_middle;
alter table new_cct7_mnet rename column fullname        to mnet_name;
alter table new_cct7_mnet rename column job_title       to mnet_job_title;
alter table new_cct7_mnet rename column smtp_addr       to mnet_email;
alter table new_cct7_mnet rename column work_phone      to mnet_work_phone;
alter table new_cct7_mnet rename column pager           to mnet_pager;
alter table new_cct7_mnet rename column street          to mnet_street;
alter table new_cct7_mnet rename column city            to mnet_city;
alter table new_cct7_mnet rename column state           to mnet_state;
alter table new_cct7_mnet rename column country_code    to mnet_country;
alter table new_cct7_mnet rename column dept_name       to mnet_rc;
alter table new_cct7_mnet rename column company_name    to mnet_company;
alter table new_cct7_mnet rename column tier1           to mnet_tier1;
alter table new_cct7_mnet rename column tier2           to mnet_tier2;
alter table new_cct7_mnet rename column tier3           to mnet_tier3;
alter table new_cct7_mnet rename column status          to mnet_status;
alter table new_cct7_mnet rename column change_date     to mnet_change_date;
alter table new_cct7_mnet rename column mgr_cuid        to mnet_ctl_cuid;
alter table new_cct7_mnet rename column outsrc_mgr_cuid to mnet_mgr_cuid;


REM
REM 4a. Increase sizes
REM
alter table new_cct7_mnet modify ( mnet_cuid               VARCHAR2(20) );
alter table new_cct7_mnet modify ( mnet_workstation_login  VARCHAR2(20) );
alter table new_cct7_mnet modify ( mnet_last_name          VARCHAR2(80) );
alter table new_cct7_mnet modify ( mnet_first_name         VARCHAR2(80) );
alter table new_cct7_mnet modify ( mnet_nick_name          VARCHAR2(80) );
alter table new_cct7_mnet modify ( mnet_middle             VARCHAR2(20) );
alter table new_cct7_mnet modify ( mnet_name               VARCHAR2(200) );
alter table new_cct7_mnet modify ( mnet_job_title          VARCHAR2(80) );
alter table new_cct7_mnet modify ( mnet_email              VARCHAR2(80) );
alter table new_cct7_mnet modify ( mnet_work_phone         VARCHAR2(20) );
alter table new_cct7_mnet modify ( mnet_pager              VARCHAR2(20) );
alter table new_cct7_mnet modify ( mnet_street             VARCHAR2(80) );
alter table new_cct7_mnet modify ( mnet_city               VARCHAR2(80) );
alter table new_cct7_mnet modify ( mnet_state              VARCHAR2(10) );
alter table new_cct7_mnet modify ( mnet_country            VARCHAR2(10) );
alter table new_cct7_mnet modify ( mnet_company            VARCHAR2(80) );
alter table new_cct7_mnet modify ( mnet_status             VARCHAR2(20) );
alter table new_cct7_mnet modify ( mnet_ctl_cuid           VARCHAR2(15) );
alter table new_cct7_mnet modify ( mnet_mgr_cuid           VARCHAR2(15) );

REM
REM 5. Add some comments
REM
COMMENT ON TABLE new_cct7_mnet                         IS 'Copy of the MNET Database';
COMMENT ON COLUMN new_cct7_mnet.mnet_cuid              IS 'User ID';
COMMENT ON COLUMN new_cct7_mnet.mnet_workstation_login IS 'CTL workstation login';
COMMENT ON COLUMN new_cct7_mnet.mnet_last_name         IS 'Last name';
COMMENT ON COLUMN new_cct7_mnet.mnet_first_name        IS 'First name';
COMMENT ON COLUMN new_cct7_mnet.mnet_nick_name         IS 'Nick name';
COMMENT ON COLUMN new_cct7_mnet.mnet_middle            IS 'Middle name';
COMMENT ON COLUMN new_cct7_mnet.mnet_name              IS 'Full name';
COMMENT ON COLUMN new_cct7_mnet.mnet_job_title         IS 'Job Title';
COMMENT ON COLUMN new_cct7_mnet.mnet_email             IS 'Email Address';
COMMENT ON COLUMN new_cct7_mnet.mnet_work_phone        IS 'Work phone number';
COMMENT ON COLUMN new_cct7_mnet.mnet_pager             IS 'Pager number';
COMMENT ON COLUMN new_cct7_mnet.mnet_street            IS 'Street address';
COMMENT ON COLUMN new_cct7_mnet.mnet_city              IS 'City';
COMMENT ON COLUMN new_cct7_mnet.mnet_state             IS 'State';
COMMENT ON COLUMN new_cct7_mnet.mnet_country           IS 'Country';
COMMENT ON COLUMN new_cct7_mnet.mnet_rc                IS 'QWEST RC Code';
COMMENT ON COLUMN new_cct7_mnet.mnet_company           IS 'Employee Company name';
COMMENT ON COLUMN new_cct7_mnet.mnet_tier1             IS 'CenturyLink Support Tier1';
COMMENT ON COLUMN new_cct7_mnet.mnet_tier2             IS 'CenturyLink Support Tier2';
COMMENT ON COLUMN new_cct7_mnet.mnet_tier3             IS 'CenturyLink Support Tier3';
COMMENT ON COLUMN new_cct7_mnet.mnet_status            IS 'Employee Status';
COMMENT ON COLUMN new_cct7_mnet.mnet_change_date       IS 'Date Record last updated';
COMMENT ON COLUMN new_cct7_mnet.mnet_ctl_cuid          IS 'CenturyLink Sponsor Manager User ID';
COMMENT ON COLUMN new_cct7_mnet.mnet_mgr_cuid          IS 'Manager User ID';

REM
REM  6. Change all CUID's to lowercase
REM
update new_cct7_mnet set 
	mnet_cuid              = lower(mnet_cuid), 
	mnet_workstation_login = lower(mnet_workstation_login),
	mnet_ctl_cuid          = lower(mnet_ctl_cuid),
	mnet_mgr_cuid          = lower(mnet_mgr_cuid);

REM
REM  7. Copy mnet_ctl_cuid into mnet_mgr_cuid if mnet_mgr_cuid is null
REM
update new_cct7_mnet set
	mnet_mgr_cuid = mnet_ctl_cuid,
	mnet_ctl_cuid = ''
	where mnet_mgr_cuid is NULL;

create index idx_mnet_temp1 on new_cct7_mnet ( mnet_cuid );

REM
REM  8. Populate mnet_name with the person's fullname
REM
DECLARE
	v_mnet_cuid       VARCHAR2(20);
	v_mnet_nick_name  VARCHAR2(80);
	v_mnet_first_name VARCHAR2(80);
	v_mnet_last_name  VARCHAR2(80);
	v_mnet_name       VARCHAR2(200);

CURSOR c_xxx IS
	select
		mnet_cuid,
		mnet_nick_name,
		mnet_first_name,
		mnet_last_name
	from
		new_cct7_mnet;

BEGIN
	OPEN c_xxx;
	LOOP
		FETCH c_xxx INTO v_mnet_cuid, v_mnet_nick_name, v_mnet_first_name, v_mnet_last_name;
		EXIT WHEN c_xxx%NOTFOUND;

    IF LENGTH(v_mnet_nick_name) > 0 THEN
      v_mnet_name := v_mnet_nick_name || ' ' || v_mnet_last_name;
    ELSE
      v_mnet_name := v_mnet_first_name || ' ' || v_mnet_last_name;
    END IF;

		UPDATE new_cct7_mnet SET
			mnet_name = v_mnet_name
		WHERE 
			mnet_cuid = v_mnet_cuid;

	END LOOP;

	CLOSE c_xxx;
	COMMIT;
END;
/

REM
REM  9. Add aliases that are no longer defined in MNET
REM     iamcct - Mark S. Vassar
REM
update new_cct7_mnet set mnet_email = 'iamscct@CenturyLink.com' where mnet_cuid = 'iamcct';

exec drop_index_if_exist('idx_mnet_temp1');

REM
REM 10. If new_cct7_mnet has more than 1 record, rename the original mnet table to cct7_mnet_backup
REM     and rename new_cct7_mnet to mnet. Otherwise, continue to use the mnet table from yesterdays run.
REM
declare
	v_num_rows  number;

cursor c_xxx is
	select
		count(*)
	from
		new_cct7_mnet;

begin
	open c_xxx;
	fetch c_xxx into v_num_rows;
	close c_xxx;

	if v_num_rows > 0 then
		execute immediate 'rename cct7_mnet to cct7_mnet_backup';
		execute immediate 'rename new_cct7_mnet to cct7_mnet';
	end if;
end;
/

REM
REM 11. Rebuild the indexes
REM
create index idx_cct7_mnet_1 on cct7_mnet ( mnet_id );
create index idx_cct7_mnet_2 on cct7_mnet ( mnet_cuid );
create index idx_cct7_mnet_3 on cct7_mnet ( mnet_workstation_login );
create index idx_cct7_mnet_4 on cct7_mnet ( mnet_last_name );
create index idx_cct7_mnet_5 on cct7_mnet ( mnet_status );
create index idx_cct7_mnet_6 on cct7_mnet ( mnet_email );

desc cct7_mnet
select count(*) as record_count from cct7_mnet;

quit;
