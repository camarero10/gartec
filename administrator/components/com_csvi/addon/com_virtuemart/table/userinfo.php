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
 * User info table.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class VirtueMartTableUserinfo extends CsviTableDefault
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
		parent::__construct('#__virtuemart_userinfos', 'virtuemart_userinfo_id', $db, $config);
	}

	/**
	 * Check if a VM user info already exists. If so, retrieve the user info ID.
	 *
	 * @return  bool  Returns true if user info ID has been found | False if no user info ID has been found.
	 *
	 * @since   6.0
	 */
	public function check()
	{
		if ($this->get('virtuemart_user_id', false))
		{
			// If we have a user_id we can get the user_info_id
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName('virtuemart_userinfo_id'))
				->from($this->db->quoteName('#__virtuemart_userinfos'))
				->where($this->db->quoteName('virtuemart_user_id') . ' = ' . (int) $this->virtuemart_user_id)
				->where($this->db->quoteName('address_type') . ' = ' . $this->db->quote($this->address_type))
				->where($this->db->quoteName('address_type_name') . ' = ' . $this->db->quote($this->address_type_name));
			$this->db->setQuery($query);
			$this->log->add('Find the user info ID');
			$this->set('virtuemart_userinfo_id', $this->db->loadResult());

			if ($this->get('virtuemart_userinfo_id', false))
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
			return false;
		}
	}

	/**
	 * Check if a certain field exists.
	 *
	 * @param   string  $name  The name of the field to check.
	 *
	 * @return  bool  True if field exists | False if field does not exist.
	 *
	 * @since   6.0
	 */
	public function fieldExists($name)
	{
		$columns = $this->db->getTableColumns($this->_tbl);

		return array_key_exists($name, $columns);
	}

	/**
	 * Create a new table field.
	 *
	 * @param   string  $name  The name of the field to create.
	 * @param   string  $type  The type of the field to create.
	 *
	 * @return  bool  True on success | False on failure.
	 *
	 * @since   6.0
	 */
	public function createField($name, $type)
	{
		$query = "ALTER TABLE " . $this->db->quoteName($this->_tbl) . " ADD COLUMN " . $this->db->quoteName($name) . " " . $type;
		$this->db->setQuery($query);

		return $this->db->execute();
	}

	/**
	 * Delete a table field.
	 *
	 * @param   string  $name  The name of the field to delete.
	 *
	 * @return  bool  True on success | False on failure.
	 *
	 * @since   6.0
	 */
	public function deleteField($name)
	{
		$query = "ALTER TABLE " . $this->db->quoteName($this->_tbl) . " DROP COLUMN " . $this->db->quoteName($name);
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
		$this->virtuemart_userinfo_id = null;

		return true;
	}
}
