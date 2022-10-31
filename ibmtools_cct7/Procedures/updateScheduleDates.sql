--
-- updateScheduleDates()
--
-- @package   PhpStorm
-- @file      updateScheduleDates.sql
-- @author    gparkin
-- @copyright 2003-2017 IBM - All Rights Reserved
-- @date      03/21/2017
-- @version   7.0
--
-- @brief This stored procedure is used to update cct7_tickets with new computed scheduled start and end dates.
--
-- library.php:		$query = sprintf("BEGIN updateScheduleDates('%s'); END;", $ticket_no);

set echo on;
set pagesize 60;
set linesize 2000;
set heading OFF;
set serveroutput on size 1000000 format wrapped;

CREATE OR REPLACE procedure updateScheduleDates(v_this_ticket_no IN varchar2) IS
  v_start_date   NUMBER;
  v_end_date     NUMBER;

  CURSOR c1(v_ticket_no VARCHAR2) IS
    select
      min(system_work_start_date)  as new_start_date,
      max(system_work_end_date)    as new_end_date
    from
      cct7_systems
    where
      ticket_no = v_ticket_no;

  BEGIN
    OPEN c1(v_this_ticket_no);
    FETCH c1 INTO v_start_date, v_end_date;
    CLOSE c1;

    UPDATE cct7_tickets set
      schedule_start_date = v_start_date,
      schedule_end_date   = v_end_date
    WHERE
      ticket_no = v_this_ticket_no;

    COMMIT;
  END;
/