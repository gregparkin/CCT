set echo on

drop table cct7_managing_group;

CREATE TABLE cct7_managing_group
(
	computer_managing_group    VARCHAR2(40)
);

comment on table cct7_managing_group is 'Used in new_work_request_step3.php - List of available asset center managing groups';
COMMENT ON COLUMN cct7_managing_group.computer_managing_group    IS 'Managing group from table computers.computer_managing_group';

insert into cct7_managing_group select * from cct6_managing_group;

select count(*) as cct7_managing_group from cct7_managing_group;

commit;
