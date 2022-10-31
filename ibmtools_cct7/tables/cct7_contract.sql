WHENEVER SQLERROR CONTINUE
set echo on

CREATE TABLE cct7_contract
(
	computer_contract    VARCHAR2(80)
);

COMMENT ON TABLE cct7_contract IS 'Quick list of IBM contracts - maintained by automation using cct6_computers';
COMMENT ON COLUMN cct7_contract.computer_contract IS 'IBM contract from cct6_computers.computer_contract';

insert into cct7_contract select * from cct6_contract;

select count(*) as cct7_contract from cct7_contract;

commit;

