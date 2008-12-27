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

class tx_realimageurl_imagemapper_be extends tx_realimageurl_imagemapper {

	function getRootLine($pid) {
		/* TODO: respect language */
		$sys_page = t3lib_div::makeInstance('t3lib_pageSelect');
		$sys_page->init(true);

		return $sys_page->getRootLine($pid);
	}

	function getRecord(&$record) {
		/* TODO: get the real array of data from somewhere else, because we may not have an uid here (for example NEW) */
		/* TODO: respect language */
		$segs = explode(':', $record);

		if ($_POST['data'][$segs[0]]) {
			reset($_POST['data'][$segs[0]]);
			$record = $segs[0] . ':' . key($_POST['data'][$segs[0]]);
			return current($_POST['data'][$segs[0]]);
		}

		return null;
	//	return t3lib_BEfunc::getRecord($segs[0], $segs[1]);
	}

	function approximateAssignment($assignment, $conf) {
		$prefix = '';

		if ($conf['includeContext']) {
			/* context-inclusion defenition turned on */
			if (is_array($conf['includeContext.'])) {
				switch ($conf['includeContext.']['reference']) {
					case 'realurl':
						$field = 'tx_realurl_pathsegment';
						foreach ($rootLine as $page)
							if (!$page['tx_realurl_exclude'])
								$prefix .= $page[$field];
						break;
					case 'recordline':
						$rec = $this->tsObj->data;
						$prefix .= $rec['header'];
					case 'rootline':
						$rootLine = $GLOBALS['TSFE']->rootLine;
						if (!($field = $conf['includeContext.']['linefield']))
							$field = 'title';
						foreach ($rootLine as $page)
							if ($page['uid'] && !$page['is_siteroot'])
								$prefix .= $page[$field];
						break;
				}
			}

			if ($conf['postfix']) {
				$prefix .= $conf['postfix'];
			}
		}
		else if ($conf['postfix']) {
			$assignment .= $conf['postfix'];
		}

		return md5($prefix . $assignment);
	}

	function makeResponseImage($pid, $record, &$attributes, $assignment, $conf = null) {
		$rootLine = $this->getRootLine($pid);
		$rec = $this->getRecord($record);

		/* detect changes */
		$tstamps = array($rec['tstamp']);
		foreach ($rootLine as $page)
			$tstamps[] = $page['tstamp'];

		/* build up some identification information */
		$sent = array($pid, $record, $attributes, $assignment, $conf);
		/* tstamp is the one indication for an update for an otherwise correct image */
		$refr = array($attributes['src'], $tstamps, array($assignment, $conf, $this->approximateAssignment($assignment, $conf)));

		/* if we don't get a match, really create the information */
		if (!($output = $this->getResponseImage($sent, $refr, $attributes))) {
			$prefix = '';

			/* # *****************
			 * # CType: image
			 * # *****************
			 * tt_content.image.20 {
			 * 	1 {
			 * 		# altText, titleText, caption, longdescURL
			 * 		imageLinkName < .altText
			 * 		imageLinkName {
			 * 			stripSpecial = 1
			 * 			stripSpecial.substitute = -
			 *
			 * 			# realurl, rootline, recordline
			 * 			includeContext = 1
			 * 			includeContext.reference = realurl
			 *
			 * 			# shorthash, hash, integer, date
			 * 			collisionHandling = hash
			 *
			 * 			# lowercase, uppercase, camelcase, idna
			 * 			textTransform = 1
			 * 			textTransform.function = lowercase
			 * 		}
			 * 	}
			 * }
			 */

			/* if it is empty, or it would be substituted (static marker) */
			if (!$assignment || preg_match('/###.*###/', $assignment)) {
				/* assign the original file-name by default if available */
				if ($info['origFile'])
					$assignment = $info['origFile'];
				/* and if everything fails, assign the current file-name */
				else
					$assignment = $info[3];
			}

			/* strip any prefix that would behave like a sub-directory */
			if (@file_exists($assignment)) {
				$assignment = basename($assignment);

				/* strip any postfix that would behave like an extension */
				$assignment = preg_replace('/(\.[a-z][a-z][a-z]?[a-z]?)$/', '', $assignment);
			}

			/* context-inclusion turned on */
			if ($conf['includeContext']) {
				/* context-inclusion defenition turned on */
				if (is_array($conf['includeContext.'])) {
					switch ($conf['includeContext.']['reference']) {
						case 'realurl':
							$field = 'tx_realurl_pathsegment';
							foreach ($rootLine as $page)
								if (!$page['tx_realurl_exclude'])
									$prefix = ($page[$field] ? $page[$field] . '/' : '') . $prefix;
							break;
						case 'recordline':
							if ($rec['header'])
								$prefix = $rec['header'] . '/' . $prefix;
						case 'rootline':
							if (!($field = $conf['includeContext.']['linefield']))
								$field = 'title';
							foreach ($rootLine as $page)
								if ($page['uid'] && !$page['is_siteroot'])
									$prefix = ($page[$field] ? $page[$field] . '/' : '') . $prefix;
							break;
					}
				}

				if ($conf['postfix']) {
					$prefix .= $conf['postfix'] . '/';
				}

				$prefix = ltrim($prefix, '/');
				$prefix = rtrim($prefix, '/');
				$prefix = $prefix . '/';
			}
			else if ($conf['postfix']) {
				$assignment .= ' [' . $conf['postfix'] . ']';
			}

		// ---- echo 'prefix: ' . $prefix . "\n<br />";
		// ---- echo 'assignment: ' . $assignment . "\n<br />";

			$assignment = $prefix . $assignment;

			/* text-transform turned on */
			if ($conf['textTransform']) {
				switch ($conf['textTransform.']['function']) {
					case 'lowercase': $assignment =   strtolower($assignment); break;
					case 'uppercase': $assignment =   strtoupper($assignment); break;
					case 'camelcase': $assignment =      ucwords($assignment); break;
				}
			}

			/* character-coding turned on */
			if ($conf['characterCoding']) {
				switch ($conf['characterCoding']) {
					default      :
					case 'ASCII' : $url =               $assignment ; break;
					case 'latin1': $url =              ($assignment); break;
					case 'utf8'  : $url =   utf8_encode($assignment); break;
					case 'idna'  :
					$idna = t3lib_div::getUserObj('EXT:realimageurl/idna_convert.class.php:&idna_convert', null);
						       $url = $idna->encode($assignment); break;
				}
			} else {
				$url = $assignment;
			}

			/* + looks better than %20 */
			$url = rawurlencode($url);

		// ---- echo 'url: ' . $url . "\n<br />";

			/* special character handling turned on */
			if ($conf['stripSpecial']) {
				$rep = trim($conf['stripSpecial.']['substitute']);
				$sep = trim($conf['stripSpecial.']['separator']);
				$url = explode('%2F', $url);

				/* replace special characters, and collapse runs */
				$url = preg_replace('/%20/', $sep, $url);
				$url = preg_replace('/%[0-9A-Z][0-9A-Z]/', $rep, $url);

				if ($sep && $rep) {
					$url = preg_replace('/(' . $sep . $rep . ')/', $sep, $url);
					$url = preg_replace('/(' . $rep . $sep . ')/', $sep, $url);
				//	$url = preg_replace('/(' . $rep . $sep . $rep . ')/', $rep, $url);
				}

				if ($rep) {
					$url = preg_replace('/(' . $rep . ')+/', $rep, $url);
					$url = preg_replace('/(' . $rep . ')+$/', '', $url);
					$url = preg_replace('/^(' . $rep . ')+/', '', $url);
				}

				if ($sep) {
					$url = preg_replace('/(' . $sep . ')+/', $sep, $url);
					$url = preg_replace('/(' . $sep . ')+$/', '', $url);
					$url = preg_replace('/^(' . $sep . ')+/', '', $url);
				}

				$url = implode('%2F', $url);
				$url = preg_replace('/(%2F)+/', '%2F', $url);
			}

			/* generate the filename from what's going to be requested by the browsers */
			$backurl = ltrim(rawurldecode($url), '/');

			/* set or replace the extension */
			preg_match('/(\.[a-z][a-z][a-z]?[a-z]?)$/', $attributes['src'], $extension);
			$backurl .= $extension[1];

		// ---- echo 'backurl: ' . $backurl . "\n<br />";

			$output = $this->putResponseImage($sent, $refr, $attributes, $backurl);

		// ---- echo 'output: ' . $output . "\n<br />";

			$output = $this->relResponseImage($sent[2]['src'], $output);

		// ---- echo 'output: ' . $output . "\n<br />";
		}
		/* if the temporary/original file has not been deleted (hash was the same, info) */
		else if (!@file_exists($output)) {
			$output = $this->relResponseImage($sent[2]['src'], $output);
		}

		return $output;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/class.tx_realimageurl_imagemapper_be.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/class.tx_realimageurl_imagemapper_be.php']);
}
?>