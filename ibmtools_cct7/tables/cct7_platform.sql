WHENEVER SQLERROR CONTINUE
set echo on

CREATE TABLE cct7_platform
(
	computer_platform    VARCHAR2(43)
);

comment on table cct7_platform is 'Used in new_work_request_step3.php - List of computer platforms';
COMMENT ON COLUMN cct7_platform.computer_platform is 'computers.computer_platform (i.e. MIDRANGE)';

insert into cct7_platform select * from cct6_platform;

select count(*) as cct7_platform from cct7_platform;

commit;

