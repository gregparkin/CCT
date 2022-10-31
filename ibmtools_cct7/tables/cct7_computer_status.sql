WHENEVER SQLERROR CONTINUE
set echo on

CREATE TABLE cct7_computer_status
(
	computer_status    VARCHAR2(80)
);

comment on table cct7_computer_status is 'List of computer status: PRODUCTION, DEVELOPMENT, etc.';

COMMENT ON COLUMN cct7_computer_status.computer_status    IS 'computers.computer_status - (i.e. PRODUCTION)';

insert into cct7_computer_status select * from cct6_computer_status;

select count(*) as cct7_computer_status from cct7_computer_status;

commit;
