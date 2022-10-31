CREATE OR REPLACE PROCEDURE drop_index_if_exist (idxname VARCHAR2) IS
BEGIN
   EXECUTE IMMEDIATE 'DROP INDEX ' || idxname;
EXCEPTION
   WHEN OTHERS THEN
      IF SQLCODE != -942 THEN
         RAISE;
      END IF;
END drop_index_if_exist;
/

