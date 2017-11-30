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
 * Select list form field with manufacturers.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class JFormFieldCsviVirtuemartManufacturer extends JFormFieldCsviForm
{
	/**
	 * Type of field
	 *
	 * @var    string
	 * @since  4.0
	 */
	protected $type = 'CsviVirtuemartManufacturer';

	/**
	 * Get the options.
	 *
	 * @return  array  An array of customfields.
	 *
	 * @since   4.0
	 */
	protected function getOptions()
	{
		$conf = JFactory::getConfig();
		$lang = strtolower($this->form->getValue('orderproduct', '', str_replace('-', '_', $conf->get('language'))));

		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('virtuemart_manufacturer_id', 'value') . ',' . $this->db->quoteName('mf_name', 'text'))
			->from($this->db->quoteName('#__virtuemart_manufacturers_' . $lang));
		$this->db->setQuery($query);
		$options = $this->db->loadObjectList();

		if (empty($options))
		{
			$options = array();
		}

		return array_merge(parent::getOptions(), $options);
	}
}
