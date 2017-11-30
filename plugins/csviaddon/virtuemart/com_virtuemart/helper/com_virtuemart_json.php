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

/**
 * The VirtueMart helper class.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartHelperCom_Virtuemart_Json
{
	/**
	 * Database connector
	 *
	 * @var    JDatabase
	 * @since  6.0
	 */
	protected $db = null;

	/**
	 * Constructor.
	 *
	 * @param   JDatabase  $db  Database connector.
	 *
	 * @since   4.0
	 */
	public function __construct(JDatabase $db)
	{
		$this->db = $db;
	}

	/**
	 * Get a filtered list of order users.
	 *
	 * @param   string  $filter  The filter to use.
	 *
	 * @return  array  List of order users matching filter.
	 *
	 * @since   4.0
	 */
	public function getOrderUser($filter)
	{
		$fullname = $this->db->quoteName('first_name') . ", " . $this->db->quoteName('middle_name') . ", " . $this->db->quoteName('last_name');

		$query = $this->db->getQuery(true)
			->select(
				array(
					$this->db->quoteName('virtuemart_user_id', 'user_id'),
					"IF (LENGTH(TRIM(CONCAT_WS(' ', " . $fullname . "))) = 0, "
						. $this->db->quote(JText::_('COM_CSVI_EXPORT_ORDER_USER_EMPTY'))
							. ", IF (TRIM(CONCAT_WS(' ', " . $fullname . ")) IS NULL, "
								. $this->db->quote(JText::_('COM_CSVI_EXPORT_ORDER_USER_EMPTY'))
								. ", TRIM(CONCAT_WS(' ', " . $fullname . "))
							)
					) AS " . $this->db->quoteName('user_name')
				)
			)
			->from($this->db->quoteName('#__virtuemart_order_userinfos'))
			->where($this->db->quoteName('first_name') . ' LIKE ' . $this->db->quote('%' . $filter . '%'), 'OR')
			->where($this->db->quoteName('middle_name') . ' LIKE ' . $this->db->quote('%' . $filter . '%'))
			->where($this->db->quoteName('last_name') . ' LIKE ' . $this->db->quote('%' . $filter . '%'))
			->order($this->db->quoteName('user_name'));

		$this->db->setQuery($query, 0, 10);

		return $this->db->loadObjectList();
	}

	/**
	 * Get a filtered list of order products.
	 *
	 * @param   string  $filter  The filter to use.
	 *
	 * @return  array  List of order users matching filter.
	 *
	 * @since   4.0
	 */
	public function getOrderProduct($filter)
	{
		$query = $this->db->getQuery(true)
			->select(
				$this->db->quoteName('virtuemart_product_id', 'product_id') . ',
				IF (LENGTH(TRIM(' . $this->db->quoteName('order_item_sku') . ')) = 0, '
					. $this->db->quote(JText::_('COM_CSVI_EXPORT_ORDER_SKU_EMPTY')) . ', '
					. $this->db->quoteName('order_item_sku')
				. ') AS ' . $this->db->quoteName('product_sku') . ', ' .
				$this->db->quoteName('order_item_name', 'product_name')
			)
			->from($this->db->quoteName('#__virtuemart_order_items'))
			->where($this->db->quoteName('order_item_sku') . ' LIKE ' . $this->db->quote('%' . $filter . '%'), 'OR')
			->where($this->db->quoteName('order_item_name') . ' LIKE ' . $this->db->quote('%' . $filter . '%'))
			->order($this->db->quoteName('order_item_name'))
			->group($this->db->quoteName('order_item_sku'));

		$this->db->setQuery($query, 0, 10);

		return $this->db->loadObjectList();
	}

	/**
	 * Load the category tree.
	 *
	 * @param   string  $filter  The filter to use.
	 *
	 * @return  array  List of categories.
	 *
	 * @since   4.0
	 */
	public function loadCategoryTree($filter)
	{
		require_once JPATH_ADMINISTRATOR . '/components/com_csvi/addon/com_virtuemart/helper/categorylist.php';
		$categoryList = new Com_VirtuemartHelperCategoryList;

		return $categoryList->getCategoryTree($filter);
	}

	/**
	 * Load the manufacturer list.
	 *
	 * @param   string  $filter  The filter to use.
	 *
	 * @return  array  List of categories.
	 *
	 * @since   4.0
	 */
	public function loadManufacturers($filter)
	{
		// Clean up the language if needed
		$lang = strtolower(str_replace('-', '_', $filter));

		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('virtuemart_manufacturer_id', 'value') . ',' . $this->db->quoteName('mf_name', 'text'))
			->from($this->db->quoteName('#__virtuemart_manufacturers_' . $lang));
		$this->db->setQuery($query);
		$mfs = $this->db->loadObjectList();

		$options = array();

		// Add a don't use option
		$options[] = JHtml::_('select.option', 'none', JText::_('COM_CSVI_DONT_USE'));

		if (isset($mfs))
		{
			if (count($mfs) > 0)
			{
				foreach ($mfs as $mf)
				{
					$options[] = JHtml::_('select.option', $mf->value, $mf->text);
				}
			}
		}

		return $options;
	}
}
