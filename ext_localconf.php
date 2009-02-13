<?php
if (!defined ("TYPO3_MODE")) 	die ("Access denied.");

// unserializing the configuration
$_EXTCONF = unserialize($_EXTCONF);

require_once(t3lib_extMgm::extPath($_EXTKEY) . "ext_templates.php");

/* lo-level tracking (instanciated conversions and showpic) */
assignTemplateClass('t3lib/class.t3lib_stdgraphic.php'    );

/* typoscript-tracking */
assignTemplateClass('tslib/class.tslib_gifbuilder.php'    );
assignTemplateClass('tslib/class.tslib_content.php'       );
assignTemplateClass('tslib/showpic.php'                   );

/* upload-tracking */
assignTemplateClass('t3lib/class.t3lib_tcemain.php'       );

/* RTE/Magic-tracking */
assignTemplateClass('t3lib/class.t3lib_parsehtml_proc.php');
assignTemplateClass('t3lib/class.t3lib_softrefproc.php'   );

assignTemplateClass('ext/rtehtmlarea/mod4/class.tx_rtehtmlarea_dam_browse_media.php');
assignTemplateClass('ext/rtehtmlarea/mod4/class.tx_rtehtmlarea_select_image.php'    );
assignTemplateClass('ext/dam/compat/class.tx_rtehtmlarea_select_image.php'          );
assignTemplateClass('ext/tinymce_rte/class.tx_tinymce_rte_base.php'                 );
assignTemplateClass('ext/tinyrte/class.tx_tinyrte_base.php'                         );

assignTemplateClass('ext/tinymce_rte/mod2/rte_select_image.php'                     );
assignTemplateClass('ext/tinyrte/mod2/rte_select_image.php'                         );

/* helpers */
assignTemplateClass('typo3/classes/class.clearcachemenu.php'                        );

//	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_parsehtml_proc.php']['transformation']['ts_images'] =
//		t3lib_extMgm::extPath($_EXTKEY) . 'class.ux_t3lib_parsehtml_proc:&ux_t3lib_parsehtml_proc';
//	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['softRefParser']['images'] =
//		t3lib_extMgm::extPath($_EXTKEY) . 'class.ux_t3lib_softrefproc.php:&ux_t3lib_softrefproc';

if ($_EXTCONF['mod_rewrite']) {
	$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'][] =
		t3lib_extMgm::extPath($_EXTKEY) . 'class.user_realimageurl_imagemapper_post.php:&user_realimageurl_imagemapper_post->killLeadingMap';
}

/* register ajax call */
$TYPO3_CONF_VARS['BE']['AJAX']['realimageurl::imageurls'] = t3lib_extMgm::extPath($_EXTKEY) . 'class.tx_realimageurl_imagemapper.php:tx_realimageurl_imagemapper->clearURLs';
$TYPO3_CONF_VARS['BE']['AJAX']['realimageurl::imageall' ] = t3lib_extMgm::extPath($_EXTKEY) . 'class.tx_realimageurl_imagemapper.php:tx_realimageurl_imagemapper->clearAll';

$GLOBALS['TYPO3_CONF_VARS']['FE']['addRootLineFields'] .= ',tstamp';

?>
