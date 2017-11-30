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
 * Exports VirtueMart products.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelExportProduct extends CsviModelExports
{
	/**
	 * The domain name for URLs.
	 *
	 * @var    string
	 * @since  6.0
	 */
	private $domainname = null;

	/**
	 * Array of prices per product
	 *
	 * @var    array
	 * @since  6.0
	 */
	private $prices = array();

	/**
	 * List of custom fields
	 *
	 * @var    array
	 * @since  6.0
	 */
	private $customfields = array();

	/**
	 * The custom fields that can be used as available field.
	 *
	 * @var    array
	 * @since  6.0
	 */
	private $customfieldsExport = array();

	/**
	 * The multi variant fields that can be used as available field.
	 *
	 * @var    array
	 * @since  6.0
	 */
	private $multivariantExport = array();

	/**
	 * VirtueMart helper
	 *
	 * @var    Com_VirtuemartHelperCom_Virtuemart
	 * @since  6.0
	 */
	protected $helper = null;

	/**
	 * VirtueMart helper config
	 *
	 * @var    Com_VirtuemartHelperCom_Virtuemart_Config
	 * @since  6.0
	 */
	protected $helperconfig = null;

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
			// Get and set the language
			$language = $this->template->get('language', false);

			if (!$language)
			{
				throw new CsviException(JText::_('COM_CSVI_NO_LANGUAGE_SET'));
			}

			// Get some basic data
			$jinput = JFactory::getApplication()->input;
			$this->domainname = $this->settings->get('hostname');
			$this->loadCustomFields();
			$this->loadMultiVariantFields();
			$exportfields = $this->fields->getFields();

			// Load the plugins
			$dispatcher = new RantaiPluginDispatcher;
			$dispatcher->importPlugins('csviext', $this->db);

			$jinput->set('vmlang', substr($language, 0, 2) . '-' . strtoupper(substr($language, 3)));

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

			// Build something fancy to only get the fieldnames the user wants
			$userfields = array();
			$userfields[] = $this->db->quoteName('#__virtuemart_products.virtuemart_product_id');
			$userfields[] = $this->db->quoteName('#__virtuemart_products.product_parent_id');

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
					case 'virtuemart_vendor_id':
					case 'hits':
					case 'metaauthor':
					case 'metarobot':
					case 'published':
						$userfields[] = $this->db->quoteName('#__virtuemart_products.' . $field->field_name);

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_products.' . $field->field_name);
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_products.' . $field->field_name);
						}
						break;
					case 'category_id':
					case 'category_path':
					case 'category_name':
						$userfields[] = $this->db->quoteName('#__virtuemart_product_categories.virtuemart_category_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_product_categories.virtuemart_category_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_product_categories.virtuemart_category_id');
						}
						break;
					case 'product_ordering':
						$userfields[] = $this->db->quoteName('#__virtuemart_product_categories.ordering', 'product_ordering');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_product_categories.ordering', 'product_ordering');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_product_categories.ordering', 'product_ordering');
						}
						break;
					case 'related_products':
					case 'related_categories':
						$userfields[] = $this->db->quoteName('#__virtuemart_products.virtuemart_product_id', 'main_product_id');
						break;
					case 'product_box':
						$userfields[] = $this->db->quoteName('#__virtuemart_products.product_params');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_products.product_params');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_products.product_params');
						}
						break;
					case 'product_price':
					case 'price_with_tax':
						$userfields[] = $this->db->quoteName('#__virtuemart_product_prices.product_price');
						$userfields[] = $this->db->quoteName('#__virtuemart_product_prices.virtuemart_shoppergroup_id');
						$userfields[] = $this->db->quoteName('#__virtuemart_currencies.currency_code_3');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_product_prices.product_price');
							$groupby[] = $this->db->quoteName('#__virtuemart_product_prices.virtuemart_shoppergroup_id');
							$groupby[] = $this->db->quoteName('#__virtuemart_currencies.currency_code_3');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_product_prices.product_price');
							$sortby[] = $this->db->quoteName('#__virtuemart_product_prices.virtuemart_shoppergroup_id');
							$sortby[] = $this->db->quoteName('#__virtuemart_currencies.currency_code_3');
						}
						break;
					case 'product_url':
						$userfields[] = $this->db->quoteName('#__virtuemart_products.virtuemart_product_id');
						$userfields[] = $this->db->quoteName('#__virtuemart_products.product_url');
						$userfields[] = $this->db->quoteName('#__virtuemart_products.product_parent_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_products.virtuemart_product_id');
							$groupby[] = $this->db->quoteName('#__virtuemart_products.product_url');
							$groupby[] = $this->db->quoteName('#__virtuemart_products.product_parent_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_products.virtuemart_product_id');
							$sortby[] = $this->db->quoteName('#__virtuemart_products.product_url');
							$sortby[] = $this->db->quoteName('#__virtuemart_products.product_parent_id');
						}
						break;
					case 'price_with_discount':
						$userfields[] = $this->db->quoteName('#__virtuemart_product_prices.product_price');
						$userfields[] = $this->db->quoteName('#__virtuemart_currencies.currency_code_3');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_product_prices.product_price');
							$groupby[] = $this->db->quoteName('#__virtuemart_currencies.currency_code_3');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_product_prices.product_price');
							$sortby[] = $this->db->quoteName('#__virtuemart_currencies.currency_code_3');
						}
						break;
					case 'basepricewithtax':
					case 'discountedpricewithouttax':
					case 'pricebeforetax':
					case 'salesprice':
					case 'taxamount':
					case 'discountamount':
					case 'pricewithouttax':
					case 'product_currency':
						$userfields[] = $this->db->quoteName('#__virtuemart_currencies.currency_code_3');
						$userfields[] = $this->db->quoteName('#__virtuemart_product_prices.virtuemart_shoppergroup_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_currencies.currency_code_3');
							$groupby[] = $this->db->quoteName('#__virtuemart_product_prices.virtuemart_shoppergroup_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_currencies.currency_code_3');
							$sortby[] = $this->db->quoteName('#__virtuemart_product_prices.virtuemart_shoppergroup_id');
						}
						break;
					case 'custom_shipping':
						$userfields[] = $this->db->quoteName('#__virtuemart_product_prices.product_price');
						$userfields[] = $this->db->quoteName('#__virtuemart_product_prices.virtuemart_shoppergroup_id');
						$userfields[] = '1 AS tax_rate';

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_product_prices.product_price');
							$groupby[] = $this->db->quoteName('#__virtuemart_product_prices.virtuemart_shoppergroup_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_product_prices.product_price');
							$sortby[] = $this->db->quoteName('#__virtuemart_product_prices.virtuemart_shoppergroup_id');
						}
						break;
					case 'max_order_level':
					case 'min_order_level':
					case 'step_order_level':
						$userfields[] = $this->db->quoteName('#__virtuemart_products.product_params');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_products.product_params');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_products.product_params');
						}
						break;
					case 'product_discount':
						$userfields[] = $this->db->quoteName('#__virtuemart_product_prices.product_discount_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_product_prices.product_discount_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_product_prices.product_discount_id');
						}
						break;
					case 'virtuemart_shoppergroup_id':
					case 'shopper_group_name_price':
						$userfields[] = $this->db->quoteName('#__virtuemart_product_prices.virtuemart_shoppergroup_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_product_prices.virtuemart_shoppergroup_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_product_prices.virtuemart_shoppergroup_id');
						}
						break;
					case 'product_name':
					case 'product_s_desc':
					case 'product_desc':
					case 'metadesc':
					case 'metakey':
					case 'slug':
					case 'customtitle':
						$userfields[] = $this->db->quoteName('#__virtuemart_products.product_parent_id');
						break;

					// Special treatment, do not export them
					case 'custom_value':
					case 'custom_param':
					case 'custom_price':
					case 'custom_title':
					case 'custom_ordering':
					case 'file_url':
					case 'file_url_thumb':
					case 'file_title':
					case 'file_description':
					case 'file_meta':
					case 'file_lang':
					case 'file_ordering':
					case 'shopper_group_name':
					case 'manufacturer_name':
					case 'picture_url':
					case 'picture_url_thumb':
					case 'product_parent_sku':
					case 'custom':
						break;
					default:
						// Do not include custom fields into the query
						if (!in_array($field->field_name, $this->customfieldsExport)
							&& !in_array($field->field_name, $this->multivariantExport))
						{
							$userfields[] = $this->db->quoteName($field->field_name);

							if (array_key_exists($field->field_name, $groupbyfields))
							{
								$groupby[] = $this->db->quoteName($field->field_name);
							}

							if (array_key_exists($field->field_name, $sortbyfields))
							{
								$sortby[] = $this->db->quoteName($field->field_name);
							}
						}
						break;
				}
			}

			// Export SQL Query
			$userfields = array_unique($userfields);
			$query = $this->db->getQuery(true);
			$query->select(implode(",\n", $userfields));
			$query->from($this->db->quoteName('#__virtuemart_products'));
			$query->leftJoin(
				$this->db->quoteName('#__virtuemart_product_prices')
				. ' ON ' . $this->db->quoteName('#__virtuemart_products.virtuemart_product_id') . ' = ' . $this->db->quoteName('#__virtuemart_product_prices.virtuemart_product_id')
			);
			$query->leftJoin(
				$this->db->quoteName('#__virtuemart_product_manufacturers')
				. ' ON ' . $this->db->quoteName('#__virtuemart_products.virtuemart_product_id') . ' = ' . $this->db->quoteName('#__virtuemart_product_manufacturers.virtuemart_product_id')
			);
			$query->leftJoin(
				$this->db->quoteName('#__virtuemart_manufacturers')
				. ' ON ' . $this->db->quoteName('#__virtuemart_product_manufacturers.virtuemart_manufacturer_id') . ' = ' . $this->db->quoteName('#__virtuemart_manufacturers.virtuemart_manufacturer_id')
			);
			$query->leftJoin(
				$this->db->quoteName('#__virtuemart_product_categories')
				. ' ON ' . $this->db->quoteName('#__virtuemart_products.virtuemart_product_id') . ' = ' . $this->db->quoteName('#__virtuemart_product_categories.virtuemart_product_id')
			);

			$query->leftJoin(
				$this->db->quoteName('#__virtuemart_currencies')
				. ' ON ' . $this->db->quoteName('#__virtuemart_currencies.virtuemart_currency_id') . ' = ' . $this->db->quoteName('#__virtuemart_product_prices.product_currency')
			);
			$query->leftJoin(
				$this->db->quoteName('#__virtuemart_product_shoppergroups')
				. ' ON ' . $this->db->quoteName('#__virtuemart_product_shoppergroups.virtuemart_product_id') . ' = ' . $this->db->quoteName('#__virtuemart_products.virtuemart_product_id')
			);

			// Filter by product category
			/**
			 * We are doing a selection on categories, need to redo the query to make sure child products get included
			 * 1. Search all product ID's for that particular category
			 * 2. Search for all child product ID's
			 * 3. Load all products with these ids
			 */
			$productcategories = $this->template->get('product_categories', false);

			// Sanity check
			Joomla\Utilities\ArrayHelper::toInteger($productcategories);

			if ($productcategories && $productcategories[0] != '')
			{
				// If selected get products of all subcategories as well
				if ($this->template->get('incl_subcategory', false))
				{
					foreach ($productcategories as $productcategory)
					{
						$subids = $this->helper->getSubCategoryIds($productcategory);

						if ($subids)
						{
							$productcategories = array_merge($productcategories, $subids);
						}
					}
				}

				// Add the category restriction
				$query->where($this->db->quoteName('#__virtuemart_product_categories.virtuemart_category_id') . ' IN (' . implode(',', $productcategories) . ')');

				// Get only the parent products and products without children
				if ($this->template->get('parent_only', 0, 'bool'))
				{
					// Get all product IDs in the selected categories
					$q_product_ids = "SELECT p.virtuemart_product_id
								FROM #__virtuemart_products p
								LEFT JOIN #__virtuemart_product_categories x
								ON p.virtuemart_product_id = x.virtuemart_product_id
								WHERE x.virtuemart_category_id IN (" . implode(',', $productcategories) . ")
								AND p.product_parent_id = 0";
					$this->db->setQuery($q_product_ids);
					$product_ids = $this->db->loadColumn();
					$this->log->add('Get all the product IDs in the selected categories');
				}
				// Get only the child products and products without children
				elseif ($this->template->get('child_only', 0, 'bool'))
				{
					// Load all non child IDs
					$q_child = "SELECT p.virtuemart_product_id
										FROM #__virtuemart_products p
										LEFT JOIN #__virtuemart_product_categories x
										ON p.virtuemart_product_id = x.virtuemart_product_id
										WHERE x.virtuemart_category_id IN ('" . implode("','", $productcategories) . "')";
					$this->db->setQuery($q_child);
					$allproduct_ids = $this->db->loadColumn();
					$this->log->add('COM_CSVI_EXPORT_QUERY', true);

					// Get all child product IDs in the selected categories
					$q_child = "SELECT p.virtuemart_product_id
								FROM #__virtuemart_products p
								WHERE p.product_parent_id IN ('" . implode("','", $allproduct_ids) . "')";
					$this->db->setQuery($q_child);
					$child_ids = $this->db->loadColumn();
					$this->log->add('COM_CSVI_EXPORT_QUERY', true);

					// Get all parent product IDs in the selected categories
					$q_child = "SELECT p.product_parent_id
								FROM #__virtuemart_products p
								WHERE p.virtuemart_product_id IN ('" . implode("','", $child_ids) . "')";
					$this->db->setQuery($q_child);
					$parent_ids = $this->db->loadColumn();
					$this->log->add('COM_CSVI_EXPORT_QUERY', true);

					// Combine all the IDs
					$product_ids = array_merge($child_ids, array_diff($allproduct_ids, $parent_ids));
				}
				else
				{
					// Get all product IDs
					$q_product_ids = "SELECT p.virtuemart_product_id
								FROM #__virtuemart_products p
								LEFT JOIN #__virtuemart_product_categories x
								ON p.virtuemart_product_id = x.virtuemart_product_id
								WHERE x.virtuemart_category_id IN ('" . implode("','", $productcategories) . "')";
					$this->db->setQuery($q_product_ids);
					$product_ids = $this->db->loadColumn();
					$this->log->add('COM_CSVI_EXPORT_QUERY', true);

					// Get all child product IDs
					if ($product_ids)
					{
						$q_childproduct_ids = "SELECT p.virtuemart_product_id
									FROM #__virtuemart_products p
									WHERE p.product_parent_id IN ('" . implode("','", $product_ids) . "')";
						$this->db->setQuery($q_childproduct_ids);
						$childproduct_ids = $this->db->loadColumn();
						$this->log->add('COM_CSVI_EXPORT_QUERY', true);

						// Now we have all the product IDs
						$product_ids = array_merge($product_ids, $childproduct_ids);
					}
				}

				// Check if the user want child products
				if (!empty($product_ids))
				{
					// Remove duplicates
					$cleanIds = array_unique($product_ids, SORT_NUMERIC);

					$query->where('#__virtuemart_products.virtuemart_product_id IN (' . implode(',', $cleanIds) . ')');
				}
			}
			else
			{
				// Filter by published category state
				$category_publish = $this->template->get('publish_state_categories');

				// Filter on parent products and products without children
				if ($this->template->get('parent_only', 0, 'bool'))
				{
					$query->where($this->db->quoteName('#__virtuemart_products.product_parent_id') . ' = 0');

					if (!empty($category_publish))
					{
						$query->where($this->db->quoteName('#__virtuemart_categories.published') . ' = ' . (int) $category_publish);
					}
				}

				// Filter on child products and products without children
				elseif ($this->template->get('child_only', 0, 'bool'))
				{
					// Load all non child IDs
					$nonchildQuery = $this->db->getQuery(true)
						->select($this->db->quoteName('p.virtuemart_product_id'))
						->from($this->db->quoteName('#__virtuemart_products', 'p'))
						->where($this->db->quoteName('p.product_parent_id') . ' = 0');

					$state = ($category_publish == '1') ? '0' : '1';

					if (!empty($category_publish))
					{
						$nonchildQuery
							->leftJoin($this->db->quoteName('#__virtuemart_product_categories', 'pc'))
							. ' ON ' . $this->db->quoteName('p.virtuemart_product_id') . ' = ' . $this->db->quoteName('pc.virtuemart_product_id')
							->leftJoin($this->db->quoteName('#__virtuemart_categories', 'c'))
							. ' ON ' . $this->db->quoteName('pc.virtuemart_category_id') . ' = ' . $this->db->quoteName('c.virtuemart_category_id')
							->where($this->db->quoteName('c.published') . ' = ' . (int) $state);
					}

					$this->db->setQuery($nonchildQuery);
					$nonchild_ids = $this->db->loadColumn();
					$this->log->add('Load all non-child IDs', true);

					// Get the child IDs from the filtered category
					if (!empty($category_publish))
					{
						$nonchildQuery
							->clear('join')
							->clear('where')
							->leftJoin($this->db->quoteName('#__virtuemart_product_categories', 'pc'))
							. ' ON ' . $this->db->quoteName('p.virtuemart_product_id') . ' = ' . $this->db->quoteName('pc.virtuemart_product_id')
							->leftJoin($this->db->quoteName('#__virtuemart_categories', 'c'))
							. ' ON ' . $this->db->quoteName('pc.virtuemart_category_id') . ' = ' . $this->db->quoteName('c.virtuemart_category_id')
							->where($this->db->quoteName('p.product_parent_id') . ' IN (' . implode(',', $nonchild_ids) . ')')
							->group($this->db->quoteName('p.virtuemart_product_id'));

						$this->db->setQuery($nonchildQuery);
						$child_ids = $this->db->loadColumn();
						$this->log->add('Get the children from the filtered categories', true);

						if (is_array($child_ids))
						{
							$nonchild_ids = array_merge($nonchild_ids, $child_ids);
						}
					}

					$query->where('#__virtuemart_products.virtuemart_product_id NOT IN (' . implode(',', $nonchild_ids) . ')');
				}
				else
				{
					if (!empty($category_publish))
					{
						// Get all product IDs
						$q_product_ids = "SELECT p.virtuemart_product_id
									FROM #__virtuemart_products p
									LEFT JOIN #__virtuemart_product_categories x
									ON p.virtuemart_product_id = x.virtuemart_product_id
									LEFT JOIN #__virtuemart_categories c
									ON x.virtuemart_category_id = c.virtuemart_category_id
									WHERE c.published = " . $this->db->Quote($category_publish);
						$this->db->setQuery($q_product_ids);
						$product_ids = $this->db->loadColumn();
						$this->log->add('COM_CSVI_EXPORT_QUERY', true);

						// Get all child product IDs
						if ($product_ids)
						{
							$q_childproduct_ids = "SELECT p.virtuemart_product_id
										FROM #__virtuemart_products p
										WHERE p.product_parent_id IN (" . implode(',', $product_ids) . ")";
							$this->db->setQuery($q_childproduct_ids);
							$childproduct_ids = $this->db->loadColumn();
							$this->log->add('COM_CSVI_EXPORT_QUERY', true);

							// Now we have all the product IDs
							$product_ids = array_merge($product_ids, $childproduct_ids);
						}

						// Check if the user want child products
						if (!empty($product_ids))
						{
							$query->where('#__virtuemart_products.virtuemart_product_id IN (' . implode(',', $product_ids) . ')');
						}
					}
				}
			}

			// Filter on featured products
			$featured = $this->template->get('featured', '');

			if ($featured)
			{
				$query->where('#__virtuemart_products.product_special = 1');
			}

			// Filter by published state
			$product_publish = $this->template->get('publish_state');

			if ($product_publish !== '' && ($product_publish == 1 || $product_publish == 0))
			{
				$query->where('#__virtuemart_products.published = ' . (int) $product_publish);
			}

			// Filter by product SKU
			$productskufilter = $this->template->get('productskufilter');

			if ($productskufilter)
			{
				$productskufilter .= ',';

				if (strpos($productskufilter, ','))
				{
					$skus = explode(',', $productskufilter);
					$wildcard = '';
					$normal = array();

					foreach ($skus as $sku)
					{
						if (!empty($sku))
						{
							if (strpos($sku, '%'))
							{
								$wildcard .= $this->db->quoteName('#__virtuemart_products.product_sku') . ' LIKE ' . $this->db->quote($sku) . ' OR ';
							}
							else
							{
								$normal[] = $this->db->quote($sku);
							}
						}
					}

					if (substr($wildcard, -3) == 'OR ')
					{
						$wildcard = substr($wildcard, 0, -4);
					}

					if (!empty($wildcard) && !empty($normal))
					{
						$query->where('(' . $wildcard . ' OR ' . $this->db->quoteName('#__virtuemart_products.product_sku') . ' IN (' . implode(',', $normal) . '))');
					}
					elseif (!empty($wildcard))
					{
						$query->where('(' . $wildcard . ')');
					}
					elseif (!empty($normal))
					{
						$query->where('(' . $this->db->quoteName('#__virtuemart_products.product_sku') . ' IN (' . implode(',', $normal) . '))');
					}
				}
			}

			// Filter on price shopper group
			$shopper_group_price = $this->template->get('shopper_group_price', array());

			if ($shopper_group_price)
			{
				if ($shopper_group_price == '*')
				{
					$query->where(
						'('
						. $this->db->quoteName('#__virtuemart_product_prices.virtuemart_shoppergroup_id')
						. ' = ' . $this->db->quote(0)
						. ' OR ' . $this->db->quoteName('#__virtuemart_product_prices.virtuemart_shoppergroup_id') . ' IS NULL)'
					);
				}
				elseif ($shopper_group_price != 'none')
				{
					$query->where($this->db->quoteName('#__virtuemart_product_prices.virtuemart_shoppergroup_id') . ' = ' . $this->db->quote($shopper_group_price));
				}
			}

			// Filter on product quantities
			$price_quantity_start = $this->template->get('price_quantity_start', null);

			if (!is_null($price_quantity_start) && $price_quantity_start >= 0)
			{
				$query->where($this->db->quoteName('#__virtuemart_product_prices.price_quantity_start') . ' = ' . $this->db->quote($price_quantity_start));
			}

			$price_quantity_end = $this->template->get('price_quantity_end', null);

			if (!is_null($price_quantity_end) && $price_quantity_end >= 0)
			{
				$query->where($this->db->quoteName('#__virtuemart_product_prices.price_quantity_end') . ' = ' . $this->db->quote($price_quantity_end));
			}

			// Filter on price from
			$priceoperator = $this->template->get('priceoperator', 'gt');
			$pricefrom = $this->template->get('pricefrom', 0, 'float');
			$priceto = $this->template->get('priceto', 0, 'float');

			if (!empty($pricefrom))
			{
				switch ($priceoperator)
				{
					case 'gt':
						$query->where(
							'ROUND('
							. $this->db->quoteName('#__virtuemart_product_prices.product_price') . ', '
							. $this->template->get('export_price_format_decimal', 2, 'int') . ') > ' . $pricefrom
						);
						break;
					case 'eq':
						$query->where(
							'ROUND('
							. $this->db->quoteName('#__virtuemart_product_prices.product_price') . ', '
							. $this->template->get('export_price_format_decimal', 2, 'int') . ') = ' . $pricefrom
						);
						break;
					case 'lt':
						$query->where(
							'ROUND('
							. $this->db->quoteName('#__virtuemart_product_prices.product_price') . ', '
							. $this->template->get('export_price_format_decimal', 2, 'int') . ') < ' . $pricefrom
						);
						break;
					case 'bt':
						$query->where(
							'ROUND('
							. $this->db->quoteName('#__virtuemart_product_prices.product_price') . ', '
							. $this->template->get('export_price_format_decimal', 2, 'int') . ') BETWEEN ' . $pricefrom . ' AND ' . $priceto
						);
						break;
				}
			}

			// Filter by stocklevel start
			$stocklevelstart = $this->template->get('stocklevelstart', 0, 'int');

			if ($stocklevelstart)
			{
				$query->where('#__virtuemart_products.product_in_stock >= ' . (int) $stocklevelstart);
			}

			// Filter by stocklevel end
			$stocklevelend = $this->template->get('stocklevelend', 0, 'int');

			if ($stocklevelend)
			{
				$query->where('#__virtuemart_products.product_in_stock <= ' . (int) $stocklevelend);
			}

			// Filter by shopper group id
			$shopper_group = $this->template->get('shopper_groups', array());

			if ($shopper_group && $shopper_group[0] != 'none')
			{
				$query->where("#__virtuemart_product_shoppergroups.virtuemart_shoppergroup_id IN ('" . implode("','", $shopper_group) . "')");
			}

			// Filter by manufacturer
			$manufacturer = $this->template->get('manufacturers', array());

			if ($manufacturer && !empty($manufacturer) && $manufacturer[0] != 'none')
			{
				$query->where("#__virtuemart_manufacturers.virtuemart_manufacturer_id IN ('" . implode("','", $manufacturer) . "')");
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

					// Reset the prices
					$this->prices = array();

					// Process all the export fields
					foreach ($exportfields as $field)
					{
						// Get the field name
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
							case 'category_id':
								$fieldvalue = trim($this->helper->createCategoryPath($record->virtuemart_product_id, true));
								break;
							case 'category_path':
								$fieldvalue = trim($this->helper->createCategoryPath($record->virtuemart_product_id));
								break;
							case 'category_name':
								$category_path = trim($this->helper->createCategoryPathById($record->virtuemart_category_id));
								$names = explode($this->template->get('category_separator', '/'), $category_path);
								$fieldvalue = array_pop($names);
								break;
							case 'product_name':
							case 'product_s_desc':
							case 'product_desc':
							case 'metadesc':
							case 'metakey':
							case 'slug':
							case 'customtitle':
								$query = $this->db->getQuery(true)
									->select(
										$this->db->quoteName(
											array(
												$fieldname,
												'virtuemart_product_id'
											)
										)
									)
									->from($this->db->quoteName('#__virtuemart_products_' . $language))
									->where($this->db->quoteName('virtuemart_product_id') . ' = ' . (int) $record->virtuemart_product_id, 'OR');

								if ($record->product_parent_id > 0)
								{
									$query->where($this->db->quoteName('virtuemart_product_id') . ' = ' . (int) $record->product_parent_id);
								}

								$this->db->setQuery($query);
								$translation = $this->db->loadAssocList('virtuemart_product_id');
								$fieldvalue = '';

								if (!empty($translation[$record->virtuemart_product_id][$fieldname]))
								{
									$fieldvalue = $translation[$record->virtuemart_product_id][$fieldname];
								}
								elseif (!empty($translation[$record->product_parent_id][$fieldname]))
								{
									$fieldvalue = $translation[$record->product_parent_id][$fieldname];
								}

								if ($fieldname == 'product_name')
								{
									$fieldvalue = html_entity_decode($fieldvalue, ENT_QUOTES, "UTF-8");
								}
								break;
							case 'picture_url':
							case 'picture_url_thumb':
								$query = $this->db->getQuery(true);

								if ($fieldname == 'picture_url_thumb')
								{
									$query->select(
										'CASE WHEN ' . $this->db->quoteName('file_url_thumb') . ' = ' . $this->db->quote('') . '
											THEN CONCAT(' . $this->db->quoteName('file_url') . ',' . $this->db->quote('-_-') . ')
											ELSE ' . $this->db->quoteName('file_url_thumb') . ' END');
								}
								else
								{
									$query->select($this->db->quoteName('file_url'));
								}

								$query->from($this->db->quoteName('#__virtuemart_medias'));
								$query->leftJoin(
									$this->db->quoteName('#__virtuemart_product_medias')
									. ' ON ' . $this->db->quoteName('#__virtuemart_product_medias.virtuemart_media_id')
									. ' = ' . $this->db->quoteName('#__virtuemart_medias.virtuemart_media_id')
								);
								$query->where($this->db->quoteName('virtuemart_product_id') . ' = ' . (int) $record->virtuemart_product_id);
								$query->where($this->db->quoteName('file_mimetype') . ' LIKE ' . $this->db->quote('image/%'));
								$query->order($this->db->quoteName('#__virtuemart_product_medias.ordering'));
								$this->db->setQuery($query, 0, $this->template->get('picture_limit', 1));
								$images = $this->db->loadColumn();

								// If child product has no image get parent product image
								if (count($images) == 0)
								{
									$images = $this->getParentImage($record->product_parent_id, $fieldname);
								}

								foreach ($images as $i => $image)
								{
									// Check if we need to create a dynamic image name
									if (substr($image, -3) == '-_-')
									{
										$width = $this->helperconfig->get('img_width', 90);
										$height = $this->helperconfig->get('img_height', 90);

										// Remove marker
										$image = substr($image, 0, -3);

										// Construct the new image name
										$image = dirname($image)
											. '/resized/'
											. basename(JFile::stripExt($image))
											. '_' . $width . 'x' . $height . '.'
											. JFile::getExt($image);
									}

									$images[$i] = $this->domainname . '/' . $image;
								}

								// Check if there is already a product full image
								$fieldvalue = implode(',', $images);
								break;
							case 'product_parent_sku':
								$query = $this->db->getQuery(true);
								$query->select('product_sku');
								$query->from('#__virtuemart_products');
								$query->where('virtuemart_product_id = ' . $record->product_parent_id);
								$this->db->setQuery($query);

								$fieldvalue = $this->db->loadResult();
								break;
							case 'related_products':
								// Get the custom ID
								$query = $this->db->getQuery(true)
									->select($this->db->quoteName('#__virtuemart_products.product_sku'))
									->from($this->db->quoteName('#__virtuemart_product_customfields'))
									->leftJoin(
										$this->db->quoteName('#__virtuemart_customs')
										. ' ON ' . $this->db->quoteName('#__virtuemart_customs.virtuemart_custom_id')
										. ' = ' . $this->db->quoteName('#__virtuemart_product_customfields.virtuemart_custom_id')
									)
									->leftJoin(
										$this->db->quoteName('#__virtuemart_products')
										. ' ON ' . $this->db->quoteName('#__virtuemart_products.virtuemart_product_id')
										. ' = ' . $this->db->quoteName('#__virtuemart_product_customfields.customfield_value')
									)
									->where($this->db->quoteName('#__virtuemart_customs.field_type') . ' = ' . $this->db->quote('R'))
									->where(
										$this->db->quoteName('#__virtuemart_product_customfields.virtuemart_product_id')
										. ' = ' . $this->db->quote($record->virtuemart_product_id)
									)
									->group($this->db->quoteName('#__virtuemart_products.product_sku'));
								$this->db->setQuery($query);
								$related_records = $this->db->loadColumn();

								if (is_array($related_records))
								{
									$fieldvalue = implode('|', $related_records);
								}
								else
								{
									$fieldvalue = '';
								}
								break;
							case 'related_categories':
								// Get the custom ID
								$query = $this->db->getQuery(true)
									->select($this->db->quoteName('#__virtuemart_product_customfields.customfield_value', 'custom_value'))
									->from($this->db->quoteName('#__virtuemart_product_customfields'))
									->leftJoin(
										$this->db->quoteName('#__virtuemart_customs')
										. ' ON ' . $this->db->quoteName('#__virtuemart_customs.virtuemart_custom_id')
										. ' = ' . $this->db->quoteName('#__virtuemart_product_customfields.virtuemart_custom_id')
									)
									->where($this->db->quoteName('#__virtuemart_customs.field_type') . ' = ' . $this->db->quote('Z'))
									->where(
										$this->db->quoteName('#__virtuemart_product_customfields.virtuemart_product_id')
										. ' = ' . $this->db->quote($record->virtuemart_product_id)
									)
									->group($this->db->quoteName('#__virtuemart_product_customfields.virtuemart_customfield_id'));
								$this->db->setQuery($query);
								$related_records = $this->db->loadColumn();

								if (is_array($related_records))
								{
									$fieldvalue = $this->helper->createCategoryPathById($related_records);
								}
								else
								{
									$fieldvalue = '';
								}
								break;
							case 'product_available_date':
							case 'created_on':
							case 'modified_on':
							case 'locked_on':
								$date = JFactory::getDate($record->$fieldname);
								$fieldvalue = date($this->template->get('export_date_format'), $date->toUnix());
								break;
							case 'product_box':
								if (strpos($record->product_params, '|'))
								{
									$params = explode('|', $record->product_params);

									foreach ($params as $param)
									{
										if ($param)
										{
											list($param_name, $param_value) = explode('=', $param);

											if ($param_name == $fieldname)
											{
												$fieldvalue = str_replace('"', '', $param_value);
											}
										}
									}
								}
								else
								{
									$fieldvalue = '';
								}
								break;
							case 'product_price':
								$fieldvalue = $this->convertPrice($record->product_price, $record->currency_code_3);
								$fieldvalue = $this->formatNumber($fieldvalue);

								if (strlen(trim($fieldvalue)) == 0)
								{
									$fieldvalue = $field->default_value;
								}

								if ($this->template->get('add_currency_to_price'))
								{
									if ($this->template->get('targetcurrency') != '')
									{
										$fieldvalue = $this->template->get('targetcurrency') . ' ' . $fieldvalue;
									}
									else
									{
										$fieldvalue = $record->currency_code_3 . ' ' . $fieldvalue;
									}
								}
								break;
							case 'product_override_price':
								$fieldvalue = $this->formatNumber($record->product_override_price);

								if (strlen(trim($fieldvalue)) == 0)
								{
									$fieldvalue = $field->default_value;
								}

								if ($this->template->get('add_currency_to_price'))
								{
									if ($this->template->get('targetcurrency') != '')
									{
										$fieldvalue = $this->template->get('targetcurrency') . ' ' . $fieldvalue;
									}
									else
									{
										$fieldvalue = $record->currency_code_3 . ' ' . $fieldvalue;
									}
								}
								break;
							case 'product_url':
								// Check if there is already a product URL
								if (is_null($record->product_url) || strlen(trim($record->product_url)) == 0)
								{
									// Get the category id
									// Check to see if we have a child product
									$category_id = $this->helper->getCategoryId($record->virtuemart_product_id);

									if ($category_id == 0 && $record->product_parent_id > 0)
									{
										$category_id = $this->helper->getCategoryId($record->product_parent_id);
									}

									if ($category_id > 0)
									{
										// Let's create a SEF URL
										$_SERVER['QUERY_STRING'] = 'option=com_virtuemart&view=productdetails&virtuemart_product_id='
											. $record->virtuemart_product_id . '&virtuemart_category_id='
											. $category_id . '&Itemid='
											. $this->template->get('vm_itemid', 1, 'int');
										$fieldvalue = $this->sef->getSiteRoute('index.php?' . $_SERVER['QUERY_STRING']);
									}
									else
									{
										$fieldvalue = '';
									}
								}
								// There is a product URL, use it
								else
								{
									$fieldvalue = $record->product_url;
								}

								// Add the suffix
								if (!empty($fieldvalue))
								{
									$fieldvalue .= $this->template->get('producturl_suffix');
								}
								break;
							case 'price_with_tax':
								$prices = $this->getProductPrice($record->virtuemart_product_id, $record->virtuemart_shoppergroup_id);
								$fieldvalue = $this->formatNumber($prices['salesPrice']);

								// Check if we have any content otherwise use the default value
								if (strlen(trim($fieldvalue)) == 0)
								{
									$fieldvalue = $field->default_value;
								}

								if ($this->template->get('add_currency_to_price'))
								{
									$fieldvalue = $record->product_currency . ' ' . $fieldvalue;
								}
								break;
							case 'basepricewithtax':
							case 'discountedpricewithouttax':
							case 'pricebeforetax':
							case 'salesprice':
							case 'taxamount':
							case 'discountamount':
							case 'pricewithouttax':
								$prices = $this->getProductPrice($record->virtuemart_product_id, $record->virtuemart_shoppergroup_id);

								// Retrieve the requested price field
								if (isset($prices[$fieldname]))
								{
									$fieldvalue = $this->formatNumber($prices[$fieldname]);

									// Apply conversion if applicable
									if (!in_array($fieldvalue, array('taxamount', 'discountamount')))
									{
										$fieldvalue = $this->convertPrice($fieldvalue, $record->currency_code_3);
									}
								}
								else
								{
									$fieldvalue = null;
								}

								// Check if we have any content otherwise use the default value
								if (strlen(trim($fieldvalue)) == 0)
								{
									$fieldvalue = $field->default_value;
								}

								// Check if the currency needs to be added
								if ($this->template->get('add_currency_to_price'))
								{
									$fieldvalue = $record->currency_code_3 . ' ' . $fieldvalue;
								}

								// Export the data
								break;
							case 'product_currency':
								$fieldvalue = $record->currency_code_3;

								// Check if we have any content otherwise use the default value
								if ($this->template->get('targetcurrency') != '')
								{
									$fieldvalue = $this->template->get('targetcurrency');
								}
								break;
							case 'custom_shipping':
								// Get the prices
								$prices = $this->getProductPrice($record->virtuemart_product_id, $record->virtuemart_shoppergroup_id);

								// Check the shipping cost
								if (isset($prices['salesprice']))
								{
									$price_with_tax = $this->formatNumber($prices['salesprice']);
									$result = $this->helper->shippingCost($price_with_tax);

									if ($result)
									{
										$fieldvalue = $result;
									}
								}
								break;
							case 'manufacturer_name':
								$query = $this->db->getQuery(true)
									->select($this->db->quoteName('mf_name'))
									->from($this->db->quoteName('#__virtuemart_manufacturers_' . $language))
									->leftJoin(
										$this->db->quoteName('#__virtuemart_product_manufacturers')
										. ' ON ' . $this->db->quoteName('#__virtuemart_product_manufacturers.virtuemart_manufacturer_id')
										. ' = ' . $this->db->quoteName('#__virtuemart_manufacturers_' . $language . '.virtuemart_manufacturer_id')
									)
									->where($this->db->quoteName('virtuemart_product_id') . ' = ' . (int) $record->virtuemart_product_id);
								$this->db->setQuery($query);
								$fieldvalue = implode('|', $this->db->loadColumn());
								break;
							case 'custom_title':
								// Get the custom title
								$query = $this->db->getQuery(true);
								$query->select($this->db->quoteName('custom_title'));
								$query->from($this->db->quoteName('#__virtuemart_customs', 'c'));
								$query->leftJoin($this->db->quoteName('#__virtuemart_product_customfields', 'f') . ' ON c.virtuemart_custom_id = f.virtuemart_custom_id');
								$query->where($this->db->quoteName('virtuemart_product_id') . ' = ' . (int) $record->virtuemart_product_id);
								$query->where($this->db->quoteName('field_type') . ' NOT IN (' . $this->db->quote('R') . ', ' . $this->db->quote('Z') . ')');

								// Check if we need to filter
								$title_filter = $this->template->get('custom_title', array(), 'array');

								if (!empty($title_filter) && $title_filter[0] != '')
								{
									$query->where($this->db->quoteName('f.virtuemart_custom_id') . ' IN (' . implode(',', $title_filter) . ')');
								}

								$query->order($this->db->quoteName('f.ordering'), $this->db->quoteName('f.virtuemart_custom_id'));
								$this->db->setQuery($query);
								$titles = $this->db->loadColumn();

								if (is_array($titles))
								{
									$fieldvalue = implode('~', $titles);
								}
								else
								{
									$fieldvalue = '';
								}
								break;
							case 'custom_value':
							case 'custom_price':
							case 'custom_param':
							case 'custom_ordering':
								// Do some field sanity check if needed
								if ($fieldname != 'custom_ordering')
								{
									$fieldname = str_ireplace(array('custom_', '_param'), array('customfield_', '_params'), $fieldname);
								}

								if (!isset($this->customfields[$record->virtuemart_product_id][$fieldname]))
								{
									if ($fieldname == 'custom_ordering')
									{
										$qfield = $this->db->quoteName('cf.ordering', 'custom_ordering');
									}
									else
									{
										$qfield = $this->db->quoteName($fieldname);
									}

									$query = $this->db->getQuery(true)
										->select($qfield)
										->select(
											$this->db->quoteName(
												array(
													'cf.virtuemart_customfield_id',
													'cf.virtuemart_custom_id',
													'cf.customfield_params',
													'c.field_type',
													'c.custom_element',
												)
											)
										)
										->from($this->db->quoteName('#__virtuemart_product_customfields', 'cf'))
										->leftJoin(
											$this->db->quoteName('#__virtuemart_customs', 'c')
											. ' ON ' . $this->db->quoteName('c.virtuemart_custom_id') . ' = ' . $this->db->quoteName('cf.virtuemart_custom_id')
										)
										->where($this->db->quoteName('cf.virtuemart_product_id') . ' = ' . (int) $record->virtuemart_product_id);

									// Check if we need to filter
									$title_filter = $this->template->get('custom_title', array());

									if (!empty($title_filter) && $title_filter[0] != '')
									{
										$query->where($this->db->quoteName('cf.virtuemart_custom_id') . ' IN (' . implode(',', $title_filter) . ')');
									}

									$query->order(
										array(
											$this->db->quoteName('cf.ordering'),
											$this->db->quoteName('cf.virtuemart_custom_id')
										)
									);
									$this->db->setQuery($query);
									$customfields = $this->db->loadObjectList();
									$this->log->add('Custom field query');

									if (!empty($customfields))
									{
										$values = array();

										foreach ($customfields as $customfield)
										{
											if ($fieldname == 'customfield_params' && $customfield->field_type != 'C')
											{
												// Fire the plugin to empty any values needed
												$result = $dispatcher->trigger(
													'exportCustomValues',
													array(
														'plugin' => $customfield->custom_element,
														'custom_param' => $customfield->customfield_params,
														'virtuemart_product_id' => $record->virtuemart_product_id,
														'virtuemart_custom_id' => $customfield->virtuemart_custom_id,
														'virtuemart_customfield_id' => $customfield->virtuemart_customfield_id,
														'log' => $this->log
													)
												);

												if (is_array($result) && !empty($result))
												{
													$values = array_merge($values, $result[0]);
												}
												else
												{
													// Create the CSVI format
													// option1[value1#value2;option2[value1#value2
													$values[] = $customfield->customfield_params;
												}
											}
											else
											{
												if (!empty($customfield->$fieldname))
												{
													$fieldvalue = $customfield->$fieldname;

													// Apply currency formatting
													if ($fieldname == 'customfield_price')
													{
														$fieldvalue = $this->formatNumber($customfield->$fieldname);
													}

													$values[] = $fieldvalue;
												}
												else
												{
													$values[] = '';
												}
											}
										}

										$this->customfields[$record->virtuemart_product_id][$fieldname] = $values;
										$fieldvalue = implode('~', $this->customfields[$record->virtuemart_product_id][$fieldname]);
									}
									else
									{
										$fieldvalue = '';
									}
								}
								else
								{
									$fieldvalue = implode('~', $this->customfields[$record->virtuemart_product_id][$fieldname]);
								}
								break;
							case 'file_url':
							case 'file_url_thumb':
							case 'file_title':
							case 'file_description':
							case 'file_meta':
							case 'file_lang':
								$query = $this->db->getQuery(true)
									->select($this->db->quoteName($fieldname))
									->from($this->db->quoteName('#__virtuemart_medias', 'm'))
									->leftJoin(
										$this->db->quoteName('#__virtuemart_product_medias', 'p')
										. ' ON ' . $this->db->quoteName('m.virtuemart_media_id') . ' = ' . $this->db->quoteName('p.virtuemart_media_id')
									)
									->where($this->db->quoteName('virtuemart_product_id') . ' = ' . (int) $record->virtuemart_product_id)
									->where($this->db->quoteName('file_type') . ' = ' . $this->db->quote('product'))
									->order('p.ordering');
								$this->db->setQuery($query);
								$titles = $this->db->loadColumn();

								if (is_array($titles))
								{
									$fieldvalue = implode('|', $titles);
								}
								else
								{
									$fieldvalue = '';
								}
								break;
							case 'file_ordering':
								$query = $this->db->getQuery(true)
									->select($this->db->quoteName('p.ordering'))
									->from($this->db->quoteName('#__virtuemart_medias', 'm'))
									->leftJoin($this->db->quoteName('#__virtuemart_product_medias', 'p') . ' ON m.virtuemart_media_id = p.virtuemart_media_id')
									->where($this->db->quoteName('virtuemart_product_id') . ' = ' . (int) $record->virtuemart_product_id)
									->where($this->db->quoteName('file_type') . ' = ' . $this->db->quote('product'))
									->order('p.ordering');
								$this->db->setQuery($query);
								$titles = $this->db->loadColumn();

								if (is_array($titles))
								{
									$fieldvalue = implode('|', $titles);
								}
								else
								{
									$fieldvalue = '';
								}
								break;
							case 'min_order_level':
							case 'max_order_level':
							case 'step_order_level':
								if (strpos($record->product_params, '|'))
								{
									$params = explode('|', $record->product_params);

									foreach ($params as $param)
									{
										if ($param)
										{
											list($param_name, $param_value) = explode('=', $param);

											if ($param_name == $fieldname)
											{
												$fieldvalue = str_replace('"', '', $param_value);
											}
										}
									}
								}
								else
								{
									$fieldvalue = '';
								}
								break;
							case 'shopper_group_name':
								$query = $this->db->getQuery(true)
									->select($this->db->quoteName($fieldname))
									->from($this->db->quoteName('#__virtuemart_shoppergroups', 'g'))
									->leftJoin(
										$this->db->quoteName('#__virtuemart_product_shoppergroups', 'p') .
										' ON g.virtuemart_shoppergroup_id = p.virtuemart_shoppergroup_id'
									)
									->where($this->db->quoteName('virtuemart_product_id') . ' = ' . $this->db->quote($record->virtuemart_product_id));
								$this->db->setQuery($query);
								$this->log->add('Get shopper group', true);
								$titles = $this->db->loadColumn();

								if (is_array($titles))
								{
									$fieldvalue = implode('|', $titles);
								}
								else
								{
									$fieldvalue = '';
								}
								break;
							case 'shopper_group_name_price':
								if ($record->virtuemart_shoppergroup_id > 0)
								{
									$query = $this->db->getQuery(true)
										->select($this->db->quoteName('shopper_group_name'))
										->from($this->db->quoteName('#__virtuemart_shoppergroups', 'g'))
										->where($this->db->quoteName('virtuemart_shoppergroup_id') . ' = ' . $this->db->quote($record->virtuemart_shoppergroup_id));
									$this->db->setQuery($query);
									$this->log->add('Get price shopper group', true);
									$fieldvalue = $this->db->loadResult();
								}
								else
								{
									$fieldvalue = '*';
								}
								break;
							case 'custom':
								if (strlen(trim($fieldvalue)) == 0)
								{
									$fieldvalue = $field->default_value;
								}
								break;
							case 'product_discount':
								$query = $this->db->getQuery(true);
								$query->select('calc_value_mathop, calc_value');
								$query->from($this->db->quoteName('#__virtuemart_calcs', 'c'));
								$query->where($this->db->quoteName('virtuemart_calc_id') . ' = ' . $this->db->quote($record->product_discount_id));
								$this->db->setQuery($query);
								$discount = $this->db->loadObject();

								if (is_object($discount))
								{
									$fieldvalue = $this->formatNumber($discount->calc_value);

									if (stristr($discount->calc_value_mathop, '%') !== false)
									{
										$fieldvalue .= '%';
									}
								}
								else
								{
									$fieldvalue = '';
								}
								break;
							case 'product_attribute':
								$options = json_decode($fieldvalue);

								$values = array();

								if (is_object($options))
								{
									foreach ($options as $option)
									{
										if (is_array($option) || is_object($option))
										{
											foreach ($option as $option_type => $value)
											{
												if (is_array($value))
												{
													foreach ($value as $option_field => $text)
													{
														$values[] = $text;
													}
												}
												else
												{
													// Get the value from the product custom fields
													$query = $this->db->getQuery(true)
														->select($this->db->quoteName('customfield_value'))
														->from($this->db->quoteName('#__virtuemart_product_customfields'))
														->where($this->db->quoteName('virtuemart_customfield_id') . ' = ' . (int) $option_type);
													$this->db->setQuery($query);
													$values[] = $this->db->loadResult();
												}
											}
										}
										else
										{
											// Get the value from the product custom fields
											$query = $this->db->getQuery(true)
												->select($this->db->quoteName('customfield_value'))
												->from($this->db->quoteName('#__virtuemart_product_customfields'))
												->where($this->db->quoteName('virtuemart_customfield_id') . ' = ' . (int) $option);
											$this->db->setQuery($query);
											$values[] = $this->db->loadResult();
										}
									}

									$fieldvalue = implode('|', $values);
								}

								if (strlen(trim($fieldvalue)) == 0)
								{
									$fieldvalue = $field->default_value;
								}
								break;
							default:
								// See if we need to retrieve a custom field
								if (in_array($fieldname, $this->customfieldsExport))
								{
									$query = $this->db->getQuery(true)
										->select($this->db->quoteName('p.customfield_value'))
										->from($this->db->quoteName('#__virtuemart_product_customfields', 'p'))
										->leftJoin(
											$this->db->quoteName('#__virtuemart_customs', 'c')
											. ' ON ' . $this->db->quoteName('p.virtuemart_custom_id') . ' = ' . $this->db->quoteName('c.virtuemart_custom_id')
										)
										->where($this->db->quoteName('c.custom_title') . ' = ' . $this->db->quote($fieldname))
										->where($this->db->quoteName('p.virtuemart_product_id') . ' = ' . (int) $record->virtuemart_product_id);
									$this->db->setQuery($query);
									$fieldvalue = $this->db->loadResult();
								}
								elseif (in_array($fieldname, $this->multivariantExport))
								{
									$fieldvalue = $this->getMultivariantValue($record, $fieldname);
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

	/**
	 * Convert prices to the new currency.
	 *
	 * @param   float   $product_price     The price to convert
	 * @param   string  $product_currency  The currency to convert to
	 *
	 * @return  float  A converted price.
	 *
	 * @since   4.0
	 */
	private function convertPrice($product_price, $product_currency)
	{
		if (empty($product_price))
		{
			return $product_price;
		}
		else
		{
			// See if we need to convert the price
			if ($this->template->get('targetcurrency', '') != '')
			{
				$query = $this->db->getQuery(true);
				$query->select($this->db->quoteName('currency_code') . ', ' . $this->db->quoteName('currency_rate'));
				$query->from($this->db->quoteName('#__csvi_currency'));
				$query->where(
					$this->db->quoteName('currency_code')
					. ' IN ('
					. $this->db->quote($product_currency) . ', ' . $this->db->quote($this->template->get('targetcurrency', 'EUR'))
					. ')'
				);
				$this->db->setQuery($query);
				$rates = $this->db->loadObjectList('currency_code');

				// Convert to base price
				$baseprice = $product_price / $rates[strtoupper($product_currency)]->currency_rate;

				// Convert to destination currency
				return $baseprice * $rates[strtoupper($this->template->get('targetcurrency', 'EUR'))]->currency_rate;
			}
			else
			{
				return $product_price;
			}
		}
	}

	/**
	 * Get product prices.
	 *
	 * @param   int  $product_id                  The ID of the product.
	 * @param   int  $virtuemart_shoppergroup_id  The ID of the price shopper group.
	 *
	 * @return array List of prices.
	 *
	 * @since   4.0
	 */
	private function getProductPrice($product_id, $virtuemart_shoppergroup_id)
	{
		$sid = $virtuemart_shoppergroup_id;

		if (!isset($this->prices[$sid][$product_id]))
		{
			// Define VM constant to make the classes work
			if (!defined('JPATH_VM_ADMINISTRATOR'))
			{
				define('JPATH_VM_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_virtuemart/');
			}

			// Load the calculation helper
			require_once JPATH_ADMINISTRATOR . '/components/com_csvi/addon/com_virtuemart/helper/com_virtuemart_calculation.php';

			// Load the configuration for the currency formatting
			require_once JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/config.php';

			// Load the VirtueMart configuration
			VmConfig::loadConfig();

			// Load the calculation helper
			/** @var CsviVmPrices $calc */
			$calc = CsviVmPrices::getInstance();

			// Check if we need to use a template shopper group
			$force = $this->template->get('force_shopper_group_price', 'none');
			$sid = $virtuemart_shoppergroup_id;
			$setShopperGroup = true;

			if ($force !== 'none')
			{
				// Force the shopper group ID
				$virtuemart_shoppergroup_id = (int) $force;

				// Set a shopper group identifier for caching prices
				$sid = $virtuemart_shoppergroup_id;

				// Make the shopper groups an array if it is not 0, so the selected shopper groups are used
				if ($virtuemart_shoppergroup_id !== 0)
				{
					$virtuemart_shoppergroup_id = (array) $virtuemart_shoppergroup_id;
				}
				else
				{
					$setShopperGroup = false;
				}
			}
			else
			{
				$virtuemart_shoppergroup_id = (array) $virtuemart_shoppergroup_id;
			}

			// Set the shopper group
			if ($setShopperGroup)
			{
				$calc->setShopperGroup($virtuemart_shoppergroup_id);
			}

			$this->log->add('Use shopper group ID: ' . $sid);

			// Load the product helper
			require_once JPATH_ADMINISTRATOR . '/components/com_virtuemart/models/product.php';
			$product = new VirtueMartModelProduct;

			// Get the prices
			$product = $product->getProductSingle($product_id, true, 1, false, $virtuemart_shoppergroup_id);
			$prices = $calc->getProductPrices($product);

			if (is_array($prices))
			{
				$this->prices[$sid][$product_id] = array_change_key_case($prices, CASE_LOWER);
			}
			else
			{
				$this->prices[$sid][$product_id] = array();
			}
		}

		return $this->prices[$sid][$product_id];
	}

	/**
	 * Get a list of custom fields that can be used as available field.
	 *
	 * @return  void.
	 *
	 * @since   4.4.1
	 */
	private function loadCustomFields()
	{
		$query = $this->db->getQuery(true);
		$query->select('TRIM(' . $this->db->quoteName('custom_title') . ') AS ' . $this->db->quoteName('title'));
		$query->from($this->db->quoteName('#__virtuemart_customs'));
		$query->where(
			$this->db->quoteName('field_type') . ' IN ('
			. $this->db->quote('S') . ','
			. $this->db->quote('I') . ','
			. $this->db->quote('B') . ','
			. $this->db->quote('D') . ','
			. $this->db->quote('T') . ','
			. $this->db->quote('M') . ','
			. $this->db->quote('Y') . ','
			. $this->db->quote('X') .
			')'
		);
		$this->db->setQuery($query);
		$result = $this->db->loadColumn();

		if (!is_array($result))
		{
			$result = array();
		}

		$this->customfieldsExport = $result;
	}

	/**
	 * Get a list of multi variant fields that can be used as available field.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function loadMultiVariantFields()
	{
		// Use the ramifications of the Multi Variant plugin as regular fields
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('cf.customfield_params'))
			->from($this->db->quoteName('#__virtuemart_customs', 'c'))
			->leftJoin(
				$this->db->quoteName('#__virtuemart_product_customfields', 'cf')
				. ' ON ' . $this->db->quoteName('cf.virtuemart_custom_id') . ' = ' . $this->db->quoteName('c.virtuemart_custom_id')
			)
			->where($this->db->quoteName('c.field_type') . ' = ' . $this->db->quote('C'));
		$this->db->setQuery($query);

		$params = $this->db->loadColumn();

		foreach ($params as $param)
		{
			// Get the different segments
			$segments = explode('|', $param);

			// Process each segment
			foreach ($segments as $segment)
			{
				if ($firstpos = stripos($segment, '='))
				{
					// Get the values
					$group = substr($segment, 0, $firstpos);
					$value = substr($segment, $firstpos + 1);

					if ($group == 'selectoptions')
					{
						$values = json_decode($value);

						foreach ($values as $ramification)
						{
							$csvi_name = trim($ramification->voption);

							if ($ramification->voption == 'clabels')
							{
								$csvi_name = trim($ramification->clabel);
							}

							$this->multivariantExport[] = $csvi_name;
						}
					}
				}
			}
		}
	}

	/**
	 * Get the multi variant value.
	 *
	 * @param   object  $record     The current record with data.
	 * @param   string  $fieldname  The name of the field to get the value for.
	 *
	 * @return string The value found.
	 *
	 * @since   6.0
	 */
	private function getMultivariantValue($record, $fieldname)
	{
		$virtuemart_product_id = (int) $record->virtuemart_product_id;
		$parent_id = (int) $record->virtuemart_product_id;

		// See if this is a child product
		if ($record->product_parent_id > 0)
		{
			$parent_id = (int) $record->product_parent_id;
		}

		$query = $this->db->getQuery(true)
			->select(
				$this->db->quoteName(
					array(
						'cf.virtuemart_customfield_id',
						'cf.virtuemart_custom_id',
						'cf.customfield_value',
						'cf.customfield_params',
					)
				)
			)
			->from($this->db->quoteName('#__virtuemart_product_customfields', 'cf'))
			->leftJoin(
				$this->db->quoteName('#__virtuemart_customs', 'c')
				. ' ON ' . $this->db->quoteName('c.virtuemart_custom_id') . ' = ' . $this->db->quoteName('cf.virtuemart_custom_id')
			)
			->where($this->db->quoteName('virtuemart_product_id') . ' = ' . $parent_id)
			->where($this->db->quoteName('c.field_type') . ' = ' . $this->db->quote('C'));

		$this->db->setQuery($query);
		$customfield = $this->db->loadObject();
		$this->log->add('Load the multi variant parameters');

		// Set the variables
		$selectoptions = array();
		$options = array();
		$position = false;
		$fieldvalue = '';

		if (isset($customfield->customfield_params))
		{
			// Get the different segments
			$segments = explode('|', $customfield->customfield_params);

			// Process each segment
			foreach ($segments as $segment)
			{
				if ($firstpos = stripos($segment, '='))
				{
					// Get the values
					$group = substr($segment, 0, $firstpos);
					$value = substr($segment, $firstpos + 1);

					// Assign the value to it's group
					$$group = json_decode($value);
				}
			}

			// Find the position of the field
			foreach ($selectoptions as $position => $selectoption)
			{
				$csvi_name = trim($selectoption->voption);

				if ($selectoption->voption == 'clabels')
				{
					$csvi_name = trim($selectoption->clabel);
				}

				if ($csvi_name == $fieldname)
				{
					break;
				}
			}

			if ($position !== false && isset($options->$virtuemart_product_id))
			{
				$values = $options->$virtuemart_product_id;

				if (isset($values[$position]))
				{
					$fieldvalue = $values[$position];

					// Check if there is dot in the value, it is usually a price value
					if (is_numeric($fieldvalue))
					{
						$fieldvalue = $this->formatNumber($fieldvalue);
					}
				}
			}
		}

		return $fieldvalue;
	}

	/**
	 * Format a value to a number.
	 *
	 * @param   float  $fieldvalue  The value to format as number.
	 *
	 * @return  string  The formatted number.
	 *
	 * @since   6.0
	 */
	private function formatNumber($fieldvalue)
	{
		return number_format(
			$fieldvalue,
			$this->template->get('export_price_format_decimal', 2, 'int'),
			$this->template->get('export_price_format_decsep'),
			$this->template->get('export_price_format_thousep')
		);
	}

	/**
	 * Get parent image for a product.
	 *
	 * @param   int     $productParentId  The product ID to get the image.
	 * @param   string  $fieldname        The fieldname to export.
	 *
	 * @return  string image url .
	 *
	 * @since  6.3.0
	 */
	private function getParentImage($productParentId, $fieldname)
	{
		$query = $this->db->getQuery(true);

		if ($fieldname == 'picture_url_thumb')
		{
			$query->select(
				'CASE WHEN ' . $this->db->quoteName('file_url_thumb') . ' = ' . $this->db->quote('') . '
											THEN CONCAT(' . $this->db->quoteName('file_url') . ',' . $this->db->quote('-_-') . ')
											ELSE ' . $this->db->quoteName('file_url_thumb') . ' END ');
		}
		else
		{
			$query->select($this->db->quoteName('file_url'));
		}

		$query->from($this->db->quoteName('#__virtuemart_medias'))
			->leftJoin(
				$this->db->quoteName('#__virtuemart_product_medias')
				. ' ON ' . $this->db->quoteName('#__virtuemart_product_medias.virtuemart_media_id')
				. ' = ' . $this->db->quoteName('#__virtuemart_medias.virtuemart_media_id')
			)
			->where($this->db->quoteName('virtuemart_product_id') . ' = ' . (int) $productParentId)
			->where($this->db->quoteName('file_mimetype') . ' LIKE ' . $this->db->quote('image/%'))
			->order($this->db->quoteName('#__virtuemart_product_medias.ordering'));

		$this->db->setQuery($query, 0, 1);

		$imageURL = $this->db->loadColumn();

		// Debug log entry for query
		$this->log->add(JText::_('COM_CSVI_FIND_PRODUCT_PARENT_IMAGE'), true);

		// Do the check till the image is retrieved from product anchestors
		if (count($imageURL) == 0)
		{
			// Check if the product parent id is not 0
			if (!is_null($productParentId))
			{
				// Get the next level parent id
				$anchestorParentId = $this->getProductParentId($productParentId);

				// If current product has no image check the parent image
				$this->getParentImage($anchestorParentId, $fieldname);
			}
			else
			{
				$imageURL = array();
			}
		}

		// Return the product parent image
		return $imageURL;
	}

	/**
	 * Get the parent ID for a product.
	 *
	 * @param   int  $productId  The product ID to get the parent for
	 *
	 * @return  int  The parent product ID.
	 *
	 * @since   3.0
	 */

	private function getProductParentId($productId)
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('product_parent_id'))
			->from($this->db->quoteName('#__virtuemart_products'))
			->where($this->db->quoteName('virtuemart_product_id') . ' = ' . (int) $productId);
		$this->db->setQuery($query);

		return $this->db->loadResult();
	}
}
