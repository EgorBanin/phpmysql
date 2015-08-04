set global
	general_log_file = default,
	general_log = default;
revoke all privileges, grant option from 'sakila'@'localhost';
drop schema `sakiladb`;
drop user 'sakila'@'localhost';
