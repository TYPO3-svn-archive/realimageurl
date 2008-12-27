<?php

########################################################################
# Extension Manager/Repository config file for ext: "realimageurl"
#
# Auto generated 04-12-2008 15:31
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Real Image URL: URLs for images like normal websites',
	'description' => 'Creates nice looking contextual URLs for TYPO3 images. Converts http://example.com/typo3temp/xyz.png to http://example.com/path/to/your/image.png',
	'category' => 'fe',
	'author' => 'Niels Fröhling',
	'author_email' => 'niels@frohling.biz',
	'author_company' => 'http://frohling.biz/',
	'shy' => '',
	'dependencies' => 'cms',
	'conflicts' => '',
	'priority' => 'bottom',
	'module' => '',
	'state' => 'alpha',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' =>
		'typo3temp/imap/,'.
		'typo3temp/imap/perscopied/,'.
		'typo3temp/imap/inline/,'.
		'typo3temp/imap/fetched/,'.
		'typo3temp/imap/build/,'.
		'typo3temp/imap/map/',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'version' => '0.0.0',
	'constraints' => array(
		'depends' => array(
			'php' => '5.0.0-',
			'typo3' => '4.2.0-',
			'cms' => '',
		),
		'conflicts' => array(
			'fl_realurl_image' => '',
		),
		'suggests' => array(
			'realurl' => '1.3.4-',
			'dam' => '1.0.13-',
			'dam_ttcontent' => '1.0.101-',
		),
	),
	'_md5_values_when_last_written' => 'a:11:{s:9:"ChangeLog";s:4:"f74d";s:10:"README.txt";s:4:"ee2d";s:12:"ext_icon.gif";s:4:"1bdc";s:14:"ext_tables.php";s:4:"61b8";s:14:"ext_tables.sql";s:4:"47df";s:33:"icon_tx_realimageurl_requests.gif";s:4:"475a";s:34:"icon_tx_realimageurl_responses.gif";s:4:"475a";s:16:"locallang_db.xml";s:4:"9baf";s:7:"tca.php";s:4:"4e87";s:19:"doc/wizard_form.dat";s:4:"f4ac";s:20:"doc/wizard_form.html";s:4:"2023";}',
);

?>