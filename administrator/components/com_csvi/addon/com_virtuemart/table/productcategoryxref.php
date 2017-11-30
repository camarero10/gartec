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
 * Product category cross reference table.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class VirtueMartTableProductcategoryxref extends CsviTableDefault
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
		parent::__construct('#__virtuemart_product_categories', 'virtuemart_product_id', $db, $config);
	}

	/**
	 * Stores a product category relation
	 *
	 * The product category relation is always inserted.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  bool  Returns true.
	 *
	 * @since   6.0
	 */
	public function store($updateNulls = false)
	{
		if (!$this->checkDuplicate())
		{
			$result = $this->db->insertObject($this->_tbl, $this);

			$this->log->add('COM_CSVI_ADD_NEW_CATEGORY_REFERENCES', true);

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
		else
		{
			return true;
		}
	}

	/**
	 * Check if the entry already exists.
	 *
	 * @return  bool  True if entry exists | False if entry does not exist.
	 *
	 * @since   6.0
	 */
	private function checkDuplicate()
	{
		$query = $this->db->getQuery(true)
			->select('COUNT(*) AS total')
			->from($this->db->quoteName($this->_tbl))
			->where($this->db->quoteName('virtuemart_product_id') . '  = ' . (int) $this->virtuemart_product_id)
			->where($this->db->quoteName('virtuemart_category_id') . '  = ' . (int) $this->virtuemart_category_id);
		$this->db->setQuery($query);
		$this->log->add('Check if a category reference already exists');
		$total = $this->db->loadResult();

		if ($total > 0)
		{
			$this->log->add('Category reference already exists', false);

			return true;
		}
		else
		{
			$this->log->add('Category reference does not exist yet', false);

			return false;
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
		$this->virtuemart_product_id = null;

		return true;
	}
}
