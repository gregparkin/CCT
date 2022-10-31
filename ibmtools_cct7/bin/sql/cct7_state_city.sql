set echo on

delete from cct7_state_city;

insert into cct7_state_city ( computer_state, computer_city ) 
	select distinct
		computer_state,
		computer_city
	from
		cct7_computers;

commit;
select count(*) as record_count from cct7_state_city;
quit;
