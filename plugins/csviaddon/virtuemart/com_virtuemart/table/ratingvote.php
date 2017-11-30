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
 * Rating votes table.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class VirtueMartTableRatingVote extends CsviTableDefault
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
		parent::__construct('#__virtuemart_rating_votes', 'virtuemart_rating_vote_id', $db, $config);
	}

	/**
	 * Check if there is already an existing review by the user.
	 *
	 * @return  bool  True if rating review exists | False if rating review does not exist.
	 *
	 * @since   3.0
	 */
	public function check()
	{
		if (empty($this->virtuemart_rating_vote_id))
		{
			// Check if a record already exists in the database
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName($this->_tbl_key))
				->from($this->db->quoteName($this->_tbl))
				->where($this->db->quoteName('virtuemart_product_id') . ' = ' . (int) $this->virtuemart_product_id)
				->where($this->db->quoteName('created_by') . ' = ' . (int) $this->created_by);
			$this->db->setQuery($query);
			$this->db->execute();
			$this->log->add('Check if a rating vote exists');

			if ($this->db->getAffectedRows() > 0)
			{
				$this->virtuemart_rating_vote_id = $this->db->loadResult();

				return true;
			}
			else
			{
				// There is no entry yet, so we must insert a new one
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
		$this->virtuemart_rating_vote_id = null;

		return true;
	}
}
