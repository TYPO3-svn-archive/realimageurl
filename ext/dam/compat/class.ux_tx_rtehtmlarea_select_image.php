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

require_once(t3lib_extMgm::extPath('realimageurl') . 'class.tx_realimageurl_imagemapper.php');

class ux_tx_rtehtmlarea_select_image extends tx_rtehtmlarea_select_image {

	var $imapObj;

	function __construct() {
	//	parent::__construct();
		$this->imapObj = t3lib_div::getUserObj('EXT:realimageurl/class.tx_realimageurl_imagemapper.php:&tx_realimageurl_imagemapper', null);
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
	function insertMagicImage($filepath, $imgInfo, $altText='', $titleText='', $additionalParams='') {
		if (is_array($imgInfo) && count($imgInfo)==4 && $this->RTEImageStorageDir)	{
			$fI = pathinfo($imgInfo[3]);
			$fileFunc = t3lib_div::makeInstance('t3lib_basicFileFunctions');
			$basename = $fileFunc->cleanFileName('RTEmagicP_'.$fI['basename']);
			$destPath =PATH_site.$this->RTEImageStorageDir;
			if (@is_dir($destPath))	{
				$destName = $fileFunc->getUniqueName($basename,$destPath);
				@copy($imgInfo[3],$destName);
				t3lib_div::fixPermissions($destName);
				$cWidth = t3lib_div::intInRange(t3lib_div::_GP('cWidth'), 0, $this->magicMaxWidth);
				$cHeight = t3lib_div::intInRange(t3lib_div::_GP('cHeight'), 0, $this->magicMaxHeight);
				if (!$cWidth)	$cWidth = $this->magicMaxWidth;
				if (!$cHeight)	$cHeight = $this->magicMaxHeight;

				$imgI = $this->imgObj->imageMagickConvert($filepath,'WEB',$cWidth.'m',$cHeight.'m');	// ($imagefile,$newExt,$w,$h,$params,$frame,$options,$mustCreate=0)
				if ($imgI[3])	{
					$fI=pathinfo($imgI[3]);
					$mainBase='RTEmagicC_'.substr(basename($destName),10).'.'.$fI['extension'];
					$destName = $fileFunc->getUniqueName($mainBase,$destPath);
					@copy($imgI[3],$destName);
					t3lib_div::fixPermissions($destName);
					$destName = dirname($destName).'/'.rawurlencode(basename($destName));
					$iurl = $this->siteURL.substr($destName,strlen(PATH_site));
					$this->imageInsertJS($iurl, $imgI[0], $imgI[1], $altText, $titleText, $additionalParams);
				}
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
	public function insertPlainImage($imgInfo, $altText='', $titleText='', $additionalParams='') {
		if (is_array($imgInfo) && count($imgInfo)==4)	{
			$iurl = $this->siteURL.substr($imgInfo[3],strlen(PATH_site));
			$this->imageInsertJS($iurl, $imgInfo[0], $imgInfo[1], $altText, $titleText, $additionalParams);
		}
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/ext_dam/compat/class.ux_rtehtmlarea_select_image.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/ext_dam/compat/class.ux_rtehtmlarea_select_image.php']);
}

?>