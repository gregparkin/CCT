WHENEVER SQLERROR CONTINUE
set echo on

CREATE TABLE cct7_systems 
(
	system_id                        NUMBER                    NOT NULL PRIMARY KEY,
	ticket_no                        VARCHAR2(20)              NOT NULL,
	system_insert_date               NUMBER          DEFAULT 0,
	system_insert_cuid               VARCHAR2(20),
	system_insert_name               VARCHAR2(200),
	system_update_date               NUMBER          DEFAULT 0,
	system_update_cuid               VARCHAR2(20),
	system_update_name               VARCHAR2(200),
	system_lastid                    NUMBER,
	system_hostname                  VARCHAR2(255),
	system_os                        VARCHAR2(20),
	system_usage                     VARCHAR2(80),
	system_location                  VARCHAR2(80),
	system_timezone_name             VARCHAR2(200),
	system_osmaint_weekly            VARCHAR2(4000),
	system_respond_by_date           NUMBER          DEFAULT 0,
	system_work_start_date           NUMBER          DEFAULT 0,
	system_work_end_date             NUMBER          DEFAULT 0,
	system_work_duration             VARCHAR2(30),
	system_work_status               VARCHAR2(20),
	total_contacts_responded         NUMBER          DEFAULT 0,
	total_contacts_not_responded     NUMBER          DEFAULT 0,
	original_work_start_date         NUMBER          DEFAULT 0,
	original_work_end_date           NUMBER          DEFAULT 0,
	original_work_duration           VARCHAR2(30),
	change_date                      NUMBER          DEFAULT 0,
	disable_schedule                 VARCHAR2(1)     DEFAULT 'N',

	FOREIGN KEY (ticket_no) REFERENCES cct7_tickets (ticket_no) ON DELETE CASCADE
);

DROP SEQUENCE cct7_systemsseq;
CREATE SEQUENCE cct7_systemsseq INCREMENT BY 1 START WITH 1 NOCACHE;

CREATE INDEX idx_cct7_systems1 ON cct7_systems (ticket_no);
CREATE INDEX idx_cct7_systems2 ON cct7_systems (system_id, ticket_no);
CREATE INDEX idx_cct7_systems3 ON cct7_systems (system_lastid);
CREATE INDEX idx_cct7_systems4 ON cct7_systems (system_hostname);
create index idx_cct7_systems5 on cct7_systems (system_id, system_update_date, change_date);

COMMENT ON TABLE cct7_systems                                  IS 'Generated list of servers for a work request identified in cct7_tickets';
COMMENT ON COLUMN cct7_systems.system_id                       IS 'PK: Unique record ID';
COMMENT ON COLUMN cct7_systems.ticket_no                       IS 'FK: Link to cct7_tickets record';
COMMENT ON COLUMN cct7_systems.system_insert_date              IS 'GMT UNIX TIME - Date of person who created this record';
COMMENT ON COLUMN cct7_systems.system_insert_cuid              IS 'CUID of person who created this record';
COMMENT ON COLUMN cct7_systems.system_insert_name              IS 'Name of person who created this record';
COMMENT ON COLUMN cct7_systems.system_update_date              IS 'GMT UNIX TIME - Date of person who updated this record';
COMMENT ON COLUMN cct7_systems.system_update_cuid              IS 'CUID of person who updated this record';
COMMENT ON COLUMN cct7_systems.system_update_name              IS 'Name of person who updated this record';
COMMENT ON COLUMN cct7_systems.system_lastid                   IS '233494988';
COMMENT ON COLUMN cct7_systems.system_hostname                 IS 'hvdnp16e';
COMMENT ON COLUMN cct7_systems.system_os                       IS 'HPUX';
COMMENT ON COLUMN cct7_systems.system_usage                    IS 'PRODUCTION';
COMMENT ON COLUMN cct7_systems.system_timezone_name            IS '(i.e. America/Chicago';
COMMENT ON COLUMN cct7_systems.system_location                 IS 'DENVER';
COMMENT ON COLUMN cct7_systems.system_osmaint_weekly           IS 'MON,TUE,WED,THU,FRI,SAT,SUN 2200 480';
COMMENT ON COLUMN cct7_systems.system_respond_by_date          IS 'Copied over from cct7_tickets.respond_by_date';
COMMENT ON COLUMN cct7_systems.system_work_start_date          IS 'GMT UNIX TIME - Actual computed work start datetime';
COMMENT ON COLUMN cct7_systems.system_work_end_date            IS 'GMT UNIX TIME - Actual computed work end datetime';
COMMENT ON COLUMN cct7_systems.system_work_duration            IS 'Actual computed work duration window';
COMMENT ON COLUMN cct7_systems.system_work_status              IS 'WAITING, READY, REJECTED, CANCELED';
COMMENT ON COLUMN cct7_systems.total_contacts_responded        IS 'Total number of contacts who have responded';
COMMENT ON COLUMN cct7_systems.total_contacts_not_responded    IS 'Total number of contacts who have NOT responded';
COMMENT ON COLUMN cct7_systems.original_work_start_date        IS 'Original scheduled work start date';
COMMENT ON COLUMN cct7_systems.original_work_end_date          IS 'Original scheduled work end date';
COMMENT ON COLUMN cct7_systems.original_work_duration          IS 'Original schedule work duration';
COMMENT ON COLUMN cct7_systems.change_date                     IS 'Used by send_notifications.php to send out email to clients';
COMMENT ON COLUMN cct7_systems.disable_schedule                IS 'Disable scheduler Y/N where Y means we are using the Remedy IR start/end/duration for all servers.';

