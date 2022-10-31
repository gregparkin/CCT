set echo on

REM
REM cct7_computers_part7.sql
REM

REM
REM os_group_contact
REM
DECLARE
  v_lastid       NUMBER;
  v_contact      VARCHAR2(20);

CURSOR c_cct_csc IS
  SELECT
    lastid,
    cct_csc_userid_1
  from
    cct7_csc
  WHERE
    cct_csc_group_name = '! Operating System Support' and
    cct_csc_userid_1 is not null;

BEGIN
  OPEN c_cct_csc;
  LOOP
    FETCH c_cct_csc INTO
      v_lastid,
      v_contact;

    EXIT WHEN c_cct_csc%NOTFOUND;

    update new_cct7_computers set
      os_group_contact = v_contact
    where
      lastid = v_lastid;

  END LOOP;

  CLOSE c_cct_csc;
END;
/

COMMIT;

REM
REM csc_os_banners 
REM
REM '! Operating System Support' - OS  - APPROVER
REM
DECLARE
	v_lastid       NUMBER;
	v_banners      NUMBER;

CURSOR c_csc IS
	select
		lastid,
		count(*) as banners
	from
		cct7_csc
	where
		cct_csc_group_name = '! Operating System Support'
	group by
		lastid;

BEGIN
	OPEN c_csc;
	LOOP
    FETCH c_csc INTO v_lastid, v_banners;

    EXIT WHEN c_csc%NOTFOUND;

		update new_cct7_computers set 
				csc_os_banners = v_banners 
			where 
				lastid = v_lastid;

  END LOOP;

  CLOSE c_csc;
END;
/

COMMIT;

REM
REM csc_pase_banners
REM
REM 'Application Support' - PASE - APPROVER
REM
DECLARE
	v_lastid       NUMBER;
	v_banners      NUMBER;

CURSOR c_csc IS
	select
		lastid,
		count(*) as banners
	from
		cct7_csc
	where
		cct_csc_group_name = 'Applications or Databases Desiring Notification (Not Hosted on this Server)' or
		cct_csc_group_name = 'Application Support' or
		cct_csc_group_name = 'Application Utilities, Middleware, Legacy DB, Other (Hosted on this Server)' or
		cct_csc_group_name = 'Infrastructure'
	group by
		lastid;

BEGIN
	OPEN c_csc;
	LOOP
    FETCH c_csc INTO v_lastid, v_banners;

    EXIT WHEN c_csc%NOTFOUND;

		update new_cct7_computers set 
				csc_pase_banners = v_banners 
			where 
				lastid = v_lastid;

  END LOOP;

  CLOSE c_csc;
END;
/

COMMIT;

REM
REM csc_dba_banners
REM
REM '! Database Support' - DBA - APPROVER
REM
DECLARE
	v_lastid       NUMBER;
	v_banners      NUMBER;

CURSOR c_csc IS
	select
		lastid,
		count(*) as banners
	from
		cct7_csc
	where
		cct_csc_group_name = '! Database Support'
	group by
		lastid;

BEGIN
	OPEN c_csc;
	LOOP
    FETCH c_csc INTO v_lastid, v_banners;

    EXIT WHEN c_csc%NOTFOUND;

		update new_cct7_computers set 
				csc_dba_banners = v_banners 
			where 
				lastid = v_lastid;

  END LOOP;

  CLOSE c_csc;
END;
/

COMMIT;

REM
REM csc_fyi_banners
REM
REM 'Development Support'                                                         - PASE - FYI
REM '! Development Support'                                                       - PASE - FYI
REM 'Application Utilities, Middleware, Legacy DB, Other (Hosted on this Server)' - PASE - FYI
REM 'Applications or Databases Desiring Notification (Not Hosted on this Server)' - PASE - FYI
REM 'Applications Owning Database (DB Hosted on this Server, Owning App Is Not)'  - PASE - FYI
REM
DECLARE
	v_lastid       NUMBER;
	v_banners      NUMBER;

CURSOR c_csc IS
	select
		lastid,
		count(*) as banners
	from
		cct7_csc
	where
		cct_csc_group_name = 'Development Support' or
		cct_csc_group_name = '! Development Support' or
		cct_csc_group_name = 'Application Utilities, Middleware, Legacy DB, Other (Hosted on this Server)' or
		cct_csc_group_name = 'Applications or Databases Desiring Notification (Not Hosted on this Server)' or
		cct_csc_group_name = 'Applications Owning Database (DB Hosted on this Server, Owning App Is Not)'
	group by
		lastid;

BEGIN
	OPEN c_csc;
	LOOP
    FETCH c_csc INTO v_lastid, v_banners;

    EXIT WHEN c_csc%NOTFOUND;

		update new_cct7_computers set 
				csc_fyi_banners = v_banners 
			where 
				lastid = v_lastid;

  END LOOP;

  CLOSE c_csc;
END;
/

COMMIT;
quit;
