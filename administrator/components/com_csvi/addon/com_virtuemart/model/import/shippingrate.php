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
 * Shipping rate import.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelImportShippingrate extends RantaiImportEngine
{
	/**
	 * Shipping method table.
	 *
	 * @var    VirtueMartTableShipmentmethod
	 * @since  6.0
	 */
	private $shipmentmethodTable = null;

	/**
	 * Shipping method language table.
	 *
	 * @var    VirtueMartTableShipmentmethodLang
	 * @since  6.0
	 */
	private $shipmentmethodLangTable = null;

	/**
	 * Shipping method shopper group table.
	 *
	 * @var    VirtueMartTableShipmentmethodShoppergroup
	 * @since  6.0
	 */
	private $shipmentmethodShoppergroupTable = null;

	/**
	 * Set to true if the language tables exist
	 *
	 * @var    bool
	 * @since  6.0
	 */
	private $tablesexist = true;

	/**
	 * Start the product import process.
	 *
	 * @return  bool  True on success | false on failure.
	 *
	 * @since   6.0
	 */
	public function getStart()
	{
		// Only continue if all tables exist
		if ($this->tablesexist)
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

							$this->setState($name, $value);
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
			if ($this->getState('shipment_name', false))
			{
				// Bind the values
				$this->shipmentmethodLangTable->bind($this->state);

				if ($this->shipmentmethodLangTable->check())
				{
					$this->setState('virtuemart_shipmentmethod_id', $this->shipmentmethodLangTable->virtuemart_shipmentmethod_id);

					// Check if we have an existing item
					if ($this->getState('virtuemart_shipmentmethod_id', 0) > 0 && !$this->template->get('overwrite_existing_data', true))
					{
						$this->log->add(JText::sprintf('COM_CSVI_DATA_EXISTS_CONTENT', $this->getState('shipment_name')));
						$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_CONTENT', $this->getState('shipment_name')));
						$this->loaded = false;
					}
					else
					{
						// Load the current content data
						$this->shipmentmethodTable->load($this->getState('virtuemart_shipmentmethod_id'));
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
			// Get the needed data
			$virtuemart_shipmentmethod_id = $this->getState('virtuemart_shipmentmethod_id', false);
			$shippingrate_delete = $this->getState('shippingrate_delete', 'N');

			// Check if we need to delete the manufacturer
			if ($virtuemart_shipmentmethod_id && $shippingrate_delete == 'Y')
			{
				$this->deleteShipmentmethod();
			}
			elseif (!$virtuemart_shipmentmethod_id && $shippingrate_delete == 'Y')
			{
				$this->log->addStats('skipped', JText::sprintf('COM_CSVI_NO_SHIPPINGRATE_ID_NO_DELETE', $this->getState('shipment_name')));
			}
			elseif (!$virtuemart_shipmentmethod_id && $this->template->get('ignore_non_exist'))
			{
				// Do nothing for new products when user chooses to ignore new products
				$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_IGNORE_NEW', $this->getState('shipment_name')));
			}
			else
			{
				// Combine all the values if needed
				$params = array();
				$params[] = 'shipment_logos';
				$params[] = 'show_on_pdetails';
				$params[] = 'countries';
				$params[] = 'zip_start';
				$params[] = 'zip_stop';
				$params[] = 'weight_start';
				$params[] = 'weight_stop';
				$params[] = 'weight_unit';
				$params[] = 'nbproducts_start';
				$params[] = 'nbproducts_stop';
				$params[] = 'orderamount_start';
				$params[] = 'orderamount_stop';
				$params[] = 'shipment_cost';
				$params[] = 'package_fee';
				$params[] = 'tax_id';
				$params[] = 'tax';
				$params[] = 'free_shipment';

				if (!$this->getState('shipment_params', false))
				{
					$shipment_params = '';

					foreach ($params as $param)
					{
						$value = $this->getState($param, false);
						$part = '';

						switch ($param)
						{
							case 'shipment_logos':
								if ($value)
								{
									$part = $param . '=' . json_encode(explode(',', $value)) . '|';
								}

								$shipment_params .= $part;
								break;
							case 'countries':
								if ($value)
								{
									// Retrieve the country ID
									$countries = explode(',', $value);
									$country_ids = array();

									foreach ($countries as $country)
									{
										$result = $this->helper->getCountryId($country);

										if (!empty($result))
										{
											$country_ids[] = $result;
										}
									}

									if (empty($country_ids))
									{
										$country_ids = '';
									}

									$part = $param . '=' . json_encode($country_ids) . '|';
								}

								$shipment_params .= $part;

								break;
							case 'tax_id':
								if ($value)
								{
									$part = $param . '="' . $value . '"|';
								}
								break;
							case 'tax':
								if ($value)
								{
									// Retrieve the calc ID
									switch ($value)
									{
										case 'norule':
											$result = -1;
											break;
										case 'default':
											$result = 0;
											break;
										default:
											$query = $this->db->getQuery(true)
												->select($this->db->quoteName('virtuemart_calc_id'))
												->from($this->db->quoteName('#__virtuemart_calcs'))
												->where($this->db->quoteName('calc_name') . ' = ' . $this->db->quote($value));
											$this->db->setQuery($query);
											$result = $this->db->loadResult();
											break;
									}

									$part = 'tax_id="' . $result . '"|';
								}
								break;
							default:
								if ($value)
								{
									$part = $param . '="' . $value . '"|';
								}
								break;
						}

						$shipment_params .= $part;
					}

					if (empty($shipment_params))
					{
						$shipment_params = null;
					}

					$this->setState('shipment_params', $shipment_params);
				}

				$this->log->add('Params: ' . $this->getState('shipment_params'));

				// Check for the plugin ID
				if (!$this->getState('shipment_jplugin_id', false) && $this->template->get('vmshipment', false))
				{
					$this->setState('shipment_jplugin_id', $this->template->get('vmshipment'));
				}
				elseif ($this->getState('shipment_element'))
				{
					// Load the plugin ID based on element name
					$query = $this->db->getQuery(true);
					$query->select($this->db->quoteName('extension_id'))
						->from($this->db->quoteName('#__extensions'))
						->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
						->where($this->db->quoteName('element') . ' = ' . $this->db->quote($this->getState('shipment_element')))
						->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('vmshipment'));
					$this->db->setQuery($query);
					$this->setState('shipment_jplugin_id', $this->db->loadResult());
				}

				if ($this->getState('shipment_jplugin_id'))
				{
					// Check if we have the shipment element
					if (!$this->getState('shipment_element', false))
					{
						// Load the plugin name based on element ID
						$query = $this->db->getQuery(true);
						$query->select($this->db->quoteName('element'))
							->from($this->db->quoteName('#__extensions'))
							->where($this->db->quoteName('extension_id') . ' = ' . (int) $this->getState('shipment_jplugin_id'));
						$this->db->setQuery($query);
						$this->setState('shipment_element', $this->db->loadResult());
					}

					// Bind the data
					$this->shipmentmethodTable->bind($this->state);

					// Set the modified date as we are modifying the product
					if (!$this->getState('modified_on', false))
					{
						$this->shipmentmethodTable->modified_on = $this->date->toSql();
						$this->shipmentmethodTable->modified_by = $this->userId;
					}

					// Add a creating date if there is no product_id
					if (!$this->getState('virtuemart_shipmentmethod_id', false))
					{
						$this->shipmentmethodTable->created_on = $this->date->toSql();
						$this->shipmentmethodTable->created_by = $this->userId;
					}

					// Store the data
					if ($this->shipmentmethodTable->store())
					{
						$this->setState('virtuemart_shipmentmethod_id', $this->shipmentmethodTable->virtuemart_shipmentmethod_id);

						// Check if there is a source translation
						if ($this->getState('shipment_name_trans', false))
						{
							$this->setState('shipment_name', $this->getState('shipment_name_trans'));
						}

						// Store the language fields
						$this->shipmentmethodLangTable->bind($this->state);

						if (!$this->shipmentmethodLangTable->store())
						{
							$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_SHIPMENTMETHOD_LANG_NOT_ADDED', $this->shipmentmethodLangTable->getError()));

							return false;
						}

						// Process any shopper groups
						if ($this->getState('shopper_group_name', false))
						{
							// Delete all existing groups
							$this->shipmentmethodShoppergroupTable->deleteOldGroups($this->getState('virtuemart_shipmentmethod_id'));

							// Add new groups
							$this->shipmentmethodShoppergroupTable->virtuemart_shipmentmethod_id = $this->getState('virtuemart_shipmentmethod_id');
							$shoppergroups = explode('|', $this->getState('shopper_group_name'));

							foreach ($shoppergroups as $group)
							{
								if ($groupId = $this->helper->getShopperGroupId($group))
								{
									$this->shipmentmethodShoppergroupTable->virtuemart_shoppergroup_id = $groupId;
									$this->shipmentmethodShoppergroupTable->store();
									$this->shipmentmethodShoppergroupTable->id = null;
								}
							}
						}
					}
					else
					{
						$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_SHIPMENTMETHOD_NOT_ADDED', $this->shipmentmethodTable->getError()));
					}
				}
				else
				{
					$this->log->addStats('incorrect', 'COM_CSVI_SHIPMENTMETHOD_NO_PLUGIN_FOUND');
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
		$this->shipmentmethodTable = $this->getTable('Shipmentmethod');

		// Check if the language tables exist
		$tables = $this->db->getTableList();

		if ($this->template->get('language') == $this->template->get('target_language'))
		{
			$lang = $this->template->get('language');
		}
		else
		{
			$lang = $this->template->get('target_language', 'general');
		}

		if (!in_array($this->db->getPrefix() . 'virtuemart_shipmentmethods_' . $lang, $tables))
		{
			$this->tablesexist = false;
		}
		else
		{
			$this->shipmentmethodLangTable = $this->getTable('ShipmentmethodLang');
		}

		$this->shipmentmethodShoppergroupTable = $this->getTable('ShipmentmethodShoppergroup');
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
		$this->shipmentmethodTable->reset();
		$this->shipmentmethodLangTable->reset();
		$this->shipmentmethodShoppergroupTable->reset();
	}

	/**
	 * Delete a manufacturer and its references.
	 *
	 * @return  void.
	 *
	 * @since   4.0
	 */
	private function deleteShipmentmethod()
	{
		if ($this->getState('virtuemart_shipmentmethod_id'))
		{
			// Delete translations
			$languages = $this->csvihelper->getLanguages();

			foreach ($languages as $language)
			{
				$query = $this->db->getQuery(true)
					->delete($this->db->quoteName('#__virtuemart_shipmentmethods_' . strtolower(str_replace('-', '_', $language->lang_code))))
					->where($this->db->quoteName('virtuemart_shipmentmethod_id') . ' = ' . (int) $this->getState('virtuemart_shipmentmethod_id'));
				$this->db->setQuery($query);
				$this->log->add('COM_CSVI_DEBUG_DELETE_SHIPMENTMETHOD_LANG_XREF');
				$this->db->execute();
			}

			// Delete the shoppergroups
			$this->shipmentmethodShoppergroupTable->deleteOldGroups($this->getState('virtuemart_shipmentmethod_id'));

			// Delete shipmentmethod
			if (!$this->shipmentmethodTable->delete($this->getState('virtuemart_shipmentmethod_id')))
			{
				$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_SHIPMENTMETHOD_NOT_DELETED', $this->shipmentmethodTable->getError()));
			}
		}
		else
		{
			$this->log->addStats('incorrect', 'COM_CSVI_SHIPMENTMETHOD_NOT_DELETED_NO_ID');
		}
	}
}
