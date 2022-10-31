--
-- cct7_subscriber_groups.sql
--

drop table cct7_subscriber_servers;
drop table cct7_subscriber_members;
drop table cct7_subscriber_groups;

create table cct7_subscriber_groups
(
  group_id      VARCHAR2(20) PRIMARY KEY,
  create_date   NUMBER,
  owner_cuid    VARCHAR2(20),
  owner_name    VARCHAR2(200),
  group_name    VARCHAR2(200)

);

create index idx_cct7_subscriber_groups1 on cct7_subscriber_groups ( owner_cuid );

DROP SEQUENCE cct7_subscriber_groupsseq;
CREATE SEQUENCE cct7_subscriber_groupsseq INCREMENT BY 1 START WITH 1 NOCACHE;

COMMENT ON TABLE  cct7_subscriber_groups                  IS 'Subscriber List Names used for users to identify their subscriber list.';
COMMENT ON COLUMN cct7_subscriber_groups.group_id         IS 'PK: Unique Record ID';
COMMENT ON COLUMN cct7_subscriber_groups.create_date      IS 'GMT date record was created';
COMMENT ON COLUMN cct7_subscriber_groups.owner_cuid       IS 'Owner CUID of this subscriber list';
COMMENT ON COLUMN cct7_subscriber_groups.owner_name       IS 'Owner NAME of this subscriber list';
COMMENT ON COLUMN cct7_subscriber_groups.group_name       IS 'Group Name';

