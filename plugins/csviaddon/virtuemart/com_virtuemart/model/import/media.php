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
 * Media import.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelImportMedia extends RantaiImportEngine
{
	/**
	 * Media table.
	 *
	 * @var    VirtueMartTableMedia
	 * @since  6.0
	 */
	private $mediaTable = null;

	/**
	 * Product media table.
	 *
	 * @var    VirtueMartTableProductMedia
	 * @since  6.0
	 */
	private $productMediaTable = null;

	/**
	 * VirtueMart helper
	 *
	 * @var    Com_VirtuemartHelperCom_Virtuemart
	 * @since  6.0
	 */
	protected $helper = array();

	/**
	 * The image helper
	 *
	 * @var    CsviHelperImage
	 * @since  6.0
	 */
	private $imagehelper = null;

	/**
	 * Load the image helper before we start.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function onBeforeStart()
	{
		// Image handling
		$this->imagehelper = new CsviHelperImage($this->template, $this->log, $this->csvihelper);
	}

	/**
	 * Here starts the processing.
	 *
	 * @return  bool  Akways returns true.
	 *
	 * @since   3.0
	 */
	public function getStart()
	{
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
					case 'media_delete':
						$this->setState($name, strtoupper($value));
						break;
					default:
						$this->setState($name, $value);
						break;
				}
			}
		}

		// Reset loaded state
		$this->loaded = true;

		// Set the full file url
		list($original, $full_path, $remote) = $this->filenameDetails();
		$this->setState('file_url', $original);

		if ($this->getState('file_url', false))
		{
			// Bind the values
			$this->mediaTable->bind($this->state);

			if ($this->mediaTable->check())
			{
				$this->setState('virtuemart_media_id', $this->mediaTable->virtuemart_media_id);

				// Check if we have an existing item
				if ($this->getState('virtuemart_media_id', 0) > 0 && !$this->template->get('overwrite_existing_data', true))
				{
					$this->log->add(JText::sprintf('COM_CSVI_DATA_EXISTS_CONTENT', $this->getState('file_url')));
					$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_CONTENT', $this->getState('file_url')));
					$this->loaded = false;
				}
				else
				{
					// Load the current content data
					$this->mediaTable->load($this->getState('virtuemart_media_id', 0));
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
			if (!$this->getState('virtuemart_media_id', false) && $this->template->get('ignore_non_exist'))
			{
				// Do nothing for new coupons when user chooses to ignore new coupons
				$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_IGNORE_NEW', $this->getState('file_url')));
			}
			else
			{
				// Do we need to delete a media file?
				if ($this->getState('media_delete', 'N') == 'Y')
				{
					$this->deleteMedia();
				}
				else
				{
					// Process the image
					$this->processMedia();

					// Set some basic values
					if (!$this->getState('modified_on', false))
					{
						$this->mediaTable->modified_on = $this->date->toSql();
						$this->mediaTable->modified_by = $this->userId;
					}

					// Check if the media exists
					if ($this->getState('virtuemart_media_id', false))
					{
						$this->mediaTable->created_on = $this->date->toSql();
						$this->mediaTable->created_by = $this->userId;
					}

					// Bind all the data
					$this->mediaTable->bind($this->state);

					// Store the data
					if ($this->mediaTable->store())
					{
						// Watermark image if needed
						if ($this->template->get('full_watermark', false))
						{
							$this->imagehelper = new CsviHelperImage($this->template, $this->log, $this->csvihelper);
							$this->imagehelper->addWatermark(JPATH_SITE . '/' . $this->mediaTable->file_url);
						}

						// Add a link to the product if the SKU is specified
						if ($this->getState('product_sku', false))
						{
							$this->productMediaTable->virtuemart_media_id = $this->mediaTable->virtuemart_media_id;
							$this->productMediaTable->virtuemart_product_id = $this->helper->getProductId();

							$this->productMediaTable->check();

							if (!$this->productMediaTable->store())
							{
								$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_MEDIA_NOT_ADDED', $this->mediaTable->getError()));
							}
						}
					}
					else
					{
						$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_MEDIAFILE_NOT_ADDED', $this->mediaTable->getError()));
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
		$this->mediaTable = $this->getTable('Media');
		$this->productMediaTable = $this->getTable('ProductMedia');
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
		$this->mediaTable->reset();
		$this->productMediaTable->reset();
	}

	/**
	 * Delete a media and its references.
	 *
	 * @return  bool  Returns true.
	 *
	 * @since   4.0
	 */
	private function deleteMedia()
	{
		if ($this->getState('virtuemart_media_id', false))
		{
			// Delete the product
			if ($this->mediaTable->delete($this->getState('virtuemart_media_id')))
			{
				// Delete product reference
				$query = $this->db->getQuery(true)
					->delete($this->db->quoteName('#__virtuemart_product_medias'))
					->where($this->db->quoteName('virtuemart_media_id') . ' = ' . (int) $this->getState('virtuemart_media_id'));
				$this->db->setQuery($query);
				$this->log->add('COM_CSVI_DEBUG_DELETE_MEDIA');
				$this->db->execute();
			}
		}
		else
		{
			$this->log->addStats('notice', JText::sprintf('COM_CSVI_DEBUG_NO_MEDIA_ID', $this->getState('file_url')));
		}

		return true;
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
				list($original, $full_path, $remote) = $this->filenameDetails();

				// Generate image names
				$file_details = $this->imagehelper->ProcessImage($original, $full_path);

				// Process the file details
				if ($file_details['exists'])
				{
					$media = array();

					if ($this->template->get('autofill'))
					{
						$media['file_title'] = $file_details['output_name'];
						$media['file_description'] = $file_details['output_name'];
						$media['file_meta'] = $file_details['output_name'];
					}
					else
					{
						$this->setState('file_title', $this->getState('file_title', $this->getState('file_url')));
						$this->setState('file_description', $this->getState('file_description', ''));
						$this->setState('file_meta', $this->getState('file_meta', ''));
					}

					$this->setState('file_mimetype', $file_details['mime_type']);

					if ($file_details['isimage'] && $this->getState('file_type') == 'product')
					{
						$this->setState('file_is_product_image', 1);
					}
					else
					{
						$this->setState('file_is_product_image', 0);
					}

					if (!$this->getState('file_is_downloadable', false) && !$file_details['isimage'])
					{
						$this->setState('file_is_downloadable', 1);
					}
					else
					{
						$this->setState('file_is_downloadable', 0);
					}

					$this->setState('file_is_forSale', $this->getState('file_is_forSale', 0));

					if (empty($file_details['output_path']))
					{
						$this->setState('file_url', $file_details['output_name']);
					}
					else
					{
						$this->setState('file_url',  $file_details['output_path'] . $file_details['output_name']);
					}

					// Create the thumbnail
					if ($file_details['isimage'])
					{
						if ($this->template->get('thumb_create'))
						{
							// Get the base folder
							$base = $this->getBasePath();

							// Get the subfolder structure
							$thumb_path = str_ireplace($base, '', $full_path);

							if (!$this->getState('file_url_thumb', false))
							{
								$this->setState('file_url_thumb', 'resized/' . $thumb_path . basename($this->getState('file_url')));
							}

							if (!$remote)
							{
								$original = $this->getState('file_url');
							}

							$this->setState('file_url_thumb', $this->imagehelper->createThumbnail($original, $base, $this->getState('file_url_thumb')));
						}
						elseif (!$this->getState('file_url_thumb', false))
						{
							$this->setState('file_url_thumb', '');
						}
					}
					else
					{
						$this->setState('file_url_thumb', '');
					}
				}
			}
		}
	}

	/**
	 * Get the filename details.
	 *
	 * @return  array.
	 *
	 * @since   6.0
	 */
	private function filenameDetails()
	{
		// Verify the original image
		if ($this->imagehelper->isRemote($this->getState('file_url')))
		{
			$original = $this->getState('file_url');
			$remote = true;

			if ($this->template->get('save_images_on_server'))
			{
				switch ($this->getState('file_type'))
				{
					case 'category':
						$base = $this->template->get('file_location_category_images');
						break;
					default:
						$base = $this->template->get('file_location_product_images');
						break;
				}
			}
			else
			{
				$base = '';
			}

			$full_path = $base;
		}
		else
		{
			// Check if the image contains the image path
			$dirname = dirname($this->getState('file_url'));
			$image = $this->getState('file_url');
			$base = $this->getBasePath();

			if (strpos($base, $dirname) !== false)
			{
				$image = basename($this->getState('file_url'));
			}

			$original = $base . $image;
			$remote = false;

			// Get subfolders
			$path_parts = pathinfo($original);
			$full_path = $path_parts['dirname'] . '/';

			$this->log->add(JText::sprintf('COM_CSVI_CREATED_FILE_URL', $original));
		}

		return array($original, $full_path, $remote);
	}

	/**
	 * Get base path.
	 *
	 * @return  string  The name of the basepath.
	 *
	 * @since   6.0
	 */
	private function getBasePath()
	{
		// Create the full file_url path
		switch ($this->getState('file_type'))
		{
			case 'category':
				$base = $this->template->get('file_location_category_images');
				break;
			default:
				$base = $this->template->get('file_location_product_files');
				break;
		}

		return $base;
	}
}
