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

class user_realimageurl_imagemapper_post extends tslib_pibase {

	var $imapObj;

	function __construct() {
	//	parent::__construct();
		$this->imapObj = t3lib_div::getUserObj('EXT:realimageurl/class.tx_realimageurl_imagemapper_fe.php:&tx_realimageurl_imagemapper_fe', null);
		$this->tempPath = str_replace(PATH_site, '', TX_RIU_IMAGEMAPPER_BUILDDIR);
	}

	function killLeadingMap(&$params, &$that) {
		/* TODO: automatic regeneration of RTE-inline pictures from their sources */

		$real = str_replace(PATH_site, '', TX_RIU_IMAGEMAPPER_REALURLDIR);
		$params['pObj']->content = str_replace($real, '', $params['pObj']->content);
	}
}

?>
