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
 * Export VirtueMart products for Yandex.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelExportYandex extends CsviModelExports
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
	 * The type of field
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

			$jinput = JFactory::getApplication()->input;
			$this->domainname = $this->settings->get('hostname');
			$this->loadCustomFields();

			// Set the language
			$jinput->set('vmlang', substr($language, 0, 2) . '-' . strtoupper(substr($language, 3)));

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
						$userfields[] = $this->db->quoteName('#__virtuemart_product_categories.virtuemart_category_id');
						$userfields[] = $this->db->quoteName('#__virtuemart_products.virtuemart_product_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_product_categories.virtuemart_category_id');
							$groupby[] = $this->db->quoteName('#__virtuemart_products.virtuemart_product_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_product_categories.virtuemart_category_id');
							$sortby[] = $this->db->quoteName('#__virtuemart_products.virtuemart_product_id');
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
					case 'product_name':
					case 'product_s_desc':
					case 'product_desc':
					case 'metadesc':
					case 'metakey':
					case 'slug':
					case 'customtitle':
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
					case 'file_ordering':
					case 'shopper_group_name':
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
					case 'product_parent_sku':
						$userfields[] = $this->db->quoteName('#__virtuemart_products.product_parent_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_products.product_parent_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_products.product_parent_id');
						}
						break;
					case 'related_products':
					case 'related_categories':
						$userfields[] = $this->db->quoteName('#__virtuemart_products.virtuemart_product_id', 'main_product_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_products.virtuemart_product_id', 'main_product_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_products.virtuemart_product_id', 'main_product_id');
						}
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
					case 'product_url':
						$userfields[] = $this->db->quoteName('#__virtuemart_products.virtuemart_product_id');
						$userfields[] = $this->db->quoteName('#__virtuemart_products.product_url');
						$userfields[] = $this->db->quoteName('#__virtuemart_products.product_parent_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_products.virtuemart_product_id');
							$groupby[] = $this->db->quoteName('#__virtuemart_products.product_url');
							$groupby[] = $this->db->quoteName('#__virtuemart_products.virtuemart_product_id');
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
						$userfields[] = $this->db->quoteName('#__virtuemart_products.virtuemart_product_id');
						$userfields[] = $this->db->quoteName('#__virtuemart_currencies.currency_code_3');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_products.virtuemart_product_id');
							$groupby[] = $this->db->quoteName('#__virtuemart_currencies.currency_code_3');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_products.virtuemart_product_id');
							$sortby[] = $this->db->quoteName('#__virtuemart_currencies.currency_code_3');
						}
						break;
					case 'custom_shipping':
						$userfields[] = $this->db->quoteName('#__virtuemart_product_prices.product_price');
						$userfields[] = '1 AS tax_rate';

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_product_prices.product_price');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_product_prices.product_price');
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
					case 'custom':
					case 'picture_url':
					case 'picture_url_thumb':
					case 'manufacturer_name':
						break;
					default:
						if (!in_array($field->field_name, $this->customfieldsExport))
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

			/** Export SQL Query
			 * Get all products - including items
			 * as well as products without a price
			 */
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
				$this->db->quoteName('#__virtuemart_categories')
				. ' ON ' . $this->db->quoteName('#__virtuemart_product_categories.virtuemart_category_id') . ' = ' . $this->db->quoteName('#__virtuemart_categories.virtuemart_category_id')
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

		if ($productcategories && $productcategories[0] != '')
		{
			$product_ids = array();

			// If selected get products of all subcategories as well
			if ($this->template->get('incl_subcategory', false))
			{
				$q_subcat_ids = "SELECT category_child_id
									FROM #__virtuemart_category_categories
									WHERE category_parent_id IN ('" . implode("','", $productcategories) . "')";
				$this->db->setQuery($q_subcat_ids);
				$subcat_ids = $this->db->loadColumn();
				$productcategories = array_merge($productcategories, $subcat_ids);
				$this->log->add('COM_CSVI_EXPORT_QUERY', true);
			}

			// Get only the parent products and products without children
			if ($this->template->get('parent_only', 0, 'bool'))
			{
				// Get all product IDs in the selected categories
				$q_product_ids = "SELECT p.virtuemart_product_id
							FROM #__virtuemart_products p
							LEFT JOIN #__virtuemart_product_categories x
							ON p.virtuemart_product_id = x.virtuemart_product_id
							WHERE x.virtuemart_category_id IN ('" . implode("','", $productcategories) . "')
							AND p.product_parent_id = 0";
				$this->db->setQuery($q_product_ids);
				$product_ids = $this->db->loadColumn();
				$this->log->add('COM_CSVI_EXPORT_QUERY', true);
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
				$query->where('#__virtuemart_products.virtuemart_product_id IN (\'' . implode("','", $product_ids) . '\')');
			}
		}
		else
		{
				// Filter by published category state
				$category_publish = $this->template->get('publish_state_categories');

				// Filter on parent products and products without children
				if ($this->template->get('parent_only', 0, 'bool'))
				{
					$query->where($this->db->quoteName('#__virtuemart_products.product_parent_id' . ' = 0'));

					if (!empty($category_publish))
					{
						$query->where($this->db->quoteName('#__virtuemart_categories.published') . ' = ' . (int) $category_publish);
					}
				}

				// Filter on child products and products without children
				elseif ($this->template->get('child_only', 0, 'bool'))
				{
					// Load all non child IDs
					$q_nonchild = 'SELECT #__virtuemart_products.virtuemart_product_id FROM #__virtuemart_products ';
					$state = ($category_publish == '1') ? '0' : '1';

					if (!empty($category_publish))
					{
						$q_nonchild .= 'LEFT JOIN #__virtuemart_product_categories
									ON #__virtuemart_products.virtuemart_product_id = #__virtuemart_product_categories.virtuemart_product_id
									LEFT JOIN #__virtuemart_categories
									ON #__virtuemart_product_categories.virtuemart_category_id = #__virtuemart_categories.virtuemart_category_id
									WHERE #__virtuemart_categories.published = ' . (int) $state;
					}

					$this->db->setQuery($q_nonchild);
					$nonchild_ids = $this->db->loadColumn();
					$this->log->add('COM_CSVI_EXPORT_QUERY', true);

					// Get the child IDs from the filtered category
					if (!empty($category_publish))
					{
						$q_nonchild = 'SELECT #__virtuemart_products.virtuemart_product_id FROM #__virtuemart_products ';
						$q_nonchild .= 'LEFT JOIN #__virtuemart_product_categories
									ON #__virtuemart_products.virtuemart_product_id = #__virtuemart_product_categories.virtuemart_product_id
									LEFT JOIN #__virtuemart_categories
									ON #__virtuemart_product_categories.virtuemart_category_id = #__virtuemart_categories.virtuemart_category_id
									WHERE #__virtuemart_products.product_parent_id IN (\'' . implode("','", $nonchild_ids) . '\')';
						$q_nonchild .= ' GROUP BY virtuemart_product_id';
						$this->db->setQuery($q_nonchild);
						$child_ids = $this->db->loadColumn();
						$this->log->add('COM_CSVI_EXPORT_QUERY', true);

						if (is_array($child_ids))
						{
							$nonchild_ids = array_merge($nonchild_ids, $child_ids);
						}
					}

					$query->where('#__virtuemart_products.virtuemart_product_id NOT IN (\'' . implode("','", $nonchild_ids) . '\')');
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
									WHERE c.category_publish = " . $this->db->Quote($category_publish);
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

						// Check if the user want child products
						if (!empty($product_ids))
						{
							$query->where('#__virtuemart_products.virtuemart_product_id IN (\'' . implode("','", $product_ids) . '\')');
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
								$wildcard .= "#__virtuemart_products.product_sku LIKE ".$this->db->quote($sku)." OR ";
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
						$query->where("(".$wildcard." OR #__virtuemart_products.product_sku IN (" . implode(',', $normal) . "))");
					}
					else if (!empty($wildcard))
					{
						$query->where("(" . $wildcard . ")");
					}
					else if (!empty($normal))
					{
						$query->where("(#__virtuemart_products.product_sku IN (" . implode(',', $normal) . "))");
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
						'(' .
							$this->db->quoteName('#__virtuemart_product_prices.virtuemart_shoppergroup_id')
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
				// Load all the categories
				$categories = $this->loadCategories();
				$this->addExportContent($this->exportclass->categories($categories));

				// Add the offers
				$this->addExportContent('<offers>' . chr(10));

				while ($record = $this->csvidb->getRow())
				{
					$this->log->incrementLinenumber();

					// Reset the prices
					$this->prices = array();

					// Process all the export fields
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
							case 'category_id':
								$fieldvalue = trim($this->helper->createCategoryPath($record->virtuemart_product_id, true));
								break;
							case 'category_path':
								$fieldvalue = trim($this->helper->createCategoryPath($record->virtuemart_product_id));
								break;
							case 'product_name':
							case 'product_s_desc':
							case 'product_desc':
							case 'metadesc':
							case 'metakey':
							case 'slug':
							case 'customtitle':
								$query = $this->db->getQuery(true);
								$query->select($fieldname);
								$query->from('#__virtuemart_products_' . $language);
								$query->where('virtuemart_product_id = ' . $record->virtuemart_product_id);
								$this->db->setQuery($query);
								$fieldvalue = $this->db->loadResult();
								break;
							case 'picture_url':
							case 'picture_url_thumb':
								$query = $this->db->getQuery(true);

								if ($fieldname == 'picture_url_thumb')
								{
									$query->select('file_url_thumb');
								}
								else
								{
									$query->select('file_url');
								}

								$query->from('#__virtuemart_medias');
								$query->leftJoin('#__virtuemart_product_medias ON #__virtuemart_product_medias.virtuemart_media_id = #__virtuemart_medias.virtuemart_media_id');
								$query->where('virtuemart_product_id = ' . $record->virtuemart_product_id);
								$query->where($this->db->quoteName('file_mimetype') . ' LIKE ' . $this->db->quote('image/%'));
								$query->order('#__virtuemart_product_medias.ordering');
								$this->db->setQuery($query, 0, $this->template->get('picture_limit', 1));
								$images = $this->db->loadColumn();

								foreach ($images as $i => $image)
								{
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
								$related_records = array();
								$query = $this->db->getQuery(true);
								$query->select($this->db->quoteName('#__virtuemart_products.product_sku'));
								$query->from($this->db->quoteName('#__virtuemart_product_customfields'));
								$query->leftJoin($this->db->quoteName('#__virtuemart_customs') . ' ON ' . $this->db->quoteName('#__virtuemart_customs.virtuemart_custom_id') . ' = ' . $this->db->quoteName('#__virtuemart_product_customfields.virtuemart_custom_id'));
								$query->leftJoin($this->db->quoteName('#__virtuemart_products') . ' ON ' . $this->db->quoteName('#__virtuemart_products.virtuemart_product_id') . ' = ' . $this->db->quoteName('#__virtuemart_product_customfields.custom_value'));
								$query->where($this->db->quoteName('#__virtuemart_customs.field_type') . ' = ' . $this->db->quote('R'));
								$query->where($this->db->quoteName('#__virtuemart_product_customfields.virtuemart_product_id') . ' = ' . $this->db->quote($record->virtuemart_product_id));
								$query->group($this->db->quoteName('#__virtuemart_products.product_sku'));
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
								$query = $this->db->getQuery(true);
								$query->select($this->db->quoteName('#__virtuemart_product_customfields.custom_value'));
								$query->from($this->db->quoteName('#__virtuemart_product_customfields'));
								$query->leftJoin($this->db->quoteName('#__virtuemart_customs') . ' ON ' . $this->db->quoteName('#__virtuemart_customs.virtuemart_custom_id') . ' = ' . $this->db->quoteName('#__virtuemart_product_customfields.virtuemart_custom_id'));
								$query->where($this->db->quoteName('#__virtuemart_customs.field_type') . ' = ' . $this->db->quote('Z'));
								$query->where($this->db->quoteName('#__virtuemart_product_customfields.virtuemart_product_id') . ' = ' . $this->db->quote($record->virtuemart_product_id));
								$query->group($this->db->quoteName('#__virtuemart_product_customfields.virtuemart_customfield_id'));
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
								$product_price = $this->convertPrice($record->product_price, $record->currency_code_3);
								$fieldvalue = number_format(
									$product_price,
									$this->template->get('export_price_format_decimal', 2, 'int'),
									$this->template->get('export_price_format_decsep'),
									$this->template->get('export_price_format_thousep')
								);

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
								$fieldvalue = number_format(
									$record->product_override_price,
									$this->template->get('export_price_format_decimal', 2, 'int'),
									$this->template->get('export_price_format_decsep'),
									$this->template->get('export_price_format_thousep')
								);

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
								$prices = $this->getProductPrice($record->virtuemart_product_id);
								$fieldvalue = number_format(
									$prices['salesPrice'],
									$this->template->get('export_price_format_decimal', 2, 'int'),
									$this->template->get('export_price_format_decsep'),
									$this->template->get('export_price_format_thousep')
								);

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
								$prices = $this->getProductPrice($record->virtuemart_product_id);

								if (isset($prices[$fieldname]))
								{
									$fieldvalue = number_format(
										$prices[$fieldname],
										$this->template->get('export_price_format_decimal', 2, 'int'),
										$this->template->get('export_price_format_decsep'),
										$this->template->get('export_price_format_thousep')
									);
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
								break;
							case 'product_currency':
								$fieldvalue = $record->currency_code_3;
								break;
							case 'custom_shipping':
								// Get the prices
								$prices = $this->getProductPrice($record->virtuemart_product_id);

								// Check the shipping cost
								if (isset($prices['salesprice']))
								{
									$price_with_tax = number_format(
										$prices['salesprice'],
										$this->template->get('export_price_format_decimal', 2, 'int'),
										$this->template->get('export_price_format_decsep'),
										$this->template->get('export_price_format_thousep')
									);

									$result = $this->helper->shippingCost($price_with_tax);

									if ($result)
									{
										$fieldvalue = $result;
									}
								}
								break;
							case 'manufacturer_name':
								$query = $this->db->getQuery(true);
								$query->select('mf_name');
								$query->from('#__virtuemart_manufacturers_' . $language);
								$query->leftJoin('#__virtuemart_product_manufacturers ON #__virtuemart_product_manufacturers.virtuemart_manufacturer_id = #__virtuemart_manufacturers_' . $language . '.virtuemart_manufacturer_id');
								$query->where('virtuemart_product_id = ' . $record->virtuemart_product_id);
								$this->db->setQuery($query);
								$fieldvalue = $this->db->loadResult();
								break;
							case 'custom_title':
								// Get the custom title
								$query = $this->db->getQuery(true);
								$query->select($this->db->quoteName('custom_title'));
								$query->from($this->db->quoteName('#__virtuemart_customs', 'c'));
								$query->leftJoin($this->db->quoteName('#__virtuemart_product_customfields', 'f') . ' ON c.virtuemart_custom_id = f.virtuemart_custom_id');
								$query->where($this->db->quoteName('virtuemart_product_id') . ' = ' . $this->db->quote($record->virtuemart_product_id));

								// Check if we need to filter
								$title_filter = $this->template->get('custom_title', array(), 'array');

								if (!empty($title_filter) && $title_filter[0] != '')
								{
									$query->where($this->db->quoteName('f.virtuemart_custom_id') . ' IN (' . implode(',', $title_filter) . ')');
								}

								$query->order(array($this->db->quoteName('f.ordering'), $this->db->quoteName('f.virtuemart_custom_id')));
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
								if (!isset($this->customfields[$record->virtuemart_product_id][$fieldname]))
								{
									if ($fieldname == 'custom_ordering')
									{
										$qfield = $this->db->quoteName('ordering', 'custom_ordering');
									}
									else
									{
										$qfield = $this->db->quoteName($fieldname);
									}

									$query = $this->db->getQuery(true);
									$query->select($qfield . ',' . $this->db->quoteName('virtuemart_custom_id') . ',' . $this->db->quoteName('custom_value') . ',' . $this->db->quoteName('custom_param'));
									$query->from($this->db->quoteName('#__virtuemart_product_customfields'));
									$query->where($this->db->quoteName('virtuemart_product_id') . ' = ' . $this->db->quote($record->virtuemart_product_id));

									// Check if we need to filter
									$title_filter = $this->template->get('custom_title', array());

									if (!empty($title_filter) && $title_filter[0] != '')
									{
										$query->where($this->db->quoteName('virtuemart_custom_id') . ' IN (' . implode(',', $title_filter) . ')');
									}

									$query->order(array($this->db->quoteName('ordering'), $this->db->quoteName('virtuemart_custom_id')));
									$this->db->setQuery($query);
									$customfields = $this->db->loadObjectList();
									$this->log->add('Custom field query');

									if (!empty($customfields))
									{
										$values = array();
										foreach ($customfields as $customfield)
										{
											if ($customfield->custom_value == 'stockable')
											{
												$options = json_decode($customfield->custom_param);
												// Create the CSVI format
												$value = '';
												foreach ($options->child AS $cid => $details)
												{
													$query->clear();
													$query->select('product_sku')->from('#__virtuemart_products')->where('virtuemart_product_id = ' . $cid);
													$this->db->setQuery($query);
													$value .= $this->db->loadResult().'[';
													$child_values = array();

													foreach ($details as $dname => $dvalue)
													{
														if (strpos($dname, 'selectoption') !== false)
														{
															$child_values[] = $dvalue;
														}
													}

													$value .= implode('#', $child_values).'[;';
												}

												$values[] = $value;
											}
											else if ($fieldname == 'custom_param' && $customfield->custom_value == 'param')
											{
												// Get the values for this custom field
												$query = $this->db->getQuery(true)
													->select($this->db->quoteName('v.value').','.$this->db->quoteName('r.val', 'val_id').','.$this->db->quoteName('r.intval').','.$this->db->quoteName('c.custom_title'))
													->from($this->db->quoteName('#__virtuemart_product_custom_plg_param_ref', 'r'))
													->leftJoin($this->db->quoteName('#__virtuemart_product_custom_plg_param_values', 'v').' ON '.$this->db->quoteName('r.val').'='.$this->db->quoteName('v.id'))
													->leftJoin($this->db->quoteName('#__virtuemart_customs', 'c').' ON '.$this->db->quoteName('r.virtuemart_custom_id').'='.$this->db->quoteName('c.virtuemart_custom_id'))
													->leftJoin($this->db->quoteName('#__virtuemart_product_customfields', 'f').' ON '.$this->db->quoteName('c.virtuemart_custom_id').' = '.$this->db->quoteName('f.virtuemart_custom_id').' AND '.$this->db->quoteName('r.virtuemart_product_id').' = '.$this->db->quoteName('f.virtuemart_product_id'))
													->where($this->db->quoteName('r.virtuemart_product_id').'='.$record->virtuemart_product_id)
													->where($this->db->quoteName('r.virtuemart_custom_id').'='.$customfield->virtuemart_custom_id);
												$this->db->setQuery($query);
												$options = $this->db->loadObjectList();

												// Group the data correctly
												$newoptions = array();
												foreach ($options as $option)
												{
													$newoptions[$option->custom_title][] = empty($option->val_id) ? $option->intval : $option->value;
												}

												// Create the CSVI format
												// option1[value1#value2;option2[value1#value2
												foreach ($newoptions as $title => $option)
												{
													$values[] = implode('#', $option);
												}
											}
											else
											{
												if (!empty($customfield->$fieldname))
												{
													$values[] = $customfield->$fieldname;
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
								$query = $this->db->getQuery(true);
								$query->select($this->db->quoteName($fieldname));
								$query->from($this->db->quoteName('#__virtuemart_medias') . ' AS m');
								$query->leftJoin($this->db->quoteName('#__virtuemart_product_medias')  .' AS p ON m.virtuemart_media_id = p.virtuemart_media_id');
								$query->where($this->db->quoteName('virtuemart_product_id') . ' = ' . $this->db->quote($record->virtuemart_product_id));
								$query->where($this->db->quoteName('file_type') . ' = ' . $this->db->quote('product'));
								$query->order('p.ordering');
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
									->where($this->db->quoteName('virtuemart_product_id') . ' = ' . $this->db->quote($record->virtuemart_product_id))
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
									->leftJoin($this->db->quoteName('#__virtuemart_product_shoppergroups', 'p') . ' ON g.virtuemart_shoppergroup_id = p.virtuemart_shoppergroup_id')
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
							case 'product_discount':
								$query = $this->db->getQuery(true);
								$query->select('calc_value_mathop, calc_value');
								$query->from($this->db->quoteName('#__virtuemart_calcs', 'c'));
								$query->where($this->db->quoteName('virtuemart_calc_id') . ' = ' . $this->db->quote($record->product_discount_id));
								$this->db->setQuery($query);
								$discount = $this->db->loadObject();

								if (is_object($discount))
								{
									$fieldvalue = number_format($discount->calc_value, $this->template->get('export_price_format_decimal', 2, 'int'), $this->template->get('export_price_format_decsep'), $this->template->get('export_price_format_thousep'));

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
							default:
								// See if we need to retrieve a custom field
								if (in_array($fieldname, $this->customfieldsExport))
								{
									$query = $this->db->getQuery(true);
									$query->select('p.custom_value');
									$query->from('#__virtuemart_product_customfields p');
									$query->leftJoin('#__virtuemart_customs c ON p.virtuemart_custom_id = c.virtuemart_custom_id');
									$query->where('c.custom_title = ' . $this->db->quote($fieldname));
									$query->where('p.virtuemart_product_id = ' . $record->virtuemart_product_id);
									$this->db->setQuery($query);
									$fieldvalue = $this->db->loadResult();
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

				$this->addExportContent('</offers>' . chr(10));
				$this->writeOutput();
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
	 * @param   int  $product_id  The ID of the product
	 *
	 * @return  array  List of prices.
	 *
	 * @since   4.0
	 */
	private function getProductPrice($product_id)
	{
		if (!isset($this->prices[$product_id]))
		{
			// Define VM constant to make the classes work
			if (!defined('JPATH_VM_ADMINISTRATOR'))
			{
				define('JPATH_VM_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_virtuemart/');
			}

			// Load the configuration for the currency formatting
			require_once JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/config.php';

			// Include the calculation helper
			require_once JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/calculationh.php';
			$calc = calculationHelper::getInstance();

			// Get the product prices
			require_once JPATH_ADMINISTRATOR . '/components/com_virtuemart/models/product.php';
			$product = new VirtueMartModelProduct;
			$prices = $calc->getProductPrices($product->getProductSingle($product_id));

			if (is_array($prices))
			{
				$this->prices[$product_id] = array_change_key_case($prices, CASE_LOWER);
			}
			else
			{
				$this->prices[$product_id] = array();
			}
		}

		return $this->prices[$product_id];
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
		$query->select('TRIM(' . $this->db->quoteName('custom_title') . ') AS title');
		$query->from($this->db->quoteName('#__virtuemart_customs'));
		$query->where(
			'field_type IN ('
			. $this->db->quote('S') . ','
			. $this->db->quote('I') . ','
			. $this->db->quote('B') . ','
			. $this->db->quote('D') . ','
			. $this->db->quote('T') . ','
			. $this->db->quote('M') .
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
	 * Get all the categories.
	 *
	 * @return  array  An array of available categories.
	 *
	 * @since   6.0
	 */
	private function loadCategories()
	{
		$query = $this->db->getQuery(true);
		$query->select('x.category_parent_id AS parent_id, x.category_child_id AS id, l.category_name AS catname');
		$query->from('#__virtuemart_categories c');
		$query->leftJoin('#__virtuemart_category_categories x ON c.virtuemart_category_id = x.category_child_id');
		$query->leftJoin('#__virtuemart_categories_' . $this->template->get('language') . ' l ON l.virtuemart_category_id = c.virtuemart_category_id');
		$this->db->setQuery($query);
		$this->log->add('Load categories', true);

		return $this->db->loadObjectList();
	}
}
