<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2009 Niels Fr�hling (niels@frohling.biz)
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
require_once(t3lib_extMgm::extPath('dam') . 'binding/imgtag/class.tx_dam_tsfeimgtag.php');

class ux_tx_dam_tsfeimgtag extends tx_dam_tsfeimgtag {

	var $imapObj;

	function __construct() {
	//	parent::__construct();
		$this->imapObj = t3lib_div::getUserObj('EXT:realimageurl/class.tx_realimageurl_imagemapper.php:&tx_realimageurl_imagemapper', null);
	}

	/**
	 * Rendering the "txdam" custom attribute on img tag, called from TypoScript
	 *
	 * @param	string		Content input. Not used, ignore.
	 * @param	array		TypoScript configuration
	 * @return	string		Unmodified content input
	 * @access private
	 */
	function renderTxdamAttribute($content,$conf)	{
		global $TYPO3_CONF_VARS;

		$mediaId = isset($this->cObj->parameters['txdam']) ? $this->cObj->parameters['txdam'] : 0;
		if ($mediaId) {
			if (!is_object($media = tx_dam::media_getByUid($mediaId))) {
				$GLOBALS['TT']->setTSlogMessage("tx_dam_tsfeimgtag->renderTxdamAttribute(): File id '".$mediaId."' was not found, so '".$content."' was not updated.",1);
				return $content;
			}
			$metaInfo = $media->getMetaArray();
			$magicPathPrefix = $TYPO3_CONF_VARS['BE']['RTE_imageStorageDir'].'RTEmagicC_';
			if (t3lib_div::isFirstPartOfStr($this->cObj->parameters['src'], $magicPathPrefix)) {
					// Process magic image
				$pI = pathinfo(substr($this->cObj->parameters['src'], strlen($magicPathPrefix)));
				$fileName = preg_replace('/_[0-9][0-9]'.preg_quote('.').'/', '.', substr($pI['basename'], 0, -strlen('.'.$pI['extension'])));
				if ($fileName != $metaInfo['file_name']) {
						// Substitute magic image
					$imgObj = t3lib_div::makeInstance('t3lib_stdGraphic');
					$imgObj->init();
					$imgObj->mayScaleUp = 0;
					$imgObj->tempPath = PATH_site.$imgObj->tempPath;
					$imgInfo = $imgObj->getImageDimensions(PATH_site.$metaInfo['file_path'].$metaInfo['file_name']);
					if (is_array($imgInfo) && count($imgInfo)==4 && $TYPO3_CONF_VARS['BE']['RTE_imageStorageDir'])	{
							// Create or update the reference and magic images
						$fileInfo = pathinfo($imgInfo[3]);
						$fileFunc = t3lib_div::makeInstance('t3lib_basicFileFunctions');
							// Construct a name based on the mediaId and on the width and height of the magic image
						$basename = $fileFunc->cleanFileName('RTEmagicP_'.$fileInfo['filename'].'_txdam'.$this->cObj->parameters['txdam'].'_'.substr(md5($this->cObj->parameters['width'].'x'.$this->cObj->parameters['height']), 0, $fileFunc->uniquePrecision).'.'.$fileInfo['extension']);
						$destPath =PATH_site.$TYPO3_CONF_VARS['BE']['RTE_imageStorageDir'];
						if (@is_dir($destPath))	{
								// Do not check file uniqueness in order to avoid creating a new one on every rendering
							$destName = $fileFunc->getUniqueName($basename, $destPath, 1);
							if (!@is_file($destName))	{
								@copy($imgInfo[3],$destName);
								t3lib_div::fixPermissions($destName);
							}
							$magicImageInfo = $imgObj->imageMagickConvert($destName, 'WEB', $this->cObj->parameters['width'].'m', $this->cObj->parameters['height'].'m');
							if ($magicImageInfo[3])	{
								$fileInfo = pathinfo($magicImageInfo[3]);
								$mainBase = 'RTEmagicC_'.substr(basename($destName),10).'.'.$fileInfo['extension'];
								$destName = $fileFunc->getUniqueName($mainBase, $destPath, 1);
								if (!@is_file($destName))	{
									@copy($magicImageInfo[3],$destName);
									t3lib_div::fixPermissions($destName);
								}
								$destName = dirname($destName).'/'.rawurlencode(basename($destName));
								$this->cObj->parameters['src'] = substr($destName,strlen(PATH_site));
							}
						}
					}
				}
			} else {
					// Substitute plain image, if needed
				if ($this->cObj->parameters['src'] != $metaInfo['file_path'].$metaInfo['file_name']) {
					$this->cObj->parameters['src'] = $metaInfo['file_path'].$metaInfo['file_name'];
				}
			}
		}
		unset($this->cObj->parameters['txdam']);
		return $content;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/ext_dam/binding/imgtag/class.tx_dam_tsfeimgtag.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/ext_dam/binding/imgtag/class.tx_dam_tsfeimgtag.php']);
}

?>