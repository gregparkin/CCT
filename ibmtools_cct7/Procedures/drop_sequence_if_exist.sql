CREATE OR REPLACE PROCEDURE drop_sequence_if_exist (seqname VARCHAR2) IS
BEGIN
   EXECUTE IMMEDIATE 'DROP SEQUENCE ' || seqname;
EXCEPTION
   WHEN OTHERS THEN
      IF SQLCODE != -942 THEN
         RAISE;
      END IF;
END drop_sequence_if_exist;
/

