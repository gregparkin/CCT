drop table cct6_computers;
drop table cct6_csc;
drop table cct6_mnet;
drop table cct6_computer_status;
drop table cct6_databases;
drop table cct6_os_lite;
drop table cct6_platform;
drop table cct6_state_city;
drop table cct6_applications;

create table cct6_computers as select * from cct6_computers@ibmtoolp.corp.intranet;
create table cct6_csc as select * from cct6_csc@ibmtoolp.corp.intranet;
create table cct6_mnet as select * from cct6_mnet@ibmtoolp.corp.intranet;
create table cct6_computer_status as select * from cct6_computer_status@ibmtoolp.corp.intranet;
create table cct6_databases as select * from cct6_databases@ibmtoolp.corp.intranet;
create table cct6_os_lite as select * from cct6_os_lite@ibmtoolp.corp.intranet;
create table cct6_platform as select * from cct6_platform@ibmtoolp.corp.intranet;
create table cct6_state_city as select * from cct6_state_city@ibmtoolp.corp.intranet;
create table cct6_applications as select * from cct6_applications@ibmtoolp.corp.intranet;

REM cct6_computer_status
comment on table cct6_computer_status is 'Used in new_work_request_step3.php - List of computer status: PRODUCTION, DEVELOPMENT, etc.';
COMMENT ON COLUMN cct6_computer_status.computer_status    IS 'computers.computer_status - (i.e. PRODUCTION)';

REM cct6_csc
create index idx_cct6_csc_1 on cct6_csc ( cct_csc_assettag );
create index idx_cct6_csc_2 on cct6_csc ( cct_csc_group_name );
create index idx_cct6_csc_3 on cct6_csc ( cct_csc_netgroup );
create index idx_cct6_csc_4 on cct6_csc ( lastid );
create index idx_cct6_csc_5 on cct6_csc ( cct_csc_group_name, cct_csc_userid_1 );
create index idx_cct6_csc_6 on cct6_csc ( cct_app_acronym, lastid );

REM cct6_mnet
create index idx_cct6_mnet_1 on cct6_mnet ( mnet_id );
create index idx_cct6_mnet_2 on cct6_mnet ( mnet_cuid );
create index idx_cct6_mnet_3 on cct6_mnet ( mnet_last_name );
create index idx_cct6_mnet_4 on cct6_mnet ( mnet_status );
COMMENT ON TABLE cct6_mnet IS 'Copy of the MNET Database';
COMMENT ON COLUMN cct6_mnet.mnet_cuid IS 'User ID';
COMMENT ON COLUMN cct6_mnet.mnet_last_name IS 'Last name';
COMMENT ON COLUMN cct6_mnet.mnet_first_name IS 'First name';
COMMENT ON COLUMN cct6_mnet.mnet_nick_name IS 'Nick name';
COMMENT ON COLUMN cct6_mnet.mnet_middle IS 'Middle name';
COMMENT ON COLUMN cct6_mnet.mnet_name IS 'Full name';
COMMENT ON COLUMN cct6_mnet.mnet_job_title IS 'Job Title';
COMMENT ON COLUMN cct6_mnet.mnet_email IS 'Email Address';
COMMENT ON COLUMN cct6_mnet.mnet_work_phone IS 'Work phone number';
COMMENT ON COLUMN cct6_mnet.mnet_pager IS 'Pager number';
COMMENT ON COLUMN cct6_mnet.mnet_street IS 'Street address';
COMMENT ON COLUMN cct6_mnet.mnet_city IS 'City';
COMMENT ON COLUMN cct6_mnet.mnet_state IS 'State';
COMMENT ON COLUMN cct6_mnet.mnet_rc IS 'QWEST RC Code';
COMMENT ON COLUMN cct6_mnet.mnet_company IS 'Employee Company name';
COMMENT ON COLUMN cct6_mnet.mnet_tier1 IS 'CenturyLink Support Tier1';
COMMENT ON COLUMN cct6_mnet.mnet_tier2 IS 'CenturyLink Support Tier2';
COMMENT ON COLUMN cct6_mnet.mnet_tier3 IS 'CenturyLink Support Tier3';
COMMENT ON COLUMN cct6_mnet.mnet_status IS 'Employee Status';
COMMENT ON COLUMN cct6_mnet.mnet_change_date IS 'Date Record last updated';
COMMENT ON COLUMN cct6_mnet.mnet_ctl_cuid IS 'CenturyLink Sponsor Manager User ID';
COMMENT ON COLUMN cct6_mnet.mnet_mgr_cuid IS 'Manager User ID';

REM cct6_computer_status - nothing to do

REM cct6_databases
create index idx_cct6_databases1 on cct6_databases ( computer_lastid );
create index idx_cct6_databases2 on cct6_databases ( computer_hostname );
create index idx_cct6_databases3 on cct6_databases ( computer_asset_tag );
create index idx_cct6_databases4 on cct6_databases ( computer_serial_no );
create index idx_cct6_databases5 on cct6_databases ( database_name );

REM cct6_os_liste - nothing to do

REM cct6_platform - nothing to do

REM cct6_state_city - nothing to do

REM cct6_applications
create index idx_cct6_applications1 on new_cct6_applications ( computer_lastid );
create index idx_cct6_applications2 on new_cct6_applications ( computer_hostname );
create index idx_cct6_applications3 on new_cct6_applications ( computer_asset_tag );
create index idx_cct6_applications4 on new_cct6_applications ( application_instance );
create index idx_cct6_applications5 on new_cct6_applications ( associated_app_appid );
COMMENT ON TABLE cct6_applications IS 'Application List to Server plus upstream, downstream application information';
COMMENT ON COLUMN cct6_applications.computer_lastid IS 'Computer LASTID number';
COMMENT ON COLUMN cct6_applications.computer_hostname IS 'Hostname that application resides on';
COMMENT ON COLUMN cct6_applications.computer_asset_tag IS 'Server asset tag number';
COMMENT ON COLUMN cct6_applications.computer_componentid IS 'Computer componentid (same as computer_hostname)';
COMMENT ON COLUMN cct6_applications.application_instance IS 'Application instance';
COMMENT ON COLUMN cct6_applications.application_name IS 'Application name';
COMMENT ON COLUMN cct6_applications.application_acronym IS 'Application acronym';
COMMENT ON COLUMN cct6_applications.application_appid IS 'Application APPID number';
COMMENT ON COLUMN cct6_applications.application_lastid IS 'Application LASTID number (not the same as computer_lastid)';
COMMENT ON COLUMN cct6_applications.application_type IS 'Applicate type: INPUT or OUTPUT';
COMMENT ON COLUMN cct6_applications.associated_computer_lastid IS 'Assoicated computer LASTID number';
COMMENT ON COLUMN cct6_applications.associated_computer_hostname IS 'Associated computer hostname';
COMMENT ON COLUMN cct6_applications.associated_computer_asset_tag IS 'Associated computer asset tag number';
COMMENT ON COLUMN cct6_applications.associated_app_name IS 'Associated application name';
COMMENT ON COLUMN cct6_applications.associated_app_slr IS 'Associated application service level: GOLD, BRONSE, etc.';
COMMENT ON COLUMN cct6_applications.associated_app_assignment IS 'Associated application assignment: PRODUCTION or PRE-PRODUCTION';
COMMENT ON COLUMN cct6_applications.associated_app_appid IS 'Assoicated application APPID number';
COMMENT ON COLUMN cct6_applications.associated_app_lastid IS 'Assoicated application LAST number (not the same as computer_lastid)';

delete from cct6_tickets;
delete from cct6_systems;
delete from cct6_event_log;
delete from cct6_contacts;
delete from cct6_list_names;
delete from cct6_list_systems;
delete from cct6_assign_groups;
delete from cct6_auto_approve;
delete from cct6_classifications;
delete from cct6_contract;
delete from cct6_debugging;
delete from cct6_email_spool;
delete from cct6_managing_group;
delete from cct6_master_approvers;
delete from cct6_oncall_overrides;
delete from cct6_page_spool;
delete from cct6_subscriber_lists;
delete from cct6_user_profiles;

insert into cct6_tickets select * from cct6_tickets@ibmtoolp.corp.intranet;
insert into cct6_systems select * from cct6_systems@ibmtoolp.corp.intranet;
insert into cct6_event_log select * from cct6_event_log@ibmtoolp.corp.intranet;
insert into cct6_contacts select * from cct6_contacts@ibmtoolp.corp.intranet;
insert into cct6_list_names select * from cct6_list_names@ibmtoolp.corp.intranet;
insert into cct6_list_systems select * from cct6_list_systems@ibmtoolp.corp.intranet;
insert into cct6_assign_groups select * from cct6_assign_groups@ibmtoolp.corp.intranet;
insert into cct6_auto_approve select * from cct6_auto_approve@ibmtoolp.corp.intranet;
insert into cct6_classifications select * from cct6_classifications@ibmtoolp.corp.intranet;
insert into cct6_contract select * from cct6_contract@ibmtoolp.corp.intranet;
insert into cct6_debugging select * from cct6_debugging@ibmtoolp.corp.intranet;
insert into cct6_email_spool select * from cct6_email_spool@ibmtoolp.corp.intranet;
insert into cct6_managing_group select * from cct6_managing_group@ibmtoolp.corp.intranet;
insert into cct6_master_approvers select * from cct6_master_approvers@ibmtoolp.corp.intranet;
insert into cct6_oncall_overrides select * from cct6_oncall_overrides@ibmtoolp.corp.intranet;
insert into cct6_page_spool select * from cct6_page_spool@ibmtoolp.corp.intranet;
insert into cct6_subscriber_lists select * from cct6_subscriber_lists@ibmtoolp.corp.intranet;
insert into cct6_user_profiles select * from cct6_user_profiles@ibmtoolp.corp.intranet;

REM
REM Rebuild sequence tables
REM

drop sequence cct6_test_ticketseq;
create sequence cct6_test_ticketseq increment by 1 start with 100000 nocache;

REM cct6_systems

drop sequence cct6_systemsseq;

DECLARE
  v_max_system_id  NUMBER;
  v_create          VARCHAR2(200);

CURSOR c_xxx IS
  select max(system_id)+1 as max_system_id from cct6_systems;

BEGIN
  OPEN c_xxx;

  FETCH c_xxx INTO v_max_system_id;

  v_create := 'create sequence cct6_systemsseq increment by 1 start with ' || to_char(v_max_system_id) || ' nocache';

  EXECUTE IMMEDIATE v_create;

  close c_xxx;

  commit;
END;

/

REM cct6_contacts

drop sequence cct6_contactsseq;

DECLARE
  v_max_contact_id  NUMBER;
  v_create          VARCHAR2(200);

CURSOR c_xxx IS
  select max(contact_id)+1 as max_contact_id from cct6_contacts;

BEGIN
  OPEN c_xxx;

  FETCH c_xxx INTO v_max_contact_id;

  v_create := 'create sequence cct6_contactsseq increment by 1 start with ' || to_char(v_max_contact_id) || ' nocache';

  EXECUTE IMMEDIATE v_create;

  close c_xxx;

  commit;
END;
/

REM cct6_event_log - no sequence table

REM cct6_list_names

drop sequence cct6_list_namesseq;

DECLARE
  v_max_list_name_id  NUMBER;
  v_create          VARCHAR2(200);

CURSOR c_xxx IS
  select max(list_name_id)+1 as max_list_name_id from cct6_list_names;

BEGIN
  OPEN c_xxx;

  FETCH c_xxx INTO v_max_list_name_id;

  v_create := 'create sequence cct6_list_namesseq increment by 1 start with ' || to_char(v_max_list_name_id) || ' nocache';

  EXECUTE IMMEDIATE v_create;

  close c_xxx;

  commit;
END;

/

REM cct6_list_systems

drop sequence cct6_list_systemsseq;

DECLARE
  v_max_list_system_id  NUMBER;
  v_create          VARCHAR2(200);

CURSOR c_xxx IS
  select max(list_system_id)+1 as max_list_system_id from cct6_list_systems;

BEGIN
  OPEN c_xxx;

  FETCH c_xxx INTO v_max_list_system_id;

  v_create := 'create sequence cct6_list_systemsseq increment by 1 start with ' || to_char(v_max_list_system_id) || ' nocache';

  EXECUTE IMMEDIATE v_create;

  close c_xxx;

  commit;
END;

/

REM cct6_assign_groups - no sequence table

REM cct6_auto_approve

drop sequence cct6_auto_approveseq;

DECLARE
  v_max_auto_approve_id  NUMBER;
  v_create          VARCHAR2(200);

CURSOR c_xxx IS
  select max(auto_approve_id)+1 as max_auto_approve_id from cct6_auto_approve;

BEGIN
  OPEN c_xxx;

  FETCH c_xxx INTO v_max_auto_approve_id;

  v_create := 'create sequence cct6_auto_approveseq increment by 1 start with ' || to_char(v_max_auto_approve_id) || ' nocache';

  EXECUTE IMMEDIATE v_create;

  close c_xxx;

  commit;
END;

/

REM cct6_classifications - no sequence table

REM cct6_contract - no sequence table

REM cct6_debugging - no sequence table

REM cct6_email_spool

drop sequence cct6_email_spoolseq;

DECLARE
  v_max_email_spool_id  NUMBER;
  v_create          VARCHAR2(200);

CURSOR c_xxx IS
  select max(email_spool_id)+1 as max_email_spool_id from cct6_email_spool;

BEGIN
  OPEN c_xxx;

  FETCH c_xxx INTO v_max_email_spool_id;

  v_create := 'create sequence cct6_email_spoolseq increment by 1 start with ' || to_char(v_max_email_spool_id) || ' nocache';

  EXECUTE IMMEDIATE v_create;

  close c_xxx;

  commit;
END;

/

REM cct6_managing_group - no sequence table

REM cct6_master_approvers - no sequence table

REM cct6_oncall_overrides - no sequence table

REM cct6_page_spool

drop sequence cct6_page_spoolseq;

DECLARE
  v_max_page_spool_id  NUMBER;
  v_create          VARCHAR2(200);

CURSOR c_xxx IS
  select max(page_spool_id)+1 as max_page_spool_id from cct6_page_spool;

BEGIN
  OPEN c_xxx;

  FETCH c_xxx INTO v_max_page_spool_id;

  v_create := 'create sequence cct6_page_spoolseq increment by 1 start with ' || to_char(v_max_page_spool_id) || ' nocache';

  EXECUTE IMMEDIATE v_create;

  close c_xxx;

  commit;
END;

/

REM cct6_subscriber_lists

drop sequence cct6_subscriber_listsseq;

DECLARE
  v_max_subscriber_list_id  NUMBER;
  v_create          VARCHAR2(200);

CURSOR c_xxx IS
  select max(subscriber_list_id)+1 as max_subscriber_list_id from cct6_subscriber_lists;

BEGIN
  OPEN c_xxx;


  FETCH c_xxx INTO v_max_subscriber_list_id;

  v_create := 'create sequence cct6_subscriber_listsseq increment by 1 start with ' || to_char(v_max_subscriber_list_id) || ' nocache';

  EXECUTE IMMEDIATE v_create;

  close c_xxx;

  commit;
END;

/

REM cct6_user_profiles - no sequence table