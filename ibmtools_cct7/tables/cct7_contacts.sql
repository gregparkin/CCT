WHENEVER SQLERROR CONTINUE
set echo on

CREATE TABLE cct7_contacts
(
  contact_id                    NUMBER         NOT NULL PRIMARY KEY,
  system_id                     NUMBER,
  contact_netpin_no             VARCHAR2(20),     -- Netpin
  contact_insert_date           NUMBER         DEFAULT 0,
  contact_insert_cuid           VARCHAR2(20),
  contact_insert_name           VARCHAR2(200),
  contact_update_date           NUMBER         DEFAULT 0,
  contact_update_cuid           VARCHAR2(20),
  contact_update_name           VARCHAR2(200),
  contact_connection            VARCHAR2(80),   -- Connections
  contact_server_os             VARCHAR2(80),   -- OS
  contact_server_usage          VARCHAR2(80),   -- Status
  contact_work_group            VARCHAR2(80),   -- Group Types
  contact_approver_fyi          VARCHAR2(80),   -- Notify Type
  contact_csc_banner            VARCHAR2(200),  -- CSC Support Banners (Primary)
  contact_apps_databases        VARCHAR2(200),  -- Apps/DBMS
  contact_respond_by_date       NUMBER         DEFAULT 0,
  contact_response_status       VARCHAR2(20),
  contact_response_date         NUMBER         DEFAULT 0,
  contact_response_cuid         VARCHAR2(20),
  contact_response_name         VARCHAR2(200),
  contact_send_page             VARCHAR2(10)   DEFAULT 'Y',
  contact_send_email            VARCHAR2(10)   DEFAULT 'Y',
  change_date                   NUMBER         DEFAULT 0,

  FOREIGN KEY (system_id) REFERENCES cct7_systems (system_id) ON DELETE CASCADE
);

-- Netpin/Members Connections              OS       Status      Approval Group Types Notify Type CSC Support Banners (Primary)       Apps/DBMS
-- ============== ======================== ======== =========== ======== =========== =========== =================================== =========
-- 51190          hcdnx11a,                COMPLEX, PRODUCTION, WAITING  OS          APROVER     Operating System Support(mits-all), NONE,
-- aa65437        hcdnx11a->hcdnx11a-san2, ,        PRODUCTION,                                  NONE,                               NONE,
-- ab04341(P)     ...                      ,        PRODUCTION,                                  NONE,                               NONE,
-- ab39729(B)     hcdnx11a->hhdnp29a       HPUX,    PRODUCTION,                                  Operating System Support(mits-all), NONE,

DROP SEQUENCE cct7_contactsseq;
CREATE SEQUENCE cct7_contactsseq INCREMENT BY 1 START WITH 1 NOCACHE;

CREATE INDEX idx_cct7_contacts1 ON cct7_contacts (contact_netpin_no);
CREATE INDEX idx_cct7_contacts2 ON cct7_contacts (system_id);
CREATE INDEX idx_cct7_contacts3 ON cct7_contacts (system_id, contact_netpin_no);
CREATE INDEX idx_cct7_contacts4 ON cct7_contacts (system_id, contact_approver_fyi);
CREATE INDEX idx_cct7_contacts5 ON cct7_contacts (contact_netpin_no, system_id, contact_approver_fyi, contact_response_status);
create index idx_cct7_contacts6 on cct7_contacts (system_id, contact_update_date, change_date);

COMMENT ON TABLE cct7_contacts IS 'Generated list of contacts for systems found in cct7_systems';

COMMENT ON COLUMN cct7_contacts.contact_id                    IS 'PK: Unique record ID';
COMMENT ON COLUMN cct7_contacts.system_id                     IS 'FK: cct7_systems.system_id - CASCADE DELETE';

COMMENT ON COLUMN cct7_contacts.contact_netpin_no             IS 'CSC/Net-Tool Pin number';

COMMENT ON COLUMN cct7_contacts.contact_insert_date           IS 'Date of person who created this record';
COMMENT ON COLUMN cct7_contacts.contact_insert_cuid           IS 'CUID of person who created this record';
COMMENT ON COLUMN cct7_contacts.contact_insert_name           IS 'Name of person who created this record';

COMMENT ON COLUMN cct7_contacts.contact_update_date           IS 'Date of person who updated this record';
COMMENT ON COLUMN cct7_contacts.contact_update_cuid           IS 'CUID of person who updated this record';
COMMENT ON COLUMN cct7_contacts.contact_update_name           IS 'Name of person who updated this record';

COMMENT ON COLUMN cct7_contacts.contact_connection            IS 'Grid label: Connections                   - Server connection list';
COMMENT ON COLUMN cct7_contacts.contact_server_os             IS 'Grid label: OS                            - Server OS list';
COMMENT ON COLUMN cct7_contacts.contact_server_usage          IS 'Grid Label: Status                        - Server OS status: Production, Test, etc.';
COMMENT ON COLUMN cct7_contacts.contact_work_group            IS 'Grid Label: Status                        - OS, APP, DBA, APP_DBA';
COMMENT ON COLUMN cct7_contacts.contact_approver_fyi          IS 'Grid Label: Notify Type                   - APPROVER or FYI';
COMMENT ON COLUMN cct7_contacts.contact_csc_banner            IS 'Grid Label: CSC Support Banners (Primary) - CSC Banner list';
COMMENT ON COLUMN cct7_contacts.contact_apps_databases        IS 'Grid Label: Apps/DBMS                     - MAL and MDL list of applications and databases';

COMMENT ON COLUMN cct7_contacts.contact_respond_by_date       IS 'Copied over from cct7_tickets.respond_by_date';
COMMENT ON COLUMN cct7_contacts.contact_response_status       IS 'Response Status: WAITING, APPROVED, REJECTED, RESCHEDULE';
COMMENT ON COLUMN cct7_contacts.contact_response_date         IS 'Response Date';
COMMENT ON COLUMN cct7_contacts.contact_response_cuid         IS 'Response CUID of the net-group member that approved this work';
COMMENT ON COLUMN cct7_contacts.contact_response_name         IS 'Response Name of the net-group member that approved this work';

COMMENT ON COLUMN cct7_contacts.contact_send_page             IS 'Do they want a page?   Y/N';
COMMENT ON COLUMN cct7_contacts.contact_send_email            IS 'Do they want an email? Y/N';
COMMENT ON COLUMN cct7_contacts.change_date                   IS 'Used by send_notifications.php to send out email to clients';


