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
 * Manufacturer import.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelImportManufacturer extends RantaiImportEngine
{
	/**
	 * Manufacturer table.
	 *
	 * @var    VirtueMartTableManufacturer
	 * @since  6.0
	 */
	private $manufacturerTable;

	/**
	 * Media table.
	 *
	 * @var    VirtueMartTableMedia
	 * @since  6.0
	 */
	private $mediaTable;

	/**
	 * Manufacturer media table.
	 *
	 * @var    VirtueMartTableManufacturerMedia
	 * @since  6.0
	 */
	private $manufacturerMediaTable;

	/**
	 * Manufacturer language table.
	 *
	 * @var    VirtueMartTableManufacturerLang
	 * @since  6.0
	 */
	private $manufacturerLangTable;

	/**
	 * Manufacturer category language table.
	 *
	 * @var    VirtueMartTableManufacturerCategoryLang
	 * @since  6.0
	 */
	private $manufacturerCategoryLangTable;

	/**
	 * Set if language tables exist
	 *
	 * @var    bool
	 * @since  6.0
	 */
	private $tablesExist = true;

	/**
	 * Here starts the processing.
	 *
	 * @return  bool  Akways returns true.
	 *
	 * @since   3.0
	 */
	public function getStart()
	{
		// Only continue if all tables exist
		if ($this->tablesExist)
		{
			// Check for vendor ID
			$this->setState('virtuemart_vendor_id', $this->helper->getVendorId());

			// Process data
			foreach ($this->fields->getData() as $fields)
			{
				foreach ($fields as $name => $details)
				{
					$value = $details->value;

					switch ($name)
					{
						case 'published':
							switch ($value)
							{
								case 'n':
								case 'N':
								case '0':
									$value = 0;
									break;
								default:
									$value = 1;
									break;
							}

							$this->setState('published', $value);
							break;
						case 'mf_category_name':
							$this->manufacturerCategoryLangTable->mf_category_name = $value;
							break;
						default:
							$this->setState($name, $value);
							break;
					}
				}
			}

			// Reset loaded state
			$this->loaded = true;

			$virtuemart_manufacturer_id = $this->getState('virtuemart_manufacturer_id', false);
			$mf_name = $this->getState('mf_name', false);

			if ($mf_name || $virtuemart_manufacturer_id)
			{
				// Check for the manufacturer ID
				if (!$virtuemart_manufacturer_id && $mf_name)
				{
					$this->getManufacturerId();
				}

				// Bind the values
				$this->manufacturerTable->bind($this->state);

				if ($this->manufacturerTable->check())
				{
					$this->setState('virtuemart_manufacturer_id', $this->manufacturerTable->virtuemart_manufacturer_id);

					// Check if we have an existing item
					if ($this->getState('virtuemart_manufacturer_id', 0) > 0 && !$this->template->get('overwrite_existing_data', true))
					{
						$this->log->add(JText::sprintf('COM_CSVI_DATA_EXISTS_MANUFACTURER', $this->getState('mf_name')));
						$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_MANUFACTURER', $this->getState('mf_name')));
						$this->loaded = false;
					}
					else
					{
						// Load the current manufacturer data
						$this->manufacturerTable->load($this->getState('virtuemart_manufacturer_id', 0));
						$this->loaded = true;
					}
				}
			}
			else
			{
				$this->loaded = false;

				$this->log->addStats('skipped', JText::_('COM_CSVI_MISSING_REQUIRED_FIELDS'));
			}

			return true;
		}
		else
		{
			$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_LANG_TABLE_NOT_EXIST', $this->template->get('language')));

			return false;
		}
	}

	/**
	 * Process a record.
	 *
	 * @return  bool  Returns true if all is OK | Returns false if no product SKU or product ID can be found.
	 *
	 * @since   6.0
	 */
	public function getProcessRecord()
	{
		if ($this->loaded)
		{
			if (!$this->getState('virtuemart_manufacturer_id', false) && $this->template->get('ignore_non_exist'))
			{
				// Do nothing for new coupons when user chooses to ignore new coupons
				$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_IGNORE_NEW', $this->getState('coupon_code')));
			}
			else
			{
				// Check if we need to delete the manufacturer
				if ($this->getState('manufacturer_delete', 'N') == 'Y')
				{
					$this->deleteManufacturer();
				}
				else
				{
					// Check if we need to get manufacturer category ID
					$virtuemart_manufacturercategories_id = $this->getState('virtuemart_manufacturercategories_id', false);

					if (!$virtuemart_manufacturercategories_id && isset($this->manufacturerCategoryLangTable->mf_category_name))
					{
						if ($this->manufacturerCategoryLangTable->check(false))
						{
							$this->setState('virtuemart_manufacturercategories_id', $this->manufacturerCategoryLangTable->virtuemart_manufacturercategories_id);
						}
					}

					// Set the modified date as we are modifying the product
					if (!$this->getState('modified_on', false))
					{
						$this->manufacturerTable->modified_on = $this->date->toSql();
						$this->manufacturerTable->modified_by = $this->userId;
					}

					// Add a creating date if there is no manufacturer_id
					if (!$this->getState('virtuemart_manufacturer_id', false))
					{
						$this->manufacturerTable->created_on = $this->date->toSql();
						$this->manufacturerTable->created_by = $this->userId;
					}

					// Store the data
					if ($this->manufacturerTable->store())
					{
						$this->virtuemart_manufacturer_id = $this->manufacturerTable->get('virtuemart_manufacturer_id');

						// Store the language fields
						$this->manufacturerLangTable->virtuemart_manufacturer_id = $this->virtuemart_manufacturer_id;

						if ($this->manufacturerLangTable->check())
						{
							$this->manufacturerLangTable->bind($this->state);

							if ($this->getState('mf_name_trans'))
							{
								$this->manufacturerLangTable->mf_name = $this->getState('mf_name_trans');
							}

							if (!$this->manufacturerLangTable->store())
							{
								return false;
							}
						}
						else
						{
							$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_PRODUCT_LANG_NOT_ADDED', $this->manufacturerLangTable->getError()));

							return false;
						}

						// Handle the images
						$this->processMedia();
					}
					else
					{
						$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_MANUFACTURER_NOT_ADDED', $this->manufacturerTable->getError()));
					}
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
	 * Load the necessary tables.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function loadTables()
	{
		$this->manufacturerTable = $this->getTable('Manufacturer');
		$this->mediaTable = $this->getTable('Media');
		$this->manufacturerMediaTable = $this->getTable('ManufacturerMedia');

		// Check if the language tables exist
		$tables = $this->db->getTableList();

		if ($this->template->get('language') == $this->template->get('target_language'))
		{
			$lang = $this->template->get('language');
		}
		else
		{
			$lang = $this->template->get('target_language');
		}

		if (!in_array($this->db->getPrefix() . 'virtuemart_manufacturers_' . $lang, $tables))
		{
			$this->tablesExist = false;
		}
		elseif (!in_array($this->db->getPrefix() . 'virtuemart_manufacturercategories_' . $lang, $tables))
		{
			$this->tablesExist = false;
		}
		else
		{
			$this->tablesExist = true;
			$this->manufacturerLangTable = $this->getTable('ManufacturerLang');
			$this->manufacturerCategoryLangTable = $this->getTable('ManufacturerCategoryLang');
		}
	}

	/**
	 * Clear the loaded tables.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function clearTables()
	{
		$this->manufacturerTable->reset();
		$this->mediaTable->reset();
		$this->manufacturerMediaTable->reset();
		$this->manufacturerLangTable->reset();
		$this->manufacturerCategoryLangTable->reset();
	}

	/**
	 * Delete a manufacturer and its references.
	 *
	 * @return  void.
	 *
	 * @since   4.0
	 */
	private function deleteManufacturer()
	{
		$virtuemart_manufacturer_id = $this->getState('virtuemart_manufacturer_id', false);

		if ($virtuemart_manufacturer_id)
		{
			// Delete product manufacturer
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName('#__virtuemart_product_manufacturers'))
				->where($this->db->quoteName('virtuemart_manufacturer_id') . ' = ' . (int) $virtuemart_manufacturer_id);
			$this->db->setQuery($query);

			if ($this->db->execute())
			{
				$this->log->addStats('deleted', 'COM_CSVI_MANUFACTURER_XREF_DELETED');
			}
			else
			{
				$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_MANUFACTURER_XREF_NOT_DELETED', $this->db->getErrorMsg()));
			}

			// Delete translations
			$languages = $this->csvihelper->getLanguages();

			foreach ($languages as $language)
			{
				$query = $this->db->getQuery(true)
					->delete($this->db->quoteName('#__virtuemart_manufacturers_' . strtolower(str_replace('-', '_', $language->lang_code))))
					->where($this->db->quoteName('virtuemart_manufacturer_id') . ' = ' . (int) $virtuemart_manufacturer_id);
				$this->db->setQuery($query);
				$this->log->add('COM_CSVI_DEBUG_DELETE_MANUFACTURER_LANG_XREF', true);
				$this->db->execute();
			}

			// Delete manufacturer
			if ($this->manufacturerTable->delete($virtuemart_manufacturer_id))
			{
				$this->log->addStats('deleted', 'COM_CSVI_DELETE_MANUFACTURER');
			}
			else
			{
				$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_MANUFACTURER_NOT_DELETED', $this->manufacturerTable->getError()));
			}

			// Delete media
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName('#__virtuemart_manufacturer_medias'))
				->where($this->db->quoteName('virtuemart_manufacturer_id') . ' = ' . (int) $virtuemart_manufacturer_id);
			$this->db->setQuery($query);
			$this->log->add('COM_CSVI_DEBUG_DELETE_MEDIA_XREF', true);
			$this->db->execute();
		}
		else
		{
			$this->log->addStats('incorrect', 'COM_CSVI_MANUFACTURER_NOT_DELETED_NO_ID');
		}
	}

	/**
	 * Get the manufacturer ID.
	 *
	 * @return  void.
	 *
	 * @since   3.0
	 */
	private function getManufacturerId()
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('virtuemart_manufacturer_id'))
			->from($this->db->quoteName('#__virtuemart_manufacturers_' . $this->template->get('language')))
			->where($this->db->quoteName('mf_name') . ' = ' . $this->db->quote($this->getState('mf_name')));
		$this->db->setQuery($query);
		$this->log->add('COM_CSVI_CHECK_MANUFACTURER_EXISTS', true);
		$id = $this->db->loadResult();

		if ($id)
		{
			$this->setState('virtuemart_manufacturer_id', $id);
		}
	}

	/**
	 * Process media files.
	 *
	 * @return  void.
	 *
	 * @since   4.0
	 */
	private function processMedia()
	{
		// Check if any image handling needs to be done
		if ($this->template->get('process_image', false))
		{
			if ($this->getState('file_url', false))
			{
				// Create an array of images to process
				$images = explode('|', $this->getState('file_url'));
				$thumbs = explode('|', $this->getState('file_url_thumb'));
				$titles = explode('|', $this->getState('file_title'));
				$descriptions = explode('|', $this->getState('file_description'));
				$metas = explode('|', $this->getState('file_meta'));
				$order = explode('|', $this->getState('file_ordering'));
				$ordering = 1;

				// Image handling
				$imagehelper = new CsviHelperImage($this->template, $this->log, $this->csvihelper);

				foreach ($images as $key => $image)
				{
					$image = trim($image);

					if (!empty($image))
					{
						$full_path = $this->template->get('file_location_manufacturer_images', 'images/stories/virtuemart/manufacturer/');

						// Make sure the final slash is present
						if (substr($full_path, -1) != '/')
						{
							$full_path .= '/';
						}

						// Verify the original image
						if ($imagehelper->isRemote($image))
						{
							$original = $image;
						}
						else
						{
							// Create the original image path
							$original = $full_path . $image;

							// Get subfolders
							$path_parts = pathinfo($original);
							$full_path = $path_parts['dirname'] . '/';
						}

						$file_details = $imagehelper->ProcessImage($original, $full_path);

						// Process the file details
						if ($file_details['exists'])
						{
							// Check if the image is an external image
							if (substr($file_details['name'], 0, 4) == 'http')
							{
								$this->log->addStats('incorrect', 'COM_CSVI_VM_NOSUPPORT_URL');
							}
							else
							{
								$title = (isset($titles[$key])) ? $titles[$key] : $file_details['output_name'];
								$description = (isset($descriptions[$key])) ? $descriptions[$key] : '';
								$meta = (isset($metas[$key])) ? $metas[$key] : '';
								$media = array();
								$media['virtuemart_vendor_id'] = $this->getState('virtuemart_vendor_id');

								if ($this->template->get('autofill'))
								{
									$media['file_title'] = $file_details['output_name'];
									$media['file_description'] = $file_details['output_name'];
									$media['file_meta'] = $file_details['output_name'];
								}
								else
								{
									$media['file_title'] = $title;
									$media['file_description'] = $description;
									$media['file_meta'] = $meta;
								}

								$media['file_mimetype'] = $file_details['mime_type'];
								$media['file_type'] = 'manufacturer';
								$media['file_is_product_image'] = 0;
								$media['file_is_downloadable'] = 0;
								$media['file_is_forSale'] = 0;
								$media['file_url'] = (empty($file_details['output_path'])) ? $file_details['output_name'] : $file_details['output_path'] . $file_details['output_name'];
								$media['published'] = $this->getState('published');

								// Create the thumbnail
								if ($file_details['isimage'])
								{
									$thumb = (isset($thumbs[$key])) ? $thumbs[$key] : null;

									if ($this->template->get('thumb_create', false))
									{
										if (empty($thumb))
										{
											$thumb = 'resized/' . basename($media['file_url']);
										}

										$media['file_url_thumb'] = $imagehelper->createThumbnail($media['file_url'], $this->template->get('file_location_product_images', 'path'), $thumb);
									}
									else
									{
										$media['file_url_thumb'] = (empty($thumb)) ? $media['file_url'] : $file_details['output_path'] . $thumb;

										if (substr($media['file_url_thumb'], 0, 4) == 'http')
										{
											$this->log->add(JText::sprintf('COM_CSVI_RESET_THUMB_NOHTTP', $media['file_url_thumb']));
											$media['file_url_thumb'] = '';
										}
									}
								}
								else
								{
									$media['file_is_product_image'] = 0;
									$media['file_url_thumb'] = '';
								}

								// Bind the media data
								$this->mediaTable->bind($media);

								// Check if the media image already exists
								$this->mediaTable->check();

								// Store the media data
								if ($this->mediaTable->store())
								{
									if ($this->queryResult() == 'UPDATE')
									{
										$this->log->addStats('updated', 'COM_CSVI_UPDATE_MEDIA');
									}
									else
									{
										$this->log->addStats('added', 'COM_CSVI_ADD_MEDIA');
									}

									// Store the debug message
									$this->log->add('COM_CSVI_MEDIA_QUERY', true);

									// Watermark the image
									if ($this->template->get('full_watermark', false) && $file_details['isimage'])
									{
										$imagehelper->addWatermark(JPATH_SITE . '/' . $media['file_url']);
									}

									// Store the product image relation
									$data = array();
									$data['virtuemart_manufacturer_id'] = $this->getState('virtuemart_manufacturer_id');
									$data['virtuemart_media_id'] = $this->mediaTable->virtuemart_media_id;
									$data['ordering'] = (isset($order[$key]) && !empty($order[$key])) ? $order[$key] : $ordering;
									$this->manufacturerMediaTable->bind($data);

									if ($this->manufacturerMediaTable->check())
									{
										if ($this->manufacturerMediaTable->store())
										{
											$this->log->add('COM_CSVI_STORE_MANUFACTURER_IMAGE_RELATION', true);
											$ordering++;
										}
									}
								}
								else
								{
									$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_MEDIA_NOT_ADDED', $this->mediaTable->getError()));
								}
								// Reset the product media table
								$this->mediaTable->reset();
								$this->manufacturerMediaTable->reset();
							}
						}
					}
				}
			}
		}
	}
}
