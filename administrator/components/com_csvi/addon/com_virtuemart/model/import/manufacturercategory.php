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
 * Manufacturer category import.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelImportManufacturercategory extends RantaiImportEngine
{
	/**
	 * Manufacturer category table.
	 *
	 * @var    VirtueMartTableManufacturerCategory
	 * @since  6.0
	 */
	private $manufacturerCategoryTable = null;

	/**
	 * Manufacturer category language table.
	 *
	 * @var    VirtueMartTableManufacturerCategoryLang
	 * @since  6.0
	 */
	private $manufacturerCategoryLangTable = null;

	/**
	 * Set if language tables exist
	 *
	 * @var    bool
	 * @since  6.0
	 */
	private $tablesExist = true;

	/**
	 * Here starts the processing.
	 *
	 * @return  bool  Akways returns true.
	 *
	 * @since   3.0
	 */
	public function getStart()
	{
		// Only continue if all tables exist
		if ($this->tablesExist)
		{
			// Process data
			foreach ($this->fields->getData() as $fields)
			{
				foreach ($fields as $name => $details)
				{
					$value = $details->value;

					switch ($name)
					{
						case 'published':
							switch ($value)
							{
								case 'n':
								case 'N':
								case '0':
									$value = 0;
									break;
								default:
									$value = 1;
									break;
							}

							$this->setState('published', $value);
							break;
						case 'mf_category_delete':
							$this->setState('mf_category_delete', strtoupper($value));
							break;
						default:
							$this->setState($name, $value);
							break;
					}
				}
			}

			// Reset loaded state
			$this->loaded = true;

			// If we have no manufacturer category name we cannot continue
			$mf_category_name = $this->getState('mf_category_name', false);

			if ($mf_category_name)
			{
				if ($this->getManufacturerCategoryId())
				{
					// Check if we have an existing item
					if ($this->getState('virtuemart_manufacturercategories_id', 0) > 0 && !$this->template->get('overwrite_existing_data', true))
					{
						$this->log->add(JText::sprintf('COM_CSVI_DATA_EXISTS_MANUFACTURERCATEGORY', $mf_category_name));
						$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_MANUFACTURERCATEGORY', $mf_category_name));
						$this->loaded = false;
					}
					else
					{
						// Load the current manufacturer data
						$this->manufacturerCategoryTable->load($this->getState('virtuemart_manufacturercategories_id', 0));
						$this->loaded = true;
					}
				}
			}
			else
			{
				$this->loaded = false;

				$this->log->addStats('skipped', JText::_('COM_CSVI_NO_MANUFACTURERCATEGORY_PATH_SET'));
			}

			return true;
		}
		else
		{
			$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_LANG_TABLE_NOT_EXIST', $this->template->get('language')));

			return false;
		}
	}

	/**
	 * Process a record.
	 *
	 * @return  bool  Returns true if all is OK | Returns false if no product SKU or product ID can be found.
	 *
	 * @since   6.0
	 */
	public function getProcessRecord()
	{
		if ($this->loaded)
		{
			if (!$this->getState('virtuemart_manufacturercategories_id', false) && $this->template->get('ignore_non_exist'))
			{
				// Do nothing for new coupons when user chooses to ignore new coupons
				$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_IGNORE_NEW', $this->getState('mf_category_name')));
			}
			else
			{
				// User wants to delete the manufacturer category
				if ($this->getState('mf_category_delete', 'N') == "Y")
				{
					$this->deleteManufacturerCategory();
				}
				// User wants to add or update the manufacturer category
				else
				{
					// Set the modified date as we are modifying the product
					if (!$this->getState('modified_on', false))
					{
						$this->manufacturerCategoryTable->modified_on = $this->date->toSql();
						$this->manufacturerCategoryTable->modified_by = $this->userId;
					}

					// Add a creating date if there is no product_id
					if ($this->getState('virtuemart_manufacturercategories_id', false) == 0)
					{
						$this->manufacturerCategoryTable->created_on = $this->date->toSql();
						$this->manufacturerCategoryTable->created_by = $this->userId;
					}

					// Bind the data
					$this->manufacturerCategoryTable->bind($this->state);


					if ($this->manufacturerCategoryTable->store())
					{
						$this->setState('virtuemart_manufacturercategories_id', $this->manufacturerCategoryTable->virtuemart_manufacturercategories_id);

						// Store the language fields
						$this->manufacturerCategoryLangTable->load($this->virtuemart_manufacturercategories_id);
						$this->manufacturerCategoryLangTable->bind($this->state);

						// Check and store the language data
						if ($this->manufacturerCategoryLangTable->check())
						{
							if ($this->getState('mf_category_name_trans'))
							{
								$this->manufacturerCategoryLangTable->mf_category_name = $this->getState('mf_category_name_trans');
							}

							if (!$this->manufacturerCategoryLangTable->store())
							{
								$this->log->addStats(
									'incorrect',
									JText::sprintf('COM_CSVI_MANUFACTURERCATEGORY_LANG_NOT_ADDED', $this->manufacturerCategoryLangTable->getError())
								);

								return false;
							}
						}
						else
						{
							$this->log->addStats(
								'incorrect',
								JText::sprintf('COM_CSVI_MANUFACTURERCATEGORY_LANG_NOT_ADDED', $this->manufacturerCategoryLangTable->getError())
							);

							return false;
						}
					}
					else
					{
						$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_MANUFACTURER_CATEGORY_NOT_ADDED', $this->manufacturerCategoryTable->getError()));
					}
				}
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Load the necessary tables.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function loadTables()
	{
		$this->manufacturerCategoryTable = $this->getTable('ManufacturerCategory');

		// Check if the language tables exist
		$tables = $this->db->getTableList();

		if ($this->template->get('language') == $this->template->get('target_language'))
		{
			$lang = $this->template->get('language');
		}
		else
		{
			$lang = $this->template->get('target_language');
		}


		if (!in_array($this->db->getPrefix() . 'virtuemart_manufacturercategories_' . $lang, $tables))
		{
			$this->tablesExist = false;
		}
		else
		{
			$this->tablesExist = true;
			$this->manufacturerCategoryLangTable = $this->getTable('ManufacturerCategoryLang');
		}
	}

	/**
	 * Clear the loaded tables.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function clearTables()
	{
		$this->manufacturerCategoryTable->reset();
		$this->manufacturerCategoryLangTable->reset();
	}

	/**
	 * Get the manufacturer category ID.
	 *
	 * @return  bool  True if manufacturer ID is found | False if manufacturer ID is not found.
	 *
	 * @since   3.0
	 */
	private function getManufacturerCategoryId()
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('virtuemart_manufacturercategories_id'))
			->from($this->db->quoteName('#__virtuemart_manufacturercategories_' . $this->template->get('language')))
			->where($this->db->quoteName('mf_category_name') . ' = ' . $this->db->quote($this->getState('mf_category_name')));
		$this->db->setQuery($query);
		$this->log->add('COM_CSVI_CHECK_MANUFACTURERCATEGORY_EXISTS', true);
		$id = $this->db->loadResult();

		if ($id)
		{
			$this->setState('virtuemart_manufacturercategories_id', $id);

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Delete a manufacturer category and its references.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function deleteManufacturerCategory()
	{
		$virtuemart_manufacturercategories_id = $this->getState('virtuemart_manufacturercategories_id', false);

		if ($virtuemart_manufacturercategories_id)
		{
			// Delete manufacturer category xref
			$query = $this->db->getQuery(true)
				->update($this->db->quoteName('#__virtuemart_manufacturers'))
				->set($this->db->quoteName('virtuemart_manufacturercategories_id') . ' = 0')
				->where($this->db->quoteName('virtuemart_manufacturercategories_id') . ' = ' . (int) $virtuemart_manufacturercategories_id);
			$this->db->setQuery($query);

			if ($this->db->execute())
			{
				$this->log->addStats('updated', 'Updated manufacturer categories ID reference');
			}
			else
			{
				$this->log->addStats('incorrect', 'Error updating manufacturer categories ID reference: ' . $this->db->getErrorMsg());
			}

			// Delete translations
			$languages = $this->csvihelper->getLanguages();

			foreach ($languages as $language)
			{
				$query = $this->db->getQuery(true)
					->delete($this->db->quoteName('#__virtuemart_manufacturercategories_' . strtolower(str_replace('-', '_', $language->lang_code))))
					->where($this->db->quoteName('virtuemart_manufacturercategories_id') . ' = ' . (int) $virtuemart_manufacturercategories_id);
				$this->db->setQuery($query);
				$this->log->add('COM_CSVI_DEBUG_DELETE_MANUFACTURER_LANG_XREF', true);
				$this->db->execute();
			}

			// Delete manufacturer
			if ($this->manufacturerCategoryTable->delete($virtuemart_manufacturercategories_id))
			{
				$this->log->add('COM_CSVI_DELETE_MANUFACTURER_CATEGORY', true);
				$this->log->addStats('deleted', 'COM_CSVI_MANUFACTURER_CAT_DELETED');
			}
			else
			{
				$this->log->addStats('error', JText::sprintf('COM_CSVI_MANUFACTURER_CAT_NOT_DELETED', $this->manufacturerCategoryTable->getError()));
			}
		}
		else
		{
			$this->log->addStats('incorrect', 'COM_CSVI_MANUFACTURERCATEGORY_NOT_DELETED_NO_ID');
		}
	}
}
