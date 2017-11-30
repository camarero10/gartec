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
 * Main processor for handling related product details.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelImportRelatedproduct extends RantaiImportEngine
{
	/**
	 * The product custom fields table
	 *
	 * @var    VirtueMartTableProductCustomfield
	 * @since  6.0
	 */
	private $productCustomfieldTable = null;

	/**
	 * Here starts the processing.
	 *
	 * @return  bool  Returns true on success | False on failure.
	 *
	 * @since   3.0
	 */
	public function getStart()
	{
		// Process data
		foreach ($this->fields->getData() as $fields)
		{
			foreach ($fields as $name => $details)
			{
				$value = $details->value;

				// Check if the field needs extra treatment
				switch ($name)
				{
					case 'related_products':
						if (substr($value, -1, 1) == "|")
						{
							$value = substr($value, 0, -1);
						}

						$this->setState($name, $value);
						break;
					default:
						$this->setState($name, $value);
						break;
				}
			}
		}

		// Reset loaded state
		$this->loaded = true;

		// Required fields are calc_kind, calc_value_mathop, calc_value
		if ($this->getState('product_sku', false) && $this->getState('related_products', false))
		{
			$this->setState('virtuemart_product_id', $this->helper->getProductId());
			$this->setState('virtuemart_vendor_id', $this->helper->getVendorId());

			// Set the record identifier
			$this->recordIdentity = ($this->getState('product_sku', false)) ?: $this->getState('virtuemart_product_id');
		}
		else
		{
			$this->loaded = false;

			$this->log->addStats('skipped', JText::_('COM_CSVI_MISSING_REQUIRED_FIELDS'));
		}

		return true;
	}

	/**
	 * Process each record and store it in the database.
	 *
	 * @return  bool  Returns true if all is OK | Returns false if no product SKU or product ID can be found.
	 *
	 * @since   3.0
	 */
	public function getProcessRecord()
	{
		if ($this->loaded)
		{
			// Remove any existing product relations
			$this->productCustomfieldTable->deleteRelated(
				$this->getState('virtuemart_product_id'),
				$this->getState('virtuemart_vendor_id'),
				$this->helper->getRelatedId('R')
			);

			// Process the related products
			$relatedProducts = explode("|", $this->getState('related_products', array()));

			foreach ($relatedProducts as $relatedProduct)
			{
				// Find the product ID
				$productId = $this->helper->getProductIdBySku($relatedProduct);

				if ($productId > 0)
				{
					// Build the object to store
					$related = new stdClass;
					$related->virtuemart_product_id = $this->getState('virtuemart_product_id');
					$related->virtuemart_custom_id = $this->helper->getRelatedId('R');
					$related->published = 0;
					$related->created_on = $this->date->toSql();
					$related->created_by = $this->userId;
					$related->modified_on = $this->date->toSql();
					$related->modified_by = $this->userId;
					$related->customfield_params = '';
					$related->customfield_value = $productId;

					// Bind the data
					$this->productCustomfieldTable->bind($related);

					// Store the data
					if ($this->productCustomfieldTable->store())
					{
						$this->log->add('COM_CSVI_PROCESS_RELATED_PRODUCTS', true);
					}
					else
					{
						$this->log->add('COM_CSVI_DEBUG_RELATED_PRODUCTS', true);
					}

					// Clean the table object for next insert
					$this->productCustomfieldTable->reset();
				}
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Load the product related tables.
	 *
	 * @return  void.
	 *
	 * @since   3.0
	 */
	public function loadTables()
	{
		// Load the main tables
		$this->productCustomfieldTable = $this->getTable('ProductCustomfield');
	}

	/**
	 * Cleaning the product related tables.
	 *
	 * @return  void.
	 *
	 * @since   3.0
	 */
	public function clearTables()
	{
		// Clean the main tables
		$this->productCustomfieldTable->reset();
	}
}
