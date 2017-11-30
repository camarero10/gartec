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
 * VirtueMart Manufacturer table.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class VirtueMartTableManufacturer extends CsviTableDefault
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
		parent::__construct('#__virtuemart_manufacturers', 'virtuemart_manufacturer_id', $db, $config);
	}

	/**
	 * Check if the manufacturer exists.
	 *
	 * @return  bool  True if manufacturer exists | False if manufacturer does not exist.
	 *
	 * @since   4.0
	 */
	public function check()
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName($this->_tbl_key))
			->from($this->db->quoteName($this->_tbl))
			->where($this->db->quoteName($this->_tbl_key) . ' = ' . (int) $this->get('virtuemart_manufacturer_id'));
		$this->db->setQuery($query);
		$id = $this->db->loadResult();

		if ($id > 0)
		{
			$this->log->add(JText::_('Manufacturer exists'));

			return true;
		}
		elseif (!$this->template->get('ignore_non_exist', false))
		{
			// Find the default category
			$query = $this->db->getQuery(true)
				->select('MIN(' . ($this->db->quoteName('virtuemart_manufacturercategories_id') . ')'))
				->from($this->db->quoteName('#__virtuemart_manufacturercategories'))
				->where($this->db->quoteName('published') . ' = 1');
			$this->db->setQuery($query);
			$this->set('virtuemart_manufacturercategories_id', $this->db->loadResult());

			// Create a dummy entry for updating
			$query->insert($this->db->quoteName($this->_tbl))
				->columns(array($this->_tbl_key . ',' . $this->db->quoteName('virtuemart_manufacturercategories_id')))
				->values((int) $this->get('virtuemart_manufacturer_id') . ',' . (int) $this->get('virtuemart_manufacturercategories_id'));
			$this->db->setQuery($query);

			if ($this->db->execute())
			{
				$this->set('virtuemart_manufacturer_id', $this->db->insertid());
			}
			else
			{
				$this->log->add(JText::_('Manufacturer does not exist'));
			}

			return false;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Reset the primary key.
	 *
	 * @return  bool  Always returns true.
	 *
	 * @since   6.0
	 */
	public function reset()
	{
		parent::reset();

		// Empty the primary key
		$this->set('virtuemart_manufacturer_id', null);

		return true;
	}
}
