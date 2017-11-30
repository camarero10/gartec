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
 * Select list form field with custom fields.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class JFormFieldCsviVirtuemartCustomfield extends JFormFieldCsviForm
{
	/**
	 * Type of field
	 *
	 * @var    string
	 * @since  4.0
	 */
	protected $type = 'CsviVirtuemartCustomfield';

	/**
	 * Get the options.
	 *
	 * @return  array  An array of customfields.
	 *
	 * @since   4.0
	 */
	protected function getOptions()
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('virtuemart_custom_id', 'value') . ',' . $this->db->quoteName('custom_title', 'text'))
			->from($this->db->quoteName('#__virtuemart_customs'))
			->where($this->db->quoteName('field_type') . ' NOT IN (' . $this->db->quote('R') . ',' . $this->db->quote('Z') . ')');
		$this->db->setQuery($query);
		$options = $this->db->loadObjectList();

		if (empty($options))
		{
			$options = array();
		}

		return array_merge(parent::getOptions(), $options);
	}
}
