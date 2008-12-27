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

if (($instance = instanciateTemplateClass('t3lib/', 't3lib_parsehtml_proc'))) {
	include_once($instance); }
else {

/* +ux_t3lib_parsehtml_proc+ */
class ux_t3lib_parsehtml_proc extends t3lib_parsehtml_proc {

	var $imapObj;

	function __construct() {
	//	parent::__construct();
		$this->imapObj = t3lib_div::getUserObj('EXT:realimageurl/class.tx_realimageurl_imagemapper_be.php:&tx_realimageurl_imagemapper_be', null);
	}

	/**
	 * Transformation handler: 'ts_images' / direction: "db"
	 * Processing images inserted in the RTE.
	 * This is used when content goes from the RTE to the database.
	 * Images inserted in the RTE has an absolute URL applied to the src attribute. This URL is converted to a relative URL
	 * If it turns out that the URL is from another website than the current the image is read from that external URL and moved to the local server.
	 * Also "magic" images are processed here.
	 *
	 * @param	string		The content from RTE going to Database
	 * @return	string		Processed content
	 */
	function TS_images_db($value) {
	// ---- echo 'TS_images_db<br />';
	//	print_r($value); echo '<br />';

			// Split content by <img> tags and traverse the resulting array for processing:
		$imgSplit = $this->splitTags('img', $value);
		foreach ($imgSplit as $k => $v) {

				// image found, do processing:
			if ($k % 2) {

				// Init
				$attribArray = $this->get_tag_attributes_classic($v, 1);
				$siteUrl = $this->siteUrl();
				$sitePath = str_replace(t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST'), '', $siteUrl);

				// It's always a absolute URL coming from the RTE into the Database.
				$absRef = trim($attribArray['src']);

				// make path absolute if it is relative and we have a site path wich is not '/'
				$pI = pathinfo($absRef);
				if ($sitePath AND !$pI['scheme'] && t3lib_div::isFirstPartOfStr($absRef, $sitePath)) {

					// if site is in a subpath (eg. /~user_jim/) this path needs to be removed because it will be added with $siteUrl
					$absRef = substr($absRef, strlen($sitePath));
					$absRef = $siteUrl . $absRef;
				}

				// External image from another URL? In that case, fetch image (unless disabled feature).
				if (!t3lib_div::isFirstPartOfStr($absRef, $siteUrl) /*&& !$this->procOptions['dontFetchExtPictures']*/) {

					if ($this->imapObj->is_url($absRef)) {
						if (($output = $this->imapObj->makePermanentCopy($absRef, TX_RIU_IMAGEMAPPER_FETCHDIR))) {
							/* Got it, create absolute but not host-prefixed urls */
							$attribArray['src'] = $absRef = str_replace(PATH_site, '', $output);
						}
					}

				}

				// Check image as local file (siteURL equals the one of the image)
				if (!$this->imapObj->is_url($absRef, 'http://')) {

				    	// Rel-path, rawurldecoded for special characters.
					$path = rawurldecode(substr($absRef, strlen($siteUrl)));
					$path = $absRef;

					// Abs filePath, locked to relative path of this project.
					$filePath = t3lib_div::getFileAbsFileName($path);

					/* find the unmodified original picture for higher-quality processing */
					if ($this->imapObj->is_build($filePath)) {
						$origPath = $this->imapObj->findRequestSource($filePath);
					} else {
						$origPath = $filePath;
					}

					$imgObj = t3lib_div::makeInstance('t3lib_stdGraphic');
					$imgObj->init();
					$imgObj->mayScaleUp = 0;
					$imgObj->tempPath = PATH_site . $imgObj->tempPath;

					// Image dimensions of the original image
					$orgInfo = $imgObj->getImageDimensions($origPath);
					// Image dimensions of the current image
					$curInfo = $imgObj->getImageDimensions($filePath);
					// Image dimensions as set in the image tag
					$curWH = $this->getWHFromAttribs($attribArray);

					/* recalculate only inline-images */
					if ($this->imapObj->is_inline($origPath)) {
						// Compare dimensions:
						if ($curWH[0] != $curInfo[0] ||
						    $curWH[1] != $curInfo[1]) {
							// Image dimensions of the current image
							$cW = $curWH[0];
							$cH = $curWH[1];
							$cH = 1000;	// Make the image based on the width solely...

							$newInfo = $imgObj->imageMagickConvert($origPath, "WEB", $cW . 'm', $cH . 'm');

							if ($newInfo[3]) {
								// Removing width and heigth form style attribute
								$attribArray['style' ] = preg_replace('/((?:^|)\s*(?:width|height)\s*:[^;]*(?:$|;))/si', '', $attribArray['style']);
								$attribArray['width' ] = $newInfo[0];
								$attribArray['height'] = $newInfo[1];
								$attribArray['src'   ] = $absRef = str_replace(PATH_site, '', $newInfo[3]);
							}
						}
					// If "plain image" has been configured:
					} else if ($this->procOptions['plainImageMode']) {
						if ($curWH[0]) $attribArray['width' ] = $curWH[0];
						if ($curWH[1]) $attribArray['height'] = $curWH[1];

						// Removing width and heigth form style attribute
						$attribArray['style'] = preg_replace('/((?:^|)\s*(?:width|height)\s*:[^;]*(?:$|;))/si', '', $attribArray['style']);

							// Perform corrections to aspect ratio based on configuration:
						switch((string)$this->procOptions['plainImageMode']) {
							case 'lockDimensions':
								$attribArray['width' ] = $orgInfo[0];
								$attribArray['height'] = $orgInfo[1];
								break;
							case 'lockRatioWhenSmaller':
									// If the ratio has to be smaller, then first set the width...:
								if ($attribArray['width'] > $orgInfo[0])
								    $attribArray['width'] = $orgInfo[0];
							case 'lockRatio':
								if ($orgInfo[0] > 0) {
									$attribArray['height'] = round($attribArray['width'] * ($orgInfo[1] / $orgInfo[0]));
								}
								break;
						}
					}
				}

				// Convert abs to rel url
				if ($attribArray['src']) {
					$absRef = trim($attribArray['src']);

					/* make the response image */
					if (($putRef = $this->imapObj->makeResponseImage($this->recPid, $this->elRef,
							$attribArray, $attribArray['alt'] ? $attribArray['alt'] : $attribArray['title']))) {
						$absRef = $putRef;
					}

					if (t3lib_div::isFirstPartOfStr($absRef, $siteUrl)) {
						$absRef = $this->relBackPath . substr($absRef, strlen($siteUrl));
					}

					$attribArray['src'] = trim($absRef);
				}

				// Must have alt-attribute for XHTML compliance.
				if (!isset($attribArray['alt']))
					$attribArray['alt'] = '';

				$params = t3lib_div::implodeAttributes($attribArray, 1, 1);
				$imgSplit[$k] = '<img ' . $params . ' />';
			}
		}

		return implode('', $imgSplit);
	}

	/**
	 * Transformation handler: 'ts_images' / direction: "rte"
	 * Processing images from database content going into the RTE.
	 * Processing includes converting the src attribute to an absolute URL.
	 *
	 * @param	string		Content input
	 * @return	string		Content output
	 */
	function TS_images_rte($value)	{
	// ---- echo 'TS_images_rte<br />';
	//	print_r($value); echo '<br />';

		$siteUrl = $this->siteUrl();
		$sitePath = str_replace(t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST'), '', $siteUrl);

			// Split content by <img> tags and traverse the resulting array for processing:
		$imgSplit = $this->splitTags('img', $value);
		foreach ($imgSplit as $k => $v)	{
			if ($k % 2) {	// image found:

					// Init
				$attribArray = $this->get_tag_attributes_classic($v, 1);

				// Convert rel to abs url
				if ($attribArray['src']) {
					$absRef = trim($attribArray['src']);

					/* restore the original source */
					if (($findRef = $this->imapObj->findResponseSource($absRef))) {
						$absRef = $findRef;
					}

						// Unless the src attribute is already pointing to an external URL:
					if (strtolower(substr($absRef, 0, 4)) != 'http')	{
						$absRef = substr($absRef, strlen($this->relBackPath));
							// if site is in a subpath (eg. /~user_jim/) this path needs to be removed because it will be added with $siteUrl
						$absRef = preg_replace('#^' . preg_quote($sitePath,'#') . '#', '', $absRef);
						$absRef = $siteUrl . $absRef;
					}

					$attribArray['src'] = trim($absRef);
				}

				// Must have alt-attribute for XHTML compliance.
				if (!isset($attribArray['alt']))
					$attribArray['alt'] = '';

				$params = t3lib_div::implodeAttributes($attribArray);
				$imgSplit[$k] = '<img '.$params.' />';
			}
		}

			// return processed content:
		return implode('', $imgSplit);
	}

}
/* -ux_t3lib_parsehtml_proc- */ }

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/t3lib/class.ux_t3lib_parsehtml_proc.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/t3lib/class.ux_t3lib_parsehtml_proc.php']);
}
?>