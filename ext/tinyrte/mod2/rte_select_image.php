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

if (($instance = instanciateTemplateClass('ext/tinyrte/mod2/', 'SC_rte_select_image', 'rte_select_image', TRUE))) {
	include_once($instance); }
else {

/* +ux_SC_rte_select_image+ */
class ux_SC_rte_select_image extends SC_rte_select_image {

	var $imapObj;

	function __construct() {
	//	parent::__construct();
		$this->imapObj = t3lib_div::getUserObj('EXT:realimageurl/class.tx_realimageurl_imagemapper_be.php:&tx_realimageurl_imagemapper_be', null);
		$this->imgPath = TX_RIU_IMAGEMAPPER_INLINEDIR;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function rteImageStorageDir()	{
		$dir = $this->imgPath ? $this->imgPath : $GLOBALS["TYPO3_CONF_VARS"]["BE"]["RTE_imageStorageDir"];;
		return $dir;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function magicProcess()	{
		if ($this->act=="magic" && t3lib_div::_GP("insertMagicImage"))	{
			$filepath = t3lib_div::_GP("insertMagicImage");

			/* TODO: don't make new clones every time, match other identical copies */
			if (($filepath = $this->imapObj->makePersistantCopy($filepath, TX_RIU_IMAGEMAPPER_INLINEDIR))) {
				$imgObj = t3lib_div::makeInstance("t3lib_stdGraphic");
				$imgObj->init();
				$imgObj->mayScaleUp=0;
				$imgObj->tempPath=PATH_site.$imgObj->tempPath;

				$imgI = $imgObj->getImageDimensions($filepath);
				$iurl = str_replace(PATH_site, '', $filepath);

				/* TODO incorporate maxwidth/maxheight in to the params */
				$cWidth  = t3lib_div::intInRange(t3lib_div::_GP('cWidth' ), 0, $this->magicMaxWidth );
				$cHeight = t3lib_div::intInRange(t3lib_div::_GP('cHeight'), 0, $this->magicMaxHeight);

				if (!$cWidth )	$cWidth  = $this->magicMaxWidth;
				if (!$cHeight)	$cHeight = $this->magicMaxHeight;

				/* TODO: images that are not web-displayable (like TIFF) */
				echo'
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title>TYPO3 Imagebrowser</title>
</head>
<script language="javascript" type="text/javascript" src="../tiny_mce/tiny_mce_popup.js"></script>
<script language="javascript" type="text/javascript">
	var popWin=tinyMCEPopup.getWin("opener");
	function insertImage(file,width,height)	{
		popWin.setTheValue(\'image\',file+"|"+width+"|"+height,document.location.search);
		return;
	}
</script>
<body>
<script language="javascript" type="text/javascript">
	insertImage(\''.$iurl.'\','.$imgI[0].','.$imgI[1].');
	tinyMCEPopup.close();
</script>
</body>
</html>';
			}
			exit;
		}
	}

}
/* -ux_SC_rte_select_image- */ }

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/ext/tinyrte/mod2/rte_select_image.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/ext/tinyrte/mod2/rte_select_image.php']);
}
?>