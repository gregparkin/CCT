set echo on

delete from cct7_computer_status;

insert into cct7_computer_status ( computer_status ) 
	select distinct
		computer_status
	from
		cct7_computers;

commit;
quit;
