REM
REM unix_timestamp.sql
REM
REM Running Oracle 11.0.2 - Problem, function does not recognize IST (India) timezone
REM
REM select unix_timestamp() as t from dual;
REM select fn_number_to_date(unix_timestamp(), 'MST') from dual;
REM
REM See also: fn_number_to_date.sql
REM

create or replace function unix_timestamp
  return number is
begin
  return round((cast(sys_extract_utc(systimestamp) as date) - date'1970-01-01') * 86400);
end;
/