set echo on

-- 
--  cct7_csc_part1.sql
-- 
--  Create the new cct_csc table in "new_cct7_csc"
-- 

-- 
--   1. Drop new_cct7_csc if it exists
-- 
drop table new_cct7_csc;

-- 
--   2. Create new_cct7_csc and copy v_acsys_assign@csc2 into it.
-- 
desc csc.v_acsys_assign@csc_cct_web;
create table new_cct7_csc as select * from csc.v_acsys_assign@csc_cct_web;
--  create table new_cct7_csc as select * from v_acsys_assign@csctest;

update new_cct7_csc set
  userid_1   = lower(userid_1),
  userid_2   = lower(userid_2),
  userid_3   = lower(userid_3),
  userid_4   = lower(userid_4),
  userid_5   = lower(userid_5),
  aip_mgr    = lower(aip_mgr),
  assign_mgr = lower(assign_mgr);

commit;
		
--  As of 10/21/2011 - v_acsys_assign@csc_cct_web
--  lastid|NUMBER|0||
--  assettag|VARCHAR2|40||
--  maint_weekly|VARCHAR2|80||
--  maint_monthly|VARCHAR2|80||
--  maint_quarterly|VARCHAR2|80||
--  netgroup|VARCHAR2|20||
--  userid_1|VARCHAR2|15||
--  userid_2|VARCHAR2|15||
--  userid_3|VARCHAR2|15||
--  userid_4|VARCHAR2|15||
--  userid_5|VARCHAR2|15||
--  group_name|VARCHAR2|80||
--  group_id|NUMBER|0|NOT NULL|
--  app_acronym|VARCHAR2|80|NOT NULL|
--  aip_mgr|VARCHAR2|1024||
--  sl_objective|NUMBER|5,2||
--  sl_score|NUMBER|4,2||
--  sl_color|VARCHAR2|20||
--  assign_mgr|VARCHAR2|20||
--  cm_special|VARCHAR2|1||

-- 
--   3. Rename the column names in new_cct7_csc
-- 
alter table new_cct7_csc rename column assettag        to cct_csc_assettag;
alter table new_cct7_csc rename column netgroup        to cct_csc_netgroup;
alter table new_cct7_csc rename column userid_1        to cct_csc_userid_1;
alter table new_cct7_csc rename column userid_2        to cct_csc_userid_2;
alter table new_cct7_csc rename column userid_3        to cct_csc_userid_3;
alter table new_cct7_csc rename column userid_4        to cct_csc_userid_4;
alter table new_cct7_csc rename column userid_5        to cct_csc_userid_5;
alter table new_cct7_csc rename column group_name      to cct_csc_group_name;
alter table new_cct7_csc rename column group_id        to cct_csc_group_id;
alter table new_cct7_csc rename column maint_weekly    to cct_csc_osmaint_weekly;
alter table new_cct7_csc rename column maint_monthly   to cct_csc_osmaint_monthly;
alter table new_cct7_csc rename column maint_quarterly to cct_csc_osmaint_quarterly;
alter table new_cct7_csc rename column app_acronym     to cct_app_acronym;
alter table new_cct7_csc rename column aip_mgr         to cct_pase_mgr;
alter table new_cct7_csc rename column sl_objective    to cct_sl_objective;
alter table new_cct7_csc rename column sl_score        to cct_sl_score;
alter table new_cct7_csc rename column sl_color        to cct_sl_color;
alter table new_cct7_csc rename column assign_mgr      to cct_assign_mgr;
alter table new_cct7_csc rename column cm_special      to cct_special_handling;

-- 
--   4. Create extra fields for the fastpg on-call cuid
-- 
alter table new_cct7_csc add ( 
	cct_csc_oncall           VARCHAR2(15), 
	cct_csc_format_weekly    VARCHAR2(256),
	cct_csc_format_monthly   VARCHAR2(256),
	cct_csc_format_quarterly VARCHAR2(256),
	cct_csc_updated          DATE
);

desc new_cct7_csc;

-- 
--   5. Insert the current date and time into cct_csc_updated 
-- 

DECLARE
	v_today       DATE;

CURSOR c_today IS select SYSDATE from dual;

BEGIN
	OPEN c_today;

	FETCH c_today INTO v_today;

	update new_cct7_csc set cct_csc_updated = v_today;

	CLOSE c_today;
END;
/

select distinct to_char(cct_csc_updated, 'MM/DD/YY HH24:MI') from new_cct7_csc;
select count(*) from new_cct7_csc;

-- 
--  Exit and run get_maintwin and get_oncall which will populate additional information
--  in table new_cct7_csc. Then you will run part2.sql to move it into place. We don't
--  want to move it into place until it ready so there be very little interruption for
--  the user.
-- 

set echo on

-- 
--  cct7_csc_part2.sql
-- 

-- 
--   6. Drop backup database table for cct7_csc_backup
-- 
drop table cct7_csc_backup;

-- 
--   7. Rename original cct7_csc to cct7_csc_backup
-- 
rename cct7_csc to cct7_csc_backup;

-- 
--   8. Rename new_cct7_csc and cct7_csc
-- 
rename new_cct7_csc to cct7_csc;

-- 
--   9. Drop old indexes
-- 
drop index idx_cct7_csc_1;
drop index idx_cct7_csc_2;
drop index idx_cct7_csc_3;
drop index idx_cct7_csc_4;
drop index idx_cct7_csc_5;
drop index idx_cct7_csc_6;

-- 
--  10. Create new indexes
-- 
create index idx_cct7_csc_1 on cct7_csc ( cct_csc_assettag );
create index idx_cct7_csc_2 on cct7_csc ( cct_csc_group_name );
create index idx_cct7_csc_3 on cct7_csc ( cct_csc_netgroup );
create index idx_cct7_csc_4 on cct7_csc ( lastid );
create index idx_cct7_csc_5 on cct7_csc ( cct_csc_group_name, cct_csc_userid_1 );
create index idx_cct7_csc_6 on cct7_csc ( cct_app_acronym, lastid );

-- 
--  11. grant select access to other oracle accounts
-- 
grant select on cct7_csc to public;

select count(*) as record_count from cct7_csc;

quit;

