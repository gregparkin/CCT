REM
REM See: /ibm/cct6/bin/sql/cct7_virtual_servers.sql
REM

WHENEVER SQLERROR CONTINUE
set echo on

create table cct7_virtual_servers
(
 LASTID                                             NUMBER(10) NOT NULL,
 ASSETTAG                                           VARCHAR2(80 CHAR),
 NAME                                               VARCHAR2(255 CHAR),
 CONNECTION_TYPE                                    VARCHAR2(80 CHAR),
 CONNECTED_LASTID                                   NUMBER(10) NOT NULL,
 CONNECTED_ASSETTAG                                 VARCHAR2(80 CHAR),
 CONNECTED_NAME                                     VARCHAR2(255 CHAR)
);

create index idx_cct7_virtual_servers1 on cct7_virtual_servers (lastid);
create index idx_cct7_virtual_servers2 on cct7_virtual_servers (name);
create index idx_cct7_virtual_servers3 on cct7_virtual_servers (connected_name, connection_type);

insert into cct7_virtual_servers select * from cct6_virtual_servers;

select count(*) as cct7_virtual_servers from cct7_virtual_servers;

commit;
