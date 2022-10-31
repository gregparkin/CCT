
select * from cct7_netpin_to_cuid where net_pin_no = '110719';

select * from cct7_mnet where mnet_cuid = 'aa11469';
select * from cct7_mnet where mnet_first_name = 'Arjish' and mnet_last_name = 'Kachhal';

select * from cct7_netpin_to_cuid where user_cuid = 'aa11469';

select 
  t.* 
from 
  cct7_tickets t, 
  (
    select 
      distinct t.ticket_no 
    from 
      cct7_tickets t, 
      cct7_systems s, 
      cct7_contacts c, 
      (
        select 
          net_pin_no 
        from 
          cct7_netpin_to_cuid 
        where 
          lower(user_cuid) = lower('gparkin')
      ) n2 
    where 
      n2.net_pin_no = c.contact_netpin_no 
      and s.system_id = c.system_id 
      and t.ticket_no = s.ticket_no 
      and t.status = 'ACTIVE' 
      and s.system_work_status = 'WAITING' 
      --and c.contact_approver_fyi = 'APPROVER' 
      and c.contact_response_status = 'WAITING' 
    order by 
      t.ticket_no
  ) m 
where 
  (t.ticket_no = m.ticket_no);
  
select * from cct7_tickets where ticket_no in ('CCT700049256','CCT700049430'); 


update cct7_contacts set
  contact_response_status = 'APPROVED'
where
  contact_approver_fyi = 'FYI';
  
select distinct contact_response_status from cct7_contacts;  

DECLARE 
  v_ticket_no               VARCHAR2(80); 

CURSOR c1 IS 
  select 
    ticket_no 
  from 
    cct7_tickets 
  where 
    total_servers_scheduled = 0; 

BEGIN 
open c1; 
LOOP 
  FETCH c1 into v_ticket_no; 
  EXIT WHEN c1%NOTFOUND; 

  updateStatus(v_ticket_no); 

END LOOP; 
close c1; 
commit; 
END;
/

-- =============================================================================

CREATE OR REPLACE procedure updateStatus(v_this_ticket_no IN varchar2) IS
  v_system_id                    NUMBER;
  v_new_status                   VARCHAR2(20);
  v_system_hostname              VARCHAR2(255);
  v_system_work_status           VARCHAR2(20);
  v_system_work_status_lock      VARCHAR2(1);
  v_contact_netpin_no            VARCHAR2(20);
  v_contact_response_status      VARCHAR2(20);

  v_last_system_id               NUMBER;
  v_last_system_hostname         VARCHAR2(255);
  v_last_system_work_status      VARCHAR2(20);
  v_last_contact_netpin_no       VARCHAR2(20);
  v_last_contact_response_status VARCHAR2(20);

  v_total_contacts_responded     NUMBER;
  v_total_contacts_not_responded NUMBER;
  v_total_servers_scheduled      NUMBER;
  v_total_servers_waiting        NUMBER;
  v_total_servers_approved       NUMBER;
  v_total_servers_rejected       NUMBER;

  CURSOR c1(v_ticket_no VARCHAR2) IS
    select distinct
      s.system_id,
      s.system_hostname,
      s.system_work_status,
      c.contact_netpin_no,
      c.contact_response_status
    from
      cct7_systems s,
      cct7_contacts c
    where
      s.ticket_no = v_ticket_no and
      c.system_id(+) = s.system_id
    order by
      s.system_hostname, c.contact_netpin_no;

  BEGIN
    open c1(v_this_ticket_no);

    v_last_system_id               := 0;
    v_last_system_hostname         := '';
    v_last_system_work_status      := '';
    v_last_contact_netpin_no       := '';
    v_last_contact_response_status := '';

    v_new_status := 'APPROVED';

    v_total_contacts_responded     := 0;
    v_total_contacts_not_responded := 0;

    v_total_servers_scheduled      := 0;
    v_total_servers_waiting        := 0;
    v_total_servers_approved       := 0;
    v_total_servers_rejected       := 0;

    LOOP
      FETCH c1 INTO
      v_system_id,
      v_system_hostname,
      v_system_work_status,
      v_contact_netpin_no,
      v_contact_response_status;

      EXIT WHEN c1%NOTFOUND;

      --
      -- If this is not the first server and the the last server does not equal this server then
      --
      IF (LENGTH(v_last_system_hostname) > 0 AND v_last_system_hostname != v_system_hostname) THEN
        --
        -- Total the number of servers we are processing.
        --
        v_total_servers_scheduled := v_total_servers_scheduled + 1;

        --
        -- Tally counts based upon contact response status type.
        --
        IF (v_last_contact_response_status = 'APPROVED' OR v_last_contact_response_status = 'EXEMPTED' OR v_last_contact_response_status IS NULL) THEN
          v_total_servers_approved := v_total_servers_approved + 1;
        ELSIF (v_last_contact_response_status = 'REJECTED' OR v_last_contact_response_status = 'CANCELED') THEN
          v_total_servers_rejected := v_total_servers_rejected + 1;
        ELSE
          v_total_servers_waiting  := v_total_servers_waiting + 1;
        END IF;

        --
        -- Available system_work_status values:
        --
        -- APPROVED
        -- BACKOUT
        -- CANCELED
        -- FAILED
        -- REJECTED
        -- STARTING
        -- SUCCESS
        -- UNKNOWN
        -- WAITING

        --
        -- Update the last cct7_systems record stored in the last_xxx variables.
        -- Do not change system_work_status if its current value is: CANCELED, STARTING, SUCCESS, or FAILED
        --
        IF (v_last_system_work_status = 'CANCELED'  OR v_last_system_work_status = 'STARTING' OR
            v_last_system_work_status = 'SUCCESS'   OR v_last_system_work_status = 'FAILED') THEN
          update cct7_systems set
            total_contacts_responded     = v_total_contacts_responded,
            total_contacts_not_responded = v_total_contacts_not_responded
          where system_id                = v_last_system_id;
        ELSE
          update cct7_systems set
            system_work_status           = v_new_status,
            total_contacts_responded     = v_total_contacts_responded,
            total_contacts_not_responded = v_total_contacts_not_responded
          where system_id                = v_last_system_id;
        END IF;

        --
        -- Reset status and totals for next server
        --
        v_new_status := 'APPROVED';
        v_total_contacts_responded := 0;
        v_total_contacts_not_responded := 0;
      END IF;

      --
      -- Copy this record to our v_last_xxx variables.
      --
      v_last_system_id               := v_system_id;
      v_last_system_hostname         := v_system_hostname;
      v_last_system_work_status      := v_system_work_status;
      v_last_contact_netpin_no       := v_contact_netpin_no;
      v_last_contact_response_status := v_contact_response_status;

      --
      -- Default value for v_new_status = APPROVED. If contact status is
      -- REJECTED or WAITING then change v_new_status to REJECTED or
      -- WAITING.
      --
      -- Possible Contact statuses
      -- WAITING
      -- APPROVED
      -- REJECTED
      -- EXEMPTED = Same as APPROVED
      --
      IF    (v_contact_response_status = 'REJECTED') THEN
        v_new_status := 'REJECTED';
      ELSIF (v_contact_response_status = 'WAITING') THEN
        v_new_status:= 'WAITING';
      END IF;

      --
      -- Tally this contact response status type.
      --
      IF (v_contact_response_status = 'WAITING') THEN
        v_total_contacts_not_responded := v_total_contacts_not_responded + 1;
      ELSE
        v_total_contacts_responded := v_total_contacts_responded + 1;
      END IF;
    END LOOP;

    --
    -- If we proceessed one or more records then v_last_system_hostname will contain
    -- the last record we need to update in cct7_systems.
    --
    IF (LENGTH(v_last_system_hostname) > 0) THEN
      --
      -- Total the number of servers we are processing
      --
      v_total_servers_scheduled := v_total_servers_scheduled + 1;

      --
      -- Tally counts based upon contact response status type
      --
      IF (v_last_contact_response_status = 'APPROVED' OR v_last_contact_response_status = 'EXEMPTED' OR v_last_contact_response_status IS NULL) THEN
        v_total_servers_approved := v_total_servers_approved + 1;
      ELSIF (v_last_contact_response_status = 'REJECTED' OR v_last_contact_response_status = 'CANCELED') THEN
        v_total_servers_rejected := v_total_servers_rejected + 1;
      ELSE
        v_total_servers_waiting := v_total_servers_waiting + 1;
      END IF;

      --
      -- Update the last cct7_systems record stored in the last_xxx variables.
      -- Do not change system_work_status if its current value is: CANCELED, STARTED, COMPLETED, or FAILED
      --
      IF (v_last_system_work_status = 'CANCELED'  OR v_last_system_work_status = 'STARTING' OR
          v_last_system_work_status = 'SUCCESS'   OR v_last_system_work_status = 'FAILED') THEN
        update cct7_systems set
          total_contacts_responded     = v_total_contacts_responded,
          total_contacts_not_responded = v_total_contacts_not_responded
        where system_id                = v_last_system_id;
      ELSE
        update cct7_systems set
          system_work_status           = v_new_status,
          total_contacts_responded     = v_total_contacts_responded,
          total_contacts_not_responded = v_total_contacts_not_responded
        where system_id                = v_last_system_id;
      END IF;
    END IF;

    --
    -- Last step is to update cct7_tickets with our tallys.
    --
    update cct7_tickets set
      total_servers_scheduled = v_total_servers_scheduled,
      total_servers_waiting   = v_total_servers_waiting,
      total_servers_approved  = v_total_servers_approved,
      total_servers_rejected  = v_total_servers_rejected
    where ticket_no           = v_this_ticket_no;

    COMMIT;
    CLOSE c1;
  END;
/

exec updateStatus('CCT700048626');

select 
  t.ticket_no,
  t.total_servers_scheduled,
  t.total_servers_waiting,
  t.total_servers_approved,
  t.total_servers_rejected,
  t.total_servers_not_scheduled,
  s.system_hostname,
  s.system_work_status,
  c.contact_netpin_no,
  c.contact_response_status
from 
  cct7_tickets t,
  cct7_systems s,
  cct7_contacts c
where 
  t.ticket_no = 'CCT700048626' and 
  s.system_hostname = 'sudnp32e' and
  s.ticket_no = t.ticket_no and
  c.system_id = s.system_id
order by
  s.system_hostname;

select
  t.cm_ticket_no,
  t.status,
  s.ticket_no,
  s.system_hostname,
  s.system_work_status,
  c.contact_netpin_no,
  c.contact_work_group,
  c.contact_response_status,
  c.contact_approver_fyi,
  c.contact_apps_databases
from
  cct7_tickets t,
  cct7_systems s,
  cct7_contacts c
where
  c.contact_netpin_no = 'SUB212' and
  c.system_id = s.system_id and
  t.ticket_no = s.ticket_no
order by
  c.contact_netpin_no;

select * from cct7_mnet where mnet_cuid = 'msaaved';

select count(*) from cct6_subscriber_lists where subscriber_cuid = 'msaaved';

select * from cct7_subscriber_members where member_cuid = 'msaaved';

select * from cct7_contacts where contact_netpin_no = 'SUB212';



select 
  t.* 
from 
  cct7_tickets t, 
  (
    select 
      distinct t.ticket_no 
    from 
      cct7_tickets t, 
      cct7_systems s, 
      cct7_contacts c
    where 
      c.contact_netpin_no in ('SUB212') 
      and s.system_id = c.system_id 
      and t.ticket_no = s.ticket_no 
      and t.status = 'ACTIVE' 
      and s.system_work_status = 'WAITING' 
      and c.contact_approver_fyi = 'APPROVER' 
      and c.contact_response_status = 'WAITING' 
    order by 
      t.ticket_no
  ) m 
where 
  (t.ticket_no = m.ticket_no) 
order by 
  t.ticket_no OFFSET 0 ROWS FETCH NEXT 25 ROWS ONLY;

       

select 
  count(*) as total_records 
from 
  (
    select 
      distinct t.system_id, 
      t.contact_netpin_no, 
      t.contact_respond_by_date, 
      t.contact_response_status, 
      t.contact_response_date, 
      t.contact_response_cuid, 
      t.contact_response_name, 
      t.contact_send_page, 
      t.contact_send_email 
    from 
      cct7_contacts t 
    where 
      t.system_id = 1041891 
      and t.contact_netpin_no = IN ('SUB212') 
      and t.contact_response_status = 'WAITING'
  ) tbl;
  
select 
  distinct t.system_id, 
  t.contact_netpin_no, 
  t.contact_respond_by_date, 
  t.contact_response_status, 
  t.contact_response_date, 
  t.contact_response_cuid, 
  t.contact_response_name, 
  t.contact_send_page, 
  t.contact_send_email 
from 
  cct7_contacts t
where 
  t.system_id = 1041895 
  and t.contact_netpin_no IN ('SUB212') 
order by 
  t.contact_netpin_no asc OFFSET 0 ROWS FETCH NEXT 15 ROWS ONLY;