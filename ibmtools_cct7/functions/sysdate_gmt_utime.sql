REM
REM datetime_to_utime.sql
REM

CREATE OR REPLACE FUNCTION
  sysdate_gmt_utime RETURN NUMBER 
IS 
  v_utime NUMBER(12,0);
BEGIN
  v_utime := round(((SYSDATE - numtodsinterval(TO_NUMBER(SUBSTR(TZ_OFFSET(sessiontimezone),1,3)), 'hour')) - to_date('19700101 000000', 'YYYYMMDD HH24MISS'))*86400);
  RETURN v_utime;
END sysdate_gmt_utime;
/
  
select sysdate_gmt_utime() from dual;