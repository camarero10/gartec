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
 * Export VirtueMart multiple prices.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelExportPrice extends CsviModelExports
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
			// Check if we have a language set
			$language = $this->template->get('language', false);

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
					case 'product_sku':
						$userfields[] = $this->db->quoteName('#__virtuemart_product_prices.virtuemart_product_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_product_prices.virtuemart_product_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_product_prices.virtuemart_product_id');
						}
						break;
					case 'product_name':
						$userfields[] = $this->db->quoteName('#__virtuemart_products.virtuemart_product_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_products.virtuemart_product_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_products.virtuemart_product_id');
						}
						break;
					case 'product_currency':
						$userfields[] = $this->db->quoteName('#__virtuemart_currencies.currency_code_3');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_currencies.currency_code_3');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_currencies.currency_code_3');
						}
						break;
					case 'created_by':
					case 'created_on':
					case 'locked_by':
					case 'locked_on':
					case 'modified_by':
					case 'modified_on':
					case 'virtuemart_product_id':
					case 'virtuemart_shoppergroup_id':
						$userfields[] = $this->db->quoteName('#__virtuemart_product_prices.' . $field->field_name);

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_product_prices.' . $field->field_name);
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_product_prices.' . $field->field_name);
						}
						break;
					case 'custom':
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
			$query->from($this->db->quoteName('#__virtuemart_product_prices'));
			$query->leftJoin(
					$this->db->quoteName('#__virtuemart_products')
					. ' ON ' . $this->db->quoteName('#__virtuemart_product_prices.virtuemart_product_id') . ' = ' . $this->db->quoteName('#__virtuemart_products.virtuemart_product_id')
			);
			$query->leftJoin(
					$this->db->quoteName('#__virtuemart_shoppergroups')
					. ' ON ' . $this->db->quoteName('#__virtuemart_product_prices.virtuemart_shoppergroup_id') . ' = ' . $this->db->quoteName('#__virtuemart_shoppergroups.virtuemart_shoppergroup_id')
			);
			$query->leftJoin(
					$this->db->quoteName('#__virtuemart_currencies')
					. ' ON ' . $this->db->quoteName('#__virtuemart_product_prices.product_currency') . ' = ' . $this->db->quoteName('#__virtuemart_currencies.virtuemart_currency_id')
			);

			// Filter by published state
			$publish_state = $this->template->get('publish_state');

			if ($publish_state != '' && ($publish_state == 1 || $publish_state == 0))
			{
				$query->where($this->db->quoteName('#__virtuemart_products.published') . ' = ' . (int) $publish_state);
			}

			// Shopper group selector
			$shopper_group = $this->template->get('shopper_groups', array());

			if ($shopper_group && $shopper_group[0] != 'none')
			{
				$query->where($this->db->quoteName('#__virtuemart_shoppergroups.virtuemart_shoppergroup_id') . " IN ('" . implode("','", $shopper_group) . "')");
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
				while ($record = $this->csvidb->getRow())
				{
					$this->log->incrementLinenumber();

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
							case 'product_sku':
								$query = $this->db->getQuery(true);
								$query->select($this->db->quoteName('product_sku'));
								$query->from($this->db->quoteName('#__virtuemart_products'));
								$query->where($this->db->quoteName('virtuemart_product_id') . ' = ' . (int) $record->virtuemart_product_id);
								$this->db->setQuery($query);
								$fieldvalue = $this->db->loadResult();
								break;
							case 'product_name':
								$query = $this->db->getQuery(true);
								$query->select($this->db->quoteName($fieldname));
								$query->from($this->db->quoteName('#__virtuemart_products_' . $language));
								$query->where($this->db->quoteName('virtuemart_product_id') . ' = ' . (int) $record->virtuemart_product_id);
								$this->db->setQuery($query);
								$fieldvalue = $this->db->loadResult();
								break;
							case 'product_price':
							case 'product_override_price':
								$fieldvalue = number_format(
									$record->$fieldname,
									$this->template->get('export_price_format_decimal', 2, 'int'),
									$this->template->get('export_price_format_decsep'),
									$this->template->get('export_price_format_thousep')
								);
								break;
							case 'product_price_publish_up':
							case 'product_price_publish_down':
							case 'created_on':
							case 'locked_on':
							case 'modified_on':
								$date = JFactory::getDate($record->$fieldname);
								$fieldvalue = date($this->template->get('export_date_format'), $date->toUnix());
								break;
							case 'product_currency':
								$fieldvalue = $record->currency_code_3;
								break;
							case 'shopper_group_name':
								// Check if the shopper group name is empty
								if (empty($field->default_value) && empty($fieldvalue))
								{
									$fieldvalue = '*';
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
