<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2009 Niels Fröhling (niels@frohling.biz)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

if (($instance = instanciateTemplateClass('ext/tinymce_rte/', 'tx_tinymce_rte_base'))) {
	include_once($instance); }
else {

/* +ux_tx_tinymce_rte_base+ */
class ux_tx_tinymce_rte_base extends tx_tinymce_rte_base {

	var $imapObj;

	function __construct() {
		$this->imapObj = t3lib_div::getUserObj('EXT:realimageurl/class.tx_realimageurl_imagemapper_be.php:&tx_realimageurl_imagemapper_be', null);
		$this->imgPath = TX_RIU_IMAGEMAPPER_INLINEDIR;
	}

	/**
	 * creates the javascript code (incl. <script> tags) for the typo3filemanager
	 *
	 * @return	string	the javascript code to allow selection of pages in a TYPO3 dialog
	 */
	function drawRTE($parentObject, $table, $field, $row, $PA, $specConf, $thisConfig, $RTEtypeVal, $RTErelPath, $thePidValue) {
		$bunch = parent::drawRTE($parentObject, $table, $field, $row, $PA, $specConf, $thisConfig, $RTEtypeVal, $RTErelPath, $thePidValue);

	//	$bunch = str_replace(
	//		$this->getPath('EXT:tinymce_rte/./' ) .                 'mod2/rte_select_image.php',
	//		$this->getPath('EXT:realimageurl/./') . 'ext/tinymce_rte/mod2/rte_select_image.php',
	//		$bunch
	//	);

		$bunch = str_replace(
			'"RTEmagicC_"',
			'"/imap/inline/"',
			$bunch
		);

		return $bunch;
	}

}
/* -ux_tx_tinymce_rte_base- */ }

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/ext/tinymce_rte/class.ux_tinymce_rte_base.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/ext/tinymce_rte/class.ux_tinymce_rte_base.php']);
}
?>
