WHENEVER SQLERROR CONTINUE
set echo on

CREATE TABLE cct7_no_changes
(
  no_change_type            varchar2(80),
  start_date                number,
  end_date                  number,
  reason                    varchar2(4000)
);

COMMENT ON TABLE cct7_no_changes                 IS 'Change Management No Change dates in (CST)';
COMMENT ON COLUMN cct7_no_changes.no_change_type IS 'No Change or Minimal Change';
COMMENT ON COLUMN cct7_no_changes.start_date     IS 'Start date and time';
COMMENT ON COLUMN cct7_no_changes.end_date       IS 'End date and time';
COMMENT ON COLUMN cct7_no_changes.reason         IS 'Reason for no changes';



commit;
