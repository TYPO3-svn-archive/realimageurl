<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA["tx_realimageurl_requests_view"] = array (
	"ctrl" => $TCA["tx_realimageurl_requests_view"]["ctrl"],
	"interface" => array (
		"showRecordFieldList" => "parameter_hash,parameter_sent,parameter_exec,parameter_info,location,path,referenced"
	),
	"feInterface" => $TCA["tx_realimageurl_requests_view"]["feInterface"],
	"columns" => array (
		"parameter_hash" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:realimageurl/locallang_db.xml:tx_realimageurl_requests.parameter_hash",
			"config" => Array (
				"type" => "input",
				"size" => "30",
				"eval" => "required,trim",
			)
		),
		"parameter_sent" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:realimageurl/locallang_db.xml:tx_realimageurl_requests.parameter_sent",
			"config" => Array (
				"type" => "text",
				"cols" => "30",
				"rows" => "5",
			)
		),
		"parameter_exec" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:realimageurl/locallang_db.xml:tx_realimageurl_requests.parameter_exec",
			"config" => Array (
				"type" => "text",
				"cols" => "30",
				"rows" => "5",
			)
		),
		"parameter_info" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:realimageurl/locallang_db.xml:tx_realimageurl_requests.parameter_info",
			"config" => Array (
				"type" => "text",
				"cols" => "30",
				"rows" => "5",
			)
		),
		"location" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:realimageurl/locallang_db.xml:tx_realimageurl_requests.location",
			"config" => Array (
				"type" => "input",
				"size" => "30",
				"eval" => "required,trim",
			)
		),
		"path" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:realimageurl/locallang_db.xml:tx_realimageurl_requests.location",
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => "png,jpg,jpeg,gif",
				"size" => "1",
				"multiple" => "0",
				"maxitems" => "1",

				"show_thumbs" => "1",
				"uploadFolder" => "typo3temp/imap/",
				"disable_controls" => "list,upload",
			)
		),
		"referenced" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:realimageurl/locallang_db.xml:tx_realimageurl_requests.referenced",
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => "png,jpg,jpeg,gif",
				"size" => "10",
				"multiple" => "1",
				"maxitems" => "1000000",

				"show_thumbs" => "0",
				"uploadFolder" => "typo3temp/imap/map/",
				"disable_controls" => "browser,upload",
			)
		),
	),
	"types" => array (
		"0" => array("showitem" => "parameter_hash;;;;1-1-1, parameter_sent, parameter_exec, parameter_info, path, referenced")
	),
	"palettes" => array (
		"1" => array("showitem" => "")
	)
);

$TCA["tx_realimageurl_responses_view"] = array (
	"ctrl" => $TCA["tx_realimageurl_responses_view"]["ctrl"],
	"interface" => array (
		"showRecordFieldList" => "parameter_hash,parameter_sent,parameter_refr,parameter_info,location,path,descr,fid,pid"
	),
	"feInterface" => $TCA["tx_realimageurl_responses_view"]["feInterface"],
	"columns" => array (
		"parameter_hash" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:realimageurl/locallang_db.xml:tx_realimageurl_responses.parameter_hash",
			"config" => Array (
				"type" => "input",
				"size" => "30",
				"eval" => "required,trim",
			)
		),
		"parameter_sent" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:realimageurl/locallang_db.xml:tx_realimageurl_responses.parameter_sent",
			"config" => Array (
				"type" => "text",
				"cols" => "30",
				"rows" => "5",
			)
		),
		"parameter_refr" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:realimageurl/locallang_db.xml:tx_realimageurl_responses.parameter_refr",
			"config" => Array (
				"type" => "text",
				"cols" => "30",
				"rows" => "5",
			)
		),
		"parameter_info" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:realimageurl/locallang_db.xml:tx_realimageurl_responses.parameter_info",
			"config" => Array (
				"type" => "text",
				"cols" => "30",
				"rows" => "5",
			)
		),
		"location" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:realimageurl/locallang_db.xml:tx_realimageurl_responses.location",
			"config" => Array (
				"type" => "input",
				"size" => "30",
				"eval" => "required,trim",
			)
		),
		"path" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:realimageurl/locallang_db.xml:tx_realimageurl_responses.location",
			"config" => Array (
				"type" => "group",
				"internal_type" => "file",
				"allowed" => "png,jpg,jpeg,gif",
				"size" => "1",
				"multiple" => "0",
				"maxitems" => "1",

				"show_thumbs" => "1",
				"uploadFolder" => "typo3temp/imap/map/",
				"disable_controls" => "list,upload",
			)
		),
		"descr" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:realimageurl/locallang_db.xml:tx_realimageurl_responses.descr",
			"config" => Array (
				"type" => "text",
				"cols" => "30",
				"rows" => "5",
			)
		),
		"fid" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:realimageurl/locallang_db.xml:tx_realimageurl_responses.fid",
			"config" => Array (
				"type" => "select",
				"size" => 1,
				"minitems" => 0,
				"maxitems" => 1,

				"foreign_table" => "tx_realimageurl_requests_view",
				"foreign_label" => "location",
				"foreign_table_where" => " OR tx_realimageurl_requests_view.uid = ###REC_FIELD_fid### GROUP BY tx_realimageurl_requests_view.uid ORDER BY tx_realimageurl_requests_view.uid",

				"fileFolder" => "typo3temp/imap/",
				"fileFolder_extList" => "png,jpg,jpeg,gif",
				"suppress_icons" => "ONLY_SELECTED",
			)
		),
		"pid" => Array (
			"exclude" => 0,
			"label" => "LLL:EXT:realimageurl/locallang_db.xml:tx_realimageurl_responses.pid",
			"config" => Array (
				"type" => "select",
				"foreign_table" => "pages",
				"foreign_table_where" => "ORDER BY pages.uid",
				"size" => 1,
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
	),
	"types" => array (
		"0" => array("showitem" => "parameter_hash;;;;1-1-1, parameter_sent, parameter_exec, parameter_info, path, descr, fid, pid")
	),
	"palettes" => array (
		"1" => array("showitem" => "")
	)
);
?>