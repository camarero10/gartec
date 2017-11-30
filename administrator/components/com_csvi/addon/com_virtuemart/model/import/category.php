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
 * Category import.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelImportCategory extends RantaiImportEngine
{
	/**
	 * Category table
	 *
	 * @var    VirtueMartTableCategory
	 * @since  6.0
	 */
	private $category = null;

	/**
	 * Media table
	 *
	 * @var    VirtueMartTableMedia
	 * @since  6.0
	 */
	private $media = null;

	/**
	 * Category media table
	 *
	 * @var    VirtueMartTableCategorymedia
	 * @since  6.0
	 */
	private $categoryMedia = null;

	/**
	 * Category language table
	 *
	 * @var    VirtueMartTableCategoryLang
	 * @since  6.0
	 */
	private $categoryLang = null;

	/**
	 * Category model helper
	 *
	 * @var    Com_virtuemartHelperCategory
	 * @since  6.0
	 */
	private $categoryModel = null;

	/**
	 * Category separator
	 *
	 * @var    string
	 * @since  6.0
	 */
	private $categorySeparator = null;

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
			// Get the general category functions
			$this->categoryModel = new Com_virtuemartHelperCategory(
				$this->db,
				$this->template,
				$this->log,
				$this->csvihelper,
				$this->fields,
				$this->helper,
				$this->helperconfig,
				$this->userId
			);
			$this->categoryModel->getStart();

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
						default:
							$this->setState($name, $value);
							break;
					}
				}
			}

			// If we have no category path we cannot continue
			$categoryPath = $this->getState('category_path', false);

			if (!$categoryPath)
			{
				$this->log->addStats('incorrect', 'COM_CSVI_NO_CATEGORY_PATH_SET');

				return false;
			}

			return true;
		}
		else
		{
			$lang = $this->template->get('target_language');

			if ($this->template->get('language') == $this->template->get('target_language'))
			{
				$lang = $this->template->get('language');
			}

			$tblname = $this->db->getPrefix() . 'virtuemart_categories_' . $lang;
			$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_LANG_TABLE_NOT_EXIST', $tblname));

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
		$translate = false;

		// Load the category separator
		if (is_null($this->categorySeparator))
		{
			$this->categorySeparator = $this->template->get('category_separator', '/');
		}

		// Set the category published state
		$this->categoryModel->category_publish = $this->getState('published', 1);

		// Loop through all categories if we are importing a translation
		if ($this->getState('category_path_trans', false))
		{
			$trans_paths = explode($this->categorySeparator, $this->getState('category_path_trans'));
			$paths = explode($this->categorySeparator, $this->getState('category_path'));

			if (!is_array($paths))
			{
				$paths = (array) $paths;
			}

			$translate = true;
		}
		elseif ($this->template->get('language') == $this->template->get('target_language'))
		{
			$trans_paths = array($this->getState('category_path'));
			$paths = array($this->getState('category_path'));
		}
		else
		{
			$this->log->addStats(
				'incorrect',
				JText::sprintf('COM_CSVI_CATEGORY_LANGUAGE_UNKNOWN', $this->template->get('language'), $this->template->get('target_language'))
			);

			return false;
		}

		// Get the last category
		$last = end($paths);
		reset($paths);

		// Process the paths
		foreach ($paths as $key => $path)
		{
			// Construct the full path
			$fullpath = array();

			for ($i = 0; $i <= $key; $i++)
			{
				$fullpath[] = $paths[$i];
			}

			$workPath = implode($this->categorySeparator, $fullpath);

			// First get the category ID
			try
			{
				$categoryid = $this->categoryModel->getCategoryIdFromPath($workPath);

				$this->setState('virtuemart_category_id', $categoryid['category_id']);
			}
			catch (Exception $e)
			{
				$this->log->addStats('incorrect', $e->getMessage());

				return false;
			}

			if ($this->getState('virtuemart_category_id', false) && !$this->template->get('overwrite_existing_data'))
			{
				$this->log->add(JText::sprintf('COM_CSVI_DATA_EXISTS_PRODUCT_SKU', $categoryid));
				$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_PRODUCT_SKU', $categoryid));
			}
			elseif ($last == $path)
			{
				// We have the category ID, lets see if it should be deleted
				if ($this->getState('category_delete', 'N') == 'Y')
				{
					$this->deleteCategory();
				}
				else
				{
					// Handle the images
					$this->processMedia();

					// Set some basic values
					if (!$this->getState('modified_on', false))
					{
						$this->category->modified_on = $this->date->toSql();
						$this->category->modified_by = $this->userId;
					}

					// Check if the category_name matches the last entry in the category_path
					if ($this->getState('category_name', false))
					{
						if ($path != $this->getState('category_name'))
						{
							$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_CATEGORY_NAME_NO_MATCH_CATEGORY_PATH', $path, $this->getState('category_name')));

							return false;
						}
					}

					// All fields have been processed, bind the data
					$this->category->bind($this->state);

					// Now store the data
					if (!$this->category->store())
					{
						$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_CATEGORY_DETAILS_NOT_ADDED', $this->category->getError()));
					}

					// Store the language fields
					$this->categoryLang->load($this->category->virtuemart_category_id);
					$this->categoryLang->bind($this->state);

					// Set the translated category name
					if ($translate)
					{
						$this->categoryLang->category_name = $trans_paths[$key];
					}

					// Check and store the language data
					if ($this->categoryLang->check())
					{
						// Recreate the slug
						if ($this->template->get('recreate_alias', false))
						{
							$this->categoryLang->createSlug();
						}

						if (!$this->categoryLang->store())
						{
							$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_CATEGORY_LANG_NOT_ADDED', $this->categoryLang->getError()));

							return false;
						}
					}
					else
					{
						$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_CATEGORY_LANG_NOT_ADDED', $this->categoryLang->getError()));

						return false;
					}
				}
			}

			$this->category->reset();
			$this->categoryLang->reset();
		}

		return true;
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
		$this->category = $this->getTable('Category');
		$this->media = $this->getTable('Media');
		$this->categoryMedia = $this->getTable('CategoryMedia');

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

		if (!in_array($this->db->getPrefix() . 'virtuemart_categories_' . $lang, $tables))
		{
			$this->tablesExist = false;
		}
		else
		{
			$this->tablesExist = true;
			$this->categoryLang = $this->getTable('CategoryLang');
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
		$this->category->reset();
		$this->media->reset();
		$this->categoryMedia->reset();
		$this->categoryLang->reset();
	}

	/**
	 * Delete a category and its references.
	 *
	 * @return  void.
	 *
	 * @since   4.0
	 */
	private function deleteCategory()
	{
		// Delete the product
		if ($this->category->delete($this->getState('virtuemart_category_id')))
		{
			$this->log->addstats('deleted', 'com_csvi_category_deleted');

			// Delete category translations
			$languages = array_keys($this->csvihelper->getLanguages('lang_code'));

			foreach ($languages as $language)
			{
				$query = $this->db->getquery(true)
					->delete($this->db->quotename('#__virtuemart_categories_' . strtolower(str_replace('-', '_', $language))))
					->where($this->db->quotename('virtuemart_category_id') . ' = ' . (int) $this->getState('virtuemart_category_id'));
				$this->db->setquery($query);
				$this->log->add('COM_CSVI_DEBUG_DELETE_CATEGORY_LANG_XREF', true);
				$this->db->execute();
			}

			// Delete category reference
			$query = $this->db->getquery(true)
				->delete($this->db->quotename('#__virtuemart_category_categories'))
				->where($this->db->quotename('category_child_id') . ' = ' . (int) $this->getState('virtuemart_category_id'));
			$this->db->setquery($query);
			$this->log->add('COM_CSVI_DEBUG_DELETE_CATEGORY_XREF', true);
			$this->db->execute();

			// Delete media
			$query = $this->db->getquery(true)
				->delete($this->db->quotename('#__virtuemart_category_medias'))
				->where($this->db->quotename('virtuemart_category_id') . ' = ' . (int) $this->getState('virtuemart_category_id'));
			$this->db->setquery($query);
			$this->log->add('COM_CSVI_DEBUG_DELETE_MEDIA_XREF', true);
			$this->db->execute();

			// Reset the products that link to this category
			$query = $this->db->getquery(true)
				->delete($this->db->quotename('#__virtuemart_product_categories'))
				->where($this->db->quotename('virtuemart_category_id') . ' = ' . (int) $this->getState('virtuemart_category_id'));
			$this->db->setquery($query);
			$this->log->add('COM_CSVI_DEBUG_DELETE_PRODUCT_CATEGORY_XREF', true);
			$this->db->execute();

			// Reset any child categories to become parent
			$query = $this->db->getquery(true)
				->update($this->db->quotename('#__virtuemart_category_categories'))
				->set($this->db->quoteName('category_parent_id') . ' = 0')
				->where($this->db->quotename('category_parent_id') . ' = ' . (int) $this->getState('virtuemart_category_id'));
			$this->db->setquery($query);
			$this->log->add('COM_CSVI_DEBUG_RESET_CATEGORY_PARENTS', true);
			$this->db->execute();
		}
		else
		{
			$this->log->addStats('incorrect', 'COM_CSVI_CATEGORY_NOT_DELETED');
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
				$max_width = $this->template->get('resize_max_width', 1024);
				$max_height = $this->template->get('resize_max_height', 768);

				// Image handling
				$imagehelper = new CsviHelperImage($this->template, $this->log, $this->csvihelper);

				foreach ($images as $key => $image)
				{
					$image = trim($image);

					$imgpath = $this->template->get('file_location_category_images', 'images/stories/virtuemart/category/');

					// Make sure the final slash is present
					if (substr($imgpath, -1) != '/')
					{
						$imgpath .= '/';
					}

					if (!empty($image))
					{
						// Verify the original image
						if ($imagehelper->isRemote($image))
						{
							$original = $image;
							$full_path = $imgpath;
						}
						else
						{
							// Check if the image contains the image path
							$dirname = dirname($image);

							if (strpos($imgpath, $dirname) !== false)
							{
								$image = basename($image);
							}

							$original = $imgpath . $image;

							// Get subfolders
							$path_parts = pathinfo($original);
							$full_path = $path_parts['dirname'] . '/';
						}

						// Generate image names
						if ($this->template->get('process_image', false))
						{
							$file_details = $imagehelper->ProcessImage($original, $full_path);
						}
						else
						{
							$file_details['exists'] = true;
							$file_details['isimage'] = $imagehelper->isImage(JPATH_SITE . '/' . $image);
							$file_details['name'] = $image;
							$file_details['output_name'] = basename($image);

							if (file_exists(JPATH_SITE . '/' . $image))
							{
								$file_details['mime_type'] = $imagehelper->findMimeType($image);
							}
							else
							{
								$file_details['mime_type'] = '';
							}

							$file_details['output_path'] = $full_path;
						}

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
								$media['file_type'] = 'category';
								$media['file_is_product_image'] = 0;
								$media['file_is_downloadable'] = 0;
								$media['file_is_forSale'] = 0;
								$media['file_url'] = (empty($file_details['output_path'])) ? $file_details['output_name'] : $file_details['output_path'] . $file_details['output_name'];
								$media['published'] = $this->getState('published', 0);

								// Create the thumbnail
								if ($file_details['isimage'])
								{
									$thumb = (isset($thumbs[$key])) ? $thumbs[$key] : null;

									if ($this->template->get('thumb_create'))
									{
										$thumb_sizes = getimagesize(JPATH_SITE . '/' . $media['file_url']);

										if ($thumb && ($thumb_sizes[0] < $max_width || $thumb_sizes[1] < $max_height))
										{
											$media['file_url_thumb'] = $imagehelper->createThumbnail($media['file_url'], $this->template->get('file_location_category_images'), $thumb);
										}
										else
										{
											$media['file_url_thumb'] = '';
										}
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
								$this->media->bind($media);

								// Check if the media image already exists
								$this->media->check();

								// Store the media data
								if ($this->media->store())
								{
									// Watermark the image
									if ($this->template->get('full_watermark') && $file_details['isimage'])
									{
										$imagehelper->addWatermark(JPATH_SITE . '/' . $media['file_url']);
									}

									// Store the category image relation
									$data = array();
									$data['virtuemart_category_id'] = $this->getState('virtuemart_category_id');
									$data['virtuemart_media_id'] = $this->media->get('virtuemart_media_id');
									$data['ordering'] = (isset($order[$key]) && !empty($order[$key])) ? $order[$key] : $ordering;
									$this->categoryMedia->bind($data);

									$this->categoryMedia->check();

									if ($this->categoryMedia->store())
									{
										$this->log->add('Store the category image relation', false);
										$ordering++;
									}
								}
								else
								{
									$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_MEDIA_NOT_ADDED', $this->media->getError()));
								}

								// Reset the product media table
								$this->media->reset();
								$this->categoryMedia->reset();
							}
						}
					}
				}
			}
		}
	}
}
