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
 * The VirtueMart helper class.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartHelperCom_Virtuemart
{
	/**
	 * Template helper
	 *
	 * @var    CsviHelperTemplate
	 * @since  6.0
	 */
	protected $template = null;

	/**
	 * Logger helper
	 *
	 * @var    CsviHelperLog
	 * @since  6.0
	 */
	protected $log = null;

	/**
	 * Fields helper
	 *
	 * @var    CsviHelperFields
	 * @since  6.0
	 */
	protected $fields = null;

	/**
	 * Database connector
	 *
	 * @var    JDatabaseDriver
	 * @since  6.0
	 */
	protected $db = null;

	/**
	 * The vendor ID
	 *
	 * @var    int
	 * @since  6.0
	 */
	private $vendorId = null;

	/**
	 * Product relation ID
	 *
	 * @var    int
	 * @since  6.0
	 */
	private $relatedId = null;

	/**
	 * Category separator
	 *
	 * @var    string
	 * @since  6.0
	 */
	private $categorySeparator = null;

	/**
	 * Holds the HTML select list options
	 *
	 * @var    array
	 * @since  6.0
	 */
	private $selectOptions = array();

	/**
	 * Constructor.
	 *
	 * @param   CsviHelperTemplate  $template  An instance of CsviHelperTemplate.
	 * @param   CsviHelperLog       $log       An instance of CsviHelperLog.
	 * @param   CsviHelperFields    $fields    An instance of CsviHelperFields.
	 * @param   JDatabase           $db        Database connector.
	 *
	 * @since   4.0
	 */
	public function __construct(CsviHelperTemplate $template, CsviHelperLog $log, CsviHelperFields $fields, JDatabase $db)
	{
		$this->template = $template;
		$this->log = $log;
		$this->fields = $fields;
		$this->db = $db;
	}

	/**
	 * Get the product id, this is necessary for updating existing products.
	 *
	 * @return  int  The product ID is returned.
	 *
	 * @since   3.0
	 */
	public function getProductId()
	{
		// Find what the update is based on
		$update_based_on = $this->template->get('update_based_on', 'product_sku');

		switch ($update_based_on)
		{
			case 'product_sku':
				$product_id = $this->fields->get('product_id');

				if ($product_id)
				{
					$this->log->add(JText::_('COM_CSVI_FIND_PRODUCT_ID') . $product_id);

					return $product_id;
				}
				else
				{
					$product_sku = $this->fields->get('product_sku');

					if ($product_sku)
					{
						$query = $this->db->getQuery(true)
							->select($this->db->quoteName('virtuemart_product_id'))
							->from($this->db->quoteName('#__virtuemart_products'))
							->where($this->db->quoteName('product_sku') . ' = ' . $this->db->quote($product_sku));
						$this->db->setQuery($query);
						$this->log->add(JText::_('COM_CSVI_FIND_PRODUCT_SKU'), true);

						return $this->db->loadResult();
					}
					else
					{
						return false;
					}
				}
				break;
			case 'product_mpn':
				$mpn_column = $this->template->get('mpn_column_name', false);
				$product_mpn = $this->fields->get($mpn_column);

				if ($product_mpn)
				{
					$query = $this->db->getQuery(true)
						->select($this->db->quoteName('virtuemart_product_id'))
						->from($this->db->quoteName('#__virtuemart_products'))
						->where($this->db->quoteName($mpn_column) . ' = ' . $this->db->quote($product_mpn));
					$this->db->setQuery($query);
					$this->log->add(JText::_('COM_CSVI_FIND_PRODUCT_MPN'), true);

					return $this->db->loadResult();
				}
				else
				{
					return false;
				}
				break;
			case 'product_child_sku':
				$product_sku = $this->fields->get('product_sku');
				$product_parent_sku = $this->fields->get('product_parent_sku');

				if ($product_sku && $product_parent_sku)
				{
					// Load the product parent ID
					$query = $this->db->getQuery(true)
						->select($this->db->quoteName('virtuemart_product_id'))
						->from($this->db->quoteName('#__virtuemart_products'))
						->where($this->db->quoteName('product_sku') . ' = ' . $this->db->quote($product_parent_sku));
					$this->db->setQuery($query);
					$this->log->add(JText::_('COM_CSVI_FIND_PRODUCT_CHILD_PARENT_SKU'), true);
					$product_parent_id = $this->db->loadResult();

					// Load the product ID of the child
					$query = $this->db->getQuery(true)
						->select($this->db->quoteName('virtuemart_product_id'))
						->from($this->db->quoteName('#__virtuemart_products'))
						->where($this->db->quoteName('product_sku') . ' = ' . $this->db->quote($product_sku))
						->where($this->db->quoteName('product_parent_id') . ' = ' . (int) $product_parent_id);
					$this->db->setQuery($query);
					$this->log->add(JText::_('COM_CSVI_FIND_PRODUCT_CHILD_SKU'), true);

					return $this->db->loadResult();
				}
				elseif ($product_sku)
				{
					$query = $this->db->getQuery(true)
						->select($this->db->quoteName('virtuemart_product_id'))
						->from($this->db->quoteName('#__virtuemart_products'))
						->where($this->db->quoteName('product_sku') . ' = ' . $this->db->quote($product_sku));
					$this->db->setQuery($query);
					$this->log->add(JText::_('COM_CSVI_FIND_PRODUCT_SKU_BASED_CHILD'), true);

					return $this->db->loadResult();
				}
				else
				{
					$this->log->add(JText::_('COM_CSVI_NO_CHILD_NO_PARENT'));

					return false;
				}
				break;
			default:
				return false;
				break;
		}
	}

	/**
	 * Determine vendor ID
	 *
	 * Determine for which vendor we are importing product details.
	 *
	 * The default vendor is the one with the lowest vendor_id value.
	 *
	 * @todo   Add full vendor support when VirtueMart supports it
	 *
	 * @return  int  The vendor ID.
	 *
	 * @since   3.0
	 */
	public function getVendorId()
	{
		if (!$this->vendorId)
		{
			// Get some values
			$vendor_id = $this->fields->get('virtuemart_vendor_id');
			$product_sku = $this->fields->get('product_sku', false);

			// User is uploading vendor_id
			if ($vendor_id)
			{
				return $vendor_id;
			}

			// User is not uploading vendor_id
			// First get the vendor with the lowest ID
			$query = $this->db->getQuery(true)
				->select('MIN(' . $this->db->quoteName('virtuemart_vendor_id') . ') AS vendor_id')
				->from($this->db->quoteName('#__virtuemart_vendors'));
			$this->db->setQuery($query);
			$min_vendor_id = $this->db->loadResult();

			if ($min_vendor_id)
			{
				if ($product_sku)
				{
					$query = $this->db->getQuery(true)
						->select(
							'IF (COUNT(' . $this->db->quoteName('virtuemart_vendor_id') . ') = 0, '
								. $min_vendor_id . ', '
								. $this->db->quoteName('virtuemart_vendor_id')
							. ') AS vendor_id'
						)
						->from($this->db->quoteName('#__virtuemart_products'))
						->where($this->db->quoteName('product_sku') . ' = ' . $this->db->quote($product_sku));
					$this->db->setQuery($query);

					// Existing vendor_id
					$vendor_id = $this->db->loadResult();
					$this->log->add('Check if the vendor exists');

					$this->vendorId = $vendor_id;

					return $vendor_id;
				}
				else
				{
					// No product_sku uploaded
					$this->vendorId = $min_vendor_id;

					return $min_vendor_id;
				}
			}
			else
			{
				// No vendor found, so lets default to 1
				$this->vendorId = 1;
			}
		}

		return $this->vendorId;
	}

	/**
	 * Get the shopper group id.
	 *
	 * Only get the shopper group id when the shopper_group_name is set.
	 *
	 * @param   string  $shopper_group_name  The name of the shopper group to find.
	 *
	 * @return  int  The shopper group ID.
	 *
	 * @since   3.0
	 */
	public function getShopperGroupId($shopper_group_name)
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('virtuemart_shoppergroup_id'))
			->from($this->db->quoteName('#__virtuemart_shoppergroups'))
			->where($this->db->quoteName('shopper_group_name') . ' = ' . $this->db->quote($shopper_group_name));
		$this->db->setQuery($query);
		$shopper_group_id = $this->db->loadResult();
		$this->log->add('Get the shopper group ID');

		return $shopper_group_id;
	}

	/**
	 * Get the shopper group id.
	 *
	 * Only get the shopper group id when the shopper_group_name is set.
	 *
	 * @param   string  $manufacturer_name  The name of the shopper group to find.
	 *
	 * @return  int  The shopper group ID.
	 *
	 * @since   3.0
	 */
	public function getManufacturerId($manufacturer_name)
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('virtuemart_manufacturer_id'))
			->from($this->db->quoteName('#__virtuemart_manufacturers_' . $this->template->get('language')))
			->where($this->db->quoteName('mf_name') . ' = ' . $this->db->quote($manufacturer_name));
		$this->db->setQuery($query);
		$manufacturer_id = $this->db->loadResult();
		$this->log->add('Get the manufacturer ID');

		return $manufacturer_id;
	}

	/**
	 * Get the currency ID of the specified vendor.
	 *
	 * @param   int  $vendor_id  The ID of the vendor to get the currency for.
	 *
	 * @return  int  The vendor currency ID.
	 *
	 * @since   4.0
	 */
	public function getVendorCurrency($vendor_id)
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('vendor_currency'))
			->from($this->db->quoteName('#__virtuemart_vendors'))
			->where($this->db->quoteName('virtuemart_vendor_id') . ' = ' . (int) $vendor_id);
		$this->db->setQuery($query);

		return $this->db->loadResult();
	}

	/**
	 * Gets the default Shopper Group ID.
	 *
	 * @return  int  The default shoppper group ID.
	 *
	 * @since   4.0
	 */
	public function getDefaultShopperGroupID()
	{
		$vendor_id = $this->getVendorId();
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('virtuemart_shoppergroup_id'))
			->from($this->db->quoteName('#__virtuemart_shoppergroups'))
			->where($this->db->quoteName('default') . ' = 1')
			->where($this->db->quoteName('virtuemart_vendor_id') . ' = ' . (int) $vendor_id);
		$this->db->setQuery($query);
		$default = $this->db->loadResult();
		$this->log->add('Get the default shopper group ID');

		return $default;
	}

	/**
	 * Create a slug.
	 *
	 * @param   string  $name  The string to turn into a slug
	 *
	 * @return  string  The slug created.
	 *
	 * @since   4.0
	 */
	public function createSlug($name)
	{
		// Get the correct language
		$sourcelanguage = $this->template->get('language', '', null, 0, false);
		$targetlanguage = $this->template->get('target_language', $sourcelanguage, null, 0, false);

		if ($sourcelanguage == $targetlanguage)
		{
			$uselang = $sourcelanguage;
		}
		else
		{
			$uselang = $targetlanguage;
		}

		// Transliterate
		$lang = new JLanguage($uselang);
		$str = $lang->transliterate($name);

		// Trim white spaces at beginning and end of alias and make lowercase
		$str = trim(\Joomla\String\String::strtolower($str));

		// Remove any duplicate whitespace, and ensure all characters are alphanumeric
		$str = preg_replace('/(\s|[^A-Za-z0-9\-])+/', '-', $str);

		// Trim dashes at beginning and end of alias
		$str = trim($str, '-');

		// If we are left with an empty string, make a date with random number
		if (trim(str_replace('-', '', $str)) == '')
		{
			$jdate = JFactory::getDate();
			$str = $jdate->format("Y-m-d-h-i-s") . mt_rand();
		}

		return $str;
	}

	/**
	 * Get the custom related field ID.
	 *
	 * @return  int  The ID of the related field.
	 *
	 * @since   4.0
	 */
	public function getRelatedId($type = 'R')
	{
		if (!$this->relatedId)
		{
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName('virtuemart_custom_id'))
				->from($this->db->quoteName('#__virtuemart_customs'))
				->where($this->db->quoteName('virtuemart_vendor_id') . ' = ' . (int) $this->getVendorId())
				->where($this->db->quoteName('field_type') . ' = ' . $this->db->quote($type));
			$this->db->setQuery($query);
			$this->relatedId = $this->db->loadResult();
		}

		return $this->relatedId;
	}

	/**
	 * Load the order status code.
	 *
	 * @param   string  $order_status_name  The name of the order status.
	 *
	 * @return  string  The order status code.
	 *
	 * @since   2.3.11
	 */
	public function getOrderStatus($order_status_name)
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('order_status_code'))
			->from($this->db->quoteName('#__virtuemart_orderstates'))
			->where($this->db->quoteName('order_status_name') . ' = ' . $this->db->quote($order_status_name));
		$this->db->setQuery($query);

		return $this->db->loadResult();
	}

	/**
	 * Get the currency ID.
	 *
	 * @param   string  $currency_name  The name of the currency
	 * @param   int     $vendor_id      The ID of the vendor
	 *
	 * @return  int  The currency ID.
	 *
	 * @since   4.0
	 */
	public function getCurrencyId($currency_name, $vendor_id)
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('virtuemart_currency_id'))
			->from($this->db->quoteName('#__virtuemart_currencies'))
			->where($this->db->quoteName('currency_code_3') . ' = ' . $this->db->quote($currency_name))
			->where($this->db->quoteName('virtuemart_vendor_id') . ' = ' . (int) $vendor_id);
		$this->db->setQuery($query);
		$currency_id = $this->db->loadResult();
		$this->log->add(JText::_('COM_CSVI_DEBUG_GET_CURRENCY_ID'), true);

		return $currency_id;
	}

	/**
	 * Get the country ID.
	 *
	 * @param   string  $country_name    The name of the country
	 * @param   string  $country_2_code  The 2 letter notification
	 * @param   string  $country_3_code  The 3 letter notification
	 *
	 * @return  int  The ID of the country.
	 *
	 * @since   4.0
	 */
	public function getCountryId($country_name=null, $country_2_code=null, $country_3_code=null)
	{
		$country_id = null;

		if (isset($country_name) || isset($country_2_code) || isset($country_3_code))
		{
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName('virtuemart_country_id'))
				->from($this->db->quoteName('#__virtuemart_countries'));

			if (isset($country_name))
			{
				$query->where($this->db->quoteName('country_name') . ' = ' . $this->db->quote($country_name));
			}
			elseif (isset($country_2_code))
			{
				$query->where($this->db->quoteName('country_2_code') . ' = ' . $this->db->quote($country_2_code));
			}
			elseif (isset($country_3_code))
			{
				$query->where($this->db->quoteName('country_3_code') . ' = ' . $this->db->quote($country_3_code));
			}

			$this->db->setQuery($query);
			$country_id = $this->db->loadResult();
			$this->log->add(JText::_('COM_CSVI_DEBUG_GET_COUNTRY_ID'), true);
		}

		return $country_id;
	}

	/**
	 * Get the state ID.
	 *
	 * @param   string  $state_name	   The name of the state
	 * @param   string  $state_2_code  The 2 letter notification
	 * @param   string  $state_3_code  The 3 letter notification
	 * @param   int     $countryId     The ID of the country the state belongs to
	 *
	 * @return  int  The state ID.
	 *
	 * @since   4.0
	 */
	public function getStateId($state_name=null, $state_2_code=null, $state_3_code=null, $countryId=null)
	{
		$state_id = null;

		if (isset($state_name) || isset($state_2_code) || isset($state_3_code))
		{
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName('virtuemart_state_id'))
				->from($this->db->quoteName('#__virtuemart_states'))
				->where($this->db->quoteName('virtuemart_country_id') . ' = ' . (int) $countryId);

			if (isset($state_name))
			{
				$query->where($this->db->quoteName('state_name') . ' = ' . $this->db->quote($state_name));
			}
			elseif (isset($state_2_code))
			{
				$query->where($this->db->quoteName('state_2_code') . ' = ' . $this->db->quote($state_2_code));
			}
			elseif (isset($state_3_code))
			{
				$query->where($this->db->quoteName('state_3_code') . ' = ' . $this->db->quote($state_3_code));
			}

			$this->db->setQuery($query);
			$state_id = $this->db->loadResult();
			$this->log->add('COM_CSVI_DEBUG_GET_STATE_ID', true);
		}

		return $state_id;
	}

	/**
	 * Get category tree.
	 *
	 * @param   string  $language  The language code for the category names.
	 *
	 * @return  array  The list of select options.
	 *
	 * @since   4.0
	 */
	public function getCategoryTree($language)
	{
		// Clean up the language if needed
		$language = strtolower(str_replace('-', '_', $language));

		$query = $this->db->getQuery(true);

		// 1. Get all categories
		$query->select('x.category_parent_id AS parent_id, x.category_child_id AS id, l.category_name AS catname');
		$query->from('#__virtuemart_categories c');
		$query->leftJoin('#__virtuemart_category_categories x ON c.virtuemart_category_id = x.category_child_id');
		$query->leftJoin('#__virtuemart_categories_' . $language . ' l ON l.virtuemart_category_id = c.virtuemart_category_id');
		$this->db->setQuery($query);
		$rawcats = $this->db->loadObjectList();

		if ($rawcats)
		{
			// 2. Group categories based on their parent_id
			$categories = array();

			foreach ($rawcats as $rawcat)
			{
				$categories[$rawcat->parent_id][$rawcat->id]['pid'] = $rawcat->parent_id;
				$categories[$rawcat->parent_id][$rawcat->id]['cid'] = $rawcat->id;
				$categories[$rawcat->parent_id][$rawcat->id]['catname'] = $rawcat->catname;
			}
		}

		// Free up some memory
		unset($rawcats);

		// Clean the array
		$this->selectOptions = array();

		// Add a don't use option
		$this->selectOptions[] = JHtml::_('select.option', '', JText::_('COM_CSVI_EXPORT_DONT_USE'));

		if (isset($categories))
		{
			if (count($categories) > 0)
			{
				// Take the toplevels first
				foreach ($categories[0] as $key => $category)
				{
					$this->selectOptions[] = JHtml::_('select.option', $category['cid'], $category['catname']);

					// Write the subcategories
					$suboptions = $this->buildCategory($categories, $category['cid'], array());
				}
			}
		}

		// Free up some memory
		unset($categories);

		// Return the select options
		return $this->selectOptions;
	}

	/**
	* Create the subcategory layout
	*
	* @copyright
	* @author		RolandD
	* @todo
	* @see
	* @access 		private
	* @param
	* @return 		array	select options for the category tree
	* @since 		3.0
	*/
	private function buildCategory($cattree, $catfilter, $subcats, $loop=1) {
		if (isset($cattree[$catfilter])) {
			foreach ($cattree[$catfilter] as $subcatid => $category) {
				$this->selectOptions[] = JHtml::_('select.option', $category['cid'], str_repeat('>', $loop).' '.$category['catname']);
				$subcats = $this->buildCategory($cattree, $subcatid, $subcats, $loop+1);
			}
		}
	}

	/**
	 * Construct the category path.
	 *
	 * @param   array   $catids    The IDs to build a category for.
	 * @param   string  $language  The name of the language selector.
	 *
	 * @return  array  List of category paths.
	 *
	 * @since   4.0
	 */
	private function constructCategoryPath($catids, $language='language')
	{
		$catpaths = array();

		if (is_array($catids))
		{
			// Load the category separator
			if (is_null($this->categorySeparator))
			{
				$this->categorySeparator = $this->template->get('category_separator', '/');
			}

			// Get the paths
			foreach ($catids as $category_id)
			{
				// Create the path
				$paths = array();

				while ($category_id > 0)
				{
					$query = $this->db->getQuery(true)
						->select(
							$this->db->quoteName(
								array(
									'category_parent_id',
									'l.category_name'
								)
							)
						)
						->from($this->db->quoteName('#__virtuemart_category_categories', 'x'))
						->leftJoin(
							$this->db->quoteName('#__virtuemart_categories', 'c')
							. ' ON ' . $this->db->quoteName('x.category_child_id') . ' = ' . $this->db->quoteName('c.virtuemart_category_id')
						)
						->leftJoin(
							$this->db->quoteName('#__virtuemart_categories_' . $this->template->get($language), 'l')
							. ' ON ' . $this->db->quoteName('x.category_child_id') . ' = ' . $this->db->quoteName('l.virtuemart_category_id')
						)
						->where($this->db->quoteName('category_child_id') . ' = ' . (int) $category_id);
					$this->db->setQuery($query);
					$path = $this->db->loadObject();
					$this->log->add('Get cat ID' . $category_id);

					if (is_object($path))
					{
						$paths[] = $path->category_name;
						$category_id = $path->category_parent_id;
					}
					else
					{
						$this->log->add('COM_CSVI_CANNOT_GET_CATEGORY_ID');

						return '';
					}
				}

				// Create the path
				$paths = array_reverse($paths);
				$catpaths[] = implode($this->categorySeparator, $paths);
			}
		}

		return $catpaths;
	}

	/**
	 * Creates the category path based on a product ID.
	 *
	 * @param   int   $product_id  The product ID to create the category path for.
	 * @param   bool  $id          Set if only the IDs should be returned
	 *
	 * @return  string  The category path.
	 *
	 * @since   3.0
	 */
	public function createCategoryPath($product_id, $id=false)
	{
		// Get the category paths
		$query = $this->db->getQuery(true);
		$query->select($this->db->quoteName('virtuemart_category_id'));
		$query->from($this->db->quoteName('#__virtuemart_product_categories'));
		$query->where($this->db->quoteName('virtuemart_product_id') . ' = ' . $this->db->quote($product_id));
		$this->db->setQuery($query);
		$catids = $this->db->loadColumn();

		if (!empty($catids))
		{
			// Return the paths
			if ($id)
			{
				$result = $this->db->loadColumn();

				if (is_array($result))
				{
					return implode('|', $result);
				}
				else
				{
					return null;
				}
			}
			else {
				$catpaths = $this->constructCategoryPath($catids);

				if (is_array($catpaths))
				{
					return implode('|', $catpaths);
				}
				else
				{
					return null;
				}
			}
		}
		else
		{
			return null;
		}
	}

	/**
	 * Create a category path based on ID.
	 *
	 * @param   array   $catids    List of IDs to generate category path for.
	 * @param   string  $language  The name of the language selector.
	 *
	 * @return  string  The constructed path.
	 *
	 * @since   4.0
	 */
	public function createCategoryPathById($catids, $language='language')
	{
		if (!is_array($catids))
		{
			$catids = (array) $catids;
		}

		$paths = $this->constructCategoryPath($catids, $language);

		if (is_array($paths))
		{
			return implode('|', $paths);
		}
		else
		{
			return '';
		}
	}

	/**
	 * Get the category ID for a product.
	 *
	 * @param   int  $product_id  The product ID to get the category for
	 *
	 * @return  int  The category ID the product is linked to limited to 1.
	 *
	 * @since   3.0
	 */

	public function getCategoryId($product_id)
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('virtuemart_category_id'))
			->from($this->db->quoteName('#__virtuemart_product_categories'))
			->where($this->db->quoteName('virtuemart_product_id') . ' = ' . (int) $product_id);
		$this->db->setQuery($query, 0, 1);

		return $this->db->loadResult();
	}

	/**
	 * Determine the shipping cost.
	 *
	 * @return  mixed  Prices if available | False otherwise.
	 *
	 * @since
	 */
	public function shippingCost($product_price)
	{
		$prices = $this->template->get('shopper_shipping_export_fields', array());
		$fee = null;

		if (!empty($prices))
		{
			foreach ($prices['_price_from'] as $kfrom => $price_from)
			{
				// Check if we have an end price
				$price_from = str_replace(',', '.', $price_from);
				$price_to = str_replace(',', '.', $prices['_price_to'][$kfrom]);

				if (!empty($price_to))
				{
					if ($product_price >= $price_from && $product_price < $price_to)
					{
						$fee = $kfrom;
						break;
					}
				}
				else
				{
					if ($product_price >= $price_from)
					{
						$fee = $kfrom;
						break;
					}
				}
			}
		}

		if (!is_null($fee))
		{
			return $prices['_fee'][$fee];
		}
		else
		{
			return false;
		}
	}

	/**
	* Get the list of order products
	*
	* @copyright
	* @author		RolandD
	* @todo
	* @see
	* @access 		public
	* @param
	* @return 		array of objects
	* @since 		4.0
	*/
	public function getOrderProduct() {
		$jinput = JFactory::getApplication()->input;
		$db = JFactory::getDbo();
		$query = $this->db->getQuery(true);
		$filter = $jinput->get('filter');
		$q = "SELECT DISTINCT order_item_sku AS product_sku, order_item_name AS product_name
				FROM #__virtuemart_order_items o
				WHERE (order_item_sku LIKE ".$this->db->Quote('%'.$filter.'%')."
					OR order_item_name LIKE ".$this->db->Quote('%'.$filter.'%').")
				ORDER BY order_item_name
				LIMIT 10;";
		$this->db->setQuery($q);
		return $this->db->loadObjectList();
	}

	/**
	 * Get the list of order item products
	 *
	 * @copyright
	 * @author		RolandD
	 * @todo
	 * @see
	 * @access 		public
	 * @param
	 * @return 		array of objects
	 * @since 		4.0
	 */
	public function getOrderItemProduct() {
		$jinput = JFactory::getApplication()->input;
		$filter = $jinput->get('filter');
		$q = "SELECT DISTINCT order_item_sku AS product_sku, order_item_name AS product_name
				FROM #__virtuemart_order_items o
				WHERE (o.order_item_sku LIKE ".$this->db->quote('%'.$filter.'%')."
					OR o.order_item_name LIKE ".$this->db->quote('%'.$filter.'%').")
				ORDER BY order_item_name
				LIMIT 10;";
		$this->db->setQuery($q);
		return $this->db->loadObjectList();
	}

	/**
	 * Get manufacturer list
	 *
	 * @copyright
	 * @author 		RolandD
	 * @todo
	 * @see
	 * @access 		public
	 * @param		string	$language	the language code for the category names
	 * @return
	 * @since 		4.0
	 */
	public function getManufacturers($language) {
		// Clean up the language if needed
		$lang = strtolower(str_replace('-', '_', $language));

		$db = JFactory::getDbo();
		$query = $this->db->getQuery(true);
		$query->select($this->db->quoteName('virtuemart_manufacturer_id').' AS value,'.$this->db->quoteName('mf_name').' AS text');
		$query->from($this->db->quoteName('#__virtuemart_manufacturers_'.$lang));
		$this->db->setQuery($query);
		$mfs = $this->db->loadObjectList();

		$options = array();
		// Add a don't use option
		$options[] = JHtml::_('select.option', 'none', JText::_('COM_CSVI_EXPORT_DONT_USE'));

		if (isset($mfs)) {
			if (count($mfs) > 0) {
				// Take the toplevels first
				foreach ($mfs as $key => $mf) {
					$options[] = JHtml::_('select.option', $mf->value, $mf->text);
				}
			}
		}
		return $options;
	}

	/**
	 * Unpublish products before import.
	 *
	 * @param  CsviHelperTemplate  $template  An instance of CsviHelperTemplate
	 * @param  CsviHelperLog       $log       An instance of CsviHelperLog
	 * @param  JDatabase           $db        JDatabase class
	 *
	 * @return  void.
	 *
	 * @since   3.0
	 */
	public function unpublishBeforeImport(CsviHelperTemplate $template, CsviHelperLog $log, JDatabase $db)
	{
		if ($this->template->get('unpublish_before_import', 0))
		{
			$query = $this->db->getQuery(true)
				->update($this->db->quoteName('#__virtuemart_products'))
				->set($this->db->quoteName('published') . ' = 0');
			$this->db->setQuery($query);

			if ($this->db->execute())
			{
				$log->add('Unpublishing products before import');
			}
			else
			{
				$log->add('Cannot unpublish products before import');
			}
		}
	}

	/**
	 * Find the product ID based on SKU.
	 *
	 * @param   string  $productSku  The SKU to find the product ID for
	 *
	 * @return  int  The ID of the product SKU.
	 *
	 * @since   6.0
	 */
	public function getProductIdBySku($productSku)
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('virtuemart_product_id'))
			->from($this->db->quoteName('#__virtuemart_products'))
			->where($this->db->quoteName('product_sku') . ' = ' . $this->db->quote($productSku));
		$this->db->setQuery($query);
		$this->log->add(JText::_('COM_CSVI_FIND_PRODUCT_SKU'), true);

		return $this->db->loadResult();
	}

	/**
	 * Get a list of subcategory IDs for a given parent category ID.
	 *
	 * @param   int  $category_id  The category ID to get the subcategories for.
	 *
	 * @return  array  List of subcategory IDs.
	 *
	 * @since   3.0
	 */
	public function getSubCategoryIds($category_id)
	{
		$subcats = $this->getChildren($category_id);

		if (!empty($subcats))
		{
			foreach ($subcats as $subcat)
			{
				$newcats = $this->getSubCategoryIds($subcat);

				$subcats = array_merge($subcats, $newcats);
			}
		}

		return $subcats;

	}

	private function getChildren($parent_category_id)
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('x.category_child_id', 'id'))
			->from($this->db->quoteName('#__virtuemart_categories', 'c'))
			->leftJoin(
				$this->db->quoteName('#__virtuemart_category_categories', 'x')
				. ' ON ' . $this->db->quoteName('c.virtuemart_category_id') . ' = ' . $this->db->quoteName('x.category_child_id')
			)
			->where($this->db->quoteName('x.category_parent_id') . ' = ' . (int) $parent_category_id);

		$this->db->setQuery($query);

		return $this->db->loadColumn();
	}
}
