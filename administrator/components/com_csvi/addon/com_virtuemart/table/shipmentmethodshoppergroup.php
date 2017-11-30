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
 * Shipping method shopper groups cross reference.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class VirtueMartTableShipmentmethodShoppergroup extends CsviTableDefault
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
		parent::__construct('#__virtuemart_shipmentmethod_shoppergroups', 'id', $db, $config);
	}

	/**
	 * Delete all selected shopper group names.
	 *
	 * @param   int  $shipment_method_id  The ID of the shipment method to delete.
	 *
	 * @return  bool  True on success | False on failure.
	 *
	 * @since   4.3.4
	 */
	public function deleteOldGroups($shipment_method_id)
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName($this->_tbl))
			->where($this->db->quoteName('virtuemart_shipmentmethod_id') . ' = ' . (int) $shipment_method_id);
		$this->db->setQuery($query);

		return $this->db->execute();
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
		$this->id = null;

		return true;
	}
}
