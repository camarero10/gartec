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

require_once JPATH_ADMINISTRATOR . '/components/com_csvi/models/exports.php';

/**
 * Export VirtueMart shipping rates.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelExportShippingrate extends CsviModelExports
{
	/**
	 * Export the data.
	 *
	 * @return  bool  True if body is exported | False if body is not exported.
	 *
	 * @since   6.0
	 *
	 * @throws  CsviException
	 */
	protected function exportBody()
	{
		if (parent::exportBody())
		{
			// Check if a language is set
			$language  = $this->template->get('language');

			if (!$language)
			{
				throw new CsviException(JText::_('COM_CSVI_NO_LANGUAGE_SET'));
			}

			// Build something fancy to only get the fieldnames the user wants
			$userfields = array();
			$exportfields = $this->fields->getFields();

			// Group by fields
			$groupbyfields = json_decode($this->template->get('groupbyfields', '', 'string'));
			$groupby = array();

			if (isset($groupbyfields->name))
			{
				$groupbyfields = array_flip($groupbyfields->name);
			}
			else
			{
				$groupbyfields = array();
			}

			// Sort selected fields
			$sortfields = json_decode($this->template->get('sortfields', '', 'string'));
			$sortby = array();

			if (isset($sortfields->name))
			{
				$sortbyfields = array_flip($sortfields->name);
			}
			else
			{
				$sortbyfields = array();
			}

			foreach ($exportfields as $field)
			{
				switch ($field->field_name)
				{
					case 'virtuemart_shipmentmethod_id':
					case 'shipment_name':
					case 'shipment_desc':
					case 'custom':
					case 'slug':
					case 'shopper_group_name':
						$userfields[] = $this->db->quoteName('#__virtuemart_shipmentmethods.virtuemart_shipmentmethod_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_shipmentmethods.virtuemart_shipmentmethod_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_shipmentmethods.virtuemart_shipmentmethod_id');
						}
						break;
					case 'shipment_logos':
					case 'countries':
					case 'zip_start':
					case 'zip_stop':
					case 'weight_start':
					case 'weight_stop':
					case 'weight_unit':
					case 'nbproducts_start':
					case 'nbproducts_stop':
					case 'orderamount_start':
					case 'orderamount_stop':
					case 'cost':
					case 'shipment_cost':
					case 'package_fee':
					case 'tax_id':
					case 'tax':
					case 'free_shipment':
						$userfields[] = $this->db->quoteName('#__virtuemart_shipmentmethods.shipment_params');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_shipmentmethods.shipment_params');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_shipmentmethods.shipment_params');
						}
						break;
					default:
						$userfields[] = $this->db->quoteName($field->field_name);

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName($field->field_name);
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName($field->field_name);
						}
						break;
				}
			}

			// Build the query
			$userfields = array_unique($userfields);
			$query = $this->db->getQuery(true);
			$query->select(implode(",\n", $userfields));
			$query->from($this->db->quoteName('#__virtuemart_shipmentmethods'));
			$query->leftJoin(
					$this->db->quoteName('#__virtuemart_shipmentmethods_' . $language)
					. ' ON '
					. $this->db->quoteName('#__virtuemart_shipmentmethods_' . $language . '.virtuemart_shipmentmethod_id')
					. ' = '
					. $this->db->quoteName('#__virtuemart_shipmentmethods.virtuemart_shipmentmethod_id')
			);

			// Filter by published state
			$publish_state = $this->template->get('publish_state');

			if ($publish_state !== '' && ($publish_state == 1 || $publish_state == 0))
			{
				$query->where($this->db->quoteName('#__virtuemart_manufacturers.published') . ' = ' . (int) $publish_state);
			}

			// Group the fields
			$groupby = array_unique($groupby);

			if (!empty($groupby))
			{
				$query->group($groupby);
			}

			// Sort set fields
			$sortby = array_unique($sortby);

			if (!empty($sortby))
			{
				$query->order($sortby);
			}

			// Add export limits
			$limits = $this->getExportLimit();

			// Execute the query
			$this->csvidb->setQuery($query, $limits['offset'], $limits['limit']);
			$this->log->add('Export query' . $query->__toString(), false);

			// Check if there are any records
			$logcount = $this->csvidb->getNumRows();

			if ($logcount > 0)
			{
				$shipment_params = array();

				while ($record = $this->csvidb->getRow())
				{
					$this->log->incrementLinenumber();

					// Check if the shipment params need to be converted
					if (isset($record->shipment_params))
					{
						$ship_params = explode('|', $record->shipment_params);
						array_pop($ship_params);

						foreach ($ship_params as $param)
						{
							list($name, $value) = explode('=', $param);
							$shipment_params[$name] = $value;
						}
					}

					foreach ($exportfields as $field)
					{
						$fieldname = $field->field_name;

						// Set the field value
						if (isset($record->$fieldname))
						{
							$fieldvalue = $record->$fieldname;
						}
						else
						{
							$fieldvalue = '';
						}

						// Process the field
						switch ($fieldname)
						{
							case 'shipment_name':
							case 'shipment_desc':
							case 'slug':
								$query = $this->db->getQuery(true)
									->select($this->db->quoteName($fieldname))
									->from($this->db->quoteName('#__virtuemart_shipmentmethods_' . $language))
									->where($this->db->quoteName('virtuemart_shipmentmethod_id') . ' = ' . (int) $record->virtuemart_shipmentmethod_id);
								$this->db->setQuery($query);
								$fieldvalue = $this->db->loadResult();
								break;
							case 'shipment_logos':
								if (isset($shipment_params[$fieldname]))
								{
									$fieldvalue = json_decode($shipment_params[$fieldname]);
								}
								else
								{
									$fieldvalue = '';
								}
								break;
							case 'countries':
								if (isset($shipment_params[$fieldname]))
								{
									$countries = json_decode($shipment_params[$fieldname]);
								}
								else
								{
									$countries = '';
								}

								if (!empty($countries))
								{
									if (!is_array($countries))
									{
										$countries = (array) $countries;
									}

									$fieldvalue = array();

									foreach ($countries as $countryid)
									{
										// Retrieve the country names
										$query = $this->db->getQuery(true)
											->select($this->db->quoteName('country_name'))
											->from($this->db->quoteName('#__virtuemart_countries'))
											->where($this->db->quoteName('virtuemart_country_id') . ' = ' . (int) $countryid);
										$this->db->setQuery($query);
										$fieldvalue[] = $this->db->loadResult();
									}

									if (!empty($fieldvalue))
									{
										$fieldvalue = implode(',', $fieldvalue);
									}
									else
									{
										$fieldvalue = '';
									}
								}
								else
								{
									$fieldvalue = '';
								}
								break;
							case 'zip_start':
							case 'zip_stop':
							case 'weight_start':
							case 'weight_stop':
							case 'weight_unit':
							case 'nbproducts_start':
							case 'nbproducts_stop':
							case 'orderamount_start':
							case 'orderamount_stop':
							case 'cost':
							case 'shipment_cost':
							case 'tax_id':
							case 'package_fee':
							case 'free_shipment':
								if ($fieldname == 'cost')
								{
									$fieldname = 'shipment_cost';
								}

								if (isset($shipment_params[$fieldname]))
								{
									$fieldvalue = json_decode($shipment_params[$fieldname]);
								}
								else
								{
									$fieldvalue = '';
								}
								break;
							case 'shopper_group_name':
								$query = $this->db->getQuery(true);
								$query->select($this->db->quoteName($fieldname));
								$query->from($this->db->quoteName('#__virtuemart_shoppergroups', 'g'));
								$query->leftJoin(
										$this->db->quoteName('#__virtuemart_shipmentmethod_shoppergroups', 's')
										. ' ON ' . $this->db->quoteName('g.virtuemart_shoppergroup_id') . ' = ' . $this->db->quoteName('s.virtuemart_shoppergroup_id')
								);
								$query->where($this->db->quoteName('s.virtuemart_shipmentmethod_id') . ' = ' . (int) $record->virtuemart_shipmentmethod_id);
								$this->db->setQuery($query);
								$fieldvalue = implode('|', $this->db->loadColumn());
								break;
							case 'tax':
								if (isset($shipment_params[$fieldname]))
								{
									$fieldvalue = json_decode($shipment_params['tax_id']);
								}
								else
								{
									$fieldvalue = '-1';
								}

								switch ($fieldvalue)
								{
									case '-1':
										$fieldvalue = 'norule';
										break;
									case '0':
										$fieldvalue = 'default';
										break;
									default:
										$query = $this->db->getQuery(true);
										$query->select($this->db->quoteName('calc_name'));
										$query->from($this->db->quoteName('#__virtuemart_calcs'));
										$query->where($this->db->quoteName('virtuemart_calc_id') . ' = ' . (int) $fieldvalue);
										$this->db->setQuery($query);
										$fieldvalue = $this->db->loadResult();
										break;
								}
								break;
						}

						// Store the field value
						$this->fields->set($field->csvi_templatefield_id, $fieldvalue);
					}

					// Output the data
					$this->addExportFields();

					// Output the contents
					$this->writeOutput();

					// Empty the shipment params
					$shipment_params = null;
				}
			}
			else
			{
				$this->addExportContent(JText::_('COM_CSVI_NO_DATA_FOUND'));

				// Output the contents
				$this->writeOutput();
			}
		}
	}
}
