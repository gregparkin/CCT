set echo on

exec drop_table_if_exist('cct7_databases');

create table cct7_databases as
select
  c.a1_lastid               as computer_lastid,
  lower(b.hostname)         as computer_hostname,
  b.host_assettag           as computer_asset_tag,
  b.host_serialno           as computer_serial_no,
  a.lastid                  as database_lastid,
  a.assettag                as database_asset_tag,
  a.seassignment            as database_seassignment,
  a.database_name           as database_name,
  b.connection_type         as database_connection_type,
  b.db_failover_priority    as database_failover_priority,
  a.instance_name           as database_instance,
  a.service_name            as database_service,
  a.db_type                 as database_type,
  a.db_version              as database_version,
  lower(a.mdl_poc)          as database_mdl_poc,
  a.patch_install           as database_patch_install,
  a.patch_level             as database_patch_level,
  a.dbfamilygroupkey        as database_family_group_key,
  a.portfoliocomment        as database_comment,
  lower(a.dba_manager)      as database_manager_cuid,
  lower(a.prod_dba_manager) as database_prod_manager_cuid,
  a.database_arrangement    as database_arrangement,
  a.non_standard_support    as database_non_standard_support,
  a.assignment_changed_dt   as database_assign_change_date,
  a.no_owning_appl_reason   as no_owning_appl_reason,
  a.asset_system            as asset_system
from
  cs_qwestibm_mdl@itast  a,
  cs_qwestibm_mdl_to_server@itast b,
  cs_qwestibm_computers@itast c
where
  a.lastid = b.lastid and
  c.a1_field1 = b.hostname;
  
create index idx_cct7_databases1 on cct7_databases ( computer_lastid );
create index idx_cct7_databases2 on cct7_databases ( computer_hostname );
create index idx_cct7_databases3 on cct7_databases ( computer_asset_tag );
create index idx_cct7_databases4 on cct7_databases ( computer_serial_no );
create index idx_cct7_databases5 on cct7_databases ( database_name );

commit;
select count(*) as record_count from cct7_databases;
quit;
