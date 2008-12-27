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

if (($instance = instanciateTemplateClass('t3lib/', 't3lib_tcemain'))) {
	include_once($instance); }
else {

/* +ux_t3lib_tcemain+ */
class ux_t3lib_tcemain extends t3lib_tcemain {

	var $imapObj;

	function __construct() {
	//	parent::__construct();
		$this->imapObj = t3lib_div::getUserObj('EXT:realimageurl/class.tx_realimageurl_imagemapper_be.php:&tx_realimageurl_imagemapper_be', null);
	}

	/**
	 * Handling files for group/select function
	 *
	 * @param	array		Array of incoming file references. Keys are numeric, values are files (basically, this is the exploded list of incoming files)
	 * @param	array		Configuration array from TCA of the field
	 * @param	string		Current value of the field
	 * @param	array		Array of uploaded files, if any
	 * @param	string		Status ("update" or ?)
	 * @param	string		tablename of record
	 * @param	integer		UID of record
	 * @param	string		Field identifier ([table:uid:field:....more for flexforms?]
	 * @return	array		Modified value array
	 * @see checkValue_group_select()
	 */
	function checkValue_group_select_file($valueArray, $tcaFieldConf, $curValue, $uploadedFileArray, $status, $table, $id, $recFID) {

	// ---- echo 'checkValue_group_select_file<br />';
	// ---- print_r($valueArray); echo '<br />';
	// ---- print_r($uploadedFileArray); echo '<br />';

		if (!$this->bypassFileHandling)	{	// If filehandling should NOT be bypassed, do processing:

				// If any files are uploaded, add them to value array
			if (is_array($uploadedFileArray) &&
				$uploadedFileArray['name'] &&
				strcmp($uploadedFileArray['tmp_name'],'none'))	{
					$valueArray[]=$uploadedFileArray['tmp_name'];
					$this->alternativeFileName[$uploadedFileArray['tmp_name']] = $uploadedFileArray['name'];
			}

				// Creating fileFunc object.
			if (!$this->fileFunc)	{
				$this->fileFunc = t3lib_div::makeInstance('t3lib_basicFileFunctions');
				$this->include_filefunctions=1;
			}
				// Setting permitted extensions.
			$all_files = Array();
			$all_files['webspace']['allow'] = $tcaFieldConf['allowed'];
			$all_files['webspace']['deny'] = $tcaFieldConf['disallowed'] ? $tcaFieldConf['disallowed'] : '*';
			$all_files['ftpspace'] = $all_files['webspace'];
			$this->fileFunc->init('', $all_files);
		}

			// If there is an upload folder defined:
		if ($tcaFieldConf['uploadfolder'])	{
			if (!$this->bypassFileHandling)	{	// If filehandling should NOT be bypassed, do processing:
					// For logging..
				$propArr = $this->getRecordProperties($table,$id);

					// Get destrination path:
			//	$persdir = TX_RIU_IMAGEMAPPER_PERSISTANCEDIR;
				$persdir = $this->destPathFromUploadFolder($tcaFieldConf['uploadfolder']);
				$permdir = $this->destPathFromUploadFolder($tcaFieldConf['uploadfolder']);

					// If we are updating:
				if ($status=='update')	{

						// Traverse the input values and convert to absolute filenames in case the update happens to an autoVersionized record.
						// Background: This is a horrible workaround! The problem is that when a record is auto-versionized the files of the record get copied and therefore get new names which is overridden with the names from the original record in the incoming data meaning both lost files and double-references!
						// The only solution I could come up with (except removing support for managing files when autoversioning) was to convert all relative files to absolute names so they are copied again (and existing files deleted). This should keep references intact but means that some files are copied, then deleted after being copied _again_.
						// Actually, the same problem applies to database references in case auto-versioning would include sub-records since in such a case references are remapped - and they would be overridden due to the same principle then.
						// Illustration of the problem comes here:
						// We have a record 123 with a file logo.gif. We open and edit the files header in a workspace. So a new version is automatically made.
						// The versions uid is 456 and the file is copied to "logo_01.gif". But the form data that we sent was based on uid 123 and hence contains the filename "logo.gif" from the original.
						// The file management code below will do two things: First it will blindly accept "logo.gif" as a file attached to the record (thus creating a double reference) and secondly it will find that "logo_01.gif" was not in the incoming filelist and therefore should be deleted.
						// If we prefix the incoming file "logo.gif" with its absolute path it will be seen as a new file added. Thus it will be copied to "logo_02.gif". "logo_01.gif" will still be deleted but since the files are the same the difference is zero - only more processing and file copying for no reason. But it will work.
					if ($this->autoVersioningUpdate===TRUE)	{
						foreach($valueArray as $key => $theFile)	{
							if ($theFile===basename($theFile))	{	// If it is an already attached file...
								$valueArray[$key] = PATH_site.$tcaFieldConf['uploadfolder'].'/'.$theFile;
							}
						}
					}

						// Finding the CURRENT files listed, either from MM or from the current record.
					$theFileValues=array();
					if ($tcaFieldConf['MM'])	{	// If MM relations for the files also!
						$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
						/* @var $dbAnalysis t3lib_loadDBGroup */
						$dbAnalysis->start('','files',$tcaFieldConf['MM'],$id);
						reset($dbAnalysis->itemArray);
						while (list($somekey,$someval)=each($dbAnalysis->itemArray))	{
							if ($someval['id'])	{
								$theFileValues[]=$someval['id'];
							}
						}
					} else {
						$theFileValues=t3lib_div::trimExplode(',',$curValue,1);
					}

						// DELETE files: If existing files were found, traverse those and register files for deletion which has been removed:
					if (count($theFileValues))	{
							// Traverse the input values and for all input values which match an EXISTING value, remove the existing from $theFileValues array (this will result in an array of all the existing files which should be deleted!)
						foreach($valueArray as $key => $theFile)	{
							if ($theFile && !strstr(t3lib_div::fixWindowsFilePath($theFile),'/'))	{
								$theFileValues = t3lib_div::removeArrayEntryByValue($theFileValues,$theFile);
							}
						}

							// This array contains the filenames in the uploadfolder that should be deleted:
						foreach($theFileValues as $key => $theFile)	{
							$theFile = trim($theFile);
							if (@is_file($persdir.'/'.$theFile))	{
								$this->removeFilesStore[]=$persdir.'/'.$theFile;
							} elseif ($theFile) {
								$this->log($table,$id,5,0,1,"Could not delete file '%s' (does not exist). (%s)",10,array($persdir.'/'.$theFile, $recFID),$propArr['event_pid']);
							}
						}
					}
				}

					// Traverse the submitted values:
				foreach($valueArray as $key => $theFile)	{
						// NEW FILES? If the value contains '/' it indicates, that the file is new and should be added to the uploadsdir (whether its absolute or relative does not matter here)
					if (strstr(t3lib_div::fixWindowsFilePath($theFile),'/'))	{
							// Init:
						$maxSize = intval($tcaFieldConf['max_size']);
						$cmd='';
						$theDestFile='';		// Must be cleared. Else a faulty fileref may be inserted if the below code returns an error!

							// Check various things before copying file:
						if (@is_dir($persdir) && (@is_file($theFile) || @is_uploaded_file($theFile)))	{		// File and destination must exist

								// Finding size. For safe_mode we have to rely on the size in the upload array if the file is uploaded.
							if (is_uploaded_file($theFile) && $theFile==$uploadedFileArray['tmp_name'])	{
								$fileSize = $uploadedFileArray['size'];
							} else {
								$fileSize = filesize($theFile);
							}

							if (!$maxSize || $fileSize<=($maxSize*1024))	{	// Check file size:
									// Prepare filename:
								$theEndFileName = isset($this->alternativeFileName[$theFile]) ? $this->alternativeFileName[$theFile] : $theFile;
								$fI = t3lib_div::split_fileref($theEndFileName);

									// Check for allowed extension:
								if ($this->fileFunc->checkIfAllowed($fI['fileext'], $persdir, $theEndFileName)) {
									/* ------------------------------------------------------------------------------------------- */

									/* uploaded files first will be transfered into the upload-dir (permanent-directory) */
									if (is_uploaded_file($theFile)) {
										if (($theDestFile = $this->imapObj->makePermanentCopy($theFile, $permdir, $fI['file']))) {
											$this->copiedFileMap[$theFile] = $theDestFile; $theFile = $theDestFile;	}
										else
											$this->log($table,$id,5,0,1,"Copying file '%s' failed!: The destination path (%s) may be write protected. Please make it write enabled!. (%s)",16,array($theFile, dirname($theDestFile), $recFID),$propArr['event_pid']);
									}

									/* duplicate files allways will be transfered into the intermediate-dir (persistant-directory) */
									if (!is_uploaded_file($theFile)) {
										if (($theDestFile = $this->imapObj->makePersistantCopy($theFile, $persdir))) {
											$this->copiedFileMap[$theFile] = $theDestFile; }
										else
											$this->log($table,$id,5,0,1,"Copying file '%s' failed!: The destination path (%s) may be write protected. Please make it write enabled!. (%s)",16,array($theFile, dirname($theDestFile), $recFID),$propArr['event_pid']);
									}

									/* ------------------------------------------------------------------------------------------- */
								}
								else
									$this->log($table,$id,5,0,1,"Fileextension '%s' not allowed. (%s)",12,array($fI['fileext'], $recFID),$propArr['event_pid']);
							}
							else
								$this->log($table,$id,5,0,1,"Filesize (%s) of file '%s' exceeds limit (%s). (%s)",13,array(t3lib_div::formatSize($fileSize),$theFile,t3lib_div::formatSize($maxSize*1024),$recFID),$propArr['event_pid']);
						}
						else
							$this->log($table,$id,5,0,1,'The destination (%s) or the source file (%s) does not exist. (%s)',14,array($dest, $theFile, $recFID),$propArr['event_pid']);

							// If the destination file was created, we will set the new filename in the value array, otherwise unset the entry in the value array!
						if (@is_file($theDestFile))	{
							$info = t3lib_div::split_fileref($theDestFile);
							$valueArray[$key]=$info['file']; // The value is set to the new filename
						} else {
							unset($valueArray[$key]);	// The value is set to the new filename
						}
					}
				}
			}

				// If MM relations for the files, we will set the relations as MM records and change the valuearray to contain a single entry with a count of the number of files!
			if ($tcaFieldConf['MM'])	{
				$dbAnalysis = t3lib_div::makeInstance('t3lib_loadDBGroup');
				/* @var $dbAnalysis t3lib_loadDBGroup */
				$dbAnalysis->tableArray['files']=array();	// dummy

				reset($valueArray);
				while (list($key,$theFile)=each($valueArray))	{
						// explode files
						$dbAnalysis->itemArray[]['id']=$theFile;
				}
				if ($status=='update')	{
					$dbAnalysis->writeMM($tcaFieldConf['MM'],$id,0);
				} else {
					$this->dbAnalysisStore[] = array($dbAnalysis, $tcaFieldConf['MM'], $id, 0);	// This will be traversed later to execute the actions
				}
				$valueArray = $dbAnalysis->countItems();
			}
		}

		return $valueArray;
	}

	/**
	 * Copies any "RTEmagic" image files found in record with table/id to new names.
	 * Usage: After copying a record this function should be called to search for "RTEmagic"-images inside the record. If such are found they should be duplicated to new names so all records have a 1-1 relation to them.
	 * Reason for copying RTEmagic files: a) if you remove an RTEmagic image from a record it will remove the file - any other record using it will have a lost reference! b) RTEmagic images keeps an original and a copy. The copy always is re-calculated to have the correct physical measures as the HTML tag inserting it defines. This is calculated from the original. Two records using the same image could have difference HTML-width/heights for the image and the copy could only comply with one of them. If you don't want a 1-1 relation you should NOT use RTEmagic files but just insert it as a normal file reference to a file inside fileadmin/ folder
	 *
	 * @param	string		Table name
	 * @param	integer		Record UID
	 * @return	void
	 */
	function copyRecord_fixRTEmagicImages($table, $theNewSQLID) {
		global $TYPO3_DB;

	// ---- echo 'copyRecord_fixRTEmagicImages<br />';
	//	print_r($table); echo '<br />';
	//	print_r($theNewSQLID); echo '<br />';

			// Creating fileFunc object.
		if (!$this->fileFunc) {
			$this->fileFunc = t3lib_div::makeInstance('t3lib_basicFileFunctions');
			$this->include_filefunctions = 1;
		}

			// Select all RTEmagic files in the reference table from the table/ID
		/* @var $TYPO3_DB t3lib_DB */
		$recs = $TYPO3_DB->exec_SELECTgetRows(
			'*',
			'sys_refindex',
			'ref_table='.$TYPO3_DB->fullQuoteStr('_FILE', 'sys_refindex').
				' AND ref_string LIKE '.$TYPO3_DB->fullQuoteStr('%/RTEmagic%', 'sys_refindex').
				' AND softref_key='.$TYPO3_DB->fullQuoteStr('images', 'sys_refindex').
				' AND tablename='.$TYPO3_DB->fullQuoteStr($table, 'sys_refindex').
				' AND recuid='.intval($theNewSQLID),
			'',
			'sorting DESC'
		);

			// Traverse the files found and copy them:
		if (is_array($recs)) {

			foreach($recs as $rec) {
				$filename = basename($rec['ref_string']);
				$fileInfo = array();

				if (t3lib_div::isFirstPartOfStr($filename, 'RTEmagicC_')) {

					$fileInfo['exists'] = @is_file(PATH_site.$rec['ref_string']);
					$fileInfo['original'] = substr($rec['ref_string'], 0, -strlen($filename)) . 'RTEmagicP_' . ereg_replace('\.[[:alnum:]]+$', '', substr($filename, 10));
					$fileInfo['original_exists'] = @is_file(PATH_site . $fileInfo['original']);

						// CODE from tx_impexp and class.rte_images.php adapted for use here:
					if ($fileInfo['exists'] && $fileInfo['original_exists']) {

							// Initialize; Get directory prefix for file and set the original name:
						$dirPrefix = dirname($rec['ref_string']) . '/';
						$rteOrigName = basename($fileInfo['original']);

							// If filename looks like an RTE file, and the directory is in "uploads/", then process as a RTE file!
						if ($rteOrigName && t3lib_div::isFirstPartOfStr($dirPrefix, 'uploads/') && @is_dir(PATH_site . $dirPrefix)) {	// RTE:

								// From the "original" RTE filename, produce a new "original" destination filename which is unused.
							$origDestName = $this->fileFunc->getUniqueName($rteOrigName, PATH_site . $dirPrefix);

								// Create copy file name:
							$pI = pathinfo($rec['ref_string']);
							$copyDestName = dirname($origDestName) . '/RTEmagicC_' . substr(basename($origDestName), 10) . '.' . $pI['extension'];

							if (!@is_file($copyDestName) &&
							    !@is_file($origDestName) &&
							    $origDestName === t3lib_div::getFileAbsFileName($origDestName) &&
							    $copyDestName === t3lib_div::getFileAbsFileName($copyDestName)) {

									// Making copies:
								t3lib_div::upload_copy_move(PATH_site . $fileInfo['original'], $origDestName);
								t3lib_div::upload_copy_move(PATH_site . $rec['ref_string'], $copyDestName);
								clearstatcache();

									// Register this:
								$this->RTEmagic_copyIndex[$rec['tablename']][$rec['recuid']][$rec['field']][$rec['ref_string']] = substr($copyDestName,strlen(PATH_site));

									// Check and update the record using the t3lib_refindex class:
								if (@is_file($copyDestName)) {
									$sysRefObj = t3lib_div::makeInstance('t3lib_refindex');
									$error = $sysRefObj->setReferenceValue($rec['hash'], substr($copyDestName, strlen(PATH_site)), FALSE, TRUE);
									if ($error) {
										echo $this->newlog('t3lib_refindex::setReferenceValue(): ' . $error, 1);
									}
								}
								else
									$this->newlog('File "' . $copyDestName . '" was not created!', 1);
							}
							else
								$this->newlog('Could not construct new unique names for file!', 1);
						}
						else
							$this->newlog('Maybe directory of file was not within "uploads/"?', 1);
					}
					else
						$this->newlog('Trying to copy RTEmagic files (' . $rec['ref_string'] . ' / ' . $fileInfo['original'] . ') but one or both were missing', 1);
				}
			}
		}
	}

}
/* -ux_t3lib_tcemain- */ }

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/class.ux_t3lib_tcemain.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/realimageurl/class.ux_t3lib_tcemain.php']);
}
?>