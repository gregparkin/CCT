set echo on

REM
REM cct7_csc_part1.sql
REM
REM Create the new cct_csc table in "new_cct7_csc"
REM

REM
REM  1. Drop new_cct7_csc if it exists
REM

exec drop_table_if_exist('new_cct7_csc');

REM
REM  2. Create new_cct7_csc and copy v_acsys_assign@csc2 into it.
REM
desc csc.v_acsys_assign@csc_cct_web;
create table new_cct7_csc as select * from csc.v_acsys_assign@csc_cct_web;
REM create table new_cct7_csc as select * from v_acsys_assign@csctest;

update new_cct7_csc set
  userid_1 = lower(userid_1),
  userid_2 = lower(userid_2),
  userid_3 = lower(userid_3),
  userid_4 = lower(userid_4),
  userid_5 = lower(userid_5),
  aip_mgr = lower(aip_mgr),
  assign_mgr = lower(assign_mgr);

commit;
		
REM As of 10/21/2011 - v_acsys_assign@csc_cct_web
REM lastid|NUMBER|0||
REM assettag|VARCHAR2|40||
REM maint_weekly|VARCHAR2|80||
REM maint_monthly|VARCHAR2|80||
REM maint_quarterly|VARCHAR2|80||
REM netgroup|VARCHAR2|20||
REM userid_1|VARCHAR2|15||
REM userid_2|VARCHAR2|15||
REM userid_3|VARCHAR2|15||
REM userid_4|VARCHAR2|15||
REM userid_5|VARCHAR2|15||
REM group_name|VARCHAR2|80||
REM group_id|NUMBER|0|NOT NULL|
REM app_acronym|VARCHAR2|80|NOT NULL|
REM aip_mgr|VARCHAR2|1024||
REM sl_objective|NUMBER|5,2||
REM sl_score|NUMBER|4,2||
REM sl_color|VARCHAR2|20||
REM assign_mgr|VARCHAR2|20||
REM cm_special|VARCHAR2|1||

REM
REM  3. Rename the column names in new_cct7_csc
REM
alter table new_cct7_csc rename column assettag to cct_csc_assettag;
alter table new_cct7_csc rename column netgroup to cct_csc_netgroup;
alter table new_cct7_csc rename column userid_1 to cct_csc_userid_1;
alter table new_cct7_csc rename column userid_2 to cct_csc_userid_2;
alter table new_cct7_csc rename column userid_3 to cct_csc_userid_3;
alter table new_cct7_csc rename column userid_4 to cct_csc_userid_4;
alter table new_cct7_csc rename column userid_5 to cct_csc_userid_5;
alter table new_cct7_csc rename column group_name to cct_csc_group_name;
alter table new_cct7_csc rename column group_id to cct_csc_group_id;
alter table new_cct7_csc rename column maint_weekly to cct_csc_osmaint_weekly;
alter table new_cct7_csc rename column maint_monthly to cct_csc_osmaint_monthly;
alter table new_cct7_csc rename column maint_quarterly to cct_csc_osmaint_quarterly;
alter table new_cct7_csc rename column app_acronym to cct_app_acronym;
alter table new_cct7_csc rename column aip_mgr to cct_pase_mgr;
alter table new_cct7_csc rename column sl_objective to cct_sl_objective;
alter table new_cct7_csc rename column sl_score to cct_sl_score;
alter table new_cct7_csc rename column sl_color to cct_sl_color;
alter table new_cct7_csc rename column assign_mgr to cct_assign_mgr;
alter table new_cct7_csc rename column cm_special to cct_special_handling;

REM
REM  4. Create extra fields for the fastpg on-call cuid
REM
alter table new_cct7_csc add ( 
	cct_csc_oncall           VARCHAR2(15), 
	cct_csc_format_weekly    VARCHAR2(256),
	cct_csc_format_monthly   VARCHAR2(256),
	cct_csc_format_quarterly VARCHAR2(256),
	cct_csc_updated          DATE
);

desc new_cct7_csc;

REM
REM  5. Insert the current date and time into cct_csc_updated 
REM

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

REM
REM Exit and run get_maintwin and get_oncall which will populate additional information
REM in table new_cct7_csc. Then you will run part2.sql to move it into place. We don't
REM want to move it into place until it ready so there be very little interruption for
REM the user.
REM

quit;
