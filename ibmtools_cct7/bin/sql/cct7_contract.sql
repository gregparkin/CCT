set echo on

delete from cct7_contract;

insert into cct7_contract ( computer_contract ) 
	select distinct
		computer_contract
	from
		cct7_computers;

commit;
select count(*) as record_count from cct7_contract;
quit;
