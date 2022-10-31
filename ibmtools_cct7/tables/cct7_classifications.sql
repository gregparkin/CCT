
-- Replaced with: cct7_work_activities.sql


WHENEVER SQLERROR CONTINUE
set echo on

create table cct7_classifications (
	classification_id             number primary key,
	classification                varchar2(80),
	classification_comments       varchar2(4000),
	classification_cuid           varchar2(20),
	classification_last_name      varchar2(80),
	classification_first_name     varchar2(80),
	classification_nick_name      varchar2(80),
	classification_middle         varchar2(20),
	classification_name           varchar2(200),
	classification_job_title      varchar2(80),
	classification_email          varchar2(80),
	classification_work_phone     varchar2(20),
	classification_pager          varchar2(20),
	classification_street         varchar2(80),
	classification_city           varchar2(80),
	classification_state          varchar2(10),
	classification_rc             varchar2(45),
	classification_company        varchar2(80),
	classification_tier1          varchar2(100),
	classification_tier2          varchar2(100),
	classification_tier3          varchar2(100),
	classification_status         varchar2(20),
	classification_change_date    date,
	classification_ctl_cuid       varchar2(20),
	classification_mgr_cuid       varchar2(20),
	delete_date                   date
);


create index idx_cct7_classifications1 on cct7_classifications (classification);

COMMENT ON TABLE cct7_classifications IS 'List of default approvers by work classifications';
COMMENT ON COLUMN cct7_classifications.classification_id IS 'Unique record identifer number';
COMMENT ON COLUMN cct7_classifications.classification IS 'Classification name';
COMMENT ON COLUMN cct7_classifications.classification_comments IS 'Comments about this classification';
COMMENT ON COLUMN cct7_classifications.classification_cuid IS 'CUID of person who will approve this work';
COMMENT ON COLUMN cct7_classifications.classification_last_name IS 'Last name of approver';
COMMENT ON COLUMN cct7_classifications.classification_first_name IS 'First name of approver';
COMMENT ON COLUMN cct7_classifications.classification_nick_name IS 'Nick name of approver';
COMMENT ON COLUMN cct7_classifications.classification_middle IS 'Middle name of approver';
COMMENT ON COLUMN cct7_classifications.classification_name IS 'Full name of approver';
COMMENT ON COLUMN cct7_classifications.classification_job_title IS 'Job title of approver';
COMMENT ON COLUMN cct7_classifications.classification_email IS 'Email address of approver';
COMMENT ON COLUMN cct7_classifications.classification_work_phone IS 'Work phone number of approver';
COMMENT ON COLUMN cct7_classifications.classification_pager IS 'Pager number of approver';
COMMENT ON COLUMN cct7_classifications.classification_street IS 'Street address of approver';
COMMENT ON COLUMN cct7_classifications.classification_city IS 'City of approver';
COMMENT ON COLUMN cct7_classifications.classification_state IS 'State of approver';
COMMENT ON COLUMN cct7_classifications.classification_rc IS 'Qwest RC code of approver';
COMMENT ON COLUMN cct7_classifications.classification_company IS 'Company name that the approver works for';
COMMENT ON COLUMN cct7_classifications.classification_tier1 IS 'Tier1 level support for approver';
COMMENT ON COLUMN cct7_classifications.classification_tier2 IS 'Tier2 level support for approver';
COMMENT ON COLUMN cct7_classifications.classification_tier3 IS 'Tier3 level support for approver';
COMMENT ON COLUMN cct7_classifications.classification_change_date IS 'Last mnet record change date for this person';
COMMENT ON COLUMN cct7_classifications.classification_ctl_cuid IS 'Persons sponsor CTL cuid';
COMMENT ON COLUMN cct7_classifications.classification_mgr_cuid IS 'Manager CUID of approver';
COMMENT ON COLUMN cct7_classifications.delete_date IS 'The date this classification was deactiviated. Do not delete the record!';

insert into cct7_classifications
(
	classification_id, 
	classification, 
	classification_comments, 
	classification_cuid, 
	classification_last_name, 
	classification_first_name,
	classification_nick_name, 
	classification_middle, 
	classification_name, 
	classification_job_title,
	classification_email, 
	classification_work_phone, 
	classification_pager,
	classification_street, 
	classification_city, 
	classification_state, 
	classification_company, 
	classification_status, 
	classification_change_date,
	classification_ctl_cuid, 
	classification_mgr_cuid
) 
values 
(
	1, 
	'Database', 
	'Test System', 
	'gparkin', 
	'Parkin', 
	'Greg', 
	'', 
	'H.', 
	'Greg Parkin',
	'Staff System Sw Support Eng', 
	'parking@us.ibm.com', 
	'801 447-9315', 
	'801 510-0069', 
	'1273 Cannon Drive', 
	'Farmington', 
	'UT', 
	'IGS', 
	'IBM Employee', 
	'17-FEB-13', 
	'lmata', 
	'aa49224'
);

insert into cct7_classifications
(
	classification_id, 
	classification, 
	classification_comments, 
	classification_cuid, 
	classification_last_name, 
	classification_first_name,
	classification_nick_name, 
	classification_middle, 
	classification_name, 
	classification_job_title,
	classification_email, 
	classification_work_phone, 
	classification_pager,
	classification_street, 
	classification_city, 
	classification_state, 
	classification_company, 
	classification_status, 
	classification_change_date,
	classification_ctl_cuid, 
	classification_mgr_cuid
) 
values 
(
	2, 
	'Emergency', 
	'Test System', 
	'gparkin', 
	'Parkin', 
	'Greg', 
	'', 
	'H.', 
	'Greg Parkin',
	'Staff System Sw Support Eng', 
	'parking@us.ibm.com', 
	'801 447-9315', 
	'801 510-0069', 
	'1273 Cannon Drive', 
	'Farmington', 
	'UT', 
	'IGS', 
	'IBM Employee', 
	'17-FEB-13', 
	'lmata', 
	'aa49224'
);

insert into cct7_classifications
(
	classification_id, 
	classification, 
	classification_comments, 
	classification_cuid, 
	classification_last_name, 
	classification_first_name,
	classification_nick_name, 
	classification_middle, 
	classification_name, 
	classification_job_title,
	classification_email, 
	classification_work_phone, 
	classification_pager,
	classification_street, 
	classification_city, 
	classification_state, 
	classification_company, 
	classification_status, 
	classification_change_date,
	classification_ctl_cuid, 
	classification_mgr_cuid
) 
values 
(
	3, 
	'Firmware', 
	'Test System', 
	'gparkin', 
	'Parkin', 
	'Greg', 
	'', 
	'H.', 
	'Greg Parkin',
	'Staff System Sw Support Eng', 
	'parking@us.ibm.com', 
	'801 447-9315', 
	'801 510-0069', 
	'1273 Cannon Drive', 
	'Farmington', 
	'UT', 
	'IGS', 
	'IBM Employee', 
	'17-FEB-13', 
	'lmata', 
	'aa49224'
);

insert into cct7_classifications
(
	classification_id, 
	classification, 
	classification_comments, 
	classification_cuid, 
	classification_last_name, 
	classification_first_name,
	classification_nick_name, 
	classification_middle, 
	classification_name, 
	classification_job_title,
	classification_email, 
	classification_work_phone, 
	classification_pager,
	classification_street, 
	classification_city, 
	classification_state, 
	classification_company, 
	classification_status, 
	classification_change_date,
	classification_ctl_cuid, 
	classification_mgr_cuid
) 
values 
(
	4, 
	'GSD331 Remediation', 
	'Test System', 
	'gparkin', 
	'Parkin', 
	'Greg', 
	'', 
	'H.', 
	'Greg Parkin',
	'Staff System Sw Support Eng', 
	'parking@us.ibm.com', 
	'801 447-9315', 
	'801 510-0069', 
	'1273 Cannon Drive', 
	'Farmington', 
	'UT', 
	'IGS', 
	'IBM Employee', 
	'17-FEB-13', 
	'lmata', 
	'aa49224'
);

insert into cct7_classifications
(
	classification_id, 
	classification, 
	classification_comments, 
	classification_cuid, 
	classification_last_name, 
	classification_first_name,
	classification_nick_name, 
	classification_middle, 
	classification_name, 
	classification_job_title,
	classification_email, 
	classification_work_phone, 
	classification_pager,
	classification_street, 
	classification_city, 
	classification_state, 
	classification_company, 
	classification_status, 
	classification_change_date,
	classification_ctl_cuid, 
	classification_mgr_cuid
) 
values 
(
	5, 
	'Hardware', 
	'Test System', 
	'gparkin', 
	'Parkin', 
	'Greg', 
	'', 
	'H.', 
	'Greg Parkin',
	'Staff System Sw Support Eng', 
	'parking@us.ibm.com', 
	'801 447-9315', 
	'801 510-0069', 
	'1273 Cannon Drive', 
	'Farmington', 
	'UT', 
	'IGS', 
	'IBM Employee', 
	'17-FEB-13', 
	'lmata', 
	'aa49224'
);

insert into cct7_classifications
(
	classification_id, 
	classification, 
	classification_comments, 
	classification_cuid, 
	classification_last_name, 
	classification_first_name,
	classification_nick_name, 
	classification_middle, 
	classification_name, 
	classification_job_title,
	classification_email, 
	classification_work_phone, 
	classification_pager,
	classification_street, 
	classification_city, 
	classification_state, 
	classification_company, 
	classification_status, 
	classification_change_date,
	classification_ctl_cuid, 
	classification_mgr_cuid
) 
values 
(
	6, 
	'Other', 
	'Test System', 
	'gparkin', 
	'Parkin', 
	'Greg', 
	'', 
	'H.', 
	'Greg Parkin',
	'Staff System Sw Support Eng', 
	'parking@us.ibm.com', 
	'801 447-9315', 
	'801 510-0069', 
	'1273 Cannon Drive', 
	'Farmington', 
	'UT', 
	'IGS', 
	'IBM Employee', 
	'17-FEB-13', 
	'lmata', 
	'aa49224'
);

insert into cct7_classifications
(
	classification_id, 
	classification, 
	classification_comments, 
	classification_cuid, 
	classification_last_name, 
	classification_first_name,
	classification_nick_name, 
	classification_middle, 
	classification_name, 
	classification_job_title,
	classification_email, 
	classification_work_phone, 
	classification_pager,
	classification_street, 
	classification_city, 
	classification_state, 
	classification_company, 
	classification_status, 
	classification_change_date,
	classification_ctl_cuid, 
	classification_mgr_cuid
) 
values 
(
	7, 
	'Patching', 
	'Test System', 
	'gparkin', 
	'Parkin', 
	'Greg', 
	'', 
	'H.', 
	'Greg Parkin',
	'Staff System Sw Support Eng', 
	'parking@us.ibm.com', 
	'801 447-9315', 
	'801 510-0069', 
	'1273 Cannon Drive', 
	'Farmington', 
	'UT', 
	'IGS', 
	'IBM Employee', 
	'17-FEB-13', 
	'lmata', 
	'aa49224'
);

insert into cct7_classifications
(
	classification_id, 
	classification, 
	classification_comments, 
	classification_cuid, 
	classification_last_name, 
	classification_first_name,
	classification_nick_name, 
	classification_middle, 
	classification_name, 
	classification_job_title,
	classification_email, 
	classification_work_phone, 
	classification_pager,
	classification_street, 
	classification_city, 
	classification_state, 
	classification_company, 
	classification_status, 
	classification_change_date,
	classification_ctl_cuid, 
	classification_mgr_cuid
) 
values 
(
	8, 
	'Project', 
	'Test System', 
	'gparkin', 
	'Parkin', 
	'Greg', 
	'', 
	'H.', 
	'Greg Parkin',
	'Staff System Sw Support Eng', 
	'parking@us.ibm.com', 
	'801 447-9315', 
	'801 510-0069', 
	'1273 Cannon Drive', 
	'Farmington', 
	'UT', 
	'IGS', 
	'IBM Employee', 
	'17-FEB-13', 
	'lmata', 
	'aa49224'
);

insert into cct7_classifications
(
	classification_id, 
	classification, 
	classification_comments, 
	classification_cuid, 
	classification_last_name, 
	classification_first_name,
	classification_nick_name, 
	classification_middle, 
	classification_name, 
	classification_job_title,
	classification_email, 
	classification_work_phone, 
	classification_pager,
	classification_street, 
	classification_city, 
	classification_state, 
	classification_company, 
	classification_status, 
	classification_change_date,
	classification_ctl_cuid, 
	classification_mgr_cuid
) 
values 
(
	9, 
	'Security', 
	'Test System', 
	'gparkin', 
	'Parkin', 
	'Greg', 
	'', 
	'H.', 
	'Greg Parkin',
	'Staff System Sw Support Eng', 
	'parking@us.ibm.com', 
	'801 447-9315', 
	'801 510-0069', 
	'1273 Cannon Drive', 
	'Farmington', 
	'UT', 
	'IGS', 
	'IBM Employee', 
	'17-FEB-13', 
	'lmata', 
	'aa49224'
);

insert into cct7_classifications
(
	classification_id, 
	classification, 
	classification_comments, 
	classification_cuid, 
	classification_last_name, 
	classification_first_name,
	classification_nick_name, 
	classification_middle, 
	classification_name, 
	classification_job_title,
	classification_email, 
	classification_work_phone, 
	classification_pager,
	classification_street, 
	classification_city, 
	classification_state, 
	classification_company, 
	classification_status, 
	classification_change_date,
	classification_ctl_cuid, 
	classification_mgr_cuid
) 
values 
(
	10, 
	'Software', 
	'Test System', 
	'gparkin', 
	'Parkin', 
	'Greg', 
	'', 
	'H.', 
	'Greg Parkin',
	'Staff System Sw Support Eng', 
	'parking@us.ibm.com', 
	'801 447-9315', 
	'801 510-0069', 
	'1273 Cannon Drive', 
	'Farmington', 
	'UT', 
	'IGS', 
	'IBM Employee', 
	'17-FEB-13', 
	'lmata', 
	'aa49224'
);

insert into cct7_classifications
(
	classification_id, 
	classification, 
	classification_comments, 
	classification_cuid, 
	classification_last_name, 
	classification_first_name,
	classification_nick_name, 
	classification_middle, 
	classification_name, 
	classification_job_title,
	classification_email, 
	classification_work_phone, 
	classification_pager,
	classification_street, 
	classification_city, 
	classification_state, 
	classification_company, 
	classification_status, 
	classification_change_date,
	classification_ctl_cuid, 
	classification_mgr_cuid
) 
values 
(
	11, 
	'Storage', 
	'Test System', 
	'gparkin', 
	'Parkin', 
	'Greg', 
	'', 
	'H.', 
	'Greg Parkin',
	'Staff System Sw Support Eng', 
	'parking@us.ibm.com', 
	'801 447-9315', 
	'801 510-0069', 
	'1273 Cannon Drive', 
	'Farmington', 
	'UT', 
	'IGS', 
	'IBM Employee', 
	'17-FEB-13', 
	'lmata', 
	'aa49224'
);

insert into cct7_classifications
(
	classification_id, 
	classification, 
	classification_comments, 
	classification_cuid, 
	classification_last_name, 
	classification_first_name,
	classification_nick_name, 
	classification_middle, 
	classification_name, 
	classification_job_title,
	classification_email, 
	classification_work_phone, 
	classification_pager,
	classification_street, 
	classification_city, 
	classification_state, 
	classification_company, 
	classification_status, 
	classification_change_date,
	classification_ctl_cuid, 
	classification_mgr_cuid
) 
values 
(
	12, 
	'UNIX', 
	'Test System', 
	'gparkin', 
	'Parkin', 
	'Greg', 
	'', 
	'H.', 
	'Greg Parkin',
	'Staff System Sw Support Eng', 
	'parking@us.ibm.com', 
	'801 447-9315', 
	'801 510-0069', 
	'1273 Cannon Drive', 
	'Farmington', 
	'UT', 
	'IGS', 
	'IBM Employee', 
	'17-FEB-13', 
	'lmata', 
	'aa49224'
);

insert into cct7_classifications
(
	classification_id, 
	classification, 
	classification_comments, 
	classification_cuid, 
	classification_last_name, 
	classification_first_name,
	classification_nick_name, 
	classification_middle, 
	classification_name, 
	classification_job_title,
	classification_email, 
	classification_work_phone, 
	classification_pager,
	classification_street, 
	classification_city, 
	classification_state, 
	classification_company, 
	classification_status, 
	classification_change_date,
	classification_ctl_cuid, 
	classification_mgr_cuid
) 
values 
(
	13, 
	'Windows', 
	'Test System', 
	'gparkin', 
	'Parkin', 
	'Greg', 
	'', 
	'H.', 
	'Greg Parkin',
	'Staff System Sw Support Eng', 
	'parking@us.ibm.com', 
	'801 447-9315', 
	'801 510-0069', 
	'1273 Cannon Drive', 
	'Farmington', 
	'UT', 
	'IGS', 
	'IBM Employee', 
	'17-FEB-13', 
	'lmata', 
	'aa49224'
);

drop sequence cct7_classificationsseq;
create sequence cct7_classificationsseq increment by 1 start with 14 nocache;

delete from cct7_classifications;

insert into cct7_classifications select * from cct6_classifications;
select count(*) as cct7_classifications from cct7_classifications;

commit;

