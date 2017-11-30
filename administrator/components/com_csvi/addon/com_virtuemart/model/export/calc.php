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
 * Export VirtueMart calculation rules.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelExportCalc extends CsviModelExports
{
	/**
	 * Export the data.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function exportBody()
	{
		if (parent::exportBody())
		{
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
					case 'virtuemart_calc_id':
					case 'created_by':
					case 'created_on':
					case 'locked_by':
					case 'locked_on':
					case 'modified_by':
					case 'modified_on':
					case 'ordering':
					case 'published':
					case 'shared':
					case 'virtuemart_vendor_id':
						$userfields[] = $this->db->quoteName('#__virtuemart_calcs.' . $field->field_name);

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_calcs.' . $field->field_name);
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_calcs.' . $field->field_name);
						}
						break;
					case 'category_path':
					case 'shopper_group_name':
					case 'country_name':
					case 'country_2_code':
					case 'country_3_code':
					case 'state_name':
					case 'state_2_code':
					case 'state_3_code':
						$userfields[] = $this->db->quoteName('#__virtuemart_calcs.virtuemart_calc_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_calcs.virtuemart_calc_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_calcs.virtuemart_calc_id');
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
			$query->from('#__virtuemart_calcs');
			$query->leftJoin(
					$this->db->quoteName('#__virtuemart_calc_categories')
					. ' ON  ' .
					$this->db->quoteName('#__virtuemart_calc_categories.virtuemart_calc_id') . ' = ' . $this->db->quoteName('#__virtuemart_calcs.virtuemart_calc_id')
			);
			$query->leftJoin(
					$this->db->quoteName('#__virtuemart_calc_countries')
					. ' ON ' .
					$this->db->quoteName('#__virtuemart_calc_countries.virtuemart_calc_id') . ' = ' . $this->db->quoteName('#__virtuemart_calcs.virtuemart_calc_id')
			);
			$query->leftJoin(
					$this->db->quoteName('#__virtuemart_calc_shoppergroups')
					. ' ON ' .
					$this->db->quoteName('#__virtuemart_calc_shoppergroups.virtuemart_calc_id') . ' = ' . $this->db->quoteName('#__virtuemart_calcs.virtuemart_calc_id')
			);
			$query->leftJoin(
					$this->db->quoteName('#__virtuemart_calc_states')
					. ' ON ' .
					$this->db->quoteName('#__virtuemart_calc_states.virtuemart_calc_id') . ' = ' . $this->db->quoteName('#__virtuemart_calcs.virtuemart_calc_id')
			);
			$query->leftJoin(
					$this->db->quoteName('#__virtuemart_currencies')
					. ' ON ' .
					$this->db->quoteName('#__virtuemart_currencies.virtuemart_currency_id') . ' = ' . $this->db->quoteName('#__virtuemart_calcs.calc_currency')
			);

			// Filter by published state
			$publish_state = $this->template->get('publish_state');

			if ($publish_state != '' && ($publish_state == 1 || $publish_state == 0))
			{
				$query->where('#__virtuemart_calcs.published = ' . (int) $publish_state);
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
							case 'category_path':
								// Get all the category IDs
								$query = $this->db->getQuery(true);
								$query->select($this->db->quoteName('virtuemart_category_id'));
								$query->from($this->db->quoteName('#__virtuemart_calc_categories'));
								$query->where($this->db->quoteName('virtuemart_calc_id') . ' = ' . (int) $record->virtuemart_calc_id);
								$this->db->setQuery($query);
								$catids = $this->db->loadColumn();

								if (!empty($catids))
								{
									$categories = array();

									foreach ($catids as $catid)
									{
										$categories[] = $this->helper->createCategoryPathById($catid);
									}

									$fieldvalue = implode('|', $categories);
								}
								break;
							case 'shopper_group_name':
								$query = $this->db->getQuery(true);
								$query->select($this->db->quoteName('virtuemart_shoppergroup_id'));
								$query->from($this->db->quoteName('#__virtuemart_calc_shoppergroups'));
								$query->where($this->db->quoteName('virtuemart_calc_id') . ' = ' . (int) $record->virtuemart_calc_id);
								$this->db->setQuery($query);
								$groupids = $this->db->loadColumn();

								if (!empty($groupids))
								{
									$query = $this->db->getQuery(true);
									$query->select($this->db->quoteName('shopper_group_name'));
									$query->from($this->db->quoteName('#__virtuemart_shoppergroups'));
									$query->where($this->db->quoteName('virtuemart_shoppergroup_id') . ' IN (' . implode(',', $groupids) . ')');
									$this->db->setQuery($query);
									$names = $this->db->loadColumn();
									$fieldvalue = implode('|', $names);
								}
								break;
							case 'country_name':
							case 'country_2_code':
							case 'country_3_code':
								$query = $this->db->getQuery(true);
								$query->select($this->db->quoteName('virtuemart_country_id'));
								$query->from($this->db->quoteName('#__virtuemart_calc_countries'));
								$query->where($this->db->quoteName('virtuemart_calc_id') . ' = ' . (int) $record->virtuemart_calc_id);
								$this->db->setQuery($query);
								$groupids = $this->db->loadColumn();

								if (!empty($groupids))
								{
									$query = $this->db->getQuery(true);
									$query->select($fieldname);
									$query->from($this->db->quoteName('#__virtuemart_countries'));
									$query->where($this->db->quoteName('virtuemart_country_id') . ' IN (' . implode(',', $groupids) . ')');
									$this->db->setQuery($query);
									$names = $this->db->loadColumn();
									$fieldvalue = implode('|', $names);
								}
								break;
							case 'state_name':
							case 'state_2_code':
							case 'state_3_code':
								$query = $this->db->getQuery(true);
								$query->select($this->db->quoteName('virtuemart_state_id'));
								$query->from($this->db->quoteName('#__virtuemart_calc_states'));
								$query->where($this->db->quoteName('virtuemart_calc_id') . ' = ' . (int) $record->virtuemart_calc_id);
								$this->db->setQuery($query);
								$groupids = $this->db->loadColumn();

								if (!empty($groupids))
								{
									$query = $this->db->getQuery(true);
									$query->select($this->db->quoteName($fieldname));
									$query->from($this->db->quoteName('#__virtuemart_states'));
									$query->where($this->db->quoteName('virtuemart_state_id') . ' IN (' . implode(',', $groupids) . ')');
									$this->db->setQuery($query);
									$names = $this->db->loadColumn();
									$fieldvalue = implode('|', $names);
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
