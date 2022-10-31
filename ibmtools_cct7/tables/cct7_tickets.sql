WHENEVER SQLERROR CONTINUE
set echo on

CREATE TABLE cct7_tickets
(
  ticket_no                   VARCHAR2(20) NULL PRIMARY KEY,

  insert_date                 NUMBER DEFAULT 0,
  insert_cuid                 VARCHAR2(20),
  insert_name                 VARCHAR2(200),

  update_date                 NUMBER DEFAULT 0,
  update_cuid                 VARCHAR2(20),
  update_name                 VARCHAR2(200),

  status                      VARCHAR2(20),
  status_date                 NUMBER DEFAULT 0,
  status_cuid                 VARCHAR2(20),
  status_name                 VARCHAR2(200),

  owner_cuid                  VARCHAR2(20),
  owner_first_name            VARCHAR2(80),
  owner_name                  VARCHAR2(200),
  owner_email                 VARCHAR2(40),
  owner_job_title             VARCHAR2(80),

  manager_cuid                VARCHAR2(20),
  manager_first_name          VARCHAR2(80),
  manager_name                VARCHAR2(200),
  manager_email               VARCHAR2(40),
  manager_job_title           VARCHAR2(80),

  work_activity               VARCHAR2(80),
  approvals_required          VARCHAR2(1) DEFAULT 'Y',
  reboot_required             VARCHAR2(1) DEFAULT 'Y',

  email_reminder1_date        NUMBER DEFAULT 0,
  email_reminder2_date        NUMBER DEFAULT 0,
  email_reminder3_date        NUMBER DEFAULT 0,
  respond_by_date             NUMBER DEFAULT 0,
  schedule_start_date         NUMBER DEFAULT 0,
  schedule_end_date           NUMBER DEFAULT 0,

  work_description            VARCHAR2(4000),
  work_implementation         VARCHAR2(4000),
  work_backoff_plan           VARCHAR2(4000),
  work_business_reason        VARCHAR2(4000),
  work_user_impact            VARCHAR2(4000),

  cm_ticket_no                VARCHAR2(20),
  remedy_cm_start_date        NUMBER DEFAULT 0,
  remedy_cm_end_date          NUMBER DEFAULT 0,
  total_servers_scheduled     NUMBER DEFAULT 0,
  total_servers_waiting       NUMBER DEFAULT 0,
  total_servers_approved      NUMBER DEFAULT 0,
  total_servers_rejected      NUMBER DEFAULT 0,
  total_servers_not_scheduled NUMBER DEFAULT 0,
  servers_not_scheduled       VARCHAR2(4000),
  generator_runtime           VARCHAR2(80),
  csc_banner1                 VARCHAR2(1) DEFAULT 'Y',
  csc_banner2                 VARCHAR2(1) DEFAULT 'Y',
  csc_banner3                 VARCHAR2(1) DEFAULT 'Y',
  csc_banner4                 VARCHAR2(1) DEFAULT 'Y',
  csc_banner5                 VARCHAR2(1) DEFAULT 'Y',
  csc_banner6                 VARCHAR2(1) DEFAULT 'Y',
  csc_banner7                 VARCHAR2(1) DEFAULT 'Y',
  csc_banner8                 VARCHAR2(1) DEFAULT 'Y',
  csc_banner9                 VARCHAR2(1) DEFAULT 'Y',
  csc_banner10                VARCHAR2(1) DEFAULT 'Y',
  exclude_virtual_contacts    VARCHAR2(1) DEFAULT 'N',
  change_date                 NUMBER      DEFAULT 0,
  maintenance_window          VARCHAR2(20),
  disable_scheduler           VARCHAR2(1) DEFAULT 'N',
  cm_start_date               NUMBER      DEFAULT 0,
  cm_end_date                 NUMBER      DEFAULT 0,
  cm_duration_computed        VARCHAR2(30),
  cm_ipl_boot                 VARCHAR2(1),
  cm_status                   VARCHAR2(15),
  cm_open_closed              VARCHAR2(10),
  cm_close_date               NUMBER      DEFAULT 0,
  cm_owner_first_name         VARCHAR2(80),
  cm_owner_last_name          VARCHAR2(80),
  cm_owner_cuid               VARCHAR2(10),
  cm_owner_group              VARCHAR2(254),
  note_to_clients             VARCHAR2(4000)

);

create index idx_cct7_tickets_1 on cct7_tickets (cm_ticket_no);
create index idx_cct7_tickets_2 on cct7_tickets (status_cuid, owner_cuid, manager_cuid);
create index idx_cct7_tickets_3 on cct7_tickets (ticket_no, update_date, change_date);

REM
REM This sequence is used when we want to create a CCT test ticket only
REM
drop sequence cct7_ticketsseq;
create sequence cct7_ticketsseq increment by 1 start with 1 nocache;

COMMENT ON TABLE  cct7_tickets                             IS 'CCT Tickets';
COMMENT ON COLUMN cct7_tickets.ticket_no                   IS 'PK: Unique Record ID';

COMMENT ON COLUMN cct7_tickets.insert_date                 IS 'Date record was inserted (GMT)';
COMMENT ON COLUMN cct7_tickets.insert_cuid                 IS 'CUID of person who inserted the record';
COMMENT ON COLUMN cct7_tickets.insert_name                 IS 'Name of person who inserted the record';

COMMENT ON COLUMN cct7_tickets.update_date                 IS 'Date record was updated (GMT)';
COMMENT ON COLUMN cct7_tickets.update_cuid                 IS 'CUID of person who updated the record';
COMMENT ON COLUMN cct7_tickets.update_name                 IS 'Name of person who updated the record';


COMMENT ON COLUMN cct7_tickets.status                      IS 'DRAFT, ACTIVE, FROZEN, CANCELED';
COMMENT ON COLUMN cct7_tickets.status_date                 IS 'Date when status last changed (GMT)';
COMMENT ON COLUMN cct7_tickets.status_cuid                 IS 'CUID of person who changed the status';
COMMENT ON COLUMN cct7_tickets.status_name                 IS 'Name of person who changed the status';

COMMENT ON COLUMN cct7_tickets.owner_cuid                  IS 'CUID of person who owns this work request.';
COMMENT ON COLUMN cct7_tickets.owner_first_name            IS 'First name of person who created this record';
COMMENT ON COLUMN cct7_tickets.owner_name                  IS 'Name of person who created this record. (Same as insert_name)';
COMMENT ON COLUMN cct7_tickets.owner_email                 IS 'Email address of person who created this record';
COMMENT ON COLUMN cct7_tickets.owner_job_title             IS 'Owners job title';

COMMENT ON COLUMN cct7_tickets.manager_cuid                IS 'Owners managers CUID';
COMMENT ON COLUMN cct7_tickets.manager_first_name          IS 'Owners managers first_name';
COMMENT ON COLUMN cct7_tickets.manager_name                IS 'Owners managers full name';
COMMENT ON COLUMN cct7_tickets.manager_email               IS 'Owners managers email address';
COMMENT ON COLUMN cct7_tickets.manager_job_title           IS 'Owners managers job title';

COMMENT ON COLUMN cct7_tickets.work_activity               IS 'Patching, GSD331, etc.';
COMMENT ON COLUMN cct7_tickets.approvals_required          IS 'Y or N';
COMMENT ON COLUMN cct7_tickets.reboot_required             IS 'Y or N';

COMMENT ON COLUMN cct7_tickets.email_reminder1_date        IS 'Reminder Email 1 date (GMT)';
COMMENT ON COLUMN cct7_tickets.email_reminder2_date        IS 'Reminder Email 2 date (GMT)';
COMMENT ON COLUMN cct7_tickets.email_reminder3_date        IS 'Reminder Email 3 date (GMT)';
COMMENT ON COLUMN cct7_tickets.respond_by_date             IS 'Respond by date (GMT)';
COMMENT ON COLUMN cct7_tickets.schedule_start_date         IS 'Work Start Date (GMT)';
COMMENT ON COLUMN cct7_tickets.schedule_end_date           IS 'Work End Date (GMT)';

COMMENT ON COLUMN cct7_tickets.work_description            IS 'Detail description of the work activity';
COMMENT ON COLUMN cct7_tickets.work_implementation         IS 'Implementation Instructions';
COMMENT ON COLUMN cct7_tickets.work_backoff_plan           IS 'Back out plans if there are problems';
COMMENT ON COLUMN cct7_tickets.work_business_reason        IS 'Business reason for the change';
COMMENT ON COLUMN cct7_tickets.work_user_impact            IS 'What impacts to users while doing the change';

COMMENT ON COLUMN cct7_tickets.cm_ticket_no                IS 'Remedy CM Ticket Number';

COMMENT ON COLUMN cct7_tickets.remedy_cm_start_date        IS 'Start Date for the Remedy CM Ticket';
COMMENT ON COLUMN cct7_tickets.remedy_cm_end_date          IS 'End Date for the Remedy CM Ticket';
COMMENT ON COLUMN cct7_tickets.total_servers_scheduled     IS 'Total scheduled servers';
COMMENT ON COLUMN cct7_tickets.total_servers_not_scheduled IS 'Total servers not scheduled';
COMMENT ON COLUMN cct7_tickets.total_servers_waiting       IS 'Total servers WAITING for responses from clients';
COMMENT ON COLUMN cct7_tickets.total_servers_approved      IS 'Total servers APPROVED by clients';
COMMENT ON COLUMN cct7_tickets.total_servers_rejected      IS 'Total servers REJECTED by clients';

COMMENT ON COLUMN cct7_tickets.servers_not_scheduled       IS 'Servers not scheduled. Not found in cct7_computers.';
COMMENT ON COLUMN cct7_tickets.generator_runtime           IS 'Total minutes and seconds the server took to generate the schedule.';

COMMENT ON COLUMN cct7_tickets.csc_banner1                 IS 'CSC Banner: Applications or Databases Desiring Notification (Not Hosted on this Server)';
COMMENT ON COLUMN cct7_tickets.csc_banner2                 IS 'CSC Banner: Application Support';
COMMENT ON COLUMN cct7_tickets.csc_banner3                 IS 'CSC Banner: Application Utilities, Middleware, Legacy DB, Other (Hosted on this Server)';
COMMENT ON COLUMN cct7_tickets.csc_banner4                 IS 'CSC Banner: Infrastructure';
COMMENT ON COLUMN cct7_tickets.csc_banner5                 IS 'CSC Banner: MiddleWare Support';
COMMENT ON COLUMN cct7_tickets.csc_banner6                 IS 'CSC Banner: Database Support';
COMMENT ON COLUMN cct7_tickets.csc_banner7                 IS 'CSC Banner: Development Database Support';
COMMENT ON COLUMN cct7_tickets.csc_banner8                 IS 'CSC Banner: Operating System Support';
COMMENT ON COLUMN cct7_tickets.csc_banner9                 IS 'CSC Banner: Applications Owning Database (DB Hosted on this Server, Owning App Is Not)';
COMMENT ON COLUMN cct7_tickets.csc_banner10                IS 'CSC Banner: Development Support';
COMMENT ON COLUMN cct7_tickets.exclude_virtual_contacts    IS 'Exclude virtual contacts';
COMMENT ON COLUMN cct7_tickets.disable_scheduler           IS 'Display scheduler and use Remedy ticket window';
COMMENT ON COLUMN cct7_tickets.change_date                 IS 'Used by send_notifications.php to send out email to clients';


COMMENT ON COLUMN cct7_tickets.maintenance_window          IS '[remedy,weekly,monthly,quarterly]';
COMMENT ON COLUMN cct7_tickets.cm_start_date               IS 'GMT utime for Remedy ticket start';
COMMENT ON COLUMN cct7_tickets.cm_end_date                 IS 'GMT utime for Remedy ticket end';
COMMENT ON COLUMN cct7_tickets.cm_duration_computed        IS 'String containing computed duration from start to end';
COMMENT ON COLUMN cct7_tickets.cm_ipl_boot                 IS 'Will server be rebooted? Y/N';
COMMENT ON COLUMN cct7_tickets.cm_status                   IS '[Returned,Approved,Pending,Turnover]';
COMMENT ON COLUMN cct7_tickets.cm_open_closed              IS '[Open,Closed]';
COMMENT ON COLUMN cct7_tickets.cm_close_date               IS 'GMT utime for Remedy ticket close';
COMMENT ON COLUMN cct7_tickets.cm_owner_first_name         IS 'First name for ticket owner';
COMMENT ON COLUMN cct7_tickets.cm_owner_last_name          IS 'Last name for ticket owner';
COMMENT ON COLUMN cct7_tickets.cm_owner_cuid               IS 'CUID for ticket owner';
COMMENT ON COLUMN cct7_tickets.cm_owner_group              IS 'Assignment Group for ticket owner';
COMMENT ON COLUMN cct7_tickets.note_to_clients             IS 'Optional not to client used when ticket is submitted.';


alter table cct7_tickets add (
cm_start_date        NUMBER DEFAULT 0,
cm_end_date          NUMBER DEFAULT 0,
cm_duration_computed VARCHAR2 (30),
cm_ipl_boot          VARCHAR2 (1),
cm_status            VARCHAR2 (15),
cm_open_closed       VARCHAR2 (10),
cm_close_date        NUMBER DEFAULT 0,
cm_owner_first_name  VARCHAR2 (80),
cm_owner_last_name   VARCHAR2 (80),
cm_owner_cuid        VARCHAR2 (10),
cm_owner_group       VARCHAR2 (254)
);

alter table cct7_tickets add (note_to_clients             VARCHAR2(4000));
COMMENT ON COLUMN cct7_tickets.note_to_clients             IS 'Optional not to client used when ticket is submitted.';
