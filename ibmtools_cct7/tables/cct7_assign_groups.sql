set echo on

drop table cct7_assign_groups;

CREATE TABLE cct7_assign_groups
(
	insert_date        DATE,
	insert_name        VARCHAR2(200),
	login_cuid         VARCHAR2(254),
	group_name         VARCHAR2(4000)
);

create index idx_cct7_assign_groups1 on cct7_assign_groups (login_cuid);
create index idx_cct7_assign_groups2 on cct7_assign_groups (group_name);
create index idx_cct7_assign_groups3 on cct7_assign_groups (login_cuid, group_name);

COMMENT ON TABLE cct7_assign_groups IS 'List of Remedy assign groups for each user - Remedy CM data - maintained by user and automation';
COMMENT ON COLUMN cct7_assign_groups.insert_date IS 'Date record was created';
COMMENT ON COLUMN cct7_assign_groups.insert_name IS 'Name of person who created this record';
COMMENT ON COLUMN cct7_assign_groups.login_cuid IS 'CUID of person who is in this assign group';
COMMENT ON COLUMN cct7_assign_groups.group_name IS 'Name of the assignment group';


CREATE OR REPLACE TRIGGER insert_cct7_assign_groups
   BEFORE INSERT ON cct7_assign_groups
   FOR EACH ROW
BEGIN
   select SYSDATE
   INTO :new.insert_date
   FROM dual;
END insert_cct7_assign_groups;
/

insert into cct7_assign_groups select * from cct6_assign_groups;

select count(*) as cct7_assign_groups from cct7_assign_groups;

commit;