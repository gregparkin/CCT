--
-- cct7_subscriber_servers.sql
--

create table cct7_subscriber_servers
(
  server_id               NUMBER PRIMARY KEY,
  group_id                VARCHAR2(20),
  create_date             NUMBER,
  owner_cuid              VARCHAR2(20),
  owner_name              VARCHAR2(200),
  computer_lastid         NUMBER,
  computer_hostname       VARCHAR2(255),
  computer_ip_address     VARCHAR2(64),
  computer_os_lite        VARCHAR2(20),
  computer_status         VARCHAR2(80),
  computer_managing_group VARCHAR2(40),
  notification_type       VARCHAR2(20),

  FOREIGN KEY (group_id)    REFERENCES cct7_subscriber_groups  (group_id)  ON DELETE CASCADE
);

create index idx_cct7_subscriber_servers1 on cct7_subscriber_servers ( computer_lastid );
create index idx_cct7_subscriber_servers2 on cct7_subscriber_servers ( computer_hostname );
create index idx_cct7_subscriber_servers3 on cct7_subscriber_servers ( group_id, computer_hostname );


DROP SEQUENCE cct7_subscriber_serversseq;
CREATE SEQUENCE cct7_subscriber_serversseq INCREMENT BY 1 START WITH 1 NOCACHE;

COMMENT ON TABLE  cct7_subscriber_servers                         IS 'List servers in a subsriber list';
COMMENT ON COLUMN cct7_subscriber_servers.server_id               IS 'PK: Unique Record ID';
COMMENT ON COLUMN cct7_subscriber_servers.group_id                IS 'FK: cct7_subscriber_groups';
COMMENT ON COLUMN cct7_subscriber_servers.create_date             IS 'GMT creation date';
COMMENT ON COLUMN cct7_subscriber_servers.owner_cuid              IS 'Owner CUID';
COMMENT ON COLUMN cct7_subscriber_servers.owner_name              IS 'Owner NAME';
COMMENT ON COLUMN cct7_subscriber_servers.computer_lastid         IS 'Asset Manager computer record ID';
COMMENT ON COLUMN cct7_subscriber_servers.computer_hostname       IS 'Server Hostname';
COMMENT ON COLUMN cct7_subscriber_servers.computer_ip_address     IS 'Server IP Address';
COMMENT ON COLUMN cct7_subscriber_servers.computer_os_lite        IS 'Server Operating System';
COMMENT ON COLUMN cct7_subscriber_servers.computer_status         IS 'Server Status: PRODUCTION, DEVELOPMENT, etc.';
COMMENT ON COLUMN cct7_subscriber_servers.computer_managing_group IS 'Server Managing Group name';
COMMENT ON COLUMN cct7_subscriber_servers.notification_type       IS 'Notification Type: APPROVER or FYI';



