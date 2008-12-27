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

if (($instance = instanciateTemplateClass('tslib/', 'tslib_cObj', 'tslib_content'))) {
	include_once($instance); }
else {

/* +ux_tslib_cObj+ */
class ux_tslib_cObj extends tslib_cObj {

	var $imapObj;
	var $takeoverIR = false;
	var $takeoverParams = array();

	function __construct() {
	//	parent::__construct();
		$this->imapObj = t3lib_div::getUserObj('EXT:realimageurl/class.tx_realimageurl_imagemapper_fe.php:&tx_realimageurl_imagemapper_fe', null);
		$this->imapObj->setTSObj($this);
	}

	/**
	 * Converts a png file to gif
	 * This converts a png file to gif IF the FLAG $GLOBALS['TYPO3_CONF_VARS']['FE']['png_to_gif'] is set true.
	 * Usage: 5
	 *
	 * @param	string		$theFile	the filename with path
	 * @return	string		new filename
	 */
	function imageMagickConvertPNGtoGIF($imagefile) {
		/* this had a flaw: didn't renew the GIF if the PNG was newer */
		if (!$GLOBALS['TYPO3_CONF_VARS']['FE']['png_to_gif'] ||
		    !$GLOBALS['TYPO3_CONF_VARS']['GFX']['im'] ||
		    !$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path_lzw'] ||
		    strtolower(substr($theFile, -4, 4)) != '.png')
			return $imagefile;

	// ---- echo 'imageMagickConvertPNGtoGIF cObj<br />';
	//	print_r($imagefile); echo '<br />';

		/* build up some identification information */
		$info = array($imagefile);
		$sent = array($imagefile, filemtime($imagefile));
		$exec = array($imagefile, substr($imagefile, 0, -4) . '.gif', '');

		/* if we don't get a match, turn off simulation and really create the image */
		if (!($output = $this->imapObj->getRequestImage($sent, $exec, $info))) {
			$output = t3lib_div::png_to_gif_by_imagemagick($imagefile);

			/* then register the created informations in the database */
			$this->imapObj->putRequestImage($sent, $exec, $info);
		}
		/* if the temporary/original file has not been deleted (hash was the same, info) */
		else if (!@file_exists($output)) {
			$output = t3lib_div::png_to_gif_by_imagemagick($imagefile);
		}

		return $output;
	}

	/**
	 * Extracts the available meta-data of a file if dam is loaded
	 *
	 * @param	string		The file
	 * @return	none
	 */
	function getImgMetadata($info, $conf) {
		$file = $info[3];

		if (t3lib_extMgm::isLoaded('dam')) {
			if ($info['origFile'])
				$file = $info['origFile'];

			/* fetch DAM data and provide it as field data prefixed with txdam_ */
			$media = tx_dam::media_getForFile($file, '*');
			if ($media->isAvailable) {
				$meta = $media->getMetaArray();

				foreach ($meta as $key => $value) {
					$this->data['txdam_' . $key] = $value;
				}
			}
		}

		if ($info['origFile'])
			$this->data['origFile'] = $info['origFile'];
	}

	/**
	 * Creates and returns a TypoScript "imgResource".
	 * The value ($file) can either be a file reference (TypoScript resource) or the string "GIFBUILDER". In the first case a current image is returned, possibly scaled down or otherwise processed. In the latter case a GIFBUILDER image is returned; This means an image is made by TYPO3 from layers of elements as GIFBUILDER defines.
	 * In the function IMG_RESOURCE() this function is called like $this->getImgResource($conf['file'],$conf['file.']);
	 *
	 * @param	string		A "imgResource" TypoScript data type. Either a TypoScript file resource or the string GIFBUILDER. See description above.
	 * @param	array		TypoScript properties for the imgResource type
	 * @return	array		Returns info-array. info[origFile] = original file.
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=315&cHash=63b593a934
	 * @see IMG_RESOURCE(), cImage(), tslib_gifBuilder
	 */
	function getImgResource($file, $conf) {
	// ---- echo 'getImgResource<br />';
	//	print_r($file); echo '<br />';
	//	print_r($conf); echo '<br />';

		$info = parent::getImgResource($file, $conf);
		if (is_array($info)) {
			$this->getImgMetadata($info, $conf);

			if ($this->takeoverIR && !$this->imapObj->is_mapped(PATH_site . $info[3])) {
				$conf = $this->takeoverParams;

				/* global configuration */
				$lconf = $conf['imageLinkName.'] ? $conf['imageLinkName.'] : array();
				$rconf = $conf['imageLinkName.'] ? $conf['imageLinkName.'] : array();
				if (($gconf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['realimageurl.'])) {
      					$lconf = t3lib_div::array_merge_recursive_overrule($lconf, $gconf, 0, 1);
      					$rconf = t3lib_div::array_merge_recursive_overrule($gconf, $rconf, 1, 0);
      				}

      				/* these not (because a real change would reflect allready in $assignment) */
				unset($rconf['ifBlank.']);

				/* make the response-image */
				$info[3] = $this->imapObj->makeResponseImage(
					$GLOBALS['TSFE']->id,
					$this->currentRecord,
	//				$this->parentRecord,
					$info,
					$this->stdWrap($conf['imageLinkName'], $lconf),
					$rconf);
			}
		}

		return $info;
	}

	/**
	 * Returns a <img> tag with the image file defined by $file and processed according to the properties in the TypoScript array.
	 * Mostly this function is a sub-function to the IMAGE function which renders the IMAGE cObject in TypoScript. This function is called by "$this->cImage($conf['file'],$conf);" from IMAGE().
	 *
	 * @param	string		File TypoScript resource
	 * @param	array		TypoScript configuration properties
	 * @return	string		<img> tag, (possibly wrapped in links and other HTML) if any image found.
	 * @access private
	 * @see IMAGE()
	 */
	function cImage($file, $conf) {
	// ---- echo 'cImage<br />';
	//	print_r($file); echo '<br />';
	//	print_r($conf); echo '<br />';

		/* primary fall-back source for the file-name */
		$this->data['ts_altText'  ] = trim($this->stdWrap($conf['altText'], $conf['altText.']));
		$this->data['ts_titleText'] = trim($this->stdWrap($conf['titleText'], $conf['titleText.']));
		$this->data['ts_longDesc' ] = trim($this->stdWrap($conf['longdescURL'], $conf['longdescURL.']));

		/* indicate to getimg Resource, that this picture should
		 * be remapped, then call the original (or possibly overloaded)
		 * funtion without affecting it's custom behaviour (blackboxing)
		 */
		$this->takeoverIR = true;
		$this->takeoverParams = $conf;
		$content = parent::cImage($file, $conf);
		$this->takeoverIR = false;

		return $content;
	}

	/**
	 * Wraps the input string in link-tags that opens the image in a new window.
	 *
	 * @param	string		String to wrap, probably an <img> tag
	 * @param	string		The original image file
	 * @param	array		TypoScript properties for the "imageLinkWrap" function
	 * @return	string		The input string, $string, wrapped as configured.
	 * @see cImage()
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=316&cHash=2848266da6
	 */
	function imageLinkWrap($string, $imageFile, $conf) {
		$info = array('', '', '', $imageFile);

		/* global configuration */
		$lconf = $conf['linkname.'] ? $conf['linkname.'] : array();
		$rconf = $conf['linkname.'] ? $conf['linkname.'] : array();
		if (($gconf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['realimageurl.'])) {
      			$lconf = t3lib_div::array_merge_recursive_overrule($lconf, $gconf, 0, 1);
      			$rconf = t3lib_div::array_merge_recursive_overrule($gconf, $rconf, 1, 0);
      		}

      		/* these not (because a real change would reflect allready in $assignment) */
		unset($rconf['ifBlank.']);

      		/* popups get a special context/appendix */
      		$rconf['postfix'] = 'popup';

		$imageFile = $this->imapObj->makeResponseImage(
			$GLOBALS['TSFE']->id,
			$this->currentRecord,
	//		$this->parentRecord,
			$info,
			$this->stdWrap($conf['linkname'], $lconf),
			$rconf);

		/* additional TypoScript, especially in combination with typolink,
		 * may try NOT to address the supplied image-file
		 *
		 * the most common pattern is an access to TSFE:lastImageInfo
		 * which is IMHO the wrong way.
		 */
		$info[0] = $GLOBALS['TSFE']->lastImageInfo[3];
		$info[1] = $GLOBALS['TSFE']->lastImageInfo['origFile'];

		$GLOBALS['TSFE']->lastImageInfo[3] = $imageFile;
		$GLOBALS['TSFE']->lastImageInfo['origFile'] = $imageFile;

		$ilw = parent::imageLinkWrap($string, $imageFile, $conf);

		$GLOBALS['TSFE']->lastImageInfo[3] = $info[0];
		$GLOBALS['TSFE']->lastImageInfo['origFile'] = $info[1];

		return $ilw;
	}

	/**
	 * Rendering the cObject, IMG_RESOURCE
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=354&cHash=46f9299706
	 * @see getImgResource()
	 */
	function IMG_RESOURCE($conf)	{
	// ---- echo 'IMG_RESOURCE<br />';
	//	print_r($conf); echo '<br />';

		/* indicate to getimg Resource, that this picture should
		 * be remapped, then call the original (or possibly overloaded)
		 * funtion without affecting it's custom behaviour (blackboxing)
		 */
		$this->takeoverIR = true;
		$this->takeoverParams = $conf;
		$content = parent::IMG_RESOURCE($conf);
		$this->takeoverIR = false;

		return $content;
	}

	/**
	 * Rendering the cObject, IMAGE
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=353&cHash=440681ea56
	 * @see cImage()
	 */
	function IMAGE($conf) {
	// ---- echo 'IMAGE<br />';
	//	print_r($conf); echo '<br />';

		return parent::IMAGE($conf);
	}

	/**
	 * Rendering the cObject, IMGTEXT
	 *
	 * @param	array		Array of TypoScript properties
	 * @return	string		Output
	 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=363&cHash=cf2969bce1
	 */
	function IMGTEXT($conf) {
	// ---- echo 'IMGTEXT<br />';
	//	print_r($conf); echo '<br />';

		return parent::IMGTEXT($conf);
	}

}
/* -ux_tslib_cObj- */ }

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/class.ux_tslib_content.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/class.ux_tslib_content.php']);
}
?>