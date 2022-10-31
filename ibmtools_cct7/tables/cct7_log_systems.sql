WHENEVER SQLERROR CONTINUE
set echo on

CREATE TABLE cct7_log_systems
(
  ticket_no                  VARCHAR2(20)               NOT NULL,
  system_id                  NUMBER                     NOT NULL,
  hostname                   VARCHAR2(255)              NOT NULL,
  event_date                 NUMBER          DEFAULT 0,
  event_cuid                 VARCHAR2(20),
  event_name                 VARCHAR2(200),
  event_type                 VARCHAR2(20),
  event_message              VARCHAR2(4000),
  sendmail_date              NUMBER          DEFAULT 0,

  FOREIGN KEY (ticket_no)  REFERENCES cct7_tickets  (ticket_no)  ON DELETE CASCADE,
  FOREIGN KEY (system_id)  REFERENCES cct7_systems  (system_id)  ON DELETE CASCADE
);

create index idx_cct7_log_systems1 on cct7_log_systems (ticket_no);            -- Pull all events by ticket_no
create index idx_cct7_log_systems2 on cct7_log_systems (system_id);            -- Pull all events by system_id
create index idx_cct7_log_systems3 on cct7_log_systems (hostname);             -- Pull all events by hostname (history)
create index idx_cct7_log_systems4 on cct7_log_systems (event_cuid);           -- Pull all events by cuid
create index idx_cct7_log_systems5 on cct7_log_systems (event_type);           -- Pull all events by event type [EMAIL, PAGE, etc.]

COMMENT ON TABLE  cct7_log_systems                   IS 'Log table for cct7_systems records';

COMMENT ON COLUMN cct7_log_systems.ticket_no         IS 'FOREIGN KEY - cct7_tickets.ticket_no - CASCADE DELETE';
COMMENT ON COLUMN cct7_log_systems.system_id         IS 'FOREIGN KEY - cct7_systems.system_id - CASCADE DELETE';
COMMENT ON COLUMN cct7_log_systems.hostname          IS 'Hostname for this log entry';
COMMENT ON COLUMN cct7_log_systems.event_date        IS 'Event Date (GMT)';
COMMENT ON COLUMN cct7_log_systems.event_cuid        IS 'Event Owner CUID';
COMMENT ON COLUMN cct7_log_systems.event_name        IS 'Event Owner Name';
COMMENT ON COLUMN cct7_log_systems.event_type        IS 'Event type';
COMMENT ON COLUMN cct7_log_systems.event_message     IS 'Event message';
COMMENT ON COLUMN cct7_log_systems.sendmail_date     IS 'Date when this log message was sent to users';
