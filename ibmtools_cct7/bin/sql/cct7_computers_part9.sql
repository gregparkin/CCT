set echo on

REM
REM cct7_computers_part9.sql
REM

REM
REM applications
REM service_level_objective
REM service_level_score
REM service_level_colors
REM
DECLARE
	v_x            NUMBER;
	v_string       VARCHAR2(4000);
	v_lastid       NUMBER;
	v_app_acronym  VARCHAR2(4000);
	v_sl_objective NUMBER(5,2);
	v_sl_score     NUMBER(4,2);
	v_sl_color     VARCHAR2(20);
	v_gold         VARCHAR2(10);
	v_silver       VARCHAR2(10);
	v_bronze       VARCHAR2(10);
	v_blue         VARCHAR2(10);
	v_bcr          VARCHAR2(80);

CURSOR c_csc IS
	select
		cct_app_acronym,
		cct_sl_objective,
		cct_sl_score,
		cct_sl_color
	from
		cct7_csc
	where
		cct_app_acronym is not null and
		upper(cct_app_acronym) != 'NONE' and
		lastid = v_lastid
	order by
		cct_app_acronym;

CURSOR c_ac IS
	select
		lastid as a1_lastid
	from
		new_cct7_computers;

BEGIN
  OPEN c_ac;
  LOOP
    FETCH c_ac INTO v_lastid;

    EXIT WHEN c_ac%NOTFOUND;

		v_string := '';
		v_gold := '';
		v_silver := '';
		v_bronze := '';
		v_blue := '';
		v_bcr := '';

		OPEN c_csc;
		LOOP
			FETCH c_csc INTO 
				v_app_acronym,
				v_sl_objective,
				v_sl_score,
				v_sl_color;

    	EXIT WHEN c_csc%NOTFOUND;

			IF LENGTH(v_app_acronym) > 0 THEN
				IF LENGTH(v_string) > 0 and LENGTH(v_string) < 3900 THEN
					v_string := v_string || ', ' || v_app_acronym;
				ELSE
					v_string := v_app_acronym;
				END IF;

				IF v_sl_color = 'GOLD' THEN
					v_gold := 'GOLD';
				END IF;

				IF v_sl_color = 'BRONZE' or v_sl_color = 'Bronze' THEN
					v_bronze := 'BRONZE';
				END IF;

				IF v_sl_color = 'BLUE' THEN
					v_blue := 'BLUE';
				END IF;

				IF v_sl_color = 'SILVER' THEN
					v_silver := 'SILVER';
				END IF;
			END IF;

		END LOOP;

		IF LENGTH(v_gold) > 0 THEN
			v_bcr := 'GOLD';
		END IF;

		IF LENGTH(v_silver) > 0 THEN
			IF LENGTH(v_bcr) > 0 THEN
				v_bcr := v_bcr || ',SILVER';
			ELSE
				v_bcr := 'SILVER';
			END IF;
		END IF;

		IF LENGTH(v_bronze) > 0 THEN
			IF LENGTH(v_bcr) > 0 THEN
				v_bcr := v_bcr || ',BRONZE';
			ELSE
				v_bcr := 'BRONZE';
			END IF;
		END IF;

		IF LENGTH(v_blue) > 0 THEN
			IF LENGTH(v_bcr) > 0 THEN
				v_bcr := v_bcr || ',BLUE';
			ELSE
				v_bcr := 'BLUE';
			END IF;
		END IF;

		update new_cct7_computers set 
			applications = v_string,
			service_level_objective = v_sl_objective,
			service_level_score = v_sl_score,
			service_level_colors = v_bcr
		 where lastid = v_lastid;

		CLOSE c_csc;

  END LOOP;

  CLOSE c_ac;
END;
/

COMMIT;

REM
REM is_gold_server
REM
update new_cct7_computers set is_gold_server = 'Y' where service_level_colors like '%GOLD%';

COMMIT;

REM
REM osmaint_weekly
REM osmaint_monthly
REM osmaint_quarterly
REM is_special_handling
REM

REM
REM Defaults:
REM
update new_cct7_computers set osmaint_weekly = 'TUE,THU 02:00 180';
update new_cct7_computers set osmaint_monthly = '2 SUN 00:00 240';
update new_cct7_computers set osmaint_quarterly = 'FEB,MAY,AUG,NOV 3 SAT 22:00 1440';
update new_cct7_computers set is_special_handling = 'N';

DECLARE
	v_lastid               NUMBER;
	v_osmaint_weekly       VARCHAR2(4000);
	v_osmaint_monthly      VARCHAR2(4000);
	v_osmaint_quarterly    VARCHAR2(4000);
	v_special_handling     VARCHAR2(10);

CURSOR c_xxx IS
	select distinct
		lastid,
		trim(cct_csc_osmaint_weekly),
		trim(cct_csc_osmaint_monthly),
		trim(cct_csc_osmaint_quarterly),
		cct_special_handling
	from
		cct7_csc;

BEGIN
  OPEN c_xxx;
  LOOP
    FETCH c_xxx INTO 
			v_lastid,
			v_osmaint_weekly,
			v_osmaint_monthly,
			v_osmaint_quarterly,
			v_special_handling;

    EXIT WHEN c_xxx%NOTFOUND;

		IF v_special_handling = '1'
		THEN
			update new_cct7_computers set is_special_handling = 'Y' where lastid = v_lastid;
		ELSE
			update new_cct7_computers set is_special_handling = 'N' where lastid = v_lastid;
		END IF;

		IF length(v_osmaint_weekly) > 0
		THEN
			update new_cct7_computers set osmaint_weekly = v_osmaint_weekly where lastid = v_lastid;
		END IF;

		IF length(v_osmaint_monthly) > 0
		THEN
			update new_cct7_computers set osmaint_monthly = v_osmaint_monthly where lastid = v_lastid;
		END IF;

		IF length(v_osmaint_quarterly) > 0
		THEN
			update new_cct7_computers set osmaint_quarterly = v_osmaint_quarterly where lastid = v_lastid;
		END IF;
		
	END LOOP;

	CLOSE c_xxx;
END;
/

COMMIT;
quit;
