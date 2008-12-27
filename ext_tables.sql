#
# Table structure for table 'tx_realimageurl_requests'
#
CREATE TABLE tx_realimageurl_requests (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	parameter_hash varchar(255) DEFAULT '' NOT NULL,
	parameter_sent text DEFAULT '' NOT NULL,
	parameter_exec text DEFAULT '' NOT NULL,
	parameter_info text DEFAULT '' NOT NULL,
	location text NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

DROP VIEW tx_realimageurl_requests_view;
CREATE VIEW tx_realimageurl_requests_view AS
SELECT tx_realimageurl_requests.*, tx_realimageurl_requests.location AS path, GROUP_CONCAT(DISTINCT tx_realimageurl_responses.location SEPARATOR ',') AS referenced
FROM tx_realimageurl_requests LEFT JOIN
tx_realimageurl_responses ON tx_realimageurl_responses.fid = tx_realimageurl_requests.uid
GROUP BY tx_realimageurl_requests.uid;

#
# Table structure for table 'tx_realimageurl_responses'
#
CREATE TABLE tx_realimageurl_responses (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	record int(11) varchar(255) DEFAULT '' NOT NULL,
	fid int(11) DEFAULT NULL,
	parameter_hash varchar(255) DEFAULT '' NOT NULL,
	parameter_sent text DEFAULT '' NOT NULL,
	parameter_refr text DEFAULT '' NOT NULL,
	parameter_info text DEFAULT '' NOT NULL,
	location text NOT NULL,
	descr text NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

CREATE VIEW tx_realimageurl_responses_view AS
SELECT *, location AS path
FROM tx_realimageurl_responses;
