
-- No longer using the procedure. Using send_notifications.php instead.

create table cct7_temp
(
  user_cuid               VARCHAR2(20),
  contact_netpin_no       VARCHAR2(20),
  mnet_cuid               VARCHAR2(20),
  mnet_first_name         VARCHAR2(80),
  mnet_nick_name          VARCHAR2(80),
  mnet_name               VARCHAR2(200),
  mnet_email              VARCHAR2(80),
  system_hostname         VARCHAR2(255),
  system_timezone_name    VARCHAR2(200),
  system_osmaint_weekly   VARCHAR2(4000),
  system_respond_by_date  NUMBER,
  system_work_start_date  NUMBER,
  system_work_end_date    NUMBER,
  system_work_duration    VARCHAR2(30)
);

create index idx_cct7_temp1 on cct7_temp (user_cuid);

drop table cct7_temp;

CREATE OR REPLACE procedure emailContacts(v_this_ticket_no IN varchar2, v_this_user_cuid IN varchar2) IS
  v_contact_netpin_no       VARCHAR2(20);
  v_mnet_cuid               VARCHAR2(20);
  v_mnet_first_name         VARCHAR2(80);
  v_mnet_nick_name          VARCHAR2(80);
  v_mnet_name               VARCHAR2(200);
  v_mnet_email              VARCHAR2(80);
  v_system_hostname         VARCHAR2(255);
  v_system_timezone_name    VARCHAR2(200);
  v_system_osmaint_weekly   VARCHAR2(4000);
  v_system_respond_by_date  NUMBER;
  v_system_work_start_date  NUMBER;
  v_system_work_end_date    NUMBER;
  v_system_work_duration    VARCHAR2(30);

CURSOR c1(v_ticket_no VARCHAR2) IS
  select distinct 
    c.contact_netpin_no, 
    m.mnet_cuid, 
    m.mnet_first_name, 
    m.mnet_nick_name, 
    m.mnet_name, 
    m.mnet_email
  from 
    cct7_systems s, 
    cct7_contacts c, 
    cct7_netpin_to_cuid n, 
    cct7_mnet m 
  where 
    s.ticket_no = v_ticket_no and 
    c.system_id = s.system_id and 
    n.net_pin_no = c.contact_netpin_no and 
    m.mnet_cuid = n.user_cuid and 
    m.mnet_email is not null;
    
CURSOR c2 IS
  select distinct
    s.system_hostname,
    s.system_timezone_name,
    s.system_osmaint_weekly,
    s.system_respond_by_date,
    s.system_work_start_date,
    s.system_work_end_date,
    s.system_work_duration
  from
    cct7_systems s,
    cct7_contacts c
  where
    c.contact_netpin_no = v_contact_netpin_no and
    c.system_id = s.system_id
  order by
    s.system_hostname;
    
BEGIN
  DELETE FROM cct7_temp where user_cuid = v_this_user_cuid;
  OPEN c1(v_this_ticket_no);
  LOOP
    FETCH c1 INTO 
      v_contact_netpin_no, v_mnet_cuid, v_mnet_first_name, v_mnet_nick_name,
      v_mnet_name, v_mnet_email;

    EXIT WHEN c1%NOTFOUND;
    
    OPEN c2;
    LOOP
      FETCH c2 INTO 
        v_system_hostname, v_system_timezone_name, 
        v_system_osmaint_weekly, v_system_respond_by_date,
        v_system_work_start_date, v_system_work_end_date,
        v_system_work_duration;
        
      EXIT WHEN c2%NOTFOUND;
           
      insert into cct7_temp   
        (
          user_cuid,
          contact_netpin_no,
          mnet_cuid,
          mnet_first_name,
          mnet_nick_name,
          mnet_name,
          mnet_email,
          system_hostname,
          system_timezone_name,
          system_osmaint_weekly,
          system_respond_by_date,
          system_work_start_date,
          system_work_end_date,
          system_work_duration
        ) 
        values
        (
          v_this_user_cuid,
          v_contact_netpin_no,
          v_mnet_cuid,
          v_mnet_first_name,
          v_mnet_nick_name,
          v_mnet_name,
          v_mnet_email,
          v_system_hostname,
          v_system_timezone_name,
          v_system_osmaint_weekly,
          v_system_respond_by_date,
          v_system_work_start_date,
          v_system_work_end_date,
          v_system_work_duration
        );
      
    END LOOP;
    CLOSE c2;
  END LOOP;
  CLOSE c1;
  
END;
/

EXECUTE emailContacts('CCT700000006', 'gparkin');