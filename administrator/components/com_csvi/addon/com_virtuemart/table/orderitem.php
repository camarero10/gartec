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
 * Order item table.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class VirtueMartTableOrderItem extends CsviTableDefault
{
	/**
	 * Table constructor.
	 *
	 * @param   string     $table   Name of the database table to model.
	 * @param   string     $key     Name of the primary key field in the table.
	 * @param   JDatabase  &$db     Database driver
	 * @param   array      $config  The configuration parameters array
	 *
	 * @since   4.0
	 */
	public function __construct($table, $key, &$db, $config = array())
	{
		parent::__construct('#__virtuemart_order_items', 'virtuemart_order_item_id', $db, $config);
	}

	/**
	 * Check if a given order item already exists.
	 *
	 * @return  boolean  True if the instance is sane and able to be stored in the database.
	 *
	 * @since   6.0
	 */
	public function check()
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName($this->_tbl_key))
			->from($this->db->quoteName($this->_tbl))
			->where($this->db->quoteName('virtuemart_order_id') . ' = ' . (int) $this->get('virtuemart_order_id'))
			->where($this->db->quoteName('virtuemart_vendor_id') . ' = ' . $this->db->quote($this->get('virtuemart_vendor_id')))
			->where($this->db->quoteName('order_item_sku') . ' = ' . $this->db->quote($this->get('order_item_sku')));
		$this->db->setQuery($query);
		$result = $this->db->loadResult();

		if ($result)
		{
			$this->set('virtuemart_order_item_id', $result);

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Reset the primary key.
	 *
	 * @return  boolean  Always returns true.
	 *
	 * @since   6.0
	 */
	public function reset()
	{
		parent::reset();

		// Empty the primary key
		$this->virtuemart_order_item_id = null;

		return true;
	}
}
