set echo on

delete from cct7_managing_group;

insert into cct7_managing_group ( computer_managing_group ) 
	select distinct
		computer_managing_group
	from
		cct7_computers;

commit;
select count(*) as record_count from cct7_managing_group;
quit;
