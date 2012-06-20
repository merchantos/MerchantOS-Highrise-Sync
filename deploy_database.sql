CREATE DATABASE sync;

USE sync;

CREATE TABLE sync_accounts 
(
	id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
	mos_account_key varchar(255),
	mos_api_key varchar(255),
	mos_account_id int, 
	highrise_api_key varchar(255),
	highrise_username varchar(255),
	custom_field_id varchar(255),
	last_synced_on datetime
);


CREATE TABLE exceptions_log
(
	id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
	sync_account_id int,
	datetime_of datetime, 
	message varchar(255), 
	data_involved text
);
