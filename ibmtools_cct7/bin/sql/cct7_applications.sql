
set echo on

exec drop_table_if_exist('new_cct7_applications');

create table new_cct7_applications 
(
  computer_lastid                NUMBER NOT NULL,
  computer_hostname              VARCHAR2(40),
  computer_asset_tag             VARCHAR2(40),
  computer_componentid           VARCHAR2(80),
  application_instance           VARCHAR2(80),
  application_name               VARCHAR2(80),
  application_acronym            VARCHAR2(35),
  application_appid              VARCHAR2(40),
  application_lastid             NUMBER,
  application_type               VARCHAR2(80),
  associated_computer_lastid     NUMBER,
  associated_computer_hostname   VARCHAR2(40),
  associated_computer_asset_tag  VARCHAR2(40),
  associated_app_name            VARCHAR2(80),
  associated_app_slr             VARCHAR2(43),
  associated_app_assignment      VARCHAR2(40),
  associated_app_appid           VARCHAR2(40),
  associated_app_lastid          NUMBER  
);

create index idx_new_cct7_applications1 on new_cct7_applications ( associated_app_appid );

insert into new_cct7_applications
(computer_lastid, computer_hostname, computer_asset_tag, computer_componentid, 
application_instance, application_name, application_acronym, application_appid,
application_type, associated_app_name, associated_app_slr, associated_app_assignment,
associated_app_appid, associated_app_lastid )
select
  a.server_lastid         as computer_lastid,
  a.hostnm                as computer_hostname,
  a.hostassettag          as computer_asset_tag,
  a.componentid           as computer_componentid,
  a.appinstance           as application_instance,
  a.appnm                 as application_name,
  a.acronym               as application_acronym,
  a.appid                 as application_appid,
  b.type                  as application_type,
  b.associated_app_name   as associated_app_name,
  b.associated_app_slr    as associated_app_slr,
  case b.associated_app_assignment
    when 0 then 'PRODUCTION'
    when 6 then 'PRE-PRODUCTION'
    else        null
  end                     as associated_app_assignment,
  b.associated_app_appid  as associated_app_appid,
  b.associated_app_lastid as associated_app_lastid
from
  cs_qwestibm_maltoserver@itast a,
  cs_qwestibm_mal_apptoapp@itast b
where 
  a.appid = b.appid (+) 
order by
  a.hostnm, a.appnm;

DECLARE
  v_computer_lastid    NUMBER;
  v_computer_hostname  VARCHAR2(40);
  v_computer_asset_tag VARCHAR2(40);
  v_application_appid  VARCHAR2(40);
  
CURSOR c_xxx IS
  select distinct
    server_lastid as computer_lastid,
    hostnm        as computer_hostname,
    hostassettag  as computer_asset_tag,
    appid         as application_appid
  from
    cs_qwestibm_maltoserver@itast;
    
BEGIN
  OPEN c_xxx;
  LOOP
    FETCH c_xxx INTO
      v_computer_lastid,
      v_computer_hostname,
      v_computer_asset_tag,
      v_application_appid;
      
    EXIT WHEN c_xxx%NOTFOUND;
    
    update new_cct7_applications set
      associated_computer_lastid     = v_computer_lastid,
      associated_computer_hostname   = v_computer_hostname,
      associated_computer_asset_tag  = v_computer_asset_tag
    where
      associated_app_appid = v_application_appid;
      
  END LOOP;
  
  CLOSE c_xxx;
END;
/

drop table cct7_applications;

rename new_cct7_applications to cct7_applications;

drop index idx_new_cct7_applications1;

create index idx_cct7_applications1 on cct7_applications ( computer_lastid );
create index idx_cct7_applications2 on cct7_applications ( computer_hostname );
create index idx_cct7_applications3 on cct7_applications ( computer_asset_tag );
create index idx_cct7_applications4 on cct7_applications ( application_instance );
create index idx_cct7_applications5 on cct7_applications ( associated_app_appid );

COMMENT ON TABLE cct7_applications IS 'Application List to Server plus upstream, downstream application information';

COMMENT ON COLUMN cct7_applications.computer_lastid IS 'Computer LASTID number';
COMMENT ON COLUMN cct7_applications.computer_hostname IS 'Hostname that application resides on';
COMMENT ON COLUMN cct7_applications.computer_asset_tag IS 'Server asset tag number';
COMMENT ON COLUMN cct7_applications.computer_componentid IS 'Computer componentid (same as computer_hostname)';
COMMENT ON COLUMN cct7_applications.application_instance IS 'Application instance';
COMMENT ON COLUMN cct7_applications.application_name IS 'Application name';
COMMENT ON COLUMN cct7_applications.application_acronym IS 'Application acronym';
COMMENT ON COLUMN cct7_applications.application_appid IS 'Application APPID number';
COMMENT ON COLUMN cct7_applications.application_lastid IS 'Application LASTID number (not the same as computer_lastid)';
COMMENT ON COLUMN cct7_applications.application_type IS 'Applicate type: INPUT or OUTPUT';
COMMENT ON COLUMN cct7_applications.associated_computer_lastid IS 'Assoicated computer LASTID number';
COMMENT ON COLUMN cct7_applications.associated_computer_hostname IS 'Associated computer hostname';
COMMENT ON COLUMN cct7_applications.associated_computer_asset_tag IS 'Associated computer asset tag number';
COMMENT ON COLUMN cct7_applications.associated_app_name IS 'Associated application name';
COMMENT ON COLUMN cct7_applications.associated_app_slr IS 'Associated application service level: GOLD, BRONSE, etc.';
COMMENT ON COLUMN cct7_applications.associated_app_assignment IS 'Associated application assignment: PRODUCTION or PRE-PRODUCTION';
COMMENT ON COLUMN cct7_applications.associated_app_appid IS 'Assoicated application APPID number';
COMMENT ON COLUMN cct7_applications.associated_app_lastid IS 'Assoicated application LAST number (not the same as computer_lastid)';

commit;
select count(*) as record_count from cct7_applications;
quit;
