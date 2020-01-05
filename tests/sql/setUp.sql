create user 'sakila'@'%'
identified by 'passw0rd';

create schema `sakiladb`
default character set 'utf8'
default collate 'utf8_general_ci';

grant all
on `sakiladb`.*
to 'sakila'@'%';
