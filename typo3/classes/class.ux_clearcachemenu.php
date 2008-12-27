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

if (($instance = instanciateTemplateClass('typo3/classes/', 'ClearCacheMenu', 'clearcachemenu'))) {
	include_once($instance); }
else {

/* +ux_ClearCacheMenu+ */
class ux_ClearCacheMenu extends ClearCacheMenu {

	/**
	 * constructor
	 *
	 * @param	TYPO3backend	TYPO3 backend object reference
	 */
	public function __construct(TYPO3backend &$backendReference = null) {
		parent::__construct($backendReference);

		// Clear cache for either ALL pages
		if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigVal('options.clearCache.imageurls')) {
			$title = $GLOBALS['LANG']->sL('LLL:EXT:realimageurl/locallang.xml:rm.clearCacheMenu_imageurls', true);
			$this->cacheActions[] = array(
				'id'    => 'pages',
				'class' => 'divider',
				'title' => $title,
				'href'  => $this->backPath . 'ajax.php?ajaxID=realimageurl::imageurls',
				'icon'  => '<img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/lightning.png', 'width="16" height="16"') . ' title="' . $title . '" alt="' . $title . '" />'
			);
		}

		// Clear cache for either ALL pages
		if ($GLOBALS['BE_USER']->isAdmin() || $GLOBALS['BE_USER']->getTSConfigVal('options.clearCache.imagesall')) {
			$title = $GLOBALS['LANG']->sL('LLL:EXT:realimageurl/locallang.xml:rm.clearCacheMenu_imagesall', true);
			$this->cacheActions[] = array(
				'id'    => 'pages',
				'title' => $title,
				'href'  => $this->backPath . 'ajax.php?ajaxID=realimageurl::imageall',
				'icon'  => '<img' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/lightning_red.png', 'width="16" height="16"') . ' title="' . $title . '" alt="' . $title . '" />'
			);
		}
	}

}
/* -ux_ClearCacheMenu- */ }

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/classes/class.ux_clearcachemenu.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/classes/class.ux_clearcachemenu.php']);
}
?>