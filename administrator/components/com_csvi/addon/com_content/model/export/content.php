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

require_once JPATH_ADMINISTRATOR . '/components/com_csvi/models/exports.php';

/**
 * Export Joomla articles.
 *
 * @package     CSVI
 * @subpackage  JoomlaContent
 * @since       6.0
 */
class Com_ContentModelExportContent extends CsviModelExports
{
	/**
	 * Export the data.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function exportBody()
	{
		if (parent::exportBody())
		{
			// Get some basic data
			require_once JPATH_SITE . '/components/com_content/helpers/route.php';
			$sef = new CsviHelperSef($this->settings, $this->template, $this->log);

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
					case 'category_path':
						$userfields[] = $this->db->quoteName('c.catid');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('c.catid');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('c.catid');
						}
						break;
					case 'article_url':
						$userfields[] = $this->db->quoteName('c.id');
						$userfields[] = $this->db->quoteName('cat.id', 'catid');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('c.id');
							$groupby[] = $this->db->quoteName('cat.id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('c.id');
							$sortby[] = $this->db->quoteName('cat.id');
						}
						break;
					case 'access':
					case 'alias':
					case 'asset_id':
					case 'checked_out':
					case 'checked_out_time':
					case 'created_by':
					case 'created_by_alias':
					case 'hits':
					case 'id':
					case 'language':
					case 'metadata':
					case 'metadesc':
					case 'metakey':
					case 'title':
					case 'version':
						$userfields[] = $this->db->quoteName('c.' . $field->field_name);

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('c.' . $field->field_name);
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('c.' . $field->field_name);
						}
					break;
					case 'show_title':
					case 'link_titles':
					case 'show_intro':
					case 'show_category':
					case 'link_category':
					case 'show_parent_category':
					case 'link_parent_category':
					case 'show_author':
					case 'link_author':
					case 'show_create_date':
					case 'show_modify_date':
					case 'show_publish_date':
					case 'show_item_navigation':
					case 'show_icons':
					case 'show_print_icon':
					case 'show_email_icon':
					case 'show_vote':
					case 'show_hits':
					case 'show_noauth':
					case 'urls_position':
					case 'alternative_readmore':
					case 'article_layout':
					case 'show_publishing_options':
					case 'show_article_options':
					case 'show_urls_images_backend':
					case 'show_urls_images_frontend':
					case 'address':
					case 'latitude':
					case 'lognitude':
					case 'type':
					case 'icon':
					case 'theicon':
					case 'zoom':
					case 'panoramioitem':
					case 'adsenseitem':
					case 'publisher':
					case 'formatitem':
					case 'positionitem':
						$userfields[] = $this->db->quoteName('c.attribs');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('c.attribs');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('c.attribs');
						}
					break;
					case 'image_intro':
					case 'float_intro':
					case 'image_intro_alt':
					case 'image_intro_caption':
					case 'image_fulltext':
					case 'float_fulltext':
					case 'image_fulltext_alt':
					case 'image_fulltext_caption':
						$userfields[] = $this->db->quoteName('c.images');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('c.images');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('c.images');
						}
					break;
					case 'urla':
					case 'urlatext':
					case 'targeta':
					case 'urlb':
					case 'urlbtext':
					case 'targetb':
					case 'urlc':
					case 'urlctext':
					case 'targetc':
						$userfields[] = $this->db->quoteName('c.urls');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('c.urls');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('c.urls');
						}
					break;
					case 'custom':
						break;
					default:
						$userfields[] = $this->db->quoteName($field->field_name);

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName($field->field_name);
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName($field->field_name);
						}
						break;
				}
			}

			// Build the query
			$userfields = array_unique($userfields);
			$query = $this->db->getQuery(true);
			$query->select(implode(",\n", $userfields));
			$query->from($this->db->quoteName('#__content', 'c'));
			$query->leftJoin($this->db->quoteName('#__categories', 'cat') . ' ON ' . $this->db->quoteName('cat.id') . ' = ' . $this->db->quoteName('c.catid'));

			// Filter by published state
			$publish_state = $this->template->get('publish_state');

			if ($publish_state != '' && ($publish_state == 1 || $publish_state == 0))
			{
				$query->where($this->db->quoteName('c.state') . ' = ' . (int) $publish_state);
			}

			// Filter by language
			$language = $this->template->get('content_language');

			if ($language != '*')
			{
				$query->where($this->db->quoteName('c.language') . ' = ' . $this->db->quote($language));
			}

			// Filter by category
			$categories = $this->template->get('content_categories');

			if ($categories && $categories[0] != '*')
			{
				$query->where($this->db->quoteName('catid') . " IN ('" . implode("','", $categories) . "')");
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

					// Clean some settings
					$attribs = '';
					$images = '';
					$urls = '';

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
							case 'category_path':
								$query->clear()
									->select($this->db->quoteName('path'))
									->from($this->db->quoteName('#__categories'))
									->where($this->db->quoteName('id') . ' = ' . (int) $record->catid);
								$this->db->setQuery($query);
								$fieldvalue = $this->db->loadResult();
								break;
							case 'article_url':
								// Let's create a SEF URL
								$fieldvalue = $sef->getSEF(ContentHelperRoute::getArticleRoute($record->id, $record->catid));
								break;
							case 'show_title':
							case 'link_titles':
							case 'show_intro':
							case 'show_category':
							case 'link_category':
							case 'show_parent_category':
							case 'link_parent_category':
							case 'show_author':
							case 'link_author':
							case 'show_create_date':
							case 'show_modify_date':
							case 'show_publish_date':
							case 'show_item_navigation':
							case 'show_icons':
							case 'show_print_icon':
							case 'show_email_icon':
							case 'show_vote':
							case 'show_hits':
							case 'show_noauth':
							case 'urls_position':
							case 'alternative_readmore':
							case 'article_layout':
							case 'show_publishing_options':
							case 'show_article_options':
							case 'show_urls_images_backend':
							case 'show_urls_images_frontend':
							case 'address':
							case 'latitude':
							case 'lognitude':
							case 'type':
							case 'icon':
							case 'theicon':
							case 'zoom':
							case 'panoramioitem':
							case 'adsenseitem':
							case 'publisher':
							case 'formatitem':
							case 'positionitem':
								if (empty($attribs))
								{
									$attribs = json_decode($record->attribs);
								}

								if (isset($attribs->$fieldname))
								{
									$fieldvalue = $attribs->$fieldname;
								}
								break;
							case 'image_intro':
							case 'float_intro':
							case 'image_intro_alt':
							case 'image_intro_caption':
							case 'image_fulltext':
							case 'float_fulltext':
							case 'image_fulltext_alt':
							case 'image_fulltext_caption':
								if (empty($images))
								{
									$images = json_decode($record->images);
								}

								if (isset($images->$fieldname))
								{
									$fieldvalue = $images->$fieldname;
								}
								break;
							case 'urla':
							case 'urlatext':
							case 'targeta':
							case 'urlb':
							case 'urlbtext':
							case 'targetb':
							case 'urlc':
							case 'urlctext':
							case 'targetc':
								if (empty($urls))
								{
									$urls = json_decode($record->urls);
								}

								if (isset($urls->$fieldname))
								{
									$fieldvalue = $urls->$fieldname;
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
}
