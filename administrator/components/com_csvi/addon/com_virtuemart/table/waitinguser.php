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
 * Waiting list table.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class VirtueMartTableWaitinguser extends CsviTableDefault
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
		parent::__construct('#__virtuemart_waitingusers', 'virtuemart_waitinguser_id', $db, $config);
	}

	/**
	 * Check if a waiting list entry exists.
	 *
	 * @return  bool  True if waiting list entry exists | False if waiting list entry does not exist.
	 *
	 * @since   3.1
	 */
	public function check()
	{
		if (empty($this->virtuemart_waitinguser_id))
		{
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName($this->_tbl_key))
				->from($this->db->quoteName($this->_tbl))
				->where($this->db->quoteName('virtuemart_product_id') . ' = ' . (int) $this->virtuemart_product_id)
				->where($this->db->quoteName('virtuemart_user_id') . ' = ' . (int) $this->virtuemart_user_id);
			$this->db->setQuery($query);
			$this->virtuemart_waitinguser_id = $this->db->loadResult();
			$this->log->add('COM_CSVI_CHECKING_WAITINGLIST_EXISTS', true);

			if ($this->virtuemart_waitinguser_id > 0)
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
		$this->virtuemart_waitinguser_id = null;

		return true;
	}
}
