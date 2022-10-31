WHENEVER SQLERROR CONTINUE
set echo on

create table cct7_list_systems 
(
	list_system_id           number primary key,
	list_name_id             number,
	create_date              number,
	owner_cuid               varchar2(20),
	owner_name               varchar2(200),
	computer_hostname        varchar2(255),
	computer_ip_address      varchar2(64),
	computer_os_lite         varchar2(20),
	computer_status          varchar2(80),
	computer_managing_group  varchar2(40),

FOREIGN KEY (list_name_id) REFERENCES cct7_list_names (list_name_id) on delete cascade);

drop sequence cct7_list_systemsseq;
create sequence cct7_list_systemsseq increment by 1 start with 1 nocache;

COMMENT ON TABLE cct7_list_systems                          IS 'List of hostnames belonging to a system list';
COMMENT ON COLUMN cct7_list_systems.list_system_id          IS 'Unique record ID';
COMMENT ON COLUMN cct7_list_systems.create_date             IS 'Date record was created. (GMT unix timestamp)';
COMMENT ON COLUMN cct7_list_systems.owner_cuid              IS 'CUID of person inserting the record';
COMMENT ON COLUMN cct7_list_systems.owner_name              IS 'Name of person inserting the record';
COMMENT ON COLUMN cct7_list_systems.computer_hostname       IS 'Computer hostname';
COMMENT ON COLUMN cct7_list_systems.computer_ip_address     IS 'Computer IP address';
COMMENT ON COLUMN cct7_list_systems.computer_os_lite        IS 'Computer short OS Name: HPUX';
COMMENT ON COLUMN cct7_list_systems.computer_status         IS 'Computer status: PRODUCTION, TEST, DEVELOPMENT';
COMMENT ON COLUMN cct7_list_systems.computer_managing_group IS 'Managing group like: IBM-UNIX (shorter version of COMPUTER_CIO_GROUP)';

insert into cct7_list_systems
(
	list_system_id,
	list_name_id,
	create_date,
	owner_cuid,
	owner_name,
	computer_hostname,
	computer_ip_address,
	computer_os_lite,
	computer_status,
	computer_managing_group
)
	select
		list_system_id,
		list_name_id,
		datetime_to_utime(to_char(insert_date, 'MM/DD/YYYY HH24:mi'), 'GMT'),
		insert_cuid,
		insert_name,
		computer_hostname,
		computer_ip_address,
		computer_os_lite,
		computer_status,
		computer_managing_group
	from
		cct6_list_systems;

select list_system_id
from cct7_list_systems order by list_system_id desc;

drop sequence cct7_list_systemsseq;
create sequence cct7_list_systemsseq increment by 1 start with 36000 nocache;



