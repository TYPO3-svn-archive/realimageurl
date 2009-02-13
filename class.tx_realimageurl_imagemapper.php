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

// no autoloading possible [t3lib_basicfilefunc != t3lib_basicFileFunctions]
require_once(PATH_t3lib . 'class.t3lib_basicfilefunc.php');

define('TX_RIU_IMAGEMAPPER_BASEDIR', PATH_site . 'typo3temp/imap/');
define('TX_RIU_IMAGEMAPPER_PERSISTANCEDIR', TX_RIU_IMAGEMAPPER_BASEDIR . 'perscopied/');// persistant copies (copies from all original images)
define('TX_RIU_IMAGEMAPPER_INLINEDIR', TX_RIU_IMAGEMAPPER_BASEDIR . 'inline/');		// images created/modified inside RTEs
define('TX_RIU_IMAGEMAPPER_FETCHDIR', TX_RIU_IMAGEMAPPER_BASEDIR . 'fetched/');		// images fetched from external sources
define('TX_RIU_IMAGEMAPPER_BUILDDIR', TX_RIU_IMAGEMAPPER_BASEDIR . 'build/');		// images manipulated with IM
define('TX_RIU_IMAGEMAPPER_REALURLDIR', TX_RIU_IMAGEMAPPER_BASEDIR . 'map/');		// realimageurl mapped destinations

class tx_realimageurl_imagemapper {

	var $RTEsubstitute = array(
		'RTEmagicC_'	=>	'',
		'RTEmagicP_'	=>	''
	);

	var $fileFunc = null;

	function __construct() {
		$this->fileFunc = t3lib_div::makeInstance('t3lib_basicFileFunctions');

		t3lib_div::mkdir(TX_RIU_IMAGEMAPPER_BASEDIR);
		t3lib_div::mkdir(TX_RIU_IMAGEMAPPER_PERSISTANCEDIR);
		t3lib_div::mkdir(TX_RIU_IMAGEMAPPER_BUILDDIR);
		t3lib_div::mkdir(TX_RIU_IMAGEMAPPER_INLINEDIR);
		t3lib_div::mkdir(TX_RIU_IMAGEMAPPER_REALURLDIR);
	}

	/* -------------------------------------------------------------------------------------- */
	// FS: orig-file -> persistance-link (stays if original is deleted, follows if original is changed or renamed)
	// FS: persistance-link -> mapped-link
	// DB: relation persitance-link <-> build-file
	// FS: build-link -> mapped-link

	/**
	 * Clears the entire cached data/files
	 */
	function clearAll() {
		$this->clearURLs();
		$this->clearFiles();
	}

	/**
	 * Clears the cached data/files for responses
	 */
	function clearURLs() {
		$this->clearResponses();
		$this->clearMappings();
	}

	/**
	 * Clears the cached data/files for requests
	 */
	function clearFiles() {
		$this->clearRequests();
		$this->clearBuilds();
	}

	/* -------------------------------------------------------------------------------------- */

	/**
	 * Checks if a given string is an absolute URL
	 *
	 * @param	string		The url to check
	 * @return	bool		yes or no
	 */
	function is_url($url) {
		return preg_match('/^(ftp|http|https):/', $url);
	}

	/**
	 * Fetches the contents of an url-location via CURL
	 *
	 * @param	string		The url to download
	 * @return	mixed		The downloaded content
	 */
	function url_get_contents($url) {
		return t3lib_div::getURL($url);
	}

	/**
	 * Copies an url-location to a local location. It tries
	 * first if not maybe the php-wrappers with that functionality
	 * are functional.
	 *
	 * @param	string		The url to download
	 * @return	bool		yes or no
	 */
	function copyurl($url, $destination) {
		/* try the low-memory version first */
		if (@copy($url, $destination) &&
		    @file_exists($destination)) {
			return true;
		}

		/* then go and allocate too much memory */
		if (($contents = $this->url_get_contents($url))) {
			return @file_put_contents($destination, $contents) ? true : false;
		}

		return false;
	}

	/* -------------------------------------------------------------------------------------- */

	/**
	 * Checks if a given string is a path for RTE-images
	 *
	 * @param	string		The path to check
	 * @return	bool		yes or no
	 */
	function is_inline($check) {
		return (strpos($check, TX_RIU_IMAGEMAPPER_INLINEDIR) === 0);
	}

	/**
	 * Checks if a given string is a path for processed images
	 *
	 * @param	string		The path to check
	 * @return	bool		yes or no
	 */
	function is_build($check) {
		return (strpos($check, TX_RIU_IMAGEMAPPER_BUILDDIR) === 0);
	}

	/**
	 * Checks if a given string is a path for image-urls
	 *
	 * @param	string		The path to check
	 * @return	bool		yes or no
	 */
	function is_mapped($check) {
		return (strpos($check, TX_RIU_IMAGEMAPPER_REALURLDIR) === 0);
	}

	/* -------------------------------------------------------------------------------------- */

	/**
	 * Clears the cached files for requests
	 */
	function clearBuilds() {
		/* TODO: delete the content of the RTE-inline directory too */
		t3lib_div::rmdir(TX_RIU_IMAGEMAPPER_BUILDDIR, true);
		t3lib_div::mkdir(TX_RIU_IMAGEMAPPER_BUILDDIR);
	}

	/**
	 * Clears the cached files for responses
	 */
	function clearMappings() {
		t3lib_div::rmdir(TX_RIU_IMAGEMAPPER_REALURLDIR, true);
		t3lib_div::mkdir(TX_RIU_IMAGEMAPPER_REALURLDIR);
	}

	/**
	 * Assignes a reserved buildable location, also applying collision-detection.
	 * The space is not going to be occupied, but the collision detection should
	 * result deterministic outputs.
	 *
	 * @param	string		The original file-name
	 * @param	string		The directory for buildable copies
	 * @return	string		The name of the buildable location or fallback to the original name
	 */
	function assignBuild($org, $builddir = TX_RIU_IMAGEMAPPER_BUILDDIR) {
		/* this must be a deterministic naming-scheme */
	//	if (($mod = tempnam(TX_RIU_IMAGEMAPPER_BUILDDIR, '')) !== false) {
	//	if (($mod = $this->fileFunc->getUniqueName(basename($org), $builddir))) {
		if (($mod = $builddir . basename($org))) {
			return $mod;
		}

		return $org;
	}

	/**
	 * Assignes a reserved fetchable location, also applying collision-detection.
	 * The space is not going to be occupied, but the collision detection should
	 * result deterministic outputs.
	 *
	 * @param	string		The original file-name
	 * @param	string		The directory for fetchable copies
	 * @return	string		The name of the fetchable location or fallback to the original name
	 */
	function assignFetch($org, $fetchdir = TX_RIU_IMAGEMAPPER_FETCHDIR) {
		/* this must be a deterministic naming-scheme (it also needs to preserve the RTEMagic) */
	//	if (($mod = tempnam(TX_RIU_IMAGEMAPPER_FETCHDIR, '')) !== false) {
	//	if (($mod = $this->fileFunc->getUniqueName(basename($org), $inlinedir))) {
		if (($mod = $fetchdir . basename($org))) {
			return $mod;
		}

		return $org;
	}

	/**
	 * Assignes a reserved magic location, also applying collision-detection.
	 * The space is not going to be occupied, but the collision detection should
	 * result deterministic outputs.
	 *
	 * @param	string		The original file-name
	 * @param	string		The directory for magic copies
	 * @return	string		The name of the magic location or fallback to the original name
	 */
	function assignInline($org, $inlinedir = TX_RIU_IMAGEMAPPER_INLINEDIR) {
		/* this must be a deterministic naming-scheme (it also needs to preserve the RTEMagic) */
	//	if (($mod = tempnam(TX_RIU_IMAGEMAPPER_INLINEDIR, '')) !== false) {
	//	if (($mod = $this->fileFunc->getUniqueName(basename($org), $inlinedir))) {
		if (($mod = $inlinedir . basename($org))) {
			return $mod;
		}

		return $org;
	}

	/**
	 * Assignes a reserved mappable location, also applying collision-detection.
	 * The space is not going to be occupied, but the collision detection should
	 * result deterministic outputs.
	 *
	 * @param	string		The original file-name
	 * @param	string		The directory for mapped copies
	 * @return	string		The name of the mappable location or fallback to the original name
	 */
	function assignMapping($org, $mapdir = TX_RIU_IMAGEMAPPER_REALURLDIR) {
		/* don't rely on WIN, it returns back-slashes */
		$dir = t3lib_div::dirname($org);

		/* this must be a deterministic naming-scheme (it also needs to preserve the RTEMagic) */
	//	if (($mod = tempnam(TX_RIU_IMAGEMAPPER_REALURLDIR, '')) !== false) {
	//	if (($mod = $this->fileFunc->getUniqueName(basename($org), $mapdir . $dir))) {
		if (($mod = $mapdir . $dir . '/' . basename($org))) {
			return str_replace('/./', '/', str_replace('//', '/', $mod));
		}

		return $org;
	}

	/* -------------------------------------------------------------------------------------- */

	/**
	 * Makes a true copy of a file into the location defined to be
	 * for permanent copies.
	 *
	 * @param	string		The file to copy
	 * @param	string		The directory for permanent copies
	 * @param	string		The name of a file, that can be used as template for unique names
	 * @return	string		The name of the generated copy or null
	 */
	function makePermanentCopy($in, $permdir, $idol = null) {
		if (($out = $this->fileFunc->getUniqueName($this->fileFunc->cleanFileName($idol ? $idol : $in), $permdir))) {
			@unlink($out);

			/* uploaded files don't "exist" yet */
			if (@is_uploaded_file($in)) {
				@move_uploaded_file($in, $out);
			}
			/* but they may be "files" */
			else if (@is_file($in)) {
				@copy($in, $out);
			}
			/* or if the wrapper is absent */
			else if ($this->is_url($in)) {
				$this->copyurl($in, $out);
			}

			t3lib_div::fixPermissions($out);

			/* we finally hit success or failure */
			if (@file_exists($out)) {
				clearstatcache();
				return $out;
			}
		}

		return null;
	}

	/**
	 * Makes a virtual copy (link) of a file into the location defined to be
	 * for persistant copies. If the file is volatile anyway, it falls back
	 * to do a real copy.
	 *
	 * @param	string		The file to copy
	 * @param	string		The directory for persistant copies
	 * @return	string		The name of the generated copy or null
	 */
	function makePersistantCopy($in, $persdir = TX_RIU_IMAGEMAPPER_PERSISTANCEDIR) {
	//	if (($out = tempnam(TX_RIU_IMAGEMAPPER_PERSISTANCEDIR, '')) !== false) {
		if (($out = $this->fileFunc->getUniqueName($in, $persdir))) {
			@unlink($out);

			/* uploaded files don't "exist" yet */
			if (@is_uploaded_file($in)) {
				@move_uploaded_file($in, $out);
			}
			/* remote files don't "exist" */
			else if (@file_exists($in)) {
           			// Hardlinks link to files in windows XP
				if (TYPO3_OS == 'WIN')
            				@exec('fsutil hardlink create "' . $out . '" "' . $in . '"');
            			else
            				@link($in, $out);
			}
			/* but they may be "files" */
			else if (@is_file($in)) {
				@copy($in, $out);
			}
			/* or if the wrapper is absent */
			else if ($this->is_url($in)) {
				$this->copyurl($in, $out);
			}

			t3lib_div::fixPermissions($out);

			/* we finally hit success or failure */
			if (@file_exists($out)) {
				clearstatcache();
				return $out;
			}
		}

		return null;
	}

	/**
	 * Makes a virtual copy (link) of a file into the location defined to be
	 * for mapped copies. If the file is volatile anyway, it falls back
	 * to do a real copy.
	 * Mapped copies are those that are exposed to the front-end.
	 *
	 * @param	string		The file to copy
	 * @param	string		The directory for mapped copies
	 * @return	string		The name of the generated copy or null
	 */
	function makePersistantMapping($in, $out, $realdir = TX_RIU_IMAGEMAPPER_REALURLDIR) {
		/* don't rely on WIN, it returns back-slashes */
		$dir = t3lib_div::dirname($out);

		if ($out && (!$dir || @is_dir($dir) || @mkdir($dir, octdec($GLOBALS['TYPO3_CONF_VARS']['BE']['folderCreateMask']), true))) {
			@unlink($out);

			/* uploaded files don't "exist" yet */
			if (@is_uploaded_file($in)) {
				@move_uploaded_file($in, $out);
			}
			/* remote files don't "exist" */
			else if (@file_exists($in)) {
           			// Hardlinks link to files in windows XP
				if (TYPO3_OS == 'WIN')
            				@exec('fsutil hardlink create "' . $out . '" "' . $in . '"');
            			else
            				@link($in, $out);
			}
			/* but they may be "files" */
			else if (@is_file($in)) {
				@copy($in, $out);
			}
			/* or if the wrapper is absent */
			else if ($this->is_url($in)) {
				$this->copyurl($in, $out);
			}

			t3lib_div::fixPermissions($out);

			/* we finally hit success or failure */
			if (@file_exists($out)) {
				clearstatcache();
				return $out;
			}
		}

		return $in;
	}

	/* -------------------------------------------------------------------------------------- */
	function handleCollision($origin, $subject, $hash) {
		if (!@file_exists($subject)) {
			return $subject;
		}

		preg_match('/(\.[a-z][a-z][a-z]?[a-z]?)$/', $subject, $extension);
		$subject = preg_replace('/(\.[a-z][a-z][a-z]?[a-z]?)$/', '', $subject);

		switch ($GLOBALS['TSFE']->tmpl->setup['plugin.']['realimageurl.']['collisionHandling']) {
			case 'be-creative':
			case 'date':
				if (($col = @filemtime($origin))) {
					$col = date("Ymd-His", $col);
					if (!@file_exists($subject . '-' . $col . $extension[1])) {
						break;
					}
				}
			case 'shorthash':
				$col = substr($hash, 0, 4);
				if (!@file_exists($subject . '-' . $col . $extension[1])) {
					break;
				}
			case 'hash':
				$col = $hash;
				if (!@file_exists($subject . '-' . $col . $extension[1])) {
					break;
				}
			case 'integer':
			default:
				$col = 1;
				while (@file_exists($subject . '-' . $col . $extension[1])) {
					$col++;
				}
				break;
		}

		return $subject . '-' . $col . $extension[1];
	}

	/* -------------------------------------------------------------------------------------- */

	/**
	 * Clears the cached data for requests
	 */
	function clearRequests() {
		/* TODO: exclude deleting the RTE-inline image-rows (they can be automatically regenerated/healed) */
		$reslt = $GLOBALS['TYPO3_DB']->sql(
			TYPO3_db,
			'TRUNCATE TABLE tx_realimageurl_requests;');
	}

	/**
	 * Parses through all requests build up the chain of modifications until
	 * it reaches the original picture.
	 * Because it's not really possible to track a batch-operation which
	 * contains several operations on a picture it's not really possible
	 * to use the found chain to virtually re-establish the exact operation
	 * done.
	 *
	 * @param	string		The resultant picture
	 * @return	array		All modified pictures until the original one
	 */
	function findRequestChain($location) {
		$chain = array();

		while ($location) {
			/* TODO: make exec local */

			$query = $GLOBALS['TYPO3_DB']->SELECTquery(
				'parameter_sent',
				'tx_realimageurl_requests',
				'location = "' . str_replace(PATH_site, '', $location) . '"',
				'',
				'');
		//	echo '<strong>' . $query . '</strong><br />' . "\n";
			$reslt = $GLOBALS['TYPO3_DB']->sql(
				TYPO3_db,
				$query);

			/* a match will fully replace the incoming request by the cached one */
			if ($reslt && ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($reslt))) {
				$sent = unserialize($row['parameter_sent']);
				$output = isarray($sent[0]) ? $sent[0][0] : $sent[0];

				$chain[] = $location = $output;
			}
		}

		return $chain;
	}

	/**
	 * Finds the previous source of operation for a given picture.
	 *
	 * @param	string		The resultant picture
	 * @return	string		The source picture
	 */
	function findRequestSource($location) {
		if ($location) {
			/* TODO: make exec local */

			$query = $GLOBALS['TYPO3_DB']->SELECTquery(
				'parameter_sent',
				'tx_realimageurl_requests',
				'location = "' . str_replace(PATH_site, '', $location) . '"',
				'',
				'');
		//	echo '<strong>' . $query . '</strong><br />' . "\n";
			$reslt = $GLOBALS['TYPO3_DB']->sql(
				TYPO3_db,
				$query);

			/* a match will fully replace the incoming request by the cached one */
			if ($reslt && ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($reslt))) {
				$sent = unserialize($row['parameter_sent']);
				$output = /*PATH_site .*/ $sent[0];

				return $output;
			}

			return null;
		}

		return $location;
	}

	function getRequestImage($sent, $exec, &$info) {
		if ($exec) {
			/* TODO: make exec local */

			/* the hash does not contain the output-file, which is irrelevant */
			$hash = md5(serialize(array($exec[0], $exec[2])));
		// ---- echo '<strong><em>get request</em> ' . $hash . '</strong><br />' . "\n";

			$query = $GLOBALS['TYPO3_DB']->SELECTquery(
				'parameter_info,location',
				'tx_realimageurl_requests',
				'parameter_hash = "' . $hash . '"',
				'',
				'');
		// ---- echo '<strong>' . $query . '</strong><br />' . "\n";
			$reslt = $GLOBALS['TYPO3_DB']->sql(
				TYPO3_db,
				$query);

			/* a match will fully replace the incoming request by the cached one */
			if ($reslt && ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($reslt))) {
				$info = unserialize($row['parameter_info']);
				$output = PATH_site . $row['location'];

			// ---- echo '<em>' . $output . '</em><br />' . "\n";
				return $output;
			}

			return null;
		}

		return PATH_site . $info[3];
	}

	function putRequestImage($sent, $exec, &$info) {
		$input = str_replace(PATH_site, '', isset($info['src']) ? $info['src'] : $info[3]);

		if ($input) {
			/* TODO: make exec local */

			/* the hash does not contain the output-file, which is irrelevant */
			$hash = md5(serialize(array($exec[0], $exec[2])));
		// ---- echo '<strong><em>put request</em> ' . $hash . '</strong><br />' . "\n";

			/* move to the build-directory and build relative path */
			if (@rename(PATH_site . $input, $output = $this->assignBuild($input)))
				$output = str_replace(PATH_site, '', $output);
			else
				$output = $input;

		//	$output = $this->handleCollision($input, $output, $hash);

			if ($info['src']) $info['src'] = $output;
			else if ($info[3]) $info[3] = $output;

			$insertArray = array();
			$insertArray['tstamp'] =
			$insertArray['crdate'] = time();
			$insertArray['parameter_hash'] = $hash;
			$insertArray['parameter_sent'] = serialize($sent);
			$insertArray['parameter_exec'] = serialize($exec);
			$insertArray['parameter_info'] = serialize($info);
			$insertArray['location'] = $output;

	//		$query = $GLOBALS['TYPO3_DB']->DELETEquery(
	//			'tx_realimageurl_requests',
	//			'parameter_hash = \'' . $insertArray['parameter_hash'] . '\' AND ' .
	//			'location = \'' . $insertArray['location'] . '\'');
	//	//	echo '<strong>' . $query . '</strong><br />' . "\n";
	//		$reslt = $GLOBALS['TYPO3_DB']->sql(
	//			TYPO3_db,
	//			$query);

			$query = $GLOBALS['TYPO3_DB']->INSERTquery(
				'tx_realimageurl_requests',
				$insertArray, array('tstamp', 'crdate'));
		//	echo '<strong>' . $query . '</strong><br />' . "\n";
			$reslt = $GLOBALS['TYPO3_DB']->sql(
				TYPO3_db,
				$query);

			return $output;
		}
	}

	/* -------------------------------------------------------------------------------------- */

	/**
	 * Clears the cached data for responses
	 */
	function clearResponses() {
		$reslt = $GLOBALS['TYPO3_DB']->sql(
			TYPO3_db,
			'TRUNCATE TABLE tx_realimageurl_responses;');
	}

	/**
	 * Finds the original source of mapping for a given picture.
	 *
	 * @param	string		The resultant picture
	 * @return	string		The source picture
	 */
	function findResponseSource($location) {
		if ($location) {
			/* TODO: make exec local */

			$query = $GLOBALS['TYPO3_DB']->SELECTquery(
				'parameter_refr',
				'tx_realimageurl_responses',
				'location = "' . str_replace(PATH_site, '', $location) . '"',
				'',
				'');
		//	echo '<strong>' . $query . '</strong><br />' . "\n";
			$reslt = $GLOBALS['TYPO3_DB']->sql(
				TYPO3_db,
				$query);

			/* a match will fully replace the incoming request by the cached one */
			if ($reslt && ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($reslt))) {
				$refr = unserialize($row['parameter_refr']);
				$output = /*PATH_site .*/ $refr[0];

				return $output;
			}

			return null;
		}

		return $location;
	}

	function getResponseImage($sent, $refr, &$info) {
		if ($refr) {
			/* the hash does not contain the output-file, which is irrelevant */
			$hash = md5(serialize($refr));
		// ---- echo '<strong><em>get response</em> ' . $hash . '</strong><br />' . "\n";

			$query = $GLOBALS['TYPO3_DB']->SELECTquery(
				'parameter_info,location',
				'tx_realimageurl_responses',
				'parameter_hash = "' . $hash . '"',
				'',
				'');
		//	echo '<strong>' . $query . '</strong><br />' . "\n";
			$reslt = $GLOBALS['TYPO3_DB']->sql(
				TYPO3_db,
				$query);


			/* a match will fully replace the incoming request by the cached one */
			if ($reslt && ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($reslt))) {
				$info = unserialize($row['parameter_info']);
				$output = $row['location'];

			// ---- echo '<em>' . $output . '</em><br />' . "\n";
				return $output;
			}

			return null;
		}

		return $info['src'] ? $info['src'] : $info[3];
	}

	function putResponseImage($sent, $refr, &$info, $output) {
		$input = str_replace(PATH_site, '', $info['src'] ? $info['src'] : $info[3]);

		if ($input) {
			/* the hash does not contain the output-file, which is irrelevant */
			$hash = md5(serialize($refr));
		// ---- echo '<strong><em>put response</em> ' . $hash . '</strong><br />' . "\n";

			/* move to the build-directory and build relative path */
			if (($output = $this->assignMapping($output)))
				$output = str_replace(PATH_site, '', $output);
			else if (($output = $this->assignMapping($input)))
				$output = str_replace(PATH_site, '', $output);
			else
				$output = $input;

			$output = $this->handleCollision($input, $output, $hash);

			if ($info['src']) $info['src'] = $output;
			else if ($info[3]) $info[3] = $output;

			$insertArray = array();
			$insertArray['tstamp'] =
			$insertArray['crdate'] = time();
			$insertArray['pid'] = $sent[0];
			$insertArray['record'] = $sent[1];
			$insertArray['fid'] = '(SELECT uid FROM tx_realimageurl_requests WHERE location LIKE \'%' . $input . '\')';
		//	$insertArray['fid'] = 'r.id';
			$insertArray['parameter_hash'] = $hash;
			$insertArray['parameter_sent'] = serialize($sent);
			$insertArray['parameter_refr'] = serialize($refr);
			$insertArray['parameter_info'] = serialize($info);
			$insertArray['location'] = $output;
			$insertArray['descr'] = $sent[3];

	//		$query = $GLOBALS['TYPO3_DB']->DELETEquery(
	//			'tx_realimageurl_responses',
	//			'parameter_info = \'' . $insertArray['parameter_info'] . '\' AND ' .
	//			'location = \'' . $insertArray['location'] . '\'');
	//	//	echo '<strong>' . $query . '</strong><br />' . "\n";
	//		$reslt = $GLOBALS['TYPO3_DB']->sql(
	//			TYPO3_db,
	//			$query);

			$query = $GLOBALS['TYPO3_DB']->INSERTquery(
				'tx_realimageurl_responses',
				$insertArray, array('tstamp', 'crdate', 'pid', 'fid'));
		// ---- echo '<strong>' . $query . '</strong><br />' . "\n";
			$reslt = $GLOBALS['TYPO3_DB']->sql(
				TYPO3_db,
				$query);

			return $output;
		}
	}

	function relResponseImage($input, $output) {

	// ---- echo '<strong>' . $input . ':</strong> <em>' . $output . '</em><br />' . "\n";

		if ($input != $output) {
			$output = $this->makePersistantMapping(PATH_site . $input, PATH_site . $output);
			$output = str_replace(PATH_site, '', $output);
		}

		return $output;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/class.tx_realimageurl_imagemapper.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/class.tx_realimageurl_imagemapper.php']);
}
?>