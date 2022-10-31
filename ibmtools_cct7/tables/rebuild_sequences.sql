REM ==================================================================================================================================
REM cct7_systemsseq
REM
drop sequence cct7_list_namesseq;

DECLARE
  v_max_id         NUMBER;
  v_create         VARCHAR2(200);

CURSOR c_xxx IS
  select max(list_name_id)+1 as max_id from cct7_list_names;

BEGIN
  OPEN c_xxx;

  FETCH c_xxx INTO v_max_id;

  v_create := 'create sequence cct7_list_namesseq increment by 1 start with ' || to_char(v_max_id) || ' nocache';

  EXECUTE IMMEDIATE v_create;

  close c_xxx;

  commit;
END;
/

REM ==================================================================================================================================
REM cct7_list_systems
REM
drop sequence cct7_list_systemsseq;

DECLARE
  v_max_id         NUMBER;
  v_create         VARCHAR2(200);

  CURSOR c_xxx IS
    select max(list_system_id)+1 as max_id from cct7_list_systems;

BEGIN
  OPEN c_xxx;

  FETCH c_xxx INTO v_max_id;

  v_create := 'create sequence cct7_list_systemsseq increment by 1 start with ' || to_char(v_max_id) || ' nocache';

  EXECUTE IMMEDIATE v_create;

  close c_xxx;

  commit;
END;
/

select group_id from cct7_subscriber_groups order by group_id desc; -- SUB333
select cct7_subscriber_groupsseq.nextval as nextval from dual; -- 334
drop sequence cct7_subscriber_groupsseq;
create sequence cct7_subscriber_groupsseq increment by 1 start with 500 nocache;

select member_id from cct7_subscriber_members order by member_id desc; -- 328
select cct7_subscriber_membersseq.nextval as nextval from dual; -- 13
drop sequence cct7_subscriber_membersseq;
create sequence cct7_subscriber_membersseq increment by 1 start with 500 nocache;

select server_id from cct7_subscriber_servers order by server_id desc;  -- 6353
select cct7_subscriber_serversseq.nextval as nextval from dual; -- 7
drop sequence cct7_subscriber_serversseq;
create sequence cct7_subscriber_serversseq increment by 1 start with 7000 nocache;
