<?php
function nameReplacement($module) {
	global $_EXTKEY;

	$replacement = $module;
	$replacement = str_replace('class.', 'class.ux_', $replacement);
	$replacement = t3lib_extMgm::extPath($_EXTKEY) . $replacement;

	return $replacement;
}

function assignTemplateClass($module) {
	if (!is_array($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']))
		$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS'] = array();
	if (!is_array($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['TCLASS']))
		$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['TCLASS'] = array();

	/* This implements templated inheritance in the way C++ does:
	 *
	 * template<class oldClass>
	 * class newClass : public oldClass {
	 *	...
	 * };
	 *
	 * It allows in PHP to super-class a dynamic given class.
	 */
	$xClass = &$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS'];
	$tClass = &$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['TCLASS'];

	$replacement = nameReplacement($module);
	if (@file_exists($replacement)) {
		if (($xClass[$module] != '') &&
		    ($xClass[$module] != $replacement))
			$tClass[$module] = $xClass[$module];

		$xClass[$module] = $replacement;
	}
	else
		t3lib_div::debug('announced replacement "' . $replacement . '" for XCLASSed "' . $module . '" = is missing');
}

function instanciateTemplateClass($subdir, $classname, $alias = null, $exact = FALSE) {
	$xClass = &$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS'];
	$tClass = &$GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['TCLASS'];

	/* construct the module-identifier */
	$module = $subdir . ($exact ? '' : 'class.') . ($alias ? $alias : $classname) . '.php';

//	echo $module . "\n<br />";
//	echo $tClass[$module] . "\n<br />";

	/* we found a tempate-class, and we could load it */
	if ($tClass[$module]) {
		$modpath = PATH_site . 'typo3temp/tclasses/';
		$modfile = $modpath . md5($module . @filemtime($tClass[$module]) . $tClass[$module] . @filemtime($xClass[$module]) . $xClass[$module]) . '.php';

		if (@file_exists($modfile))
			return $modfile;

		if (($tClassBody = @file_get_contents($tClass[$module])) &&
		    ($xClassBody = @file_get_contents($xClass[$module]))) {
			$tstart = strpos ($tClassBody, '<?php');
			$tstop  = strrpos($tClassBody, '?>');
			$xstart = strpos ($xClassBody, '/* +ux_' . $classname . '+ */');
			$xstop  = strrpos($xClassBody, '/* -ux_' . $classname . '- */');

		//	echo '/* +ux_' . $classname . '+ */' . "\n<br />";
		//	echo $start . "\n<br />";
		//	echo $stop . "\n<br />";
		//	echo $classname . ' is super-classed ' . $tClass[$module] . "\n<br />";

			if (($tstart === 0) &&
			    ($xstart !== FALSE) &&
			    ($xstop !== FALSE)) {
				$lower = substr($tClassBody, $tstart + 5, $tstop - $tstart - 5);
				$upper = substr($xClassBody, $xstart + 0, $xstop - $xstart - 0);

				/* class ux_classname extends xx_classname extends classname { */
			//	$lower = preg_replace('/([^a-zA-Z_])(ux_' . $classname . ')([^a-zA-Z_])/', '\1xx_' . $classname . '\3', $lower);
			//	$upper = preg_replace(   '/([^a-zA-Z_])(' . $classname . ')([^a-zA-Z_])/', '\1xx_' . $classname . '\3', $upper);

				/* class ux_xx_classname extends xx_classname extends classname { */
			//	$lower = str_replace('ux_' . $classname . '', 'xx_' . $classname . '', $lower);
			//	$upper = str_replace(   '' . $classname . '', 'xx_' . $classname . '', $upper);

				/* class ux_ux_classname extends ux_classname extends classname { */
				$lower = 'include_once("' . $tClass[$module] . '");' . "\n\n";
				$upper = str_replace(   '' . $classname . '', 'ux_' . $classname . '', $upper);

		//		echo strlen($lower) . '/' . strlen($tClassBody) . "\n<br />";
		//		echo strlen($upper) . '/' . strlen($xClassBody) . "\n<br />";

				if ((@is_dir($modpath) === TRUE || @mkdir($modpath) !== FALSE) &&
				    (@file_put_contents($modfile, '<?php' . "\n\n" . $lower . "\n\n" . $upper . "\n\n" . '?>') !== FALSE)) {
					return $modfile;
				}
				else
					t3lib_div::debug('cannot write templated components for XCLASSed "' . $module . '"');
			}
			else
				t3lib_div::debug('cannot identify templating components for XCLASSed "' . $module . '"');
		}
		else
			t3lib_div::debug('cannot load templating components for XCLASSed "' . $module . '"');
	}

	return null;
}
?>