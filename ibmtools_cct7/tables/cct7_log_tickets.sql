WHENEVER SQLERROR CONTINUE
set echo on

CREATE TABLE cct7_log_tickets
(
  ticket_no                  VARCHAR2(20)               NOT NULL,
  event_date                 NUMBER          DEFAULT 0,
  event_cuid                 VARCHAR2(20),
  event_name                 VARCHAR2(200),
  event_type                 VARCHAR2(20),
  event_message              VARCHAR2(4000),
  sendmail_date              NUMBER          DEFAULT 0,

  FOREIGN KEY (ticket_no)  REFERENCES cct7_tickets  (ticket_no)  ON DELETE CASCADE
);

create index idx_cct7_log_tickets1 on cct7_log_tickets (ticket_no);            -- Pull all events by ticket_no
create index idx_cct7_log_tickets2 on cct7_log_tickets (event_cuid);           -- Pull all events by cuid
create index idx_cct7_log_tickets3 on cct7_log_tickets (event_type);           -- Pull all events by event type [EMAIL, PAGE, etc.]

COMMENT ON TABLE  cct7_log_tickets                   IS 'Event log for CCT Ticket. cct7_log_tickets';

COMMENT ON COLUMN cct7_log_tickets.ticket_no         IS 'FOREIGN KEY: cct7_tickets.ticket_no';
COMMENT ON COLUMN cct7_log_tickets.event_date        IS 'Event Date (GMT)';
COMMENT ON COLUMN cct7_log_tickets.event_cuid        IS 'Event Owner CUID';
COMMENT ON COLUMN cct7_log_tickets.event_name        IS 'Event Owner Name';
COMMENT ON COLUMN cct7_log_tickets.event_type        IS 'Event type';
COMMENT ON COLUMN cct7_log_tickets.event_message     IS 'Event message';
COMMENT ON COLUMN cct7_log_tickets.sendmail_date     IS 'Date when this log message was sent to users';


