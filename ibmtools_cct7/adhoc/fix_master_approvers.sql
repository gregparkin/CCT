DECLARE
  v_computer_hostname  VARCHAR2(80);
  
CURSOR c_xxx IS select computer_hostname from cct6_master_approvers;

BEGIN
  OPEN c_xxx;
  LOOP
    FETCH c_xxx INTO v_computer_hostname;
    EXIT WHEN c_xxx%NOTFOUND;
    
    update cct6_master_approvers set
      computer_hostname = lower(v_computer_hostname)
      where computer_hostname = v_computer_hostname;
  END LOOP;
  
  CLOSE c_xxx;
END;
/
