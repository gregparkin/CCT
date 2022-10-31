set echo on
WHENEVER SQLERROR CONTINUE

drop table cct7_contacts cascade constraints;
drop table cct7_systems cascade constraints;
drop table cct7_tickets cascade constraints;

drop table cct7_list_systems cascade constraints;
drop table cct7_list_names cascade constraints;

drop table cct7_subscriber_servers;
drop table cct7_subscriber_members cascade constraints;
drop table cct7_subscriber_groups cascade constraints;

drop table cct7_applications;
drop table cct7_assign_groups;
drop table cct7_classifications;
drop table cct7_computers;
drop table cct7_computer_status;
drop table cct7_contract;
drop table cct7_databases;
drop table cct7_event_log;
drop table cct7_log_contacts;
drop table cct7_log_systems;
drop table cct7_log_tickets;
drop table cct7_managing_group;
drop table cct7_mnet;
drop table cct7_netpin_to_cuid;
drop table cct7_os_lite;
drop table cct7_platform;
drop table cct7_sendmail_log;
drop table cct7_state_city;
drop table cct7_users;
drop table cct7_virtual_servers;
drop table cct7_work_activities;

drop table cct6_computers cascade constraints;
drop table cct6_csc cascade constraints;
drop table cct6_mnet cascade constraints;
drop table cct6_computer_status cascade constraints;
drop table cct6_databases cascade constraints;
drop table cct6_os_lite cascade constraints;
drop table cct6_platform cascade constraints;
drop table cct6_state_city cascade constraints;
drop table cct6_applications cascade constraints;
drop table cct6_assign_groups cascade constraints;
drop table cct6_auto_approve cascade constraints;
drop table cct6_auto_approve_test cascade constraints;
drop table cct6_classifications cascade constraints;
drop table cct6_computers_backup cascade constraints;
drop table cct6_contract cascade constraints;
drop table cct6_csc_backup cascade constraints;
drop table cct6_csc_changes cascade constraints;
drop table cct6_debugging cascade constraints;
drop table cct6_email_list cascade constraints;
drop table cct6_email_spool cascade constraints;
drop table cct6_email_spool_old cascade constraints;
drop table cct6_event_log cascade constraints;
drop table cct6_list_names cascade constraints;
drop table cct6_list_systems cascade constraints;
drop table cct6_managing_group cascade constraints;
drop table cct6_master_approvers cascade constraints;
drop table cct6_net_members cascade constraints;
drop table cct6_netpin_to_cuid cascade constraints;
drop table cct6_oncall_overrides cascade constraints;
drop table cct6_page_pool cascade constraints;
drop table cct6_page_pool_old cascade constraints;
drop table cct6_subscriber_lists cascade constraints;


drop table cct6_contacts;
drop table cct6_systems;
drop table cct6_tickets;

drop table cct6_user_profiles;
drop table cct6_virtual_servers;

--


drop table cct6_page_spool;
drop table cct6_page_spool_old;
drop table cct7_computers_backup;
drop table cct7_csc;
drop table cct7_csc_backup;
drop table cct7_mnet_backup;
drop table new_cct7_mnet;
drop table new_cct7_csc;
drop table new_cct7_mnet;

drop table new_cct6_mnet;
drop table new_cct7_computers;

drop sequence cct6_auto_approve_testseq;

drop sequence cct6_auto_approveseq;
drop sequence cct6_classificationsseq;
drop sequence cct6_contactsseq;
drop sequence cct6_email_spoolseq;
drop sequence cct6_list_namesseq;
drop sequence cct6_list_systemsseq;
drop sequence cct6_page_spoolseq;
drop sequence cct6_subscriber_listsseq;
drop sequence cct6_systemsseq;
drop sequence cct6_test_ticketseq;

drop sequence cct7_classificationsseq;
drop sequence cct7_connectionsseq;
drop sequence cct7_contactsseq;
drop sequence cct7_email_spoolseq;
drop sequence cct7_grid_contactsseq;
drop sequence cct7_grid_mainseq;
drop sequence cct7_list_namesseq;
drop sequence cct7_list_systemsseq;
drop sequence cct7_netpinsseq;
drop sequence cct7_page_spoolseq;
drop sequence cct7_spool_emailseq;
drop sequence cct7_spool_pageseq;
drop sequence cct7_subscriber_groupseq;
drop sequence cct7_subscriber_groupsseq;
drop sequence cct7_subscriber_membersseq;
drop sequence cct7_subscriber_serversseq;
drop sequence cct7_systemsseq;
drop sequence cct7_test_ticketseq;
drop sequence cct7_ticketsseq;

commit;
