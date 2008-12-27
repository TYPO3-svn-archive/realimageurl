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

if (($instance = instanciateTemplateClass('tslib/', 'SC_tslib_showpic', 'showpic', TRUE))) {
	include_once($instance); }
else {

/* +ux_SC_tslib_showpic+ */
require_once(t3lib_extMgm::extPath('realimageurl') . 'class.tx_realimageurl_imagemapper_fe.php');

class ux_SC_tslib_showpic extends SC_tslib_showpic {

	var $imapObj;

	function __construct() {
	//	parent::__construct();
		$this->imapObj = t3lib_div::getUserObj('EXT:realimageurl/class.tx_realimageurl_imagemapper_fe.php:&tx_realimageurl_imagemapper_fe', null);
	}

	/**
	 * Main function which creates the image if needed and outputs the HTML code for the page displaying the image.
	 * Accumulates the content in $this->content
	 *
	 * @return	void
	 */
	function main()	{

			// Creating stdGraphic object, initialize it and make image:
		$img = t3lib_div::makeInstance('t3lib_stdGraphic');
		$img->mayScaleUp = 0;
		$img->init();

		if ($this->sample) {
			$img->scalecmd = '-sample';
		}

		// Need to connect to database, because this is used (typo3temp_db_tracking, cached image dimensions).
		$GLOBALS['TYPO3_DB']->sql_pconnect(TYPO3_db_host, TYPO3_db_username, TYPO3_db_password);
		$GLOBALS['TYPO3_DB']->sql_select_db(TYPO3_db);

		if (strstr($this->width . $this->height, 'm')) {
			$max = 'm';
		} else {
			$max = '';
		}

		$this->height = t3lib_div::intInRange($this->height, 0);
		$this->width = t3lib_div::intInRange($this->width, 0);

		if ($this->frame) {
			$this->frame = intval($this->frame);
		}

		$filePath = PATH_site . $this->file;

		/* find the unmodified original picture for higher-quality processing */
		if ($this->imapObj->is_mapped($filePath)) {
			$origPath = $this->imapObj->findResponseSource($filePath);
		} else {
			$origPath = $filePath;
		}

		$info = $img->imageMagickConvert($origPath, 'web', $this->width . $max, $this->height, $img->IMparams($this->effects), $this->frame, '');

		if ($info[3] == $origPath) {
			$info[3] = $this->file;
		}
		else if ($this->imapObj->is_build($info[3])) {
			$conf = array('postfix' => '-' . $info[0] . 'x' . $info[1]);

			/* make the response image */
			$imageFile = $this->imapObj->makeResponseImageClone(
				$info,
				$this->file,
				$conf);
		}

			// Create HTML output:
		$this->content='';
		$this->content.='
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
	<title>'.htmlspecialchars($this->title ? $this->title : "Image").'</title>
</head>
		'.($this->bodyTag ? $this->bodyTag : '<body>');

		if (is_array($info))	{
			$wrapParts = explode('|',$this->wrap);
			$this->content.=trim($wrapParts[0]).$img->imgTag($info).trim($wrapParts[1]);
		}
		$this->content.='
		</body>
		</html>';
	}

}
/* -ux_SC_tslib_showpic- */ }

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/tslib/showpic.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/tslib/showpic.php']);
}
?>