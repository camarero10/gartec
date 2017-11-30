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
 * Category cross reference table.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class VirtueMartTableCategoryXref extends CsviTableDefault
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
	public function __construct($table, $key, &$db, $config)
	{
		parent::__construct('#__virtuemart_category_categories', 'category_parent_id', $db, $config);
	}

	/**
	 * Stores a category relation
	 *
	 * The product relation is always inserted.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  bool  True if relation is stored | False if relation is not stored.
	 *
	 * @since   4.0
	 */
	public function store($updateNulls = false)
	{
		$k = $this->check();

		if ($k)
		{
			$result = $this->db->updateObject($this->_tbl, $this, $this->_tbl_key, false);
		}
		else
		{
			$result = $this->db->insertObject($this->_tbl, $this, $this->_tbl_key);
		}

		$area = $this->getArea();

		if (!$result)
		{
			$this->log->add(JText::sprintf('COM_CSVI_NOT_ADDED', $this->getError()), true, 'INCORRECT', $area);
		}
		else
		{
			if ($this->queryResult() == 'UPDATE')
			{
				$this->log->add('COM_CSVI_TABLE_' . get_called_class() . '_UPDATED', true, 'UPDATE', $area);
			}
			else
			{
				$this->log->add('COM_CSVI_TABLE_' . get_called_class() . '_ADDED', true, 'ADD', $area);
			}
		}

		return $result;
	}

	/**
	 * Check if a relation already exists.
	 *
	 * @return  bool  True if relation exists | False if relation does not exist.
	 *
	 * @since   4.0
	 */
	public function check()
	{
		$query = $this->db->getQuery(true)
			->select('COUNT(' . $this->db->quoteName($this->_tbl_key) . ') AS ' . $this->db->quoteName('total'))
			->from($this->db->quoteName($this->_tbl))
			->where($this->db->quoteName('category_parent_id') . ' = ' . (int) $this->get('category_parent_id'))
			->where($this->db->quoteName('category_child_id') . ' = ' . (int) $this->get('category_child_id'));
		$this->db->setQuery($query);
		$result = $this->db->loadResult();

		if ($result > 0)
		{
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
	 * @return  array  The field option objects.
	 *
	 * @since   6.0
	 */
	public function reset()
	{
		parent::reset();

		// Empty the primary key
		$this->set('category_parent_id', null);

		return true;
	}
}
