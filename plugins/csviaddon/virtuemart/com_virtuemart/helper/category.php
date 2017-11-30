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
 * Category helper.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartHelperCategory extends RantaiImportEngine
{
	/**
	 * Category table
	 *
	 * @var    VirtuemartTableCategory
	 * @since  6.0
	 */
	private $category = null;

	/**
	 * Category language table
	 *
	 * @var    VirtueMartTableCategoryLang
	 * @since  6.0
	 */
	private $categoryLang = null;

	/**
	 * Category cross reference
	 *
	 * @var    VirtueMartTableCategoryXref
	 * @since  6.0
	 */
	private $categoryXref = null;

	/**
	 * Category product cross reference
	 *
	 * @var    VirtueMartTableProductCategoryXref
	 * @since  6.0
	 */
	private $productCategoryXref = null;

	/**
	 * Set if tables are loaded
	 *
	 * @var    bool
	 * @since  6.0
	 */
	private $tablesLoaded = false;

	/**
	 * Cache for processed category paths.
	 *
	 * @var    array
	 * @since  3.0
	 */
	private $categoryCache = array();

	/**
	 * Category separator
	 *
	 * @var    string
	 * @since  3.0
	 */
	private $categorySeparator = null;

	/**
	 * Contains the category path for a product.
	 *
	 * @var    string
	 * @since  3.0
	 */
	public $category_path = null;

	/**
	 * Contains the category ID for a product.
	 *
	 * @var    int
	 * @since  3.0
	 */
	public $category_id = null;

	/**
	 * Contains the category setting for publishing
	 *
	 * @var    bool
	 * @since  3.0
	 */
	public $category_publish = 1;

	/**
	 * Here starts the processing.
	 *
	 * @return  void.
	 *
	 * @since   3.0
	 */
	public function getStart()
	{
		if (!$this->tablesLoaded)
		{
			$this->loadTables();
		}
	}

	/**
	 * Process record.
	 *
	 * @return  bool  Returns true if all is OK | Returns false if no product SKU or product ID can be found.
	 *
	 * @since   3.0
	 */

	public function getProcessRecord()
	{
		// Empty since we don't want to process any record
	}

	/**
	 * Gets the ID belonging to the category path.
	 *
	 * @param   string  $category_path  The path to get the ID for
	 * @param   int     $vendor_id      The vendor ID the category belongs to
	 *
	 * @return  array  Contains the category ID.
	 *
	 * @since   3.0
	 */
	public function getCategoryIdFromPath($category_path, $vendor_id = 1)
	{
		// Check for any missing categories, otherwise create them
		$category = $this->verifyCategory($category_path, $vendor_id);

		return array('category_id' => $category[0]);
	}

	/**
	 * Inserts the category/categories for a product
	 *
	 * Any existing categories will be removed first, after that the new
	 * categories will be imported.
	 *
	 * @param   integer  $product_id     Contains the product ID the category/categories belong to
	 * @param   integer  $category_path  Contains the category/categories path for the product
	 * @param   integer  $category_id    Contains a single or array of category IDs
	 * @param   integer  $ordering       The product order in the category
	 * @param   integer  $vendor_id      The id of the vendor the category belongs to
	 *
	 * @return  bool  True on success | False on failure.
	 *
	 * @since   3.0
	 */
	public function checkCategoryPath($product_id=false, $category_path=array(), $category_id=false, $ordering='NULL', $vendor_id = 1)
	{
		$this->log->add('Checking category', false);

		// Check if there is a product ID
		if (!$product_id)
		{
			return false;
		}
		else
		{
			// If product_parent_id is true, we have a child product, child products do not have category paths
			// We have a category path, need to find the ID
			if (!$category_id)
			{
				// Use verifyCategory() method to confirm/add category tree for this product
				// Modification: $category_id now is an array
				$category_id = $this->verifyCategory($category_path, $vendor_id);
			}

			// We have a category_id, no need to find the path
			if ($category_id)
			{
				// Delete old entries only if the user wants us to
				if (!$this->template->get('append_categories', false))
				{
					$query = $this->db->getQuery(true)
						->delete($this->db->quoteName('#__virtuemart_product_categories'))
						->where($this->db->quoteName('virtuemart_product_id') . ' = ' . (int) $product_id);
					$this->db->setQuery($query)->execute();
					$this->log->add('COM_CSVI_DELETE_OLD_CATEGORIES_XREF');
				}
				else
				{
					$this->log->add('Do not delete old category references, going to append the categories', false);
				}

				// Insert new product/category relationships
				$category_xref_values = array('virtuemart_product_id' => $product_id, 'ordering' => $ordering);

				foreach ($category_id as $value)
				{
					$category_xref_values['virtuemart_category_id'] = $value;
					$this->productCategoryXref->bind($category_xref_values);
					$this->productCategoryXref->store();
					$this->productCategoryXref->reset();
					$category_xref_values['virtuemart_category_id'] = '';
				}
			}
		}

		// Clean the tables
		$this->cleanTables();

		return true;
	}

	/**
	 * Load the necessary tables.
	 *
	 * @return  void.
	 *
	 * @since   3.0
	 */
	private function loadTables()
	{
		$this->category = $this->getTable('Category', 'VirtueMartTable');
		$this->categoryLang = $this->getTable('CategoryLang', 'VirtueMartTable');
		$this->categoryXref = $this->getTable('CategoryXref', 'VirtueMartTable');
		$this->productCategoryXref = $this->getTable('ProductCategoryXref', 'VirtueMartTable');
		$this->tablesLoaded = true;
	}

	/**
	 * Clear the loaded tables.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function cleanTables()
	{
		$this->category->reset();
		$this->categoryLang->reset();
		$this->categoryXref->reset();
	}

	/**
	 * Creates categories from slash delimited line.
	 *
	 * @param   array  $category_path  Contains the category/categories for a product.
	 * @param   int    $vendor_id      The id of the vendor the category belongs to.
	 *
	 * @return  array  Contains the category IDs.
	 *
	 * @throws  Exception
	 *
	 * @since   3.0
	 */
	private function verifyCategory($category_path, $vendor_id = 1)
	{
		$lang = $this->template->get('language');
		$category = array();

		// Load the category separator
		if (is_null($this->categorySeparator))
		{
			$this->categorySeparator = $this->template->get('category_separator', '/');
		}

		// Check if category_path is an array, if not make it one
		if (!is_array($category_path))
		{
			$category_path = array($category_path);
		}

		// Get all categories in this field delimited with |
		foreach ($category_path as $line)
		{
			// Explode slash delimited category tree into array
			$category_list = explode($this->categorySeparator, $line);
			$category_count = count($category_list);
			$category_id = null;
			$category_parent_id = '0';

			// For each category in array
			for ($i = 0; $i < $category_count; $i++)
			{
				// Check the cache first
				if (array_key_exists($category_parent_id . '.' . $category_list[$i], $this->categoryCache))
				{
					$category_id = $this->categoryCache[$category_parent_id . '.' . $category_list[$i]];
				}
				else
				{
					// See if this category exists with it's parent in xref
					$query = $this->db->getQuery(true)
						->select($this->db->quoteName('c.virtuemart_category_id'))
						->from($this->db->quoteName('#__virtuemart_categories', 'c'))
						->leftJoin(
							$this->db->quoteName('#__virtuemart_category_categories', 'x')
							. ' ON ' . $this->db->quoteName('c.virtuemart_category_id') . ' = ' . $this->db->quoteName('x.category_child_id')
						)
						->leftJoin(
							$this->db->quoteName('#__virtuemart_categories_' . $lang, 'l')
							. ' ON ' . $this->db->quoteName('l.virtuemart_category_id') . ' = ' . $this->db->quoteName('c.virtuemart_category_id')
						)
						->where($this->db->quoteName('l.category_name') . ' = ' . $this->db->quote($category_list[$i]))
						->where($this->db->quoteName('x.category_child_id') . ' = ' . $this->db->quoteName('c.virtuemart_category_id'))
						->where($this->db->quoteName('x.category_parent_id') . ' = ' . (int) $category_parent_id);
					$this->db->setQuery($query);
					$category_id = $this->db->loadResult();

					$this->log->add(JText::sprintf('COM_CSVI_CHECK_CATEGORY_EXISTS', $category_list[$i]));

					// Add result to cache if category exists
					if ($category_id)
					{
						$this->categoryCache[$category_parent_id . '.' . $category_list[$i]] = $category_id;
					}
				}

				// Category does not exist, create it
				if (is_null($category_id) && !$this->template->get('ignore_non_exist', false))
				{
					$timestamp = $this->date->toSql();

					// Let's find out the last category in the level of the new category
					$query = $this->db->getQuery(true)
						->select('MAX(' . $this->db->quoteName('c.ordering') . ') + 1 AS ordering')
						->from($this->db->quoteName('#__virtuemart_categories', 'c'))
						->leftJoin(
							$this->db->quoteName('#__virtuemart_category_categories', 'x')
							. ' ON ' . $this->db->quoteName('c.virtuemart_category_id') . ' = ' . $this->db->quoteName('x.category_child_id')
						)
						->where($this->db->quoteName('x.category_child_id') . ' = ' . $this->db->quoteName('c.virtuemart_category_id'))
						->where($this->db->quoteName('x.category_parent_id') . ' = ' . (int) $category_parent_id);
					$this->db->setQuery($query);
					$list_order = $this->db->loadResult();

					if (is_null($list_order))
					{
						$list_order = 1;
					}

					// Add category
					$this->category->set('virtuemart_vendor_id', $vendor_id);
					$this->category->set('created_on', $timestamp);
					$this->category->set('created_by', $this->userId);
					$this->category->set('modified_on', $timestamp);
					$this->category->set('modified_by', $this->userId);
					$this->category->set('ordering', $list_order);
					$this->category->set('published', $this->category_publish);
					$this->category->set('category_template', $this->helperconfig->get('categorytemplate'));
					$this->category->set('category_layout', $this->helperconfig->get('categorylayout'));
					$this->category->set('products_per_row', $this->helperconfig->get('products_per_row'));
					$this->category->set('category_product_layout', $this->helperconfig->get('productlayout'));
					$this->category->set('limit_list_step', 0);
					$this->category->set('limit_list_initial', 0);
					$this->category->store();
					$this->log->add('Category publish ' . $this->category_publish, false);

					// Get the new created category ID
					$category_id = $this->category->get('virtuemart_category_id');

					// Add the category name to the language table
					$this->categoryLang->set('virtuemart_category_id', $category_id);
					$this->categoryLang->set('category_name', $category_list[$i]);
					$this->categoryLang->check();
					$this->categoryLang->store();

					// Add result to cache
					$this->categoryCache[$category_parent_id . '.' . $category_list[$i]] = $category_id;

					// Create xref with parent
					$this->categoryXref->set('category_parent_id', $category_parent_id);
					$this->categoryXref->set('category_child_id', $category_id);
					$this->categoryXref->store();

					// Clean for the next row
					$this->category->reset();
					$this->categoryLang->reset();
					$this->categoryXref->reset();
				}
				elseif (is_null($category_id))
				{
					throw new Exception(JText::sprintf('COM_CSVI_DATA_EXISTS_IGNORE_NEW', $line));
				}

				// Set this category as parent of next in line
				$category_parent_id = $category_id;
			}

			$category[] = $category_id;
		}

		// Return an array with the last category_ids which is where the product goes
		return $category;
	}
}
