REM
REM sysdate_gmt.sql
REM

CREATE OR REPLACE FUNCTION
  sysdate_gmt RETURN DATE
IS
  v_date DATE;
BEGIN
  v_date := SYSDATE - numtodsinterval(TO_NUMBER(SUBSTR(TZ_OFFSET(sessiontimezone),1,3)), 'hour');
  RETURN v_date;
END sysdate_gmt;
/

select sysdate_gmt() from dual;