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
 * Export VirtueMart custom fields.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelExportCustomfield extends CsviModelExports
{
	/**
	 * Array of plugins
	 *
	 * @var    array
	 * @since  6.0
	 */
	private $plugins = array();

	/**
	 * Array of vendors
	 *
	 * @var    array
	 * @since  6.0
	 */
	private $vendors = array();

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
					case 'custom';
					case 'vendor_name':
						break;
					case 'plugin_name':
						$userfields[] = $this->db->quoteName('#__virtuemart_customs.custom_jplugin_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_customs.custom_jplugin_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_customs.custom_jplugin_id');
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
			$query->from($this->db->quoteName('#__virtuemart_customs'));

			// Filter by published state
			$publish_state = $this->template->get('publish_state');

			if ($publish_state != '' && ($publish_state == 1 || $publish_state == 0))
			{
				$query->where($this->db->quoteName('#__virtuemart_customs.published') . ' = ' . (int) $publish_state);
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
							case 'plugin_name':
								if (!isset($this->plugins[$record->custom_jplugin_id]))
								{
									$query = $this->db->getQuery(true);
									$query->select($this->db->quoteName('name'));
									$query->from($this->db->quoteName('#__extensions'));
									$query->where($this->db->quoteName('extension_id') . ' = ' . (int) $record->custom_jplugin_id);
									$query->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'));
									$this->db->setQuery($query);
									$fieldvalue = $this->db->loadResult();
									$this->plugins[$record->custom_jplugin_id] = $fieldvalue;
								}
								else
								{
									$fieldvalue = $this->plugins[$record->custom_jplugin_id];
								}
								break;
							case 'vendor_name':
								if (!isset($this->vendors[$record->virtuemart_vendor_id]))
								{
									$query = $this->db->getQuery(true);
									$query->select($this->db->quoteName('vendor_name'));
									$query->from($this->db->quoteName('#__virtuemart_vendors'));
									$query->where($this->db->quoteName('virtuemart_vendor_id') . ' = ' . (int) $record->virtuemart_vendor_id);
									$this->db->setQuery($query);
									$fieldvalue = $this->db->loadResult();
									$this->vendors[$record->virtuemart_vendor_id] = $fieldvalue;
								}
								else
								{
									$fieldvalue = $this->vendors[$record->virtuemart_vendor_id];
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
