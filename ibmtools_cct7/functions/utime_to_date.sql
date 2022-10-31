REM
REM utime_to_date.sql
REM

CREATE OR REPLACE FUNCTION
  utime_to_date(p_utime IN NUMBER, p_timezone IN VARCHAR2) RETURN DATE
IS
  v_date DATE;
BEGIN
  v_date := to_date('01/01/1970 00:00','mm/dd/yyyy HH24:mi') + (p_utime + TO_NUMBER(SUBSTR(TZ_OFFSET(p_timezone), 1, 3)) * 3600) / 24 / 60 / 60;
  RETURN v_date;
END utime_to_date;
/

select sessiontimezone from dual;
select utime_to_date(1457971380, 'America/Denver') from dual;
