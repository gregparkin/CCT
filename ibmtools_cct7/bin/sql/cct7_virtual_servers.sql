set echo on

exec drop_table_if_exist('cct7_virtual_servers');

create table cct7_virtual_servers as select * from pt.cv_qwestibm_vctrassns@itast;

create index idx_cct7_virtual_servers1 on cct7_virtual_servers (lastid);
create index idx_cct7_virtual_servers2 on cct7_virtual_servers (name);
create index idx_cct7_virtual_servers3 on cct7_virtual_servers (connected_name, connection_type);
  
commit;
select count(*) as record_count from cct7_virtual_servers;
quit;