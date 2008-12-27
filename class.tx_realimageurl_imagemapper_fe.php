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

class tx_realimageurl_imagemapper_fe extends tx_realimageurl_imagemapper {

	var $tsObj;

	function setTSObject($tsObj) {
		$this->tsObj = $tsObj;
	}

	function setTSObj($tsObj) {
		$this->tsObj = $tsObj;
	}

	function approximateAssignment($assignment, $conf) {
		$prefix = '';

		if ($conf['includeContext']) {
			/* context-inclusion defenition turned on */
			if (is_array($conf['includeContext.'])) {
				switch ($conf['includeContext.']['reference']) {
					case 'realurl':
						$prefix = $_SERVER['REQUEST_URI'];
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
					case 'custom':
						$prefix = $conf['customContext'] . serialize($conf['customContext.']);
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

	function makeResponseImage($pid, $record, &$info, $assignment, $conf = null) {
		$rootLine = $GLOBALS['TSFE']->rootLine;
		$rec = $this->tsObj->data;
//echo '<pre>';
//$catDBTrace = debug_backtrace();
//$catBTrace  = '<dd><ol>';
//for ($n = 0; $n < count($catDBTrace); $n++)
//if (($catDBTrace[$n + 1]['function'] != 'require_once') &&
// ($catDBTrace[$n + 1]['function'] != 'include'))
//$catBTrace .= '<li><strong>' . $catDBTrace[$n + 1]['function'] . '</strong> (<em>' . str_replace($catPath, '', $catDBTrace[$n]['file']) . '</em> [line ' . $catDBTrace[$n]['line'] . '])</li>';
//$catBTrace .= '</ol></dd>';
//
//$catSQLLog .=
// '<dl>' .
//  '<dt>backtrace:</dt>' . $catBTrace .
//  '<dt>query:</dt>' . $catQuery .
//  '<dt>result:</dt>' . $catResult .
//  '<dt>error:</dt>' . $catError .
// '</dl>' .
//$catTerm;
//echo $catSQLLog;
//echo '</pre>';

		/* detect changes */
		$tstamps = array($rec['tstamp']);
		foreach ($rootLine as $page)
			$tstamps[] = $page['tstamp'];

		/* build up some identification information */
		$sent = array($pid, $record, $info, $assignment, $conf);
		/* tstamp is the one indication for an update for an otherwise correct image */
		$refr = array($info[3], $tstamps, array($assignment, $conf, $this->approximateAssignment($assignment, $conf)));

		/* if we don't get a match, really create the information */
		if (!($output = $this->getResponseImage($sent, $refr, $info))) {
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

		// ---- echo 'assignment in: "' . $assignment . '"' . "\n<br />";

			/* if it is empty, or it would be substituted (static marker) */
			if (!trim($assignment) || preg_match('/###.*###/', $assignment)) {
				/* assign the original file-name by default if available */
				if ($info['origFile'])
					$assignment = $info['origFile'];
				/* and if everything fails, assign the current file-name */
				else
					$assignment = $info[3];
			}

		// ---- echo 'assignment checked: "' . $assignment . '"' . "\n<br />";

			/* strip any prefix that would behave like a sub-directory */
			if (@file_exists($assignment)) {
				$assignment = basename($assignment);

				/* strip any postfix that would behave like an extension */
				$assignment = preg_replace('/(\.[a-z][a-z][a-z]?[a-z]?)$/', '', $assignment);
			}

		// ---- echo 'assignment stripped: "' . $assignment . '"' . "\n<br />";

			/* context-inclusion turned on */
			if ($conf['includeContext']) {
				/* context-inclusion defenition turned on */
				if (is_array($conf['includeContext.'])) {
					switch ($conf['includeContext.']['reference']) {
						case 'realurl':
							$prefix = $_SERVER['REQUEST_URI'];
							$prefix = explode('/', $prefix);
							$final = explode('?', array_pop($prefix));
							$final = explode('.', $final[0]);
							array_push($prefix, $final[0]);
							array_push($prefix, '');
							$prefix = implode('/', $prefix);
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
						case 'custom':
							$prefix = $this->tsObj->stdWrap($conf['customContext'], $conf['customContext.']);
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

		// ---- echo 'prefix: "' . $prefix . '"' . "\n<br />";
		// ---- echo 'assignment: "' . $assignment . '"' . "\n<br />";

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
					// Network.enableIDN		true
					// network.IDN_show_punycode	false
					// network.IDN.whitelist.com	true
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
				$url =  str_replace('.', '', $url);
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
			preg_match('/(\.[a-z][a-z][a-z]?[a-z]?)$/', $info[3], $extension);
			$backurl .= $extension[1];

		// ---- echo 'backurl: ' . $backurl . "\n<br />";

			$output = $this->putResponseImage($sent, $refr, $info, $backurl);

		// ---- echo 'output: ' . $output . "\n<br />";

			$output = $this->relResponseImage($sent[2][3], $output);

		// ---- echo 'output: ' . $output . "\n<br />";
		}
		/* if the temporary/original file has not been deleted (hash was the same, info) */
		else if (!@file_exists($output)) {
			$output = $this->relResponseImage($sent[2][3], $output);
		}

		return $output;
	}

	function makeResponseImageClone(&$info, $assignment, $conf = null) {
		/* detect changes */
		$tstamps = @filemtime($assignment);

		/* build up some identification information */
		$sent = array(null, null, $info, $assignment, $conf);
		$refr = array($info[3], $tstamps, array($assignment, $conf));

		/* if we don't get a match, really create the information */
		if (!($output = $this->getResponseImage($sent, $refr, $info))) {

		// ---- echo 'assignment: ' . $assignment . "\n<br />";

			/* set or replace the extension */
			preg_match('/(\.[a-z][a-z][a-z]?[a-z]?)$/', $info[3], $extension);
			$backurl = preg_replace('/(\.[a-z][a-z][a-z]?[a-z]?)$/', '', $assignment) . $conf['postfix'] . $extension[1];

		// ---- echo 'backurl: ' . $backurl . "\n<br />";

			$output = $this->putResponseImage($sent, $refr, $info, $backurl);

		// ---- echo 'output: ' . $output . "\n<br />";

			$output = $this->relResponseImage($sent[2][3], $output);

		// ---- echo 'output: ' . $output . "\n<br />";
		}
		/* if the temporary/original file has not been deleted (hash was the same, info) */
		else if (!@file_exists($output)) {
			$output = $this->relResponseImage($sent[2][3], $output);
		}

		return $output;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/class.tx_realimageurl_imagemapper_fe.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/class.tx_realimageurl_imagemapper_fe.php']);
}
?>