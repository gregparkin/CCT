WHENEVER SQLERROR CONTINUE
set echo on

CREATE TABLE cct7_os_lite
(
	computer_os_lite    VARCHAR2(20)
);

comment on table cct7_os_lite is 'Used in new_work_request_step3.php - List OS short names like HPUX, SunOS';
COMMENT ON COLUMN cct7_os_lite.computer_os_lite    IS 'OS Short name';

insert into cct7_os_lite select * from cct6_os_lite;

select count(*) as cct7_os_lite from cct7_os_lite;

commit;
