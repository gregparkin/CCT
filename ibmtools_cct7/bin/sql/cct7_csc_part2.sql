set echo on

REM
REM cct7_csc_part2.sql
REM

REM
REM  6. Drop backup database table for cct7_csc_backup
REM

exec drop_table_if_exist('cct7_csc_backup');

REM
REM  7. Rename original cct7_csc to cct7_csc_backup
REM
rename cct7_csc to cct7_csc_backup;

REM
REM  8. Rename new_cct7_csc and cct7_csc
REM
rename new_cct7_csc to cct7_csc;

REM
REM  9. Drop old indexes
REM
exec drop_index_if_exist('idx_cct7_csc_1');
exec drop_index_if_exist('idx_cct7_csc_2');
exec drop_index_if_exist('idx_cct7_csc_3');
exec drop_index_if_exist('idx_cct7_csc_4');
exec drop_index_if_exist('idx_cct7_csc_5');
exec drop_index_if_exist('idx_cct7_csc_6');

REM
REM 10. Create new indexes
REM
create index idx_cct7_csc_1 on cct7_csc ( cct_csc_assettag );
create index idx_cct7_csc_2 on cct7_csc ( cct_csc_group_name );
create index idx_cct7_csc_3 on cct7_csc ( cct_csc_netgroup );
create index idx_cct7_csc_4 on cct7_csc ( lastid );
create index idx_cct7_csc_5 on cct7_csc ( cct_csc_group_name, cct_csc_userid_1 );
create index idx_cct7_csc_6 on cct7_csc ( cct_app_acronym, lastid );

REM
REM 11. grant select access to other oracle accounts
REM
grant select on cct7_csc to public;

select count(*) as record_count from cct7_csc;

quit;
