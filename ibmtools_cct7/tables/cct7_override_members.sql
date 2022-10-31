WHENEVER SQLERROR CONTINUE
set echo on

create table cct7_override_members
(
	member_id                number primary key,
	netpin_id                number,
	create_date              number,
  create_cuid              varchar2(20),
  create_name              varchar2(200),
	member_cuid              varchar2(20),
	member_name              varchar2(200),

FOREIGN KEY (netpin_id) REFERENCES cct7_override_netpins (netpin_id) on delete cascade);

drop sequence cct7_override_membersseq;
create sequence cct7_override_membersseq increment by 1 start with 1 nocache;

COMMENT ON TABLE cct7_override_members               IS 'List members from cct7_override_netpins list';
COMMENT ON COLUMN cct7_override_members.member_id    IS 'PK: Unique record ID';
COMMENT ON COLUMN cct7_override_members.netpin_id    IS 'FK: cct7_override_netpins.netpin_id';
COMMENT ON COLUMN cct7_override_members.create_date  IS 'Date record was created. (GMT unix timestamp)';
COMMENT ON COLUMN cct7_override_members.create_cuid  IS 'CUID of person who created this record';
COMMENT ON COLUMN cct7_override_members.create_name  IS 'Name of person who created this record';
COMMENT ON COLUMN cct7_override_members.member_cuid  IS 'CUID of person who will receive notifications';
COMMENT ON COLUMN cct7_override_members.member_name  IS 'Name of person who will receive notifications';
