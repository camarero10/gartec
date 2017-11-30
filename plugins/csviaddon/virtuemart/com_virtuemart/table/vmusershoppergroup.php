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
 * User shopper group table.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class VirtueMartTableVmuserShoppergroup extends CsviTableDefault
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
		parent::__construct('#__virtuemart_vmuser_shoppergroups', 'id', $db, $config);
	}

	/**
	 * Check if the shopper group relation exists.
	 *
	 * @return  bool  True if shopper group relation entry exists | False if shopper group relation entry does not exist.
	 *
	 * @since   4.0
	 */
	public function check()
	{
		if (!empty($this->virtuemart_user_id) && !empty($this->virtuemart_shoppergroup_id))
		{
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName($this->_tbl_key))
				->from($this->db->quoteName($this->_tbl))
				->where($this->db->quoteName('virtuemart_user_id') . ' = ' . (int) $this->virtuemart_user_id)
				->where($this->db->quoteName('virtuemart_shoppergroup_id') . ' = ' . (int) $this->virtuemart_shoppergroup_id);
			$this->db->setQuery($query);
			$this->id = $this->db->loadResult();
			$this->log->add('Find existing VirtueMart user with shopper group link');

			if ($this->id > 0)
			{
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return true;
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
		$this->id = null;

		return true;
	}
}
