set echo on

REM
REM cct7_computers_part3.sql
REM

REM
REM cio_group
REM managing_group
REM nature 
REM
DECLARE
	V_AP_LPROJID              NUMBER;
	V_AP_DREMOVED             DATE;
	V_AP_LASTID               NUMBER;
	V_PR_LPROJID              NUMBER;
	V_PR_REF                  VARCHAR2(40);
	V_PR_STATUS               VARCHAR2(80);
	V_PR_TITLE                VARCHAR2(100);

CURSOR c_xxx IS
  SELECT DISTINCT
		ap.LPROJID,
		ap.DREMOVED,
		ap.LASTID,
		PR.LPROJID,
		PR.REF,
		PR.STATUS,
		PR.TITLE
	FROM
		AM_AP ap,
		AM_A7 a7,
		AM_C9 c9,
		AM_PR pr
	WHERE
		A7.DPLANNEDREMOV is NULL
		AND C9.NATURE like '%SUPPORT'
		AND C9.STATUS = 'SERVER'
		AND A7.LASTID = AP.LASTID
		AND AP.DREMOVED IS NULL
		AND AP.LPROJID = PR.LPROJID
		AND PR.STATUS = 'SUPPORT';

BEGIN
	DBMS_OUTPUT.PUT_LINE('Merged: amAstProjDesc and amProject');
  OPEN c_xxx;
  LOOP
    FETCH c_xxx INTO
			V_AP_LPROJID,
			V_AP_DREMOVED,
			V_AP_LASTID,
			V_PR_LPROJID,
			V_PR_REF,
			V_PR_STATUS,
			V_PR_TITLE;

    EXIT WHEN c_xxx%NOTFOUND;

    update new_cct7_computers set
			cio_group = V_PR_TITLE,
			managing_group = V_PR_REF,
			nature = 'SUPPORT'
			where lastid = V_AP_LASTID;

  END LOOP;

  CLOSE c_xxx;
END;
/

COMMIT;
quit;
