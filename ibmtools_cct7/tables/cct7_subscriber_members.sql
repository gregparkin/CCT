--
-- cct7_subscriber_members.sql
--

create table cct7_subscriber_members
(
  member_id         NUMBER PRIMARY KEY,
  group_id          VARCHAR2(20),
  create_date       NUMBER,
  member_cuid       VARCHAR2(20),
  member_name       VARCHAR2(200),

  FOREIGN KEY (group_id)  REFERENCES cct7_subscriber_groups (group_id)  ON DELETE CASCADE
);

create index idx_cct7_subscriber_members1 on cct7_subscriber_members ( member_cuid );

DROP SEQUENCE cct7_subscriber_membersseq;
CREATE SEQUENCE cct7_subscriber_membersseq INCREMENT BY 1 START WITH 1 NOCACHE;

COMMENT ON TABLE  cct7_subscriber_members                  IS 'List of subscriber members';
COMMENT ON COLUMN cct7_subscriber_members.member_id        IS 'PK: Unique Record ID';
COMMENT ON COLUMN cct7_subscriber_members.group_id         IS 'FK: cct7_subscriber_groups';
COMMENT ON COLUMN cct7_subscriber_members.create_date      IS 'GMT date record was created';
COMMENT ON COLUMN cct7_subscriber_members.member_cuid      IS 'Member CUID';
COMMENT ON COLUMN cct7_subscriber_members.member_name      IS 'Member NAME';

