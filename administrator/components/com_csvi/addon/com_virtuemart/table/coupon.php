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
 * Coupon table.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class VirtueMartTableCoupon extends CsviTableDefault
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
		parent::__construct('#__virtuemart_coupons', 'virtuemart_coupon_id', $db, $config);
	}

	/**
	 * Check if a coupon already exists.
	 *
	 * @return  bool  True if coupon already exists | False if coupon does not exist.
	 *
	 * @since   3.0
	 */
	public function check()
	{
		if (isset($this->virtuemart_coupon_id))
		{
			return true;
		}
		else
		{
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName($this->_tbl_key))
				->from($this->db->quoteName($this->_tbl))
				->where($this->db->quoteName('coupon_code') . ' = ' . $this->db->quote($this->coupon_code));
			$this->db->setQuery($query);
			$this->log->add('COM_CSVI_CHECK_COUPON_CODE_EXISTS', true);
			$this->db->execute();

			if ($this->db->getAffectedRows() > 0)
			{
				$this->virtuemart_coupon_id = $this->db->loadResult();

				return true;
			}
			else
			{
				return false;
			}
		}
	}

	/**
	 * Reset the primary key.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   6.0
	 */
	public function reset()
	{
		parent::reset();

		// Empty the primary key
		$this->virtuemart_coupon_id = null;

		return true;
	}
}
