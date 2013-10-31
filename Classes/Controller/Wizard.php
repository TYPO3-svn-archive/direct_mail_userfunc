<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Xavier Perseguers (xavier@causal.ch)
 *  (c) 2009-2011 Xavier Perseguers (typo3@perseguers.ch)
 *
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


/**
 * This class encapsulates display of a user wizard.
 *
 * $Id$
 */
class Tx_DirectMailUserfunc_Controller_Wizard {

	/**
	 * Default constructor.
	 */
	public function __construct() {
		$GLOBALS['LANG']->includeLLFile('EXT:direct_mail_userfunc/Resources/Private/Language/locallang_tca.xml');
	}

	/**
	 * Returns code to show whether the itemsProcFunc definition is valid.
	 *
	 * @param array $PA TCA configuration passed by reference
	 * @param t3lib_TCEforms $pObj
	 * @return string HTML snippet to be put after the itemsProcFunc field
	 */
	public function itemsprocfunc_procWizard(array $PA, t3lib_TCEforms $pObj) {
		$itemsProcFunc = $PA['row']['tx_directmailuserfunc_itemsprocfunc'];
		if (!$itemsProcFunc) {
			// Show the required icon
			$PA['item'] = self::getIcon('gfx/required_h.gif') . $PA['item'];
		}

		// Show the user function provider selector
		self::addUserFunctionProviders($PA);

		if (!$itemsProcFunc) {
			return;
		} elseif (self::isClassValid($itemsProcFunc) && self::isMethodValid($itemsProcFunc)) {
			$PA['item'] .= self::getIcon('gfx/icon_ok.gif');
		} elseif (!self::isClassValid($itemsProcFunc)) {
			$PA['item'] .= self::getIcon('gfx/icon_warning.gif') . ' ' . $GLOBALS['LANG']->getLL('wizard.itemsProcFunc.invalidClass');
		} else {
			$PA['item'] .= self::getIcon('gfx/icon_warning.gif') . ' ' . $GLOBALS['LANG']->getLL('wizard.itemsProcFunc.invalidMethod');
		}
	}

	/**
	 * Returns code to show a user-handled wizard associated to current
	 * itemsProcFunc value.
	 *
	 * @param array $PA TCA configuration passed by reference
	 * @param t3lib_TCEforms $pObj Parent object
	 * @return string HTML snippet to be put after the params field
	 */
	public function params_procWizard(array $PA, t3lib_TCEforms $pObj) {
		$itemsProcFunc = $PA['row']['tx_directmailuserfunc_itemsprocfunc'];
		if (!self::isMethodValid($itemsProcFunc)) {
			return '';
		}

		list($className, $methodName) = explode('->', $itemsProcFunc);
		if (!method_exists($className, 'getWizard')) {
			return '';
		}

		$autoJS = TRUE;
		$wizardJS = trim(call_user_func_array(
			array($className, 'getWizard'),
			array($methodName, &$PA, $pObj, &$autoJS)
		));

		if (!$wizardJS) {
			return '';
		}

		$altIcon = $GLOBALS['LANG']->getLL('wizard.parameters.title');
		if ($autoJS) {
			if ($wizardJS{strlen($wizardJS) - 1} !== ';') {
				$wizardJS .= ';';
			}
			$wizardJS .= 'return false;';

			$output = '<a href="#" onclick="' . htmlspecialchars($wizardJS) . '" title="' . $altIcon . '">';
			$output .= self::getIcon('gfx/options.gif');
			$output .= '</a>';
		} else {
			$output = self::getIcon('gfx/options.gif', $altIcon, 'id="params-btn" style="cursor:pointer"');
			$output .= $wizardJS;
		}

		return $output;
	}

	/**
	 * Checks whether the class part of a given itemsProcFunc definition is valid.
	 *
	 * @param string $itemsProcFunc
	 * @return boolean
	 */
	protected static function isClassValid($itemsProcFunc) {
		list($className, $methodName) = explode('->', $itemsProcFunc);

		if ($className && class_exists($className)) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Checks whether the method part of a given itemsProcFunc definition is valid.
	 *
	 * @param string $itemsProcFunc
	 * @return boolean
	 */
	protected static function isMethodValid($itemsProcFunc) {
		if (!self::isClassValid($itemsProcFunc)) {
			return FALSE;
		}

		list($className, $methodName) = explode('->', $itemsProcFunc);

		if ($methodName && method_exists($className, $methodName)) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Returns a HTML image tag with a given icon (taking t3skin into account).
	 *
	 * @param string $src Image source
	 * @param string $alt Alternate text
	 * @param string $params Additional parameters for the img tag
	 * @return string
	 */
	protected static function getIcon($src, $alt = '', $params = '') {
		return '<img ' . t3lib_iconWorks::skinImg($GLOBALS['BACKPATH'], $src) .
			' alt="' . $alt . '" title="' . $alt . '" vspace="4" align="absmiddle" ' . $params .'/>';
	}

	/**
	 * Prepends a user function provider selector to the itemsProcFunc field.
	 *
	 * @param array $PA TCA configuration passed by reference
	 * @return void
	 */
	protected static function AddUserFunctionProviders($PA) {
		if (!count($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['direct_mail_userfunc']['userFunc'])) {
			return;
		}

		// Sort list of providers
		$providers = array();
		foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['direct_mail_userfunc']['userFunc'] as $provider) {
			$itemsProcFunc = $provider['class'] .'->' . $provider['method'];
			$providers[$itemsProcFunc] = self::getLL($provider['label']);
		}
		asort($providers);

		$options = array();
		foreach ($providers as $itemsProcFunc => $label) {
			$options[] = sprintf('<option value="%s">%s</option>', $itemsProcFunc, $label);
		}

		$updateJS = 'var itemsProcFunc = document.' . $PA['formName'] . '[\'userfunc_provider\'].value;';
		$updateJS .= 'document.' . $PA['formName'] . '[\'' . $PA['itemName'] . '\'].value = itemsProcFunc;';
		$updateJS .= implode('', $PA['fieldChangeFunc']) . ';return false;';

		$selector = '
			<select name="userfunc_provider" onchange="' . $updateJS . '">
				<option value=""></option>' .
				join('', $options) . '
			</select>
		';

		// Prepend the provider selector
		$PA['item'] = $GLOBALS['LANG']->getLL('wizard.itemsProcFunc.providers') . $selector . '<br />' . $PA['item'];
	}

	/**
	 * Returns the label with key $index for current backend language.
	 *
	 * @param string $label Label/key reference
	 * @return string
	 */
	public static function getLL($label) {
		if (strcmp(substr($label, 0, 8), 'LLL:EXT:')) {
			// Non-localizable string provided
			return $label;
		}

		$label = substr($label, 8);	// Remove 'LLL:EXT:' at the beginning
		$extension = substr($label, 0, strpos($label, '/'));
		$references = explode(':', substr($label, strlen($extension) + 1));
		if (!($extension && count($references))) {
			return $label;
		}

		$file = t3lib_extMgm::extPath($extension) . $references[0];
		$index = $references[1];
		$LOCAL_LANG = t3lib_div::readLLfile($file, $GLOBALS['LANG']->lang);

		$ret = $label;
		if (version_compare(TYPO3_version, '4.6.0', '<')) {
			if (strcmp($LOCAL_LANG[$GLOBALS['LANG']->lang][$index], '')) {
				$ret = $LOCAL_LANG[$GLOBALS['LANG']->lang][$index];
			} else {
				$ret = $LOCAL_LANG['default'][$index];
			}
		} else {
			if (strcmp($LOCAL_LANG[$GLOBALS['LANG']->lang][$index][0]['target'], '')) {
				$ret = $LOCAL_LANG[$GLOBALS['LANG']->lang][$index][0]['target'];
			} else {
				$ret = $LOCAL_LANG['default'][$index][0]['target'];
			}
		}

		return $ret;
	}

}
