REM
REM datetime_to_utime.sql
REM

CREATE OR REPLACE FUNCTION
  datetime_to_utime(p_mmddyyyy_hhmm IN VARCHAR2, p_timezone IN VARCHAR2) RETURN NUMBER
IS
  v_utime NUMBER(12,0);
BEGIN
  v_utime := round((to_date(p_mmddyyyy_hhmm, 'mm/dd/yyyy HH24:mi') -
    to_date('01/01/1970 00:00', 'mm/dd/yyyy HH24:mi')) * 24 * 60 * 60 -
    TO_NUMBER(SUBSTR(TZ_OFFSET(p_timezone), 1, 3)) * 3600);
  RETURN v_utime;
END datetime_to_utime;
/

select sessiontimezone from dual;
select datetime_to_utime('03/14/2016 10:03', 'America/Denver') from dual;