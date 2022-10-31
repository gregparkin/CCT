set echo on

delete from cct7_os_lite;

insert into cct7_os_lite ( computer_os_lite ) 
	select distinct
		computer_os_lite
	from
		cct7_computers;

commit;
select count(*) as record_count from cct7_os_lite;
quit;
