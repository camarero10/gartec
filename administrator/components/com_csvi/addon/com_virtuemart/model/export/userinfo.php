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
 * Export VirtueMart user info.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelExportUserinfo extends CsviModelExports
{
	/**
	 * Export the data.
	 *
	 * @return  bool  True if body is exported | False if body is not exported.
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
					case 'virtuemart_user_id':
					case 'created_on':
					case 'modified_on':
					case 'locked_on':
					case 'created_by':
					case 'modified_by':
					case 'locked_by':
					case 'name':
					case 'agreed':
						$userfields[] = $this->db->quoteName('#__virtuemart_userinfos.' . $field->field_name);

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_userinfos.' . $field->field_name);
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_userinfos.' . $field->field_name);
						}
						break;
					case 'full_name':
						$userfields[] = $this->db->quoteName('#__virtuemart_userinfos.first_name');
						$userfields[] = $this->db->quoteName('#__virtuemart_userinfos.middle_name');
						$userfields[] = $this->db->quoteName('#__virtuemart_userinfos.last_name');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_userinfos.first_name');
							$groupby[] = $this->db->quoteName('#__virtuemart_userinfos.middle_name');
							$groupby[] = $this->db->quoteName('#__virtuemart_userinfos.last_name');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_userinfos.first_name');
							$sortby[] = $this->db->quoteName('#__virtuemart_userinfos.middle_name');
							$sortby[] = $this->db->quoteName('#__virtuemart_userinfos.last_name');
						}
						break;
					case 'id':
						$userfields[] = $this->db->quoteName('#__users.id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__users.id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__users.id');
						}
						break;
					case 'usergroup_name':
						$userfields[] = $this->db->quoteName('#__virtuemart_userinfos.virtuemart_user_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_userinfos.virtuemart_user_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_userinfos.virtuemart_user_id');
						}
						break;
					case 'virtuemart_vendor_id':
						$userfields[] = $this->db->quoteName('#__virtuemart_vmusers.virtuemart_vendor_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_vmusers.virtuemart_vendor_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_vmusers.virtuemart_vendor_id');
						}
						break;
					case 'state_2_code':
					case 'state_3_code':
					case 'state_name':
						$userfields[] = $this->db->quoteName('#__virtuemart_userinfos.virtuemart_state_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_userinfos.virtuemart_state_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_userinfos.virtuemart_state_id');
						}
						break;
					case 'country_2_code':
					case 'country_3_code':
					case 'country_name':
					case 'virtuemart_country_id':
						$userfields[] = $this->db->quoteName('#__virtuemart_userinfos.virtuemart_country_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_userinfos.virtuemart_country_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_userinfos.virtuemart_country_id');
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

			/** Export SQL Query
			 * Get all products - including items
			 * as well as products without a price
			 */
			$userfields = array_unique($userfields);
			$query = $this->db->getQuery(true);
			$query->select(implode(",\n", $userfields));
			$query->from($this->db->quoteName('#__virtuemart_userinfos'));
			$query->leftJoin(
				$this->db->quoteName('#__virtuemart_vmusers')
				. ' ON ' . $this->db->quoteName('#__virtuemart_vmusers.virtuemart_user_id') . ' = ' . $this->db->quoteName('#__virtuemart_userinfos.virtuemart_user_id')
			);
			$query->leftJoin(
				$this->db->quoteName('#__virtuemart_vmuser_shoppergroups') . ' ON ' . $this->db->quoteName('#__virtuemart_vmuser_shoppergroups.virtuemart_user_id') . ' = ' . $this->db->quoteName('#__virtuemart_userinfos.virtuemart_user_id')
			);
			$query->leftJoin(
				$this->db->quoteName('#__virtuemart_vendors') . ' ON ' . $this->db->quoteName('#__virtuemart_vendors.virtuemart_vendor_id') . ' = ' . $this->db->quoteName('#__virtuemart_vmusers.virtuemart_vendor_id')
			);
			$query->leftJoin(
				$this->db->quoteName('#__virtuemart_shoppergroups') . ' ON ' . $this->db->quoteName('#__virtuemart_shoppergroups.virtuemart_shoppergroup_id') . ' = ' . $this->db->quoteName('#__virtuemart_vmuser_shoppergroups.virtuemart_shoppergroup_id')
			);
			$query->leftJoin(
				$this->db->quoteName('#__users')
				. ' ON ' . $this->db->quoteName('#__users.id') . ' = ' . $this->db->quoteName('#__virtuemart_userinfos.virtuemart_user_id')
			);

			// Filter by vendors
			$vendors = $this->template->get('vendors', false);

			if ($vendors && $vendors[0] != 'none')
			{
				$query->where($this->db->quoteName('#__virtuemart_vmusers.virtuemart_vendor_id') . ' IN (\'' . implode("','", $vendors) . '\')');
			}

			// Filter by permissions
			$permissions = $this->template->get('permissions', false);

			if ($permissions && $permissions[0] != 'none')
			{
				$query->where($this->db->quoteName('#__virtuemart_vmusers.perms') . ' IN (\'' . implode("','", $permissions) . '\')');
			}

			// Filter by address type
			$address = $this->template->get('userinfo_address', false);

			if ($address)
			{
				$query->where($this->db->quoteName('#__virtuemart_userinfos.address_type') . ' = ' . $this->db->quote(strtoupper($address)));
			}

			// Filter by user info modified date start
			$date = $this->template->get('userinfomdatestart', false);

			if ($date)
			{
				$userinfomdate = JFactory::getDate($date);
				$query->where($this->db->quoteName('#__virtuemart_userinfos.modified_on') . ' >= ' . $this->db->quote($userinfomdate->toSql()));
			}

			// Filter by user info date end
			$date = $this->template->get('userinfomdateend', false);

			if ($date)
			{
				$userinfomdate = JFactory::getDate($date);
				$query->where($this->db->quoteName('#__virtuemart_userinfos.modified_on') . ' <= ' . $this->db->quote($userinfomdate->toSql()));
			}

			// Filter by block status
			$blocked = $this->template->get('blocked', false);

			if ($blocked != '')
			{
				$query->where($this->db->quoteName('#__users.block') . ' = ' . (int) (($blocked) ? 1 : 0));
			}

			// Filter by activated status
			$activated = $this->template->get('activated', false);

			if ($activated !== false)
			{
				if ($activated == '0')
				{
					$query->where($query->length($this->db->quoteName('#__users.activation')) . ' = 32');

				}
				elseif ($activated == '1')
				{
					$query->where($this->db->quoteName('#__users.activation') . ' = ' . $this->db->quote(''));
				}
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
							case 'created_on':
							case 'modified_on':
							case 'locked_on':
							case 'lastvisitdate':
								$date = JFactory::getDate($record->$fieldname);
								$fieldvalue = date($this->template->get('export_date_format'), $date->toUnix());
								break;
							case 'address_type':
								// Check if we have any content otherwise use the default value
								if (strlen(trim($fieldvalue)) == 0)
								{
									$fieldvalue = $field->default_value;
								}

								if ($fieldvalue == 'BT')
								{
									$fieldvalue = JText::_('COM_CSVI_BILLING_ADDRESS');
								}
								elseif ($fieldvalue == 'ST')
								{
									$fieldvalue = JText::_('COM_CSVI_SHIPPING_ADDRESS');
								}
								break;
							case 'full_name':
								$fieldvalue = str_replace('  ', ' ', $record->first_name . ' ' . $record->middle_name . ' ' . $record->last_name);
								break;
							case 'usergroup_name':
								$query = $this->db->getQuery(true)
									->select($this->db->quoteName('title'))
									->from($this->db->quoteName('#__usergroups'))
									->leftJoin(
										$this->db->quoteName('#__user_usergroup_map') . ' ON ' .
										$this->db->quoteName('#__user_usergroup_map.group_id') . ' = ' . $this->db->quoteName('#__usergroups.id')
									)
									->where($this->db->quoteName('user_id') . ' = ' . (int) $record->virtuemart_user_id);
								$this->db->setQuery($query);
								$fieldvalue = $this->db->loadResult();
								$this->log->add('Usergroup name');
								break;
							case 'state_2_code':
							case 'state_3_code':
							case 'state_name':
								$query = $this->db->getQuery(true);
								$query->select($fieldname);
								$query->from('#__virtuemart_states');
								$query->where('virtuemart_state_id = ' . $record->virtuemart_state_id);
								$this->db->setQuery($query);
								$fieldvalue = $this->db->loadResult();
								break;
							case 'country_2_code':
							case 'country_3_code':
							case 'country_name':
								$query = $this->db->getQuery(true);
								$query->select($fieldname);
								$query->from('#__virtuemart_countries');
								$query->where('virtuemart_country_id = ' . $record->virtuemart_country_id);
								$this->db->setQuery($query);
								$fieldvalue = $this->db->loadResult();
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
