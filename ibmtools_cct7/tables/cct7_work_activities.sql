
WHENEVER SQLERROR CONTINUE
set echo on

create table cct7_work_activities
(
	work_activity    varchar2(200) PRIMARY KEY
);

COMMENT ON TABLE cct7_work_activities IS 'List of available work activities to choose from';
COMMENT ON COLUMN cct7_work_activities.work_activity IS 'Discription of the work activity';

insert into cct7_work_activities (work_activity) values ('Database');
insert into cct7_work_activities (work_activity) values ('Emergency');
insert into cct7_work_activities (work_activity) values ('Firmware');
insert into cct7_work_activities (work_activity) values ('Hardware');
insert into cct7_work_activities (work_activity) values ('Other');
insert into cct7_work_activities (work_activity) values ('Patching APP');
insert into cct7_work_activities (work_activity) values ('Patching DB');
insert into cct7_work_activities (work_activity) values ('Patching OS');
insert into cct7_work_activities (work_activity) values ('Patching OTHER');
insert into cct7_work_activities (work_activity) values ('Project');
insert into cct7_work_activities (work_activity) values ('Remediation');
insert into cct7_work_activities (work_activity) values ('Security');
insert into cct7_work_activities (work_activity) values ('Software');

commit;

