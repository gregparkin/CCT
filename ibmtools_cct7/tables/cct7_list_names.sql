WHENEVER SQLERROR CONTINUE
set echo on

create table cct7_list_names 
(
	list_name_id           number         primary key,
	create_date            number,
	owner_cuid             varchar2(20),
	owner_name             varchar2(200),
	list_name              varchar2(200)
);

drop sequence cct7_list_namesseq;

create sequence cct7_list_namesseq increment by 1 start with 1 nocache;
create index idx_cct7_list_names1 on cct7_list_names (owner_cuid);

COMMENT ON COLUMN cct7_list_names.list_name_id IS 'Unique record ID';
COMMENT ON COLUMN cct7_list_names.create_date  IS 'Date record was inserted (GMT timestamp)';
COMMENT ON COLUMN cct7_list_names.owner_cuid   IS 'CUID of person inserting the record';
COMMENT ON COLUMN cct7_list_names.owner_name   IS 'Name of person inserting the record';
COMMENT ON COLUMN cct7_list_names.list_name    IS 'Name of the system list';


insert into cct7_list_names
(
	list_name_id,
	create_date,
	owner_cuid,
	owner_name,
	list_name
)
	select
		list_name_id,
		datetime_to_utime(to_char(insert_date, 'MM/DD/YYYY HH24:mi'), 'GMT'),
		insert_cuid,
		insert_name,
		list_name
	from
		cct6_list_names;

select list_name_id
from cct7_list_names order by list_name_id desc;

drop sequence cct7_list_namesseq;
create sequence cct7_list_namesseq increment by 1 start with 2400 nocache;