create user 'sakila'@'localhost'
identified by 'password123';

grant super
on *.*
to 'sakila'@'localhost';

create schema `sakiladb`
default character set 'utf8'
default collate 'utf8_general_ci';

grant all
on `sakiladb`.*
to 'sakila'@'localhost';
