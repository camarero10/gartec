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
 * Main processor for handling product images.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelImportMediaproduct extends RantaiImportEngine
{
	/**
	 * The media table
	 *
	 * @var    VirtueMartTableMedia
	 * @since  6.0
	 */
	private $media = null;

	/**
	 * The product media cross reference table
	 *
	 * @var    VirtueMartTableProductMedia
	 * @since  6.0
	 */
	private $productMedia = null;

	/**
	 * Here starts the processing.
	 *
	 * @return  bool  Returns true on success | False on failure.
	 *
	 * @since   3.0
	 */
	public function getStart()
	{
		$this->setState('virtuemart_product_id', $this->helper->getProductId());

		foreach ($this->fields->getData() as $fields)
		{
			foreach ($fields as $name => $details)
			{
				$value = $details->value;

				// Check if the field needs extra treatment
				switch ($name)
				{
					default:
						$this->setState($name, $value);
						break;
				}
			}
		}

		// Set the record identifier
		$this->recordIdentity = $this->getState('product_sku', $this->getState('virtuemart_product_id'));

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
		// Get the needed data
		$product_sku = $this->getState('product_sku', false);
		$virtuemart_product_id = $this->getState('virtuemart_product_id', false);

		if (!$virtuemart_product_id)
		{
			$this->log->addStats('incorrect', 'COM_CSVI_DEBUG_NO_SKU');
			$this->log->add('COM_CSVI_DEBUG_NO_SKU_OR_ID');

			return false;
		}
		else
		{
			if (!$this->template->get('overwrite_existing_data'))
			{
				$this->log->add(JText::sprintf('COM_CSVI_DATA_EXISTS_PRODUCT_MEDIA_SKU', $product_sku));
				$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_PRODUCT_MEDIA_SKU', $product_sku));
			}
			else
			{
				$this->log->add(JText::sprintf('COM_CSVI_DEBUG_PROCESS_SKU', $this->recordIdentity));

				// Do nothing for new products when user chooses to ignore new products
				if ($virtuemart_product_id)
				{
					$this->processMedia();
				}
			}

			return true;
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
		$this->media = $this->getTable('Media');
		$this->productMedia = $this->getTable('ProductMedia');
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
		$this->media->reset();
		$this->productMedia->reset();
	}

	/**
	 * Process media files.
	 *
	 * @return  bool Returns true on OK | False on failure.
	 *
	 * @since   4.0
	 */
	private function processMedia()
	{
		$generate_image = $this->template->get('auto_generate_image_name', false);

		// Check if any image handling needs to be done
		if (!is_null($this->file_url) || $generate_image)
		{
			// Check if we have any images
			if (is_null($this->file_url) && $generate_image)
			{
				$this->_createImageName();
			}

			// Create an array of images to process
			$images = explode('|', $this->file_url);
			$thumbs = explode('|', $this->file_url_thumb);
			$titles = explode('|', $this->file_title);
			$descriptions = explode('|', $this->file_description);
			$metas = explode('|', $this->file_meta);
			$order = explode('|', $this->file_ordering);
			$ordering = 1;
			$max_width = $this->template->get('resize_max_width', 1024);
			$max_height = $this->template->get('resize_max_height', 768);

			// Image handling
			$imagehelper = new CsviHelperImage($this->template, $this->log, $this->csvihelper);

			// Delete existing image links
			if ($this->template->get('delete_product_images', false))
			{
				$query = $this->db->getQuery(true)
					->delete($this->db->quoteName('#__virtuemart_product_medias'))
					->where($this->db->quoteName('virtuemart_product_id') . '=' . $this->virtuemart_product_id);
				$this->db->setQuery($query);
				$this->db->execute();
				$this->log->add('Delete images', true);
			}

			foreach ($images as $key => $image)
			{
				$image = trim($image);

				// Create image name if needed
				if (count($images) == 1)
				{
					$img_counter = 0;
				}
				else
				{
					$img_counter = $key + 1;
				}

				if ($generate_image)
				{
					$name = null;
					$name = $this->_createImageName($img_counter);

					if (!empty($name))
					{
						$image = $name;
					}
				}

				if (!empty($image))
				{
					// Get the image path
					$imgpath = $this->template->get('file_location_product_files');

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
						if ($generate_image)
						{
							$file_details = $imagehelper->ProcessImage($original, $full_path, $this->product_full_image_output);
						}
						else
						{
							$file_details = $imagehelper->ProcessImage($original, $full_path);
						}
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
							$media['virtuemart_vendor_id'] = $this->virtuemart_vendor_id;

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
							$media['file_type'] = 'product';
							$media['file_is_product_image'] = 1;
							$media['file_is_downloadable'] = ($file_details['isimage']) ? 0 : 1;
							$media['file_is_forSale'] = 0;
							$media['file_url'] = (empty($file_details['output_path'])) ? $file_details['output_name'] : $file_details['output_path'] . $file_details['output_name'];
							$media['published'] = $this->published;

							// Create the thumbnail
							if ($file_details['isimage'])
							{
								$thumb = (isset($thumbs[$key])) ? $thumbs[$key] : null;

								if ($this->template->get('thumb_create'))
								{
									$thumb_sizes = getimagesize(JPATH_SITE . '/' . $media['file_url']);

									if (empty($thumb) || $generate_image)
									{
										// Get the subfolder structure
										$thumb_path = str_ireplace($imgpath, '', $full_path);

										if (empty($this->file_url_thumb))
										{
											$thumb = 'resized/' . $thumb_path . basename($media['file_url']);
										}
									}
									else
									{
										// Check if we are not overwriting any large images
										$thumb_path_parts = pathinfo($thumb);

										if ($thumb_path_parts['dirname'] == '.')
										{
											$this->log->AddStats('incorrect', 'COM_CSVI_THUMB_OVERWRITE_FULL');
											$thumb = false;
										}
									}

									if ($thumb && ($thumb_sizes[0] < $max_width || $thumb_sizes[1] < $max_height))
									{
										$media['file_url_thumb'] = $imagehelper->createThumbnail($media['file_url'], $imgpath, $thumb);
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
								if ($this->template->get('full_watermark', 'image') && $file_details['isimage'])
								{
									$imagehelper->addWatermark(JPATH_SITE . '/' . $media['file_url']);
								}

								// Store the product image relation
								$data = array();
								$data['virtuemart_product_id'] = $this->getState('virtuemart_product_id');
								$data['virtuemart_media_id'] = $this->media->virtuemart_media_id;
								$data['ordering'] = (isset($order[$key]) && !empty($order[$key])) ? $order[$key] : $ordering;
								$this->productMedia->bind($data);

								$this->productMedia->check();

								if (!$this->productMedia->store())
								{
									$this->log->add('Can not store the product media relation', true);
									$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_MEDIA_NOT_ADDED', $this->media->getError()));
									$ordering++;
								}
							}
							else
							{
								$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_MEDIA_NOT_ADDED', $this->media->getError()));

								return false;
							}

							// Reset the product media table
							$this->media->reset();
							$this->productMedia->reset();
						} // else
					} // if
				} // if
			} // foreach
		} // if

		return true;
	}

	/**
	 * Create image name.
	 *
	 * Check if the user wants to have CSVI VirtueMart create the image names if so
	 * create the image names without path.
	 *
	 * @param   int  $ordering  The number to apply to a generated image name.
	 *
	 * @return  string  The name of the image.
	 *
	 * @since   3.0
	 */
	private function _createImageName($ordering = 0)
	{
		$this->log->add('COM_CSVI_GENERATE_IMAGE_NAME');

		// Create extension
		$ext = $this->template->get('autogenerateext');

		// Check if the user wants to convert the images to a different type
		switch ($this->template->get('type_generate_image_name'))
		{
			case 'product_sku':
				$this->log->add('COM_CSVI_CREATE_PRODUCT_SKU_NAME');

				if ($this->getState('product_sku', false))
				{
					$name = $this->getState('product_sku');
				}
				else
				{
					$this->log->addStats('error', 'COM_CSVI_CANNOT_FIND_PRODUCT_SKU');

					return false;
				}
				break;
			case 'product_name':
				$this->log->add('COM_CSVI_CREATE_PRODUCT_NAME_NAME');

				if (!is_null($this->productLang->product_name))
				{
					$name = $this->productLang->product_name;
				}
				else
				{
					$this->log->addStats('error', 'COM_CSVI_CANNOT_FIND_PRODUCT_NAME');

					return false;
				}
				break;
			case 'product_id':
				$this->log->add('COM_CSVI_CREATE_PRODUCT_ID_NAME');

				if ($this->getState('virtuemart_product_id', false))
				{
					$name = $this->getState('virtuemart_product_id');
				}
				else
				{
					$this->log->addStats('error', 'COM_CSVI_CANNOT_FIND_PRODUCT_ID');

					return false;
				}
				break;
			case 'random':
				$this->log->add('COM_CSVI_CREATE_RANDOM_NAME');
				$name = mt_rand();
				break;
			default:
				$this->log->addStats('error', 'COM_CSVI_CANNOT_FIND_PRODUCT_SKU');

				return false;
				break;
		}

		// Build the new name
		if ($ordering > 0)
		{
			$image_name = $name . '_' . $ordering . '.' . $ext;
		}
		else
		{
			$image_name = $name . '.' . $ext;
		}

		$this->log->add(JText::sprintf('COM_CSVI_CREATED_IMAGE_NAME', $image_name));
		$this->setState('product_full_image_output', $image_name);

		// Check if the user is supplying image data
		if (!$this->getState('file_url', false))
		{
			$this->setState('file_url', $this->getState('product_full_image_output'));
		}

		return $this->getState('file_url');
	}
}
