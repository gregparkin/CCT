CREATE OR REPLACE PROCEDURE drop_table_if_exist (tabname VARCHAR2) IS
BEGIN
   EXECUTE IMMEDIATE 'DROP TABLE ' || tabname;
EXCEPTION
   WHEN OTHERS THEN
      IF SQLCODE != -942 THEN
         RAISE;
      END IF;
END drop_table_if_exist;
/

create table crap ( name varchar2(200));

exec drop_table_if_exist('CRAP');