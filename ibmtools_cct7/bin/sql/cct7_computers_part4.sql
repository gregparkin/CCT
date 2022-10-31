set echo on

REM
REM cct7_computers_part4.sql
REM

REM
REM is_complex
REM
DECLARE
	v_complex_lastid   NUMBER;

CURSOR c_xxx IS
	SELECT DISTINCT complex_lastid from new_cct7_computers where complex_lastid > 0;

BEGIN
	DBMS_OUTPUT.PUT_LINE('Setting: is_complex ');
	OPEN c_xxx;
	LOOP
		FETCH c_xxx INTO
			v_complex_lastid;

		EXIT WHEN c_xxx%NOTFOUND;

		update new_cct7_computers set
			is_complex = 'Y'
			where lastid = v_complex_lastid;

	END LOOP;

	CLOSE c_xxx;
END;
/

COMMIT;

REM
REM is_ibm_supported
REM
DECLARE
  v_lastid     NUMBER;

CURSOR c_asset IS
  SELECT
    lastid
  FROM
    new_cct7_computers
  WHERE
		contract like 'IGS%';

BEGIN
	DBMS_OUTPUT.PUT_LINE('Setting asset_par_ibm_supported flag');
  OPEN c_asset;
  LOOP
    FETCH c_asset INTO v_lastid;
    EXIT WHEN c_asset%NOTFOUND;

    update new_cct7_computers set is_ibm_supported = 'Y' where lastid = v_lastid;
  END LOOP;

  CLOSE c_asset;
END;
/

COMMIT;

REM
REM is_dmz
REM
update new_cct7_computers set is_dmz='Y' where ip_address like '150.159.%';
update new_cct7_computers set is_dmz='Y' where ip_address like '155.70.%';
update new_cct7_computers set is_dmz='Y' where ip_address like '192.65.140.%';
update new_cct7_computers set is_dmz='Y' where ip_address like '192.104.175.%';
update new_cct7_computers set is_dmz='Y' where ip_address like '192.104.176.%';
update new_cct7_computers set is_dmz='Y' where ip_address like '192.104.177.%';
update new_cct7_computers set is_dmz='Y' where ip_address like '192.104.178.%';
update new_cct7_computers set is_dmz='Y' where ip_address like '192.104.179.%';
update new_cct7_computers set is_dmz='Y' where ip_address like '192.104.180.%';
update new_cct7_computers set is_dmz='Y' where ip_address like '198.91.0%';
update new_cct7_computers set is_dmz='Y' where ip_address like '198.91.1%';
update new_cct7_computers set is_dmz='Y' where ip_address like '198.186.195.%';
update new_cct7_computers set is_dmz='Y' where ip_address like '198.186.196.%';
update new_cct7_computers set is_dmz='Y' where ip_address like '198.186.197.%';
update new_cct7_computers set is_dmz='Y' where ip_address like '198.186.198.%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.32%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.33%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.34%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.35%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.36%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.37%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.38%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.39%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.40%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.41%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.42%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.43%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.44%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.45%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.46%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.47%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.48%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.49%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.50%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.51%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.52%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.53%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.54%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.55%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.56%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.57%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.58%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.59%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.60%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.61%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.62%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.117.49.63%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.120.190.%';
update new_cct7_computers set is_dmz='Y' where ip_address like '199.168.%';

COMMIT;

REM
REM Get SOX information
REM

update new_cct7_computers set
  app_server_assn_sox_critical = 1
where
  lower(hostname) IN 
    (select distinct lower(hostnm) from pt.cv_qwestibm_maltoserver@itast where app_server_assn_sox_critical = 1);
    
update new_cct7_computers set
  db_server_assn_sox_critical = 1
where
  lower(hostname) IN
    (select distinct lower(hostname) from pt.cv_qwestibm_mdl_to_server@itast where db_server_assn_sox_critical = 1); 

COMMIT;

quit;
