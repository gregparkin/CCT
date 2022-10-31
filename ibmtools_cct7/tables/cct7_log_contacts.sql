WHENEVER SQLERROR CONTINUE
set echo on

CREATE TABLE cct7_log_contacts
(
  ticket_no                  VARCHAR2(20)               NOT NULL,
  system_id                  NUMBER                     NOT NULL,
  hostname                   VARCHAR2(255)              NOT NULL,
  netpin_no                  VARCHAR2(20)               NOT NULL,
  event_date                 NUMBER          DEFAULT 0,
  event_cuid                 VARCHAR2(20),
  event_name                 VARCHAR2(200),
  event_type                 VARCHAR2(20),
  event_message              VARCHAR2(4000),
  sendmail_date              NUMBER          DEFAULT 0,

  FOREIGN KEY (system_id)  REFERENCES cct7_systems  (system_id)  ON DELETE CASCADE
);

create index idx_cct7_log_contacts1 on cct7_log_contacts (ticket_no);            -- Pull all events by ticket_no
create index idx_cct7_log_contacts2 on cct7_log_contacts (system_id);            -- Pull all events by system_id
create index idx_cct7_log_contacts3 on cct7_log_contacts (hostname);             -- Pull all events by hostname (multiple ticket events)
create index idx_cct7_log_contacts4 on cct7_log_contacts (netpin_no);            -- Pull all events by netpin_no
create index idx_cct7_log_contacts5 on cct7_log_contacts (system_id, netpin_no); -- Pull all events by system_id and netpin_no
create index idx_cct7_log_contacts6 on cct7_log_contacts (hostname, netpin_no);  -- Pull all events by hostname and netpin_no
create index idx_cct7_log_contacts7 on cct7_log_contacts (event_cuid);           -- Pull all events by cuid
create index idx_cct7_log_contacts8 on cct7_log_contacts (event_type);           -- Pull all events by event type [EMAIL, PAGE, etc.]

COMMENT ON TABLE  cct7_log_contacts                   IS 'Log table for cct7_systems records';

COMMENT ON COLUMN cct7_log_contacts.ticket_no         IS 'CCT ticket number.';
COMMENT ON COLUMN cct7_log_contacts.system_id         IS 'FOREIGN KEY - cct7_systems.system_id - CASCADE DELETE';
COMMENT ON COLUMN cct7_log_contacts.hostname          IS 'Hostname for this log entry';
COMMENT ON COLUMN cct7_log_contacts.netpin_no         IS 'CSC/Net-Tool Pin No.';
COMMENT ON COLUMN cct7_log_contacts.event_date        IS 'Event Date (GMT)';
COMMENT ON COLUMN cct7_log_contacts.event_cuid        IS 'Event Owner CUID';
COMMENT ON COLUMN cct7_log_contacts.event_name        IS 'Event Owner Name';
COMMENT ON COLUMN cct7_log_contacts.event_type        IS 'Event type';
COMMENT ON COLUMN cct7_log_contacts.event_message     IS 'Event message';
COMMENT ON COLUMN cct7_log_contacts.sendmail_date     IS 'Date when this log message was sent to users';
