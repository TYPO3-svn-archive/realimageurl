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

if (($instance = instanciateTemplateClass('t3lib/', 't3lib_softrefproc'))) {
	include_once($instance); }
else {

/* +ux_t3lib_softrefproc+ */
class ux_t3lib_softrefproc extends t3lib_softrefproc {

	var $imapObj;

	function __construct() {
	//	parent::__construct();
		$this->imapObj = t3lib_div::getUserObj('EXT:realimageurl/class.tx_realimageurl_imagemapper.php:&tx_realimageurl_imagemapper', null);
	}

	/**
	 * Finding image tags in the content.
	 * All images that are not from external URLs will be returned with an info text
	 * Will only return files in fileadmin/ and files in uploads/ folders which are prefixed with "RTEmagic[C|P]_" for substitution
	 * Any "clear.gif" images are ignored.
	 *
	 * @param	string		The input content to analyse
	 * @param	array		Parameters set for the softref parser key in TCA/columns
	 * @return	array		Result array on positive matches, see description above. Otherwise false
	 */
	function findRef_images($content, $spParams) {
	// ---- echo 'findRef_images<br />';
	//	print_r($content); echo '<br />';
	//	print_r($spParams); echo '<br />';

			// Start HTML parser and split content by image tag:
		$htmlParser = t3lib_div::makeInstance('t3lib_parsehtml');
		$splitContent = $htmlParser->splitTags('img',$content);
		$elements = array();

			// Traverse splitted parts:
		foreach ($splitContent as $k => $v) {
			if ($k % 2) {

					// Get file reference:
				$attribs = $htmlParser->get_tag_attributes($v);
				$srcRef = t3lib_div::htmlspecialchars_decode($attribs[0]['src']);
				$pI = pathinfo($srcRef);

					// If it looks like a local image, continue. Otherwise ignore it.
				$absPath = t3lib_div::getFileAbsFileName(PATH_site . $srcRef);
				if (!$pI['scheme'] && !$pI['query'] && $absPath && $srcRef !== 'clear.gif') {

						// Initialize the element entry with info text here:
					$tokenID = $this->makeTokenID($k);
					$elements[$k] = array();
					$elements[$k]['matchString'] = $v;

					/* If the image seems to be from fileadmin/ folder or an RTE image,
					 * then proceed to set up substitution token:
					 */
					if (t3lib_div::isFirstPartOfStr($srcRef, $this->fileAdminDir . '/') ||
					   (t3lib_div::isFirstPartOfStr($srcRef, 'uploads/') && ereg('^RTEmagicC_', basename($srcRef)))) {

							// Token and substitute value:
						if (strstr($splitContent[$k], $attribs[0]['src'])) {
							/* Make sure the value we work on is found and will get substituted in the content
							 * (Very important that the src-value is not DeHSC'ed)
							 */
							$splitContent[$k] = str_replace($attribs[0]['src'], '{softref:' . $tokenID . '}', $splitContent[$k]);
							/* Substitute value with token (this is not be an exact method if the value is in
							 * there twice, but we assume it will not)
							 */
							$elements[$k]['subst'] = array(
								'type' => 'file',
								'relFileName' => $srcRef,
								'tokenID' => $tokenID,
								'tokenValue' => $attribs[0]['src'],
							);

							if (!@is_file($absPath)) {
									// Finally, notice if the file does not exist.
								$elements[$k]['error'] = 'File does not exist!';
							}
						}
						else {
							$elements[$k]['error'] = 'Could not substitute image source with token!';
						}
					}
				}
			}
		}

			// Return result:
		if (count($elements)) {

			$resultArray = array(
				'content' => implode('', $splitContent),
				'elements' => $elements
			);

			return $resultArray;
		}
	}

}
/* -ux_t3lib_softrefproc- */ }

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/class.ux_t3lib_softrefproc.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/class.ux_t3lib_softrefproc.php']);
}
?>