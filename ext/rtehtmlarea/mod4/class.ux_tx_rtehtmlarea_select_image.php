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

if (($instance = instanciateTemplateClass('ext/rtehtmlarea/', 'tx_rtehtmlarea_select_image'))) {
	include_once($instance); }
else {

/* +ux_tx_rtehtmlarea_select_image+ */
class ux_tx_rtehtmlarea_select_image extends tx_rtehtmlarea_select_image {

	var $imapObj;

	function __construct() {
	//	parent::__construct();
		$this->imapObj = t3lib_div::getUserObj('EXT:realimageurl/class.tx_realimageurl_imagemapper_be.php:&tx_realimageurl_imagemapper_be', null);
	}

	/**
	 * Get the path to the folder where RTE images are stored
	 *
	 * @return	string		the path to the folder where RTE images are stored
	 */
	function getRTEImageStorageDir() {
		return ($this->imgPath ? $this->imgPath : $GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_imageStorageDir']);
	}

	/**
	 * Insert a magic image
	 *
	 * @param	string		$filepath: the path to the image file
	 * @param	array		$imgInfo: a 4-elements information array about the file
	 * @param	string		$altText: text for the alt attribute of the image
	 * @param	string		$titleText: text for the title attribute of the image
	 * @param	string		$additionalParams: text representing more HTML attributes to be added on the img tag
	 * @return	void
	 */
	function insertMagicImage($filepath, $imgInfo, $altText = '', $titleText = '', $additionalParams = '') {

		if (is_array($imgInfo) && count($imgInfo) == 4) {

			/* TODO: don't make new clones every time, match other identical copies */
			if (($filepath = $this->imapObj->makePersistantCopy($filepath, TX_RIU_IMAGEMAPPER_INLINEDIR))) {
				/* TODO incorporate maxwidth/maxheight in to the params */
				$cWidth  = t3lib_div::intInRange(t3lib_div::_GP('cWidth' ), 0, $this->magicMaxWidth );
				$cHeight = t3lib_div::intInRange(t3lib_div::_GP('cHeight'), 0, $this->magicMaxHeight);

				if (!$cWidth )	$cWidth  = $this->magicMaxWidth;
				if (!$cHeight)	$cHeight = $this->magicMaxHeight;

				$iurl = $this->siteURL . str_replace(PATH_site, '', $filepath);

				/* TODO: images that are not web-displayable (like TIFF) */
				$this->imageInsertJS($iurl, $imgInfo[0], $imgInfo[1], $altText, $titleText, $additionalParams);
			}
		}
	}

	/**
	 * Insert a plain image
	 *
	 * @param	array		$imgInfo: a 4-elements information array about the file
	 * @param	string		$altText: text for the alt attribute of the image
	 * @param	string		$titleText: text for the title attribute of the image
	 * @param	string		$additionalParams: text representing more HTML attributes to be added on the img tag
	 * @return	void
	 */
	function insertPlainImage($imgInfo, $altText = '', $titleText = '', $additionalParams = '') {

		if (is_array($imgInfo) && count($imgInfo) == 4)	{

			$destName = dirname($destName) . '/' . rawurlencode($imgInfo[3]);

			$iurl = $this->siteURL . substr($destName, strlen(PATH_site));

			/* TODO: images that are not web-displayable (like TIFF) */
			$this->imageInsertJS($iurl, $imgInfo[0], $imgInfo[1], $altText, $titleText, $additionalParams);
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getJSCode() {
		$JScode = parent::getJSCode();

		$JScode = str_replace(
			'"RTEmagic"',
			'"/imap/inline/"',
			$JScode
		);

		return $JScode;
	}

}
/* -ux_tx_rtehtmlarea_select_image- */ }

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/class.ux_rtehtmlarea_select_image.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/class.ux_rtehtmlarea_select_image.php']);
}
?>