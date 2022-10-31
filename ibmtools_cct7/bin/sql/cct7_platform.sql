set echo on

delete from cct7_platform;

insert into cct7_platform ( computer_platform ) 
	select distinct
		computer_platform
	from
		cct7_computers;

commit;
select count(*) as record_count from cct7_platform;
quit;
