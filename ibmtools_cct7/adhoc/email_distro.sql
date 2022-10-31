REM
REM Create a CCT email distro. list
REM
REM create table cct6_email_list
REM (
REM   mnet_cuid   varchar2(20),
REM   mnet_name   varchar2(200),
REM   mnet_email  varchar2(80)
REM );

delete from cct6_email_list;

insert into cct6_email_list
		select 
				c.cct_csc_userid_1, 
        m.mnet_name,
        m.mnet_email
			from 
				cct6_csc c,
        cct6_mnet m
			where 
        m.mnet_cuid = c.cct_csc_userid_1 and
				(c.cct_csc_group_name = 'MiddleWare Support' or 
				c.cct_csc_group_name = 'Development Support' or 
				c.cct_csc_group_name = '! Operating System Support' or 
				c.cct_csc_group_name = '! Database Support' or 
				c.cct_csc_group_name = 'Application Support' or 
				c.cct_csc_group_name = 'Infrastructure' or 
				c.cct_csc_group_name = 'Application Utilities, Middleware, Legacy DB, Other (Hosted on this Server)' or 
				c.cct_csc_group_name = 'Applications or Databases Desiring Notification (Not Hosted on this Server)' or 
				c.cct_csc_group_name = 'Applications Owning Database (DB Hosted on this Server, Owning App Is Not)')  
			order by c.cct_csc_group_name;
      
insert into cct6_email_list
		select 
				c.cct_csc_userid_2, 
        m.mnet_name,
        m.mnet_email
			from 
				cct6_csc c,
        cct6_mnet m
			where 
        m.mnet_cuid = c.cct_csc_userid_2 and
				(c.cct_csc_group_name = 'MiddleWare Support' or 
				c.cct_csc_group_name = 'Development Support' or 
				c.cct_csc_group_name = '! Operating System Support' or 
				c.cct_csc_group_name = '! Database Support' or 
				c.cct_csc_group_name = 'Application Support' or 
				c.cct_csc_group_name = 'Infrastructure' or 
				c.cct_csc_group_name = 'Application Utilities, Middleware, Legacy DB, Other (Hosted on this Server)' or 
				c.cct_csc_group_name = 'Applications or Databases Desiring Notification (Not Hosted on this Server)' or 
				c.cct_csc_group_name = 'Applications Owning Database (DB Hosted on this Server, Owning App Is Not)')  
			order by c.cct_csc_group_name;     
      
insert into cct6_email_list
		select 
				c.cct_csc_userid_3, 
        m.mnet_name,
        m.mnet_email
			from 
				cct6_csc c,
        cct6_mnet m
			where 
        m.mnet_cuid = c.cct_csc_userid_3 and
				(c.cct_csc_group_name = 'MiddleWare Support' or 
				c.cct_csc_group_name = 'Development Support' or 
				c.cct_csc_group_name = '! Operating System Support' or 
				c.cct_csc_group_name = '! Database Support' or 
				c.cct_csc_group_name = 'Application Support' or 
				c.cct_csc_group_name = 'Infrastructure' or 
				c.cct_csc_group_name = 'Application Utilities, Middleware, Legacy DB, Other (Hosted on this Server)' or 
				c.cct_csc_group_name = 'Applications or Databases Desiring Notification (Not Hosted on this Server)' or 
				c.cct_csc_group_name = 'Applications Owning Database (DB Hosted on this Server, Owning App Is Not)')  
			order by c.cct_csc_group_name;      
      
insert into cct6_email_list
		select 
				c.cct_csc_userid_4, 
        m.mnet_name,
        m.mnet_email
			from 
				cct6_csc c,
        cct6_mnet m
			where 
        m.mnet_cuid = c.cct_csc_userid_4 and
				(c.cct_csc_group_name = 'MiddleWare Support' or 
				c.cct_csc_group_name = 'Development Support' or 
				c.cct_csc_group_name = '! Operating System Support' or 
				c.cct_csc_group_name = '! Database Support' or 
				c.cct_csc_group_name = 'Application Support' or 
				c.cct_csc_group_name = 'Infrastructure' or 
				c.cct_csc_group_name = 'Application Utilities, Middleware, Legacy DB, Other (Hosted on this Server)' or 
				c.cct_csc_group_name = 'Applications or Databases Desiring Notification (Not Hosted on this Server)' or 
				c.cct_csc_group_name = 'Applications Owning Database (DB Hosted on this Server, Owning App Is Not)')  
			order by c.cct_csc_group_name;      
      
insert into cct6_email_list
		select 
				c.cct_csc_userid_5, 
        m.mnet_name,
        m.mnet_email
			from 
				cct6_csc c,
        cct6_mnet m
			where 
        m.mnet_cuid = c.cct_csc_userid_5 and
				(c.cct_csc_group_name = 'MiddleWare Support' or 
				c.cct_csc_group_name = 'Development Support' or 
				c.cct_csc_group_name = '! Operating System Support' or 
				c.cct_csc_group_name = '! Database Support' or 
				c.cct_csc_group_name = 'Application Support' or 
				c.cct_csc_group_name = 'Infrastructure' or 
				c.cct_csc_group_name = 'Application Utilities, Middleware, Legacy DB, Other (Hosted on this Server)' or 
				c.cct_csc_group_name = 'Applications or Databases Desiring Notification (Not Hosted on this Server)' or 
				c.cct_csc_group_name = 'Applications Owning Database (DB Hosted on this Server, Owning App Is Not)')  
			order by c.cct_csc_group_name;      
      
insert into cct6_email_list
		select 
				c.cct_csc_oncall, 
        m.mnet_name,
        m.mnet_email
			from 
				cct6_csc c,
        cct6_mnet m
			where 
        m.mnet_cuid = c.cct_csc_oncall and
				(c.cct_csc_group_name = 'MiddleWare Support' or 
				c.cct_csc_group_name = 'Development Support' or 
				c.cct_csc_group_name = '! Operating System Support' or 
				c.cct_csc_group_name = '! Database Support' or 
				c.cct_csc_group_name = 'Application Support' or 
				c.cct_csc_group_name = 'Infrastructure' or 
				c.cct_csc_group_name = 'Application Utilities, Middleware, Legacy DB, Other (Hosted on this Server)' or 
				c.cct_csc_group_name = 'Applications or Databases Desiring Notification (Not Hosted on this Server)' or 
				c.cct_csc_group_name = 'Applications Owning Database (DB Hosted on this Server, Owning App Is Not)')  
			order by c.cct_csc_group_name;      
      
insert into cct6_email_list
		select 
				c.approver_cuid, 
        m.mnet_name,
        m.mnet_email
			from 
				cct6_master_approvers c,
        cct6_mnet m
			where     
        c.approver_cuid = m.mnet_cuid;
        
insert into cct6_email_list
		select 
				c.subscriber_cuid, 
        m.mnet_name,
        m.mnet_email
			from 
				cct6_subscriber_lists c,
        cct6_mnet m
			where     
        c.subscriber_cuid = m.mnet_cuid;        
        
REM insert into cct6_email_list (mnet_cuid, mnet_name, mnet_email) values ( 'mxsena', 'Mary Ann Sena', 'mxsena@us.ibm.com');
REM insert into cct6_email_list (mnet_cuid, mnet_name, mnet_email) values ( 'vefrien', 'Valerie Holloway', 'val.holloway@us.ibm.com');
insert into cct6_email_list (mnet_cuid, mnet_name, mnet_email) values ( 'esimon', 'Elizabeth Simon', 'esimon@us.ibm.com');
insert into cct6_email_list (mnet_cuid, mnet_name, mnet_email) values ( 'kme1566', 'Kristy Estabine', 'kestabin@us.ibm.com');
insert into cct6_email_list (mnet_cuid, mnet_name, mnet_email) values ( 'rlp6436', 'Robert Pelan', 'rpelan@us.ibm.com');

create table new_email_list as select distinct * from cct6_email_list;
  
drop table cct6_email_list;
rename new_email_list to cct6_email_list;
create index idx_cct6_email_list1 on cct6_email_list ( mnet_cuid );
select count(*) from cct6_email_list;
select * from cct6_email_list order by mnet_email;
quit;
