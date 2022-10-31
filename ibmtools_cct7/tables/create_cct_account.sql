
alter session set "_ORACLE_SCRIPT"=true;
create user cct identified by candy4Kids;
grant connect to cct;
grant create session, grant any privilege to cct;
grant all privileges to cct;
grant dba to cct;
select * from system_privilege_map where name like '%PRIV%';

select * from all_users;
