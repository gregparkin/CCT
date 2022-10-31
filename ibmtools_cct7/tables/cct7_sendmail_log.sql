REM
REM cct7_sendmail_log.sql
REM
REM Used by send_notifications.php (cronjob) to record PHP mail() results
REM
REM Used for debugging and email tracking
REM

create table cct7_sendmail_log
(
  sendmail_date      NUMBER,
  sendmail_cuid      VARCHAR2(20),
  sendmail_name      VARCHAR2(200),
  sendmail_email     VARCHAR2(80),
  sendmail_success   VARCHAR2(1),
  sendmail_subject   VARCHAR2(4000),
  sendmail_message   VARCHAR2(4000)
);

create index idx_cct7_sendmail_log1 on cct7_sendmail_log (sendmail_cuid);
create index idx_cct7_sendmail_log2 on cct7_sendmail_log (sendmail_email);

COMMENT ON TABLE  cct7_sendmail_log                  IS 'CCT sendmail log for debugging purposes';
COMMENT ON COLUMN cct7_sendmail_log.sendmail_date    IS 'GMT Date of this record';
COMMENT ON COLUMN cct7_sendmail_log.sendmail_cuid    IS 'CUID of the person we emailed to';
COMMENT ON COLUMN cct7_sendmail_log.sendmail_name    IS 'Name of the person';
COMMENT ON COLUMN cct7_sendmail_log.sendmail_email   IS 'Email address';
COMMENT ON COLUMN cct7_sendmail_log.sendmail_success IS 'PHP mail() successful? Y/N';
COMMENT ON COLUMN cct7_sendmail_log.sendmail_subject IS 'Email Subject Line';
COMMENT ON COLUMN cct7_sendmail_log.sendmail_message IS 'Email message body';