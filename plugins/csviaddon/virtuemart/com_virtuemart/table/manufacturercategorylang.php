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
 * Manufacturer category language.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class VirtueMartTableManufacturerCategoryLang extends CsviTableDefault
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
		if (isset($config['template']))
		{
			$this->template = $config['template'];
		}

		if ($this->template->get('operation') == 'manufacturercategory')
		{
			if ($this->template->get('language') == $this->template->get('target_language'))
			{
				$lang = $this->template->get('language');
			}
			else
			{
				$lang = $this->template->get('target_language');
			}
		}
		else
		{
			$lang = $this->template->get('language');
		}

		parent::__construct(
			'#__virtuemart_manufacturercategories_' . $lang, 'virtuemart_manufacturercategories_id', $db, $config
		);
	}

	/**
	 * Check if the manufacturer category exists.
	 *
	 * @param   bool  $create  Set true if a dummy entry needs to be added.
	 *
	 * @return  bool  True if manufacturer category exists | False if manufacturer category does not exist.
	 *
	 * @since   4.0
	 */
	public function check($create = true)
	{
		if (!empty($this->virtuemart_manufacturercategories_id))
		{
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName($this->_tbl_key))
				->from($this->db->quoteName($this->_tbl))
				->where($this->db->quoteName($this->_tbl_key) . ' = ' . (int) $this->virtuemart_manufacturercategories_id);
			$this->db->setQuery($query);
			$this->log->add('COM_CSVI_LOAD_PRIMARY_KEY');
			$id = $this->db->loadResult();

			if ($id > 0)
			{
				$this->log->add('COM_CSVI_DEBUG_MANUFACTURERCATEGORY_EXISTS');

				return true;
			}
			else
			{
				if ($create)
				{
					// Create a dummy entry for updating
					$query = "INSERT IGNORE INTO "
						. $this->_tbl . " (" . $this->db->quoteName($this->_tbl_key) . ") "
						. "VALUES (" . (int) $this->virtuemart_manufacturercategories_id . ")";
					$this->db->setQuery($query);

					if ($this->db->execute())
					{
						return true;
					}
					else
					{
						$this->log->add('COM_CSVI_DEBUG_MANUFACTURERCATEGORY_NOT_EXISTS', true);

						return false;
					}
				}
				else
				{
					return false;
				}
			}
		}
		else
		{
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName($this->_tbl_key))
				->from($this->db->quoteName($this->_tbl))
				->where($this->db->quoteName('mf_category_name') . ' = ' . $this->db->quote($this->mf_category_name));
			$this->db->setQuery($query);
			$id = $this->db->loadResult();

			if ($id > 0)
			{
				$this->log->add('COM_CSVIVIRTUEMART_DEBUG_MANUFACTURERCATEGORY_EXISTS', true);
				$this->virtuemart_manufacturercategories_id = $id;

				return true;
			}
			else
			{
				if ($create)
				{
					// Create a dummy entry for updating
					$query = "INSERT IGNORE INTO "
						. $this->_tbl . " (" . $this->db->quoteName($this->_tbl_key) . ") "
						. "VALUES (" . (int) $this->virtuemart_manufacturercategories_id . ")";
					$this->db->setQuery($query);

					if ($this->db->execute())
					{
						$this->virtuemart_manufacturercategories_id = $this->db->insertid();

						return true;
					}
					else
					{
						$this->log->add('COM_CSVI_DEBUG_MANUFACTURERCATEGORY_NOT_EXISTS', true);

						return false;
					}
				}
				else
				{
					$this->log->add('COM_CSVI_DEBUG_MANUFACTURERCATEGORY_NOT_EXISTS', true);

					return false;
				}
			}
		}
	}

	/**
	 * Create a slug if needed and store the product.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  bool  True on success | False on failure.
	 *
	 * @since   4.0
	 */
	public function store($updateNulls = false)
	{
		if (empty($$this->_tbl_key))
		{
			// Create the slug
			$this->slug = $this->helper->createSlug($this->mf_category_name);
		}

		return parent::store();
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
		$this->virtuemart_manufacturercategories_id = null;

		return true;
	}
}
