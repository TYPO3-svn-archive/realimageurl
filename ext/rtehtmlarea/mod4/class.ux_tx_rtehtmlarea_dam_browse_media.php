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

if (($instance = instanciateTemplateClass('ext/rtehtmlarea/', 'tx_rtehtmlarea_dam_browse_media'))) {
	include_once($instance); }
else {

/* +ux_tx_rtehtmlarea_dam_browse_media+ */
require_once(t3lib_extMgm::extPath('realimageurl') . 'class.tx_realimageurl_imagemapper.php');

class ux_tx_rtehtmlarea_dam_browse_media extends tx_rtehtmlarea_dam_browse_media {

	var $imapObj;

	function __construct() {
	//	parent::__construct();
		$this->imapObj = t3lib_div::getUserObj('EXT:realimageurl/class.tx_realimageurl_imagemapper.php:&tx_realimageurl_imagemapper', null);
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function rteImageStorageDir()	{
		$dir = $this->imgPath ? $this->imgPath : $GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_imageStorageDir'];;
		return $dir;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function imageInsert()	{
		global $TCA,$TYPO3_CONF_VARS;

		if (t3lib_div::_GP('insertImage')) {
			$filepath = t3lib_div::_GP('insertImage');

			$imgObj = t3lib_div::makeInstance('t3lib_stdGraphic');
			$imgObj->init();
			$imgObj->mayScaleUp=0;
			$imgObj->tempPath=PATH_site.$imgObj->tempPath;
			$imgInfo = $imgObj->getImageDimensions($filepath);
			$imgMetaData = tx_dam::meta_getDataForFile($filepath,'uid,pid,alt_text,hpixels,vpixels,'.$this->imgTitleDAMColumn.','.$TCA['tx_dam']['ctrl']['languageField']);
			$imgMetaData = $this->getRecordOverlay('tx_dam',$imgMetaData,$this->sys_language_content);

			switch ($this->act) {
				case 'magic':
					if (is_array($imgInfo) && count($imgInfo)==4 && $this->rteImageStorageDir() && is_array($imgMetaData))	{
						$fI=pathinfo($imgInfo[3]);
						$fileFunc = t3lib_div::makeInstance('t3lib_basicFileFunctions');
						$basename = $fileFunc->cleanFileName('RTEmagicP_'.$fI['basename']);
						$destPath =PATH_site.$this->rteImageStorageDir();
						if (@is_dir($destPath))	{
							$destName = $fileFunc->getUniqueName($basename,$destPath);
							@copy($imgInfo[3],$destName);
							t3lib_div::fixPermissions($destName);
							$cWidth = t3lib_div::intInRange(t3lib_div::_GP('cWidth'),0,$this->magicMaxWidth);
							$cHeight = t3lib_div::intInRange(t3lib_div::_GP('cHeight'),0,$this->magicMaxHeight);
							if (!$cWidth)	$cWidth = $this->magicMaxWidth;
							if (!$cHeight)	$cHeight = $this->magicMaxHeight;

							$imgI = $imgObj->imageMagickConvert($filepath,'WEB',$cWidth.'m',$cHeight.'m');	// ($imagefile,$newExt,$w,$h,$params,$frame,$options,$mustCreate=0)
							if ($imgI[3])	{
								$fI=pathinfo($imgI[3]);
								$mainBase='RTEmagicC_'.substr(basename($destName),10).'.'.$fI['extension'];
								$destName = $fileFunc->getUniqueName($mainBase,$destPath);
								@copy($imgI[3],$destName);
								t3lib_div::fixPermissions($destName);
								$iurl = $this->siteUrl.substr($destName,strlen(PATH_site));
								$this->imageInsertJS($iurl,$imgI[0],$imgI[1],$imgMetaData['alt_text'],$imgMetaData[$this->imgTitleDAMColumn],substr($imgInfo[3],strlen(PATH_site)));
							}
						}
					}
					exit;
					break;
				case 'plain':
					if (is_array($imgInfo) && count($imgInfo)==4 && is_array($imgMetaData))	{
						$iurl = $this->siteUrl.substr($imgInfo[3],strlen(PATH_site));
						$this->imageInsertJS($iurl,$imgMetaData['hpixels'],$imgMetaData['vpixels'],$imgMetaData['alt_text'],$imgMetaData[$this->imgTitleDAMColumn],substr($imgInfo[3],strlen(PATH_site)));
					}
					exit;
					break;
			}
		}
	}

	/**
	 * Generate JS code to be used on the image insert/modify dialogue
	 *
	 * @param	string		$act: the action to be performed
	 * @param	string		$editorNo: the number of the RTE instance on the page
	 * @param	string		$sys_language_content: the language of the content element
	 *
	 * @return	string		the generated JS code
	 */
	function getJSCode($act, $editorNo, $sys_language_content) {
		$JScode = parent::getJSCode($act, $editorNo, $sys_language_content);

		$JScode = str_replace(
			'"RTEmagic"',
			'"RTEmagic"',
			$JScode
		);

		return $JScode;
	}

}
/* -ux_tx_rtehtmlarea_dam_browse_media- */ }

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/ext_rtehtmlarea/mod4/class.ux_rtehtmlarea_dam_browse_media.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/ext_rtehtmlarea/mod4/class.ux_rtehtmlarea_dam_browse_media.php']);
}

?>