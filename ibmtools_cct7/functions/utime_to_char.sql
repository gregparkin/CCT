REM
REM utime_to_char.sql
REM

CREATE OR REPLACE FUNCTION
  utime_to_char(p_utime IN NUMBER, p_timezone IN VARCHAR2) RETURN VARCHAR2
IS
  v_datetime VARCHAR2(80);
  BEGIN
    v_datetime := to_char(to_date('01/01/1970 00:00','mm/dd/yyyy HH24:mi') +
                          (p_utime + TO_NUMBER(SUBSTR(TZ_OFFSET(p_timezone), 1, 3)) * 3600) / 24 / 60 / 60, 'MM/DD/YYYY HH24:MI');
    RETURN v_datetime;
  END utime_to_char;
/

CREATE OR REPLACE FUNCTION
  utime_to_char2(p_utime IN NUMBER, p_timezone IN VARCHAR2) RETURN VARCHAR2
IS
  v_datetime VARCHAR2(80);
  BEGIN
    v_datetime := to_char(to_date('01/01/1970 00:00','mm/dd/yyyy HH24:MI') +
                          (p_utime + TO_NUMBER(SUBSTR(TZ_OFFSET(p_timezone), 1, 3)) * 3600) / 24 / 60 / 60, 'MM/DD/YYYY');
    RETURN v_datetime;
  END utime_to_char2;
/

select sessiontimezone from dual;
-- 1457971380 = 03/14/2016 10:03
select utime_to_char(1457971380, 'America/Denver') from dual;