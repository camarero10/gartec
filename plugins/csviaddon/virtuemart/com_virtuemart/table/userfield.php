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
 * User field table.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class VirtueMartTableUserfield extends CsviTableDefault
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
		parent::__construct('#__virtuemart_userfields', 'virtuemart_userfield_id', $db, $config);
	}

	/**
	 * Check if the user field exists.
	 *
	 * @return  bool  True if user field entry exists | False if user field entry does not exist.
	 *
	 * @since   4.0
	 */
	public function check()
	{
		if (empty($this->virtuemart_userfield_id))
		{
			// Check if a record already exists in the database
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName($this->_tbl_key))
				->from($this->db->quoteName($this->_tbl))
				->where($this->db->quoteName('name') . ' = ' . $this->db->quote($this->name))
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote($this->type));
			$this->db->setQuery($query);
			$id = $this->db->loadResult();
			$this->log->add('COM_CSVI_CHECK_RATING_EXISTS', true);

			if ($id)
			{
				$this->virtuemart_userfield_id = $id;

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
		$this->virtuemart_userfield_id = null;

		return true;
	}
}
