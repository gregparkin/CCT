REM
REM fn_number_to_date.sql
REM


CREATE OR REPLACE FUNCTION
	fn_number_to_date(p_no IN NUMBER, p_tz IN VARCHAR2) RETURN DATE
IS
	v_dt DATE;
	v_tz VARCHAR2(3);
BEGIN
	v_dt := to_date('01-Jan-1970 00:00','DD-MON-YYYY hh24:mi')+(p_no / (60*60*24));
	v_dt := NEW_TIME(v_dt,'GMT',p_tz);
	RETURN v_dt;
END fn_number_to_date;
/

