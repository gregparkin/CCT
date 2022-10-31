set echo on

REM
REM cct7_computers_part5.sql
REM

REM
REM ewebars_title
REM ewebars_status
REM
REM    (PR.title like '%EWEBARS%' or
REM		 PR.title = 'BACKUP' or
REM		 PR.title = 'BACKUP AND HARDWARE MAINT ONLY' or
REM		 PR.title = 'BACKUP MONITORING AND HARDWARE MAINT' or
REM		 PR.title = 'BACKUP ONLY');
REM
DECLARE
  v_lastid   NUMBER;
  v_title    VARCHAR2(100);
  v_status   VARCHAR2(80);

CURSOR c_amproject IS
  SELECT distinct
    ap.lastid,
    PR.title,
    PR.status
  from
    AM_AP ap,
    AM_PR pr
  where
    ap.dRemoved is null and
    ap.lProjID = PR.lProjID and
		pr.title is not null and
		pr.status = 'SUPPORT';

BEGIN
	DBMS_OUTPUT.PUT_LINE('Updating EWEBARS information');
  OPEN c_amproject;
  LOOP
    FETCH c_amproject INTO
      v_lastid,
      v_title,
      v_status;

    EXIT WHEN c_amproject%NOTFOUND;

    update new_cct7_computers set
      ewebars_title = v_title,
      ewebars_status = v_status
    where
      lastid = v_lastid;

  END LOOP;

  CLOSE c_amproject;
END;
/

COMMIT;
quit;
