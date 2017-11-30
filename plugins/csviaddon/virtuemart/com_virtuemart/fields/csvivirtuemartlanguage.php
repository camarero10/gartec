<?php
/**
 * @package     CSVI
 * @subpackage  VirtueMart
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

jimport('joomla.form.helper');
JFormHelper::loadFieldClass('CsviForm');

/**
 * Select list form field with languages.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class JFormFieldCsviVirtuemartLanguage extends JFormFieldCsviForm
{
	/**
	 * Type of field
	 *
	 * @var    string
	 * @since  4.0
	 */
	protected $type = 'CsviVirtuemartLanguage';

	/**
	 * Get the options.
	 *
	 * @return  array  An array of available languages.
	 *
	 * @since   4.0
	 */
	protected function getOptions()
	{
		// Make sure the languages are sorted base on locale instead of random sorting
		$languages = JLanguageHelper::createLanguageList($this->value, constant('JPATH_SITE'), true, true);

		if (count($languages) > 1)
		{
			usort(
				$languages,
				function ($a, $b)
				{
					return strcmp($a["value"], $b["value"]);
				}
			);
		}

		// Merge any additional options in the XML definition.
		$options = array_merge(
			parent::getOptions(),
			$languages
		);

		// Check if the tables exist
		$tables = $this->db->getTableList();

		foreach ($options as $key => $lang)
		{
			if (!in_array($this->db->getPrefix() . 'virtuemart_products_' . strtolower(str_replace('-', '_', $lang['value'])), $tables))
			{
				unset($options[$key]);
			}
		}

		return $options;
	}
}
