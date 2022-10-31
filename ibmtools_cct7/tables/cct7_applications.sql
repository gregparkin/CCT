set echo on
WHENEVER SQLERROR CONTINUE

REM
REM See: /opt/ibmtools/cct7/bin/sql/cct7_applications.sql
REM

create table cct7_applications
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

insert into cct7_applications select * from cct6_applications;
select count(*) as cct7_applications from cct7_applications;

commit;
