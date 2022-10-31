WHENEVER SQLERROR CONTINUE
set echo on

drop table cct7_override_members;
drop table cct7_override_netpins;

create table cct7_override_netpins 
(
	netpin_id         number         primary key,
	create_date       number,
	create_cuid       varchar2(20),
	create_name       varchar2(200),
	netpin_no         varchar2(20)   not null
);

drop sequence cct7_override_netpinsseq;

create sequence cct7_override_netpinsseq increment by 1 start with 1 nocache;
create index idx_cct7_override_netpins1 on cct7_override_netpins (create_cuid);

COMMENT ON TABLE cct7_override_netpins               IS 'List of netpin overrides';
COMMENT ON COLUMN cct7_override_netpins.netpin_id    IS 'PK: Unique record ID';
COMMENT ON COLUMN cct7_override_netpins.create_date  IS 'Date record was created (GMT timestamp)';
COMMENT ON COLUMN cct7_override_netpins.create_cuid  IS 'CUID of person who created the record';
COMMENT ON COLUMN cct7_override_netpins.create_name  IS 'Name of person who created the record';
COMMENT ON COLUMN cct7_override_netpins.netpin_no    IS 'Netpin No.';

