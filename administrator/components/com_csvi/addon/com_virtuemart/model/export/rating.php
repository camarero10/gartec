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
 * Export VirtueMart product ratings.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelExportRating extends CsviModelExports
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
					case 'created_on':
					case 'modified_on':
					case 'locked_on':
					case 'created_by':
					case 'modified_by':
					case 'locked_by':
					case 'virtuemart_product_id':
					case 'published':
						$userfields[] = $this->db->quoteName('#__virtuemart_rating_reviews.' . $field->field_name);

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_rating_reviews.' . $field->field_name);
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_rating_reviews.' . $field->field_name);
						}
						break;
					case 'product_sku':
					case 'vote':
						$userfields[] = $this->db->quoteName('#__virtuemart_rating_reviews.virtuemart_product_id');
						$userfields[] = $this->db->quoteName('#__virtuemart_rating_reviews.created_by');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_rating_reviews.virtuemart_product_id');
							$groupby[] = $this->db->quoteName('#__virtuemart_rating_reviews.created_by');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_rating_reviews.virtuemart_product_id');
							$sortby[] = $this->db->quoteName('#__virtuemart_rating_reviews.created_by');
						}
						break;
					case 'username':
						$userfields[] = $this->db->quoteName('#__virtuemart_rating_reviews.created_by');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_rating_reviews.created_by');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_rating_reviews.created_by');
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
			$query->from($this->db->quoteName('#__virtuemart_rating_reviews'));
			$query->leftJoin(
					$this->db->quoteName('#__virtuemart_products')
					. ' ON ' . $this->db->quoteName('#__virtuemart_products.virtuemart_product_id') . ' = ' . $this->db->quoteName('#__virtuemart_rating_reviews.virtuemart_product_id')
			);
			$query->leftJoin(
					$this->db->quoteName('#__users')
					. ' ON ' . $this->db->quoteName('#__users.id') . ' = ' . $this->db->quoteName('#__virtuemart_rating_reviews.created_by')
			);

			// Filter by published state
			$publish_state = $this->template->get('publish_state');

			if ($publish_state !== '' && ($publish_state == 1 || $publish_state == 0))
			{
				$query->where($this->db->quoteName('#__virtuemart_rating_reviews.published') . ' = ' . (int) $publish_state);
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
							case 'vote':
								$query = $this->db->getQuery(true)
									->select($this->db->quoteName('vote'))
									->from($this->db->quoteName('#__virtuemart_rating_votes'))
									->where($this->db->quoteName('virtuemart_product_id') . ' = ' . (int) $record->virtuemart_product_id)
									->where($this->db->quoteName('created_by') . ' = ' . (int) $record->created_by);
								$this->db->setQuery($query);
								$fieldvalue = $this->db->loadResult();
								break;
							case 'username':
								$query = $this->db->getQuery(true)
									->select($this->db->quoteName('username'))
									->from($this->db->quoteName('#__users'))
									->where($this->db->quoteName('id') . ' = ' . (int) $record->created_by);
								$this->db->setQuery($query);
								$fieldvalue = $this->db->loadResult();
								break;
							case 'created_on':
							case 'modified_on':
							case 'locked_on':
								$date = JFactory::getDate($record->$fieldname);
								$fieldvalue = date($this->template->get('export_date_format'), $date->toUnix());
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
