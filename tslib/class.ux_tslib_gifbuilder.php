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

if (($instance = instanciateTemplateClass('tslib/', 'tslib_gifBuilder', 'tslib_gifbuilder'))) {
	include_once($instance); }
else {

/* +ux_tslib_gifBuilder+ */
class ux_tslib_gifBuilder extends tslib_gifBuilder {

	var $imapObj;
	var $conf;

	var $simulateIM = false;
	var $simulateResult = array();

	function __construct() {
		global $TYPO3_CONF_VARS;

	//	parent::__construct();
		$this->imapObj = t3lib_div::getUserObj('EXT:realimageurl/class.tx_realimageurl_imagemapper_fe.php:&tx_realimageurl_imagemapper_fe', null);
		$this->conf = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['realimageurl']);

		$this->tempPath = str_replace(PATH_site, '', TX_RIU_IMAGEMAPPER_BUILDDIR);
	}

	/**
	 * Init function. Must always call this when using the class.
	 * This function will read the configuration information from $GLOBALS['TYPO3_CONF_VARS']['GFX'] can set some values in internal variables.
	 *
	 * @return	void
	 */
	function init()	{
		parent::init();

		/* interlaced JPEG are ALWAYS smaller, it's a natural property of DCT */
		if ($this->conf['progressive_jpgs']) {
			if (!preg_match('/-interlace/', $this->cmds['jpg']))
				$this->cmds['jpg'] .= ' -interlace';
			if (!preg_match('/-interlace/', $this->cmds['jpeg']))
				$this->cmds['jpeg'] .= ' -interlace';
		}

		/* interlaced GIF are allways bigger */
		if ($this->conf['progressive_gifs']) {
			if (!preg_match('/-interlace/', $this->cmds['gif']))
				$this->cmds['gif'] .= ' -interlace';
		}

		/* interlaced PNG are allways bigger */
		if ($this->conf['progressive_pngs']) {
			if (!preg_match('/-interlace/', $this->cmds['png']))
				$this->cmds['png'] .= ' -interlace';
		}

		/* strip meta-data */
		if ($this->conf['strip']) {
			if (!preg_match('/-strip/', $this->cmds['jpg']))
				$this->cmds['jpg'] .= ' -strip';
			if (!preg_match('/-strip/', $this->cmds['jpeg']))
				$this->cmds['jpeg'] .= ' -strip';
			if (!preg_match('/-strip/', $this->cmds['gif']))
				$this->cmds['gif'] .= ' -strip';
			if (!preg_match('/-strip/', $this->cmds['png']))
				$this->cmds['png'] .= ' -strip';
		}
	}

	function getProgram($sKeyArray) {
		$ops = array();
		$text = array();
		$names = array();
		$dates = array();

		foreach ($sKeyArray as $theKey)	{
			$theValue = $this->setup[$theKey];

			if (intval($theKey) && ($conf = $this->setup[$theKey . '.'])) {
				$ops[] = $theValue;

				if (@file_exists($conf['file'])) {
					$names[] = $conf['file'];
					$dates[] = @filemtime($conf['file']);
				}

				if (($theValue == 'TEXT') && !$conf['hide']) {
					$text[] = $this->recodeString($conf['text']);
				}
			}
		}

		return array($names, $ops, $text, $dates);
	}

	/**
	 * Returns Image Tag for input image information array.
	 *
	 * @param	array		Image information array, key 0/1 is width/height and key 3 is the src value
	 * @return	string		Image tag for the input image information array.
	 */
	function imgTag($imgInfo) {
		/* two locations this gets called:
		 * - show_item.php	->	BE	->	http://site/images/...
		 * - showpic.php	->	FE	->	http://site/images/...
		 */

	// ---- echo 'imgTag<br />';
	//	print_r($imgInfo); echo '<br />';

		if ($imgInfo[3]) {
		}

		return parent::imgTag($imgInfo);
	}

	/**
	 * Executes a ImageMagick "convert" on two filenames, $input and $output using $params before them.
	 * Can be used for many things, mostly scaling and effects.
	 *
	 * @param	string		The relative (to PATH_site) image filepath, input file (read from)
	 * @param	string		The relative (to PATH_site) image filepath, output filename (written to)
	 * @param	string		ImageMagick parameters
	 * @return	string		The result of a call to PHP function "exec()"
	 */
	function imageMagickExec($input, $output, $params) {
		if (!$this->simulateIM)
			parent::imageMagickExec($input, $output, $params);
		else
			$this->simulateResult = array($input, $output, preg_replace('/ -(interlace|colorspace [a-zA-Z])/', '', $params));
	}

	/**
	 * Converts $imagefile to another file in temp-dir of type $newExt (extension).
	 *
	 * @param	string		The image filepath
	 * @param	string		New extension, eg. "gif", "png", "jpg", "tif". If $newExt is NOT set, the new imagefile will be of the original format. If newExt = 'WEB' then one of the web-formats is applied.
	 * @param	string		Width. $w / $h is optional. If only one is given the image is scaled proportionally. If an 'm' exists in the $w or $h and if both are present the $w and $h is regarded as the Maximum w/h and the proportions will be kept
	 * @param	string		Height. See $w
	 * @param	string		Additional ImageMagick parameters.
	 * @param	string		Refers to which frame-number to select in the image. '' or 0 will select the first frame, 1 will select the next and so on...
	 * @param	array		An array with options passed to getImageScale (see this function).
	 * @param	boolean		If set, then another image than the input imagefile MUST be returned. Otherwise you can risk that the input image is good enough regarding messures etc and is of course not rendered to a new, temporary file in typo3temp/. But this option will force it to.
	 * @return	array		[0]/[1] is w/h, [2] is file extension and [3] is the filename.
	 * @see getImageScale(), typo3/show_item.php, fileList_ext::renderImage(), tslib_cObj::getImgResource(), SC_tslib_showpic::show(), maskImageOntoImage(), copyImageOntoImage(), scale()
	 */
	function imageMagickConvert($imagefile, $newExt = '', $w = '', $h = '', $params = '', $frame = '', $options = '', $mustCreate = 0) {
	// ---- echo 'imageMagickConvert GIF<br />';
	//	print_r($imagefile); echo '<br />';

		/* receive the simuated results of the convert-command */
		$this->simulateIM = true;
		$info = parent::imageMagickConvert($imagefile, $newExt, $w, $h, $params, $frame, $options, $mustCreate);

		/* build up some identification information */
		$sent = array($imagefile, $newExt, $w, $h, $params, $frame, $options, $mustCreate, filemtime($imagefile));
		$exec = $this->simulateResult; $this->simulateResult = array();

		/* if we don't get a match, turn off simulation and really create the image */
		if (!($output = $this->imapObj->getRequestImage($sent, $exec, $info))) {
			$this->simulateIM = false;
			$info = parent::imageMagickConvert($imagefile, $newExt, $w, $h, $params, $frame, $options, $mustCreate);

			/* then register the created informations in the database */
			$this->imapObj->putRequestImage($sent, $exec, $info);
		}
		/* if the temporary/original file has not been deleted (hash was the same, info) */
		else if (!@file_exists($output)) {
			$this->simulateIM = false;
			parent::imageMagickExec($exec[0], $output, $exec[2]);
		}

		return $info;
	}

	/**
	 * Writes the input GDlib image pointer to file
	 *
	 * @param	pointer		The GDlib image resource pointer
	 * @param	string		The filename to write to
	 * @param	integer		The image quality (for JPEGs)
	 * @return	mixed		The output of either imageGif, imagePng or imageJpeg based on the filename to write
	 * @see maskImageOntoImage(), scale(), output()
	 */
	function ImageWrite($destImg, $theImage, $quality = 0)	{
		$result = false;

		$ext = strtolower(substr($theImage, strrpos($theImage, '.') + 1));
		switch ($ext) {
			case 'jpg':
			case 'jpeg':
				/* interlaced JPEG are ALWAYS smaller, it's a natural property of DCT */
				imageinterlace($destImg, $this->conf['progressive_jpgs']);
				if (function_exists('imageJpeg')) {
					if ($quality == 0) {
						$quality = $this->jpegQuality;
					}

					$result = imageJpeg($destImg, $theImage, $quality);
				}
				break;
			case 'gif':
				/* interlaced GIF are allways bigger */
				imageinterlace($destImg, $this->conf['progressive_gifs']);
				if (function_exists('imageGif')) {
					if ($this->truecolor) {
						imagetruecolortopalette($destImg, true, 256);
					}

					$result = imageGif($destImg, $theImage);
				}
				break;
			case 'png':
				/* interlaced PNG are allways bigger */
				imageinterlace($destImg, $this->conf['progressive_pngs']);
				if (function_exists('imagePng')) {

					$result = ImagePng($destImg, $theImage);
				}
				break;
		}

		// Extension invalid or write-function does not exist
		return $result;
	}

	/**
	 * Initialization of the GIFBUILDER objects, in particular TEXT and IMAGE. This includes finding the bounding box, setting dimensions and offset values before the actual rendering is started.
	 * Modifies the ->setup, ->objBB internal arrays
	 * Should be called after the ->init() function which initializes the parent class functions/variables in general.
	 * The class tslib_gmenu also uses gifbuilder and here there is an interesting use since the function findLargestDims() from that class calls the init() and start() functions to find the total dimensions before starting the rendering of the images.
	 *
	 * @param	array		TypoScript properties for the GIFBUILDER session. Stored internally in the variable ->setup
	 * @param	array		The current data record from tslib_cObj. Stored internally in the variable ->data
	 * @return	void
	 * @see tslib_cObj::getImgResource(), tslib_gmenu::makeGifs(), tslib_gmenu::findLargestDims()
	 */
	function start($conf, &$data) {
		parent::start($conf, $data);

		/* make data modifiable */
		$this->data = &$data;
	}

	/**
	 * Initiates the image file generation if ->setup is true and if the file did not exist already.
	 * Gets filename from fileName() and if file exists in typo3temp/ dir it will - of course - not be rendered again.
	 * Otherwise rendering means calling ->make(), then ->output(), then ->destroy()
	 *
	 * @return	string		The filename for the created GIF/PNG file. The filename will be prefixed "GB_"
	 * @see make(), fileName()
	 */
	function gifBuild()	{
		if ($this->setup) {
			$sKeyArray = t3lib_TStemplate::sortedKeyList($this->setup);
			list($filenames, $fileops, $filetext, $filemtimes) = $this->getProgram($sKeyArray);

			/* assume the first file given is the mother-image */
			$this->data['origFile'] = $filenames[0];
			$this->data['ts_ops'] = implode('+', $fileops);
			$this->data['ts_imgText'] = implode('\n', $filetext);

			/* build up some identification information */
			$sent = array($filenames, $fileops);
			$exec = array(array($this->setup, $filemtimes), '', '');
			$info = array(0, 0, 0, $this->fileName('GB/'));		// Relative to PATH_site

			/* if we don't get a match, turn off simulation and really create the image */
			if (!($output = $this->imapObj->getRequestImage($sent, $exec, $info)) ||
			    !@file_exists($output)) {				// File exists

				// Create temporary directory if not done:
				$this->createTempSubDir('GB/');

				// Create file:
				$this->make();
				$this->output($info[3]);
				$this->destroy();

				/* then register the created informations in the database */
				$this->imapObj->putRequestImage($sent, $exec, $info);
			}

			return $info[3];
		}
	}

}
/* -ux_tslib_gifBuilder- */ }

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/class.ux_tslib_gifbuilder.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/class.ux_tslib_gifbuilder.php']);
}
?>