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
 * Select list form field with order products.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       4.0
 */
class JFormFieldCsviVirtuemartOrderProduct extends JFormFieldCsviForm
{
	/**
	 * Type of field
	 *
	 * @var    string
	 * @since  4.0
	 */
	protected $type = 'CsviVirtuemartOrderProduct';

	/**
	 * Select order products.
	 *
	 * @return  array  An array of users.
	 *
	 * @since   4.0
	 */
	protected function getOptions()
	{
		$ids = $this->form->getValue('orderproduct', 'jform');

		if (!empty($ids) && !empty($ids[0]))
		{
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName('virtuemart_product_id', 'value') . ',' . $this->db->quoteName('order_item_name', 'text'))
				->from($this->db->quoteName('#__virtuemart_order_items'))
				->where($this->db->quoteName('virtuemart_product_id') . ' IN (' . implode(',', $ids) . ')')
				->order($this->db->quoteName('order_item_name'));

			$this->db->setQuery($query);
			$orderproducts = $this->db->loadObjectList();

			if (empty($orderproducts))
			{
				$orderproducts = array();
			}

			return array_merge(parent::getOptions(), $orderproducts);
		}
		else
		{
			return parent::getOptions();
		}
	}
}
