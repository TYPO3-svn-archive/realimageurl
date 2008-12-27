<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

// unserializing the configuration
$_EXTCONF = unserialize($_EXTCONF);

// Add static template for enabling the Click-enlarge feature
if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$_EXTKEY]['enableClickEnlarge']) {
	t3lib_extMgm::addStaticFile($_EXTKEY, 'ext/rtehtmlarea/static/clickenlarge/', 'Clickenlarge Rendering');
}

// Add static template for enabling the txdam attribute
if (t3lib_extMgm::isLoaded('dam')) {
//	t3lib_extMgm::addStaticFile($_EXTKEY, 'ext/dam/static/txdam/', 'txdam-Attribute on img-Tag');
}

// Add static template for enabling custom realimageurl configuration
//if (1) {
	t3lib_extMgm::addStaticFile($_EXTKEY, 'static/', 'Real Image-URLs');
	t3lib_extMgm::addStaticFile($_EXTKEY, 'static/imagelinkname/', 'CSS Styled Content (Real Image-URLs)');
//}

//if (1) {
//	t3lib_extMgm::addPageTSConfig('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:css_styled_content/pageTSconfig.txt">');
//}

if ($_EXTCONF['enable.']['list_module_tca']) {

	$TCA["tx_realimageurl_requests_view"] = array (
		"ctrl" => array (
			'label' => 'location',
			'tstamp' => 'tstamp',
			'crdate' => 'crdate',
			'cruser_id' => 'cruser_id',
			'default_sortby' => "ORDER BY crdate",
			'sortby' => 'location',
			'readOnly' => '1',
			'adminOnly' => '1',
			'rootLevel' => '1',
			'thumbnail' => 'location',
			'title'     => 'LLL:EXT:realimageurl/locallang_db.xml:tx_realimageurl_requests',
			'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
			'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_realimageurl_requests.gif',
		),
		"feInterface" => array (
			"fe_admin_fieldList" => "parameter_hash, location",
		)
	);

	$TCA["tx_realimageurl_responses_view"] = array (
		"ctrl" => array (
			'label' => 'location',
			'tstamp' => 'tstamp',
			'crdate' => 'crdate',
			'cruser_id' => 'cruser_id',
			'default_sortby' => "ORDER BY crdate",
			'sortby' => 'location',
			'readOnly' => '1',
			'adminOnly' => '1',
			'thumbnail' => 'location',
			'title'     => 'LLL:EXT:realimageurl/locallang_db.xml:tx_realimageurl_responses',
			'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php',
			'iconfile'          => t3lib_extMgm::extRelPath($_EXTKEY).'icon_tx_realimageurl_responses.gif',
		),
		"feInterface" => array (
			"fe_admin_fieldList" => "ppid, cid, fid, descr, url",
		)
	);
}
?>