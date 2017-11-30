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
 * Product custom field table.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class VirtueMartTableProductCustomfield extends CsviTableDefault
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
		parent::__construct('#__virtuemart_product_customfields', 'virtuemart_customfield_id', $db, $config);
	}

	/**
	 * Check if an entry already exists.
	 *
	 * @return  bool  True if ID exists | False if ID doesn't exist.
	 *
	 * @since   4.0
	 */
	public function check()
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName($this->_tbl_key))
			->from($this->db->quoteName($this->_tbl))
			->where($this->db->quoteName('virtuemart_product_id') . ' = ' . (int) $this->virtuemart_product_id)
			->where($this->db->quoteName('virtuemart_custom_id') . ' = ' . (int) $this->virtuemart_custom_id)
			->where($this->db->quoteName('customfield_value') . ' = ' . $this->db->quote($this->customfield_value));

		$this->db->setQuery($query);
		$id = $this->db->loadResult();

		if ($id)
		{
			$this->virtuemart_customfield_id = $id;

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Delete all related products for given product ID.
	 *
	 * @param   int  $product_id  The product to delete related products for.
	 * @param   int  $vendor_id   The vendor ID to filter on.
	 * @param   int  $related_id  The related ID to filter on.
	 *
	 * @return  bool  True if deleted | False if not deleted.
	 *
	 * @since   4.0
	 */
	public function deleteRelated($product_id, $vendor_id, $related_id)
	{
		if ($product_id && $related_id)
		{
			$query = $this->db->getQuery(true);
			$query->delete($this->db->quoteName($this->_tbl));
			$query->where($this->db->quoteName('virtuemart_product_id') . ' = ' . (int) $product_id);
			$query->where($this->db->quoteName('virtuemart_custom_id') . ' = ' . (int) $related_id);
			$this->db->setQuery($query);

			return $this->db->execute();
		}

		return false;
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
		$this->virtuemart_customfield_id = null;

		return true;
	}
}
