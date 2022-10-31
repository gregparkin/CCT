WHENEVER SQLERROR CONTINUE
set echo on

create table cct7_netpin_to_cuid 
(
	net_pin_no      varchar2(20),
	user_cuid       varchar2(20),
	oncall_primary  varchar2(10),
	oncall_backup   varchar2(10),
	last_update     DATE
);

create index idx_cct7_netpin_to_cuid1 on cct7_netpin_to_cuid (net_pin_no);
create index idx_cct7_netpin_to_cuid2 on cct7_netpin_to_cuid (user_cuid);

COMMENT ON TABLE cct7_netpin_to_cuid IS 'List of NetTool group pin members for net-pin number';
COMMENT ON COLUMN cct7_netpin_to_cuid.net_pin_no IS 'Net-Pin number defined in Net-Tool';
COMMENT ON COLUMN cct7_netpin_to_cuid.user_cuid  IS 'Employee CUID that is a member of the net_pin_no group';
COMMENT ON COLUMN cct7_netpin_to_cuid.last_update IS 'Record last updated. Used to verify records are being refreshed nightly.';
COMMENT ON COLUMN cct7_netpin_to_cuid.oncall_primary IS 'Is this person the primary oncall person? Y/N';
COMMENT ON COLUMN cct7_netpin_to_cuid.oncall_backup IS 'Is this person the backup oncall person? Y/N';

create or replace trigger insert_cct7_netpin_to_cuid
	before insert on cct7_netpin_to_cuid
	for each row
BEGIN
	select SYSDATE
	INTO :new.last_update
	FROM dual;
END insert_cct7_netpin_to_cuid;
/

insert into cct7_netpin_to_cuid select * from cct6_netpin_to_cuid;

select count(*) as cct7_netpin_to_cuid from cct7_netpin_to_cuid;

commit;
