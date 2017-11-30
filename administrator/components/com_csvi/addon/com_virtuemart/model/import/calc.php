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
 * Main processor for importing calculation rules.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelImportCalc extends RantaiImportEngine
{
	/**
	 * The calculation rule table
	 *
	 * @var    VirtuemartTableCalc
	 * @since  6.0
	 */
	private $calcTable = null;

	/**
	 * The category class
	 *
	 * @var    Com_virtuemartHelperCategory
	 * @since  6.0
	 */
	private $categoryModel = null;

	/**
	 * Category IDs
	 *
	 * @var    array
	 * @since  6.0
	 */
	private $categoryIds = array();

	/**
	 * Country IDs
	 *
	 * @var    array
	 * @since  6.0
	 */
	private $countryIds = array();

	/**
	 * Manufacturer IDs
	 *
	 * @var    array
	 * @since  6.0
	 */
	private $manufacturerIds = array();

	/**
	 * Shopper group IDs
	 *
	 * @var    array
	 * @since  6.0
	 */
	private $shoppergroupIds = array();

	/**
	 * State IDs
	 *
	 * @var    array
	 * @since  6.0
	 */
	private $stateIds = array();

	/**
	 * Here starts the processing.
	 *
	 * @return  bool  Always returns true.
	 *
	 * @since   3.0
	 */
	public function getStart()
	{
		$this->setState('virtuemart_vendor_id', $this->helper->getVendorId());

		foreach ($this->fields->getData() as $fields)
		{
			foreach ($fields as $name => $details)
			{
				$value = $details->value;

				// Check if the field needs extra treatment
				switch ($name)
				{
					case 'currency_code_3':
						$this->setState($name, strtoupper($value));
						break;
					default:
						$this->setState($name, $value);
						break;
				}
			}
		}

		// Reset loaded state
		$this->loaded = true;

		// Required fields are calc_kind, calc_value_mathop, calc_value
		if ($this->getState('calc_kind', false)
			&& $this->getState('calc_value_mathop', false)
			&& $this->getState('calc_value', false))
		{
			// Bind the values
			$this->calcTable->bind($this->state);

			// Check the currency
			if ($this->getState('currency_code_3', false))
			{
				$this->calcTable->calc_currency = $this->helper->getCurrencyId($this->currency_code_3, $this->virtuemart_vendor_id);
			}

			// Get the linked IDs
			$this->processCategorySelector();
			$this->processCountrySelector();
			$this->processShoppergroupSelector();
			$this->processStateSelector();
			$this->processManufacturerSelector();

			// Check which fields we need to check on
			$field_selectors = $this->template->get('field_selectors', array());

			foreach ($field_selectors as $selector)
			{
				switch ($selector)
				{
					case 'category':
						$this->calcTable->set('categoryIds', $this->categoryIds);
						break;
					case 'country':
						$this->calcTable->set('countryIds', $this->countryIds);
						break;
					case 'shoppergroup':
						$this->calcTable->set('shoppergroupIds', $this->shoppergroupIds);
						break;
					case 'state':
						$this->calcTable->set('stateIds', $this->stateIds);
						break;
					case 'manufacturer':
						$this->calcTable->set('manufacturerIds', $this->manufacturerIds);
						break;
				}
			}

			if ($this->calcTable->check())
			{
				$this->setState('virtuemart_calc_id', $this->calcTable->virtuemart_calc_id);

				// Check if we have an existing item
				if ($this->getState('virtuemart_calc_id', 0) > 0 && !$this->template->get('overwrite_existing_data', true))
				{
					$this->log->add(JText::sprintf('COM_CSVI_DATA_EXISTS_CONTENT', $this->getState('calc_value')));
					$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_CONTENT', $this->getState('calc_value')));
					$this->loaded = false;
				}
				else
				{
					// Load the current content data
					$this->calcTable->load();
					$this->loaded = true;
				}
			}
		}
		else
		{
			$this->loaded = false;

			$this->log->addStats('skipped', JText::_('COM_CSVI_MISSING_REQUIRED_FIELDS'));
		}

		return true;
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
			if (!$this->getState('virtuemart_calc_id', false) && $this->template->get('ignore_non_exist'))
			{
				// Do nothing for new rules when user chooses to ignore new rules
				$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_IGNORE_NEW', $this->getState('calc_value')));
			}
			else
			{
				// Set the modified date as we are modifying the product
				if (!$this->getState('modified_on', false))
				{
					$this->calcTable->modified_on = $this->date->toSql();
					$this->calcTable->modified_by = $this->userId;
				}

				if (!$this->getState('virtuemart_calc_id', false))
				{
					$this->calcTable->calc_shopper_published = $this->getState('calc_shopper_published', 1);
					$this->calcTable->calc_vendor_published = $this->getState('calc_vendor_published', 1);
					$this->calcTable->calc_params = $this->getState('calc_params', '');
					$this->calcTable->created_on = $this->date->toSql();
					$this->calcTable->created_by = $this->userId;
				}

				// Bind the data
				$this->calcTable->bind($this->state);

				// Store the data
				if ($this->calcTable->store())
				{
					// Process any categories
					$this->processCategories();

					// Process any countries
					$this->processCountries();

					// Process any manufacturers
					$this->processManufacturers();

					// Process any shoppergroups
					$this->processShoppergroups();

					// Process any states
					$this->processStates();
				}
				else
				{
					$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_CALC_NOT_ADDED', $this->calcTable->getError()));

					return false;
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
		$this->calcTable = $this->getTable('Calc');
	}

	/**
	 * Clear the necessary tables.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function clearTables()
	{
		$this->calcTable->reset();
		$this->categoryIds = array();
		$this->countryIds = array();
		$this->manufacturerIds = array();
		$this->shoppergroupIds = array();
		$this->stateIds = array();
	}

	/**
	 * Process the category selector to get the IDs to check on.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function processCategorySelector()
	{
		// Check if there are any categories
		$categoryPath = $this->getState('category_path', false);

		if ($categoryPath)
		{
			// Add any new categories
			if (is_null($this->categoryModel))
			{
				$this->categoryModel = new Com_virtuemartHelperCategory(
					$this->db,
					$this->template,
					$this->log,
					$this->csvihelper,
					$this->fields,
					$this->helper,
					$this->helperconfig,
					$this->userId
				);
			}

			$this->categoryModel->getStart();
			$categories = explode('|', $categoryPath);

			foreach ($categories as $category)
			{
				$catid = $this->categoryModel->getCategoryIdFromPath($category, $this->getState('virtuemart_vendor_id'));

				if (!empty($catid))
				{
					$this->categoryIds[] = (int) $catid['category_id'];
				}
			}

			// Remove any duplicate values
			$this->categoryIds = array_unique($this->categoryIds);
		}
	}

	/**
	 * Get the country selector IDs to check on.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function processCountrySelector()
	{
		$countryName = $this->getState('country_name', false);
		$country2Code = $this->getState('country_2_code', false);
		$country3Code = $this->getState('country_3_code', false);

		if ($countryName || $country2Code || $country3Code)
		{
			// Add any new countries
			if ($countryName)
			{
				$countries = explode('|', $countryName);
			}
			elseif ($country2Code)
			{
				$countries = explode('|', $country2Code);
			}
			elseif ($country3Code)
			{
				$countries = explode('|', $country3Code);
			}

			foreach ($countries as $country)
			{
				if ($countryName)
				{
					$cid = $this->helper->getCountryId($country);
				}
				elseif ($country2Code)
				{
					$cid = $this->helper->getCountryId(null, $country);
				}
				elseif ($country3Code)
				{
					$cid = $this->helper->getCountryId(null, null, $country);
				}

				if (!empty($cid))
				{
					$this->countryIds[] = (int) $cid;
				}
			}

			$this->countryIds = array_unique($this->countryIds);
		}
	}

	/**
	 * Get the shoppergroup selector IDs to check on.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function processShoppergroupSelector()
	{
		$shoppergroupName = $this->getState('shopper_group_name', false);

		if ($shoppergroupName)
		{
			// Add any new shoppergroups
			$shoppergroups = explode('|', $shoppergroupName);

			foreach ($shoppergroups as $shoppergroup)
			{
				$sid = $this->helper->getShopperGroupId($shoppergroup);

				if (!empty($sid))
				{
					$this->shoppergroupIds[] = (int) $sid;
				}
			}

			// Remove any duplicate values
			$this->shoppergroupIds = array_unique($this->shoppergroupIds);
		}
	}

	/**
	 * Get the state selector IDs to check on.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function processStateSelector()
	{
		$countryName = $this->getState('country_name', false);
		$country2Code = $this->getState('country_2_code', false);
		$country3Code = $this->getState('country_3_code', false);
		$stateName = $this->getState('state_name', false);
		$state2Code = $this->getState('state_2_code', false);
		$state3Code = $this->getState('state_3_code', false);
		$states = array();

		if (($countryName || $country2Code || $country3Code) && ($stateName || $state2Code || $state3Code))
		{
			// Find the country ID
			if ($countryName)
			{
				$cid = $this->helper->getCountryId($countryName);
			}
			elseif ($country2Code)
			{
				$cid = $this->helper->getCountryId(null, $country2Code);
			}
			elseif ($country3Code)
			{
				$cid = $this->helper->getCountryId(null, null, $country3Code);
			}

			// Add any new states
			if ($stateName)
			{
				$states = explode('|', $stateName);
			}
			elseif ($state2Code)
			{
				$states = explode('|', $state2Code);
			}
			elseif ($state3Code)
			{
				$states = explode('|', $state3Code);
			}

			foreach ($states as $state)
			{
				if ($stateName)
				{
					$sid = $this->helper->getstateId($state, null, null, $cid);
				}
				elseif ($state2Code)
				{
					$sid = $this->helper->getstateId(null, $state, null, $cid);
				}
				elseif ($state3Code)
				{
					$sid = $this->helper->getstateId(null, null, $state, $cid);
				}

				if (!empty($sid))
				{
					$this->stateIds[] = (int) $sid;
				}
			}

			$this->stateIds = array_unique($this->stateIds);
		}
	}

	/**
	 * Get the manufacturer selector IDs to check on.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function processManufacturerSelector()
	{
		$manufacturerName = $this->getState('manufacturer_name', false);

		if ($manufacturerName)
		{
			// Add any new manufacturers
			$manufacturers = explode('|', $manufacturerName);

			foreach ($manufacturers as $manufacturer)
			{
				$mid = $this->helper->getManufacturerId($manufacturer);

				if (!empty($mid))
				{
					$this->manufacturerIds[] = (int) $mid;
				}
			}

			$this->manufacturerIds = array_unique($this->manufacturerIds);
		}
	}

	/**
	 * Process any categories to be applied to the calculation rule.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function processCategories()
	{
		if (!empty($this->categoryIds))
		{
			// Remove any existing categories for the calc rule
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName('#__virtuemart_calc_categories'))
				->where($this->db->quoteName('virtuemart_calc_id') . ' = ' . (int) $this->calcTable->virtuemart_calc_id);
			$this->db->setQuery($query)->execute();

			$query->clear()
				->insert($this->db->quoteName('#__virtuemart_calc_categories'));

			foreach ($this->categoryIds as $catid)
			{
				$query->values('null, ' . (int) $this->calcTable->get('virtuemart_calc_id') . ', ' . (int) $catid);
			}

			$this->db->setQuery($query)->execute();

			// Store the debug message
			$this->log->add('Add calculation category');
		}
	}

	/**
	 * Process any countries to be applied to the calculation rule.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function processCountries()
	{
		if (!empty($this->countryIds))
		{
			// Remove any existing countries for the calc rule
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName('#__virtuemart_calc_countries'))
				->where($this->db->quoteName('virtuemart_calc_id') . ' = ' . (int) $this->calcTable->virtuemart_calc_id);
			$this->db->setQuery($query)->execute();

			$query->clear()
				->insert($this->db->quoteName('#__virtuemart_calc_countries'));

			foreach ($this->countryIds as $cid)
			{
				$query->values('null, ' . (int) $this->calcTable->virtuemart_calc_id . ', ' . $cid);
			}

			$this->db->setQuery($query)->execute();

			// Store the debug message
			$this->log->add('Add calculation country');
		}
	}

	/**
	 * Process any shopper groups to be applied to the calculation rule.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function processShoppergroups()
	{
		if (!empty($this->shoppergroupIds))
		{
			// Remove any existing countries for the calc rule
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName('#__virtuemart_calc_shoppergroups'))
				->where($this->db->quoteName('virtuemart_calc_id') . ' = ' . (int) $this->calcTable->virtuemart_calc_id);
			$this->db->setQuery($query)->execute();

			// Add any new shoppergroups
			$query->clear()
				->insert($this->db->quoteName('#__virtuemart_calc_shoppergroups'));

			foreach ($this->shoppergroupIds as $shoppergroup)
			{
				$query->values('null, ' . (int) $this->calcTable->virtuemart_calc_id . ', ' . (int) $shoppergroup);
			}

			$this->db->setQuery($query)->execute();

			// Store the debug message
			$this->log->add('Add calculation shoppergroup');
		}
	}

	/**
	 * Process any states to be applied to the calculation rule.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function processStates()
	{
		if (!empty($this->stateIds))
		{
			// Remove any existing states for the calc rule
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName('#__virtuemart_calc_states'))
				->where($this->db->quoteName('virtuemart_calc_id') . ' = ' . (int) $this->calcTable->virtuemart_calc_id);
			$this->db->setQuery($query)->execute();

			$query->clear()
				->insert($this->db->quoteName('#__virtuemart_calc_states'));

			foreach ($this->stateIds as $state)
			{
				$query->values('null, ' . (int) $this->calcTable->virtuemart_calc_id . ', ' . (int) $state);
			}

			$this->db->setQuery($query)->execute();

			// Store the debug message
			$this->log->add('Add calculation state');
		}
	}

	/**
	 * Process any shopper groups to be applied to the calculation rule.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function processManufacturers()
	{
		if (!empty($this->manufacturerIds))
		{
			// Remove any existing manufacturers for the calc rule
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName('#__virtuemart_calc_manufacturers'))
				->where($this->db->quoteName('virtuemart_calc_id') . ' = ' . (int) $this->calcTable->virtuemart_calc_id);
			$this->db->setQuery($query)->execute();

			$query->clear()
				->insert($this->db->quoteName('#__virtuemart_calc_manufacturers'));

			foreach ($this->manufacturerIds as $manufacturer)
			{
				$query->values('null, ' . (int) $this->calcTable->virtuemart_calc_id . ', ' . (int) $manufacturer);
			}

			$this->db->setQuery($query)->execute();

			// Store the debug message
			$this->log->add('Add calculation manufacturer');
		}
	}
}
