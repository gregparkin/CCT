WHENEVER SQLERROR CONTINUE
set echo on

CREATE TABLE cct7_users
(
  user_cuid                 varchar2(20)      PRIMARY KEY,
  insert_cuid               varchar2(20),
  insert_name               varchar2(200),
  insert_date               number,
  update_cuid               varchar2(20),
  update_name               varchar2(200),
  update_date               number,
  local_timezone            varchar2(80)      default 'America/Denver (MST)' not null,
  local_timezone_name       varchar2(80)      default 'America/Denver'       not null,
  local_timezone_abbr       varchar2(10)      default 'MST'                  not null,
  local_timezone_offset     number            default 0                      not null,
  baseline_timezone         varchar2(80)      default 'America/Denver (MST)' not null,
  baseline_timezone_name    varchar2(80)      default 'America/Denver'       not null,
  baseline_timezone_abbr    varchar2(10)      default 'MST'                  not null,
  baseline_timezone_offset  number            default 0                      not null,
  time_difference           varchar2(80)      default '0 seconds or 0 hours' not null,
  sql_zone_offset           varchar2(80)      default '(0)'                  not null,
  sql_zone_abbr             varchar2(10)      default 'MST'                  not null,
  inbox_netpins             varchar2(2000),
  user_or_admin             varchar2(10)      default 'User'                 not null,
  last_login_date           number,
  http_user_agent           varchar2(500),
  disable_email             varchar2(10)      default 'N'                    not null,
  disable_pages             varchar2(10)      default 'N'                    not null,
  is_debug_on               varchar2(1)       default 'N'                    not null,
  debug_level1              varchar2(1)       default 'Y'                    not null,
  debug_level2              varchar2(1)       default 'Y'                    not null,
  debug_level3              varchar2(1)       default 'Y'                    not null,
  debug_level4              varchar2(1)       default 'Y'                    not null,
  debug_level5              varchar2(1)       default 'Y'                    not null,
  debug_path                varchar2(256)     default '/opt/ibmtools/cct7/debug/'     not null,
  debug_mode                varchar2(10)      default 'w'                    not null,
  pref_toolbar_open         varchar2(10)      default 'group'                not null
);

COMMENT ON TABLE cct7_users                            IS 'CCT user settings information';
COMMENT ON COLUMN cct7_users.user_cuid                 IS 'PRIMARY KEY - unique record CUID';
COMMENT ON COLUMN cct7_users.insert_cuid               IS 'CUID of person who created this record';
COMMENT ON COLUMN cct7_users.insert_name               IS 'Name of person who created this record';
COMMENT ON COLUMN cct7_users.insert_date               IS 'Date of person who created this record';
COMMENT ON COLUMN cct7_users.update_cuid               IS 'CUID of person who updated this record';
COMMENT ON COLUMN cct7_users.update_name               IS 'Name of person who updated this record';
COMMENT ON COLUMN cct7_users.update_date               IS 'Date when this record was last updated';
COMMENT ON COLUMN cct7_users.local_timezone            IS 'America/Chicago (CDT)';
COMMENT ON COLUMN cct7_users.local_timezone_name       IS 'America/Chicago';
COMMENT ON COLUMN cct7_users.local_timezone_abbr       IS 'CDT';
COMMENT ON COLUMN cct7_users.local_timezone_offset     IS '-18000';
COMMENT ON COLUMN cct7_users.baseline_timezone         IS 'America/Denver (MDT)';
COMMENT ON COLUMN cct7_users.baseline_timezone_name    IS 'America/Denver';
COMMENT ON COLUMN cct7_users.baseline_timezone_abbr    IS 'MDT';
COMMENT ON COLUMN cct7_users.baseline_timezone_offset  IS '-21600';
COMMENT ON COLUMN cct7_users.time_difference           IS '3600';
COMMENT ON COLUMN cct7_users.sql_zone_offset           IS '(3600)';
COMMENT ON COLUMN cct7_users.sql_zone_abbr             IS 'MDT';
COMMENT ON COLUMN cct7_users.inbox_netpins             IS '17340,xxx,...';
COMMENT ON COLUMN cct7_users.user_or_admin             IS 'CCT user type: user or admin';
COMMENT ON COLUMN cct7_users.last_login_date           IS 'Last login date';
COMMENT ON COLUMN cct7_users.http_user_agent           IS 'Record what browser they are using';
COMMENT ON COLUMN cct7_users.disable_email             IS 'Disable all email notifications: Y/N';
COMMENT ON COLUMN cct7_users.disable_pages             IS 'Disable all paging notifications: Y/N';
COMMENT ON COLUMN cct7_users.is_debug_on               IS 'Is debugging turned on?';
COMMENT ON COLUMN cct7_users.debug_level1              IS 'Debug level1 set?';
COMMENT ON COLUMN cct7_users.debug_level2              IS 'Debug level 2 set?';
COMMENT ON COLUMN cct7_users.debug_level3              IS 'Debug level 3 set?';
COMMENT ON COLUMN cct7_users.debug_level4              IS 'Debug level 4 set?';
COMMENT ON COLUMN cct7_users.debug_level5              IS 'Debug level 5 set?';
COMMENT ON COLUMN cct7_users.debug_path                IS 'Full path do debug directory';
COMMENT ON COLUMN cct7_users.debug_mode                IS 'Debug mode: a (append), w (write)';
COMMENT ON COLUMN cct7_users.pref_toolbar_open         IS 'Show what open tickets: group or all';

CREATE OR REPLACE TRIGGER insert_cct7_users
   BEFORE INSERT ON cct7_users
   FOR EACH ROW
BEGIN
   select unix_timestamp()
   INTO :new.insert_date
   FROM dual;
END insert_cct7_users;
/

CREATE OR REPLACE TRIGGER update_cct7_users
   BEFORE UPDATE ON cct7_users
   FOR EACH ROW
BEGIN
   SELECT unix_timestamp()
   INTO :new.update_date
   FROM DUAL;
END update_cct7_users;
/

insert into cct7_users (
    user_cuid,
    insert_cuid,
    insert_name,
    inbox_netpins,
    user_or_admin,
    is_debug_on,
    pref_toolbar_open
  )
values
  ( 'gparkin', 'gparkin', 'Greg Parkin', '17340', 'admin', 'Y', 'all' );

select count(*) as cct7_users from cct7_users;

commit;
