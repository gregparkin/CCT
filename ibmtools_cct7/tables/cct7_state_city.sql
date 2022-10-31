WHENEVER SQLERROR CONTINUE
set echo on

CREATE TABLE cct7_state_city
(
	computer_state    VARCHAR2(80),
	computer_city     VARCHAR2(80)
);

comment on table cct7_state_city is 'Used in new_work_request_step3.php - List of City and States identified in Asset Center';
COMMENT ON COLUMN cct7_state_city.computer_state    IS 'computers.computer_state (i.e. CO)';
COMMENT ON COLUMN cct7_state_city.computer_city     IS 'computers.computer_city (i.e. DENVER)';

insert into cct7_state_city select * from cct6_state_city;

select count(*) as cct7_state_city from cct7_state_city;

commit;
