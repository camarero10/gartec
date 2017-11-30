<?php
/**
 * @package     CSVI
 * @subpackage  JoomlaContent
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

JLoader::register('JArrayHelper', JPATH_PLATFORM . '/joomla/utilities/arrayhelper.php');

/**
 * Content import.
 *
 * @package     CSVI
 * @subpackage  JoomlaContent
 * @since       6.0
 */
class Com_ContentModelImportContent extends RantaiImportEngine
{
	/**
	 * Content table.
	 *
	 * @var    ContentTableContent
	 * @since  6.0
	 */
	private $content = null;

	/**
	 * The Joomla content helper
	 *
	 * @var    Com_ContentHelperCom_Content
	 * @since  6.0
	 */
	protected $helper = null;

	/**
	 * Start the product import process.
	 *
	 * @return  bool  True on success | false on failure.
	 *
	 * @since   6.0
	 */
	public function getStart()
	{
		// Process data
		foreach ($this->fields->getData() as $fields)
		{
			foreach ($fields as $name => $details)
			{
				$value = $details->value;

				switch ($name)
				{
					case 'category_path':
						$this->setState('catid', $this->helper->getCategoryId($value));
						$this->setState($name, $value);
						break;
					default:
						$this->setState($name, $value);
						break;
				}
			}
		}

		// There must be an id or alias and catid or category_path
		if ($this->getState('id', false)
			|| ($this->getState('alias', false) && ($this->getState('catid', false) || !$this->getState('category_path', false))) )
		{
			$this->loaded = true;

			if (!$this->getState('id', false))
			{
				$this->setState('id', $this->helper->getContentId());
			}

			// Load the current content data
			if ($this->content->load($this->getState('id', 0)))
			{
				if (!$this->template->get('overwrite_existing_data'))
				{
					$this->log->add('Article ' . $this->getState('alias') . 'not updated because the option overwrite existing data is set to No');
					$this->loaded = false;
				}
			}
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
			if (!$this->getState('id', false) && $this->template->get('ignore_non_exist'))
			{
				// Do nothing for new products when user chooses to ignore new products
				$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_IGNORE_NEW', $this->getState('alias', '')));
			}
			else
			{
				// Check if we have a title
				if (empty($this->content->title))
				{
					$this->content->title = $this->getState('alias');
				}

				// Set the attributes
				$this->setAttributes();

				// Set the images
				$this->setImages();

				// Set the urls
				$this->setUrls();

				// Check for meta data
				$this->setMetadata();

				// Get the transliteration helper
				$translit = new CsviHelperTranslit($this->template);

				if ($this->getState('alias', false) || ($this->getState('id', false) == 0))
				{
					$this->setState('alias', $translit->stringURLSafe($this->getState('alias')));
				}

				// Data must be in an array
				$data = Joomla\Utilities\ArrayHelper::fromObject($this->state);

				// Bind the data
				if ($this->content->bind($data))
				{
					// Add a creating date if there is no product_id
					if (!$this->getState('id', false))
					{
						$this->content->created = $this->date->toSql();
						$this->content->created_by = $this->userId;
					}
					else
					{
						if (!$this->getState('modified_on', false))
						{
							$this->content->modified = $this->date->toSql();
							$this->content->modified_by = $this->userId;
						}
					}

					if ($this->content->check())
					{
						$this->content->store();
					}
					else
					{
						$errors = $this->content->getErrors();

						foreach ($errors as $error)
						{
							$this->log->add($error);
							$this->log->addStats('incorrect', $error);
						}
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
		$this->content = $this->getTable('Content');
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
		$this->content->reset();
	}

	/**
	 * Set the attributes field.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function setAttributes()
	{
		// Check for attributes
		if (!$this->getState('attribs', false))
		{
			$attributeFields = array
			(
				'show_title',
				'link_titles',
				'show_intro',
				'show_category',
				'link_category',
				'show_parent_category',
				'link_parent_category',
				'show_author',
				'link_author',
				'show_create_date',
				'show_modify_date',
				'show_publish_date',
				'show_item_navigation',
				'show_icons',
				'show_print_icon',
				'show_email_icon',
				'show_vote',
				'show_hits',
				'show_noauth',
				'urls_position',
				'alternative_readmore',
				'article_layout',
				'show_publishing_options',
				'show_article_options',
				'show_urls_images_backend',
				'show_urls_images_frontend'
			);

			// Check if content plugin exists
			if ($this->helper->pluginExists('plg_content_swmap'))
			{
				$pluginFields = array
				(
					'address',
					'latitude',
					'lognitude',
					'type',
					'icon',
					'theicon',
					'zoom',
					'panoramioitem',
					'adsenseitem',
					'publisher',
					'formatitem',
					'positionitem'
				);

				$attributeFields = array_merge($attributeFields, $pluginFields);
			}

			// Load the current attributes
			$attributes = json_decode($this->content->attribs);

			if (!is_object($attributes))
			{
				$attributes = new stdClass;
			}

			foreach ($attributeFields as $field)
			{
				if (!$this->getState($field, false))
				{
					if ($this->$field == '*')
					{
						$attributes->$field = '';
					}
					else
					{
						$attributes->$field = $this->getState($field, '');
					}
				}
			}

			// Store the new attributes
			$this->setState('attribs', json_encode($attributes));
		}
	}

	/**
	 * Set the images.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function setImages()
	{
		if (!$this->getState('images'))
		{
			$imageFields = array
			(
				'image_intro',
				'float_intro',
				'image_intro_alt',
				'image_intro_caption',
				'image_fulltext',
				'float_fulltext',
				'image_fulltext_alt',
				'image_fulltext_caption'
			);

			// Load the current images
			$images = json_decode($this->content->images);

			if (!is_object($images))
			{
				$images = new stdClass;
			}

			foreach ($imageFields as $field)
			{
				$images->$field = $this->getState($field, '');
			}

			// Store the new attributes
			$this->setState('images', json_encode($images));
		}
	}

	/**
	 * Set the urls.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function setUrls()
	{
		if (!$this->getState('urls'))
		{
			$urlFields = array
			(
				'urla',
				'urlatext',
				'targeta',
				'urlb',
				'urlbtext',
				'targetb',
				'urlc',
				'urlctext',
				'targetc',
			);

			// Load the current images
			$urls = json_decode($this->content->urls);

			if (!is_object($urls))
			{
				$urls = new stdClass;
			}

			foreach ($urlFields as $field)
			{
				$urls->$field = $this->getState($field, '');
			}

			// Store the new attributes
			$this->setState('urls', json_encode($urls));
		}
	}

	/**
	 * Set the meta data.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function setMetadata()
	{
		if (!$this->getState('metadata', false))
		{
			$metadataFields = array
			(
				'meta_robots',
				'meta_author',
				'meta_rights',
				'meta_xreference'
			);

			// Load the current attributes
			$metadata = json_decode($this->content->metadata);

			if (!is_object($metadata))
			{
				$metadata = new stdClass;
			}

			foreach ($metadataFields as $field)
			{
				$newField = str_ireplace('meta_', '', $field);

				if ($this->getState($field, false))
				{
					if ($this->getState($field, '') == '*')
					{
						$metadata->$field = '';
					}
					else
					{
						$metadata->$newField = $this->getState($field, '');
					}
				}
				elseif (!isset($metadata->$newField))
				{
					$metadata->$newField = '';
				}
			}

			// Store the new attributes
			$this->setState('metadata', json_encode($metadata));
		}
	}
}
