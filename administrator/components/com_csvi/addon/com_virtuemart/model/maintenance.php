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
 * VirtueMart maintenance.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartMaintenance
{
	/**
	 * Database connector
	 *
	 * @var    JDatabaseDriver
	 * @since  6.0
	 */
	private $db = null;

	/**
	 * Logger helper
	 *
	 * @var    CsviHelperLog
	 * @since  6.0
	 */
	private $log = null;

	/**
	 * CSVI Helper.
	 *
	 * @var    CsviHelperCsvi
	 * @since  6.0
	 */
	private $csvihelper = null;

	/**
	 * The categories in the system
	 *
	 * @var    array
	 * @since  3.0
	 */
	private $categories = array();

	/**
	 * A list of levels deep per category
	 *
	 * @var    array
	 * @since  3.0
	 */
	private $catlevels = array();

	/**
	 * A list of subcategories per category
	 *
	 * @var    array
	 * @since  3.0
	 */
	private $catpaths = array();

	/**
	 * Constructor.
	 *
	 * @param   JDatabase       $db          The database class
	 * @param   CsviHelperLog   $log         The CSVI logger
	 * @param   CsviHelperCsvi  $csvihelper  The CSVI helper
	 *
	 * @since   6.0
	 */
	public function __construct($db, $log, $csvihelper)
	{
		$this->db = $db;
		$this->log = $log;
		$this->csvihelper = $csvihelper;
	}

	/**
	 * Load a number of maintenance tasks.
	 *
	 * @return  array  List of available operations.
	 *
	 * @since   6.0
	 */
	public function getOperations()
	{
		return array('options' => array(
						'' => JText::_('COM_CSVI_MAKE_CHOICE'),
						'sortcategories' => JText::_('COM_CSVI_SORTCATEGORIES_LABEL'),
						'removeemptycategories' => JText::_('COM_CSVI_REMOVEEMPTYCATEGORIES_LABEL'),
						'removeproductprices' => JText::_('COM_CSVI_REMOVEPRODUCTPRICES_LABEL'),
						'unpublishproductbycategory' => JText::_('COM_CSVI_UNPUBLISHPRODUCTBYCATEGORY_LABEL'),
						'removeproductmedialink' => JText::_('COM_CSVI_REMOVEPRODUCTMEDIALINK_LABEL'),
						'backupvm' => JText::_('COM_CSVI_BACKUPVM_LABEL'),
						'emptydatabase' => JText::_('COM_CSVI_EMPTYDATABASE_LABEL'),
						'vmexchangerates' => JText::_('COM_CSVI_VMEXCHANGERATES_LABEL'),
						'ecbexchangerates' => JText::_('COM_CSVI_ECBEXCHANGERATES_LABEL')
				)
		);
	}

	/**
	 * Load the options for a selected operation.
	 *
	 * @param   string  $operation  The operation to get the options for
	 *
	 * @return  string  The options for a selected operation.
	 *
	 * @since   6.0
	 */
	public function getOptions($operation)
	{
		switch ($operation)
		{
			case 'sortcategories':
				$language = JFactory::getLanguage();
				$known = $language->getKnownLanguages();
				$options = array();

				foreach ($known as $lang)
				{
					$options[] = JHtml::_('select.option', str_replace('-', '_', strtolower($lang['tag'])), $lang['name']);
				}

				return '<div class="control-group ">
							<div class="control-label">
								<label title="" class="hasTooltip" for="jform_title" id="jform_title-lbl" data-original-title=" ' . JText::_('COM_CSVI_LANGUAGE_DESC') . '">
									' . JText::_('COM_CSVI_SORTCATEGORIES_LABEL') . '
								</label>
							</div>
							<div class="controls">
								' . JHtml::_('select.genericlist', $options, 'form[language]') . '
							</div>
						</div>';
				break;
			case 'removeemptycategories':
				$layout = new JLayoutFile('csvi.modal');
				return $layout->render(
					array(
						'modal-header' => JText::_('COM_CSVI_' . $operation . '_LABEL'),
						'modal-body' => JText::_('COM_CSVI_CONFIRM_CATEGORY_DELETE'),
						'cancel-button' => true
					));
				break;
			case 'emptydatabase':
				$layout = new JLayoutFile('csvi.modal');
				$html = '<span class="help-block">' . JText::_('COM_CSVI_' . $operation . '_DESC') . '</span>';
				$html .= $layout->render(
					array(
						'modal-header' => JText::_('COM_CSVI_' . $operation . '_LABEL'),
						'modal-body' => JText::_('COM_CSVI_CONFIRM_DB_DELETE'),
						'cancel-button' => true
					));
				return $html;
				break;
			default:
				return '<span class="help-block">' . JText::_('COM_CSVI_' . $operation . '_DESC') . '</span>';
				break;
		}
	}

	/**
	 * Sorts all VirtueMart categories in alphabetical order.
	 *
	 * @return  bool  True if categories are sorted | False if an error occured.
	 *
	 * @since   3.0
	 */
	public function sortCategories(FOFInput $input)
	{
		$jinput = JFactory::getApplication()->input;
		$linenumber = 1;

		// Load the form values
		$language = $input->get('language');

		// Check if the table exists
		$tables = $this->db->getTableList();

		if (!in_array($this->db->getPrefix() . 'virtuemart_categories_' . $language, $tables))
		{
			$this->log->addStats('information', JText::sprintf('COM_CSVI_LANG_TABLE_NOT_EXIST', $language));
		}
		else
		{
			// Get all categories
			$query = $this->db->getQuery(true);
			$query->select('LOWER(' . $this->db->quoteName('category_name') . ') AS ' . $this->db->quoteName('category_name'));
			$query->select($this->db->quoteName('category_child_id', 'cid'));
			$query->select($this->db->quoteName('category_parent_id', 'pid'));
			$query->from($this->db->quoteName('#__virtuemart_categories', 'c'));
			$query->leftJoin(
				$this->db->quoteName('#__virtuemart_category_categories', 'cc')
				. ' ON ' . $this->db->quoteName('c.virtuemart_category_id') . ' = ' . $this->db->quoteName('cc.category_child_id')
			);
			$query->leftJoin(
				$this->db->quoteName('#__virtuemart_categories_' . $language, 'cl')
				. ' ON ' . $this->db->quoteName('cc.category_child_id') . ' = ' . $this->db->quoteName('cl.virtuemart_category_id')
			);

			// Execute the query
			$this->db->setQuery($query);
			$records = $this->db->loadObjectList();

			if (count($records) > 0)
			{
				$categories = array();

				// Group all categories together according to their level
				foreach ($records as $record)
				{
					$categories[$record->pid][$record->cid] = $record->category_name;
				}

				// Sort the categories and store the item list
				foreach ($categories as $category)
				{
					asort($category);
					$listorder = 1;

					foreach ($category as $category_id => $category_name)
					{
						// Store the new sort order
						$query = $this->db->getQuery(true);
						$query->update($this->db->quoteName('#__virtuemart_categories'));
						$query->set($this->db->quoteName('ordering') . ' = ' . $this->db->quote($listorder));
						$query->where($this->db->quoteName('virtuemart_category_id') . ' = ' . (int) $category_id);
						$this->db->setQuery($query);
						$this->db->execute();

						// Set the line number
						$this->log->setLinenumber($linenumber++);
						$this->log->addStats('information', JText::sprintf('COM_CSVI_SAVED_CATEGORY', $category_name, $listorder));
						$listorder++;
					}
				}
				// Store the log count
				$linenumber--;
				$jinput->set('logcount', $linenumber);
			}
			else
			{
				$this->log->addStats('information', 'COM_CSVI_NO_CATEGORIES_FOUND');
			}
		}

		return true;
	}

	/**
	 * Remove all categories that have no products
	 * Parent categories are only deleted if there are no more children left.
	 *
	 * @return  bool  Always returns true.
	 *
	 * @since   3.0
	 */
	public function removeEmptyCategories()
	{
		$this->getCategoryTreeModule();
		arsort($this->catlevels);

		foreach ($this->catlevels as $catid => $nrlevels)
		{
			// Check if there are any products in the category
			$this->db->setQuery($this->getCatQuery($catid));

			if ($this->db->loadResult() > 0 && array_key_exists($catid, $this->catpaths))
			{
				foreach ($this->catpaths[$catid] as $level)
				{
					unset($this->catpaths[$level]);
					unset($this->catlevels[$level]);
				}

				unset($this->catpaths[$catid]);
				unset($this->catlevels[$catid]);
			}
			else
			{
				if (array_key_exists($catid, $this->catpaths))
				{
					foreach ($this->catpaths[$catid] as $level)
					{
						$this->db->setQuery($this->getCatQuery($level));

						if ($this->db->loadResult() > 0)
						{
							unset($this->catpaths[$level]);
							unset($this->catlevels[$level]);
						}
					}
				}
			}
		}

		$delcats = array_keys($this->catpaths);

		if (!empty($delcats))
		{
			// Remove all categories except the ones we have
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName('#__virtuemart_categories'))
				->where($this->db->quoteName('virtuemart_category_id') . ' IN (' . implode(', ', $delcats) . ')');
			$this->db->setQuery($query);

			if ($this->db->execute())
			{
				$this->log->addStats('deleted', 'COM_CSVI_MAINTENANCE_CATEGORIES_DELETED');
			}
			else
			{
				$this->log->addStats('incorrect', 'COM_CSVI_MAINTENANCE_CATEGORIES_NOT_DELETED');
			}

			// Remove all category parent-child relations except the ones we have
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName('#__virtuemart_category_categories'))
				->where($this->db->quoteName('category_child_id') . ' IN (' . implode(', ', $delcats) . ')');
			$this->db->setQuery($query);

			if ($this->db->execute())
			{
				$this->log->addStats('deleted', 'COM_CSVI_MAINTENANCE_CATEGORIES_XREF_DELETED');
			}
			else
			{
				$this->log->addStats('incorrect', 'COM_CSVI_MAINTENANCE_CATEGORIES_XREF_NOT_DELETED');
			}

			// Delete category translations
			jimport('joomla.language.helper');
			$languages = array_keys(JLanguageHelper::getLanguages('lang_code'));

			foreach ($languages as $language)
			{
				$query = $this->db->getQuery(true)
					->delete($this->db->quoteName('#__virtuemart_categories_' . strtolower(str_replace('-', '_', $language))))
					->where($this->db->quoteName('virtuemart_category_id') . ' IN (' . implode(', ', $delcats) . ')');
				$this->db->setQuery($query);
				$this->log->add(JText::_('COM_CSVI_DEBUG_DELETE_CATEGORY_LANG_XREF'), true);
				$this->db->execute();
			}

			// Delete media
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName('#__virtuemart_category_medias'))
				->where($this->db->quoteName('virtuemart_category_id') . ' IN (' . implode(', ', $delcats) . ')');
			$this->db->setQuery($query);
			$this->log->add(JText::_('COM_CSVI_DEBUG_DELETE_MEDIA_XREF'), true);
			$this->db->execute();
		}
		else
		{
			$this->log->addStats('information', 'COM_CSVI_NO_CATEGORIES_FOUND');
		}

		return true;
	}

	/**
	 * This function is repsonsible for returning an array containing category information.
	 *
	 * @return  bool  False if no categories are found.
	 *
	 * @since   2.3.6
	 */
	private function getCategoryTreeModule()
	{
		$query = $this->db->getQuery(true);

		// Get all categories
		$query->select($this->db->quoteName('category_child_id', 'cid') . ',' . $this->db->quoteName('category_parent_id', 'pid'))
			->from($this->db->quoteName('#__virtuemart_categories', 'c'))
			->leftJoin(
				$this->db->quoteName('#__virtuemart_category_categories', 'x')
				. ' ON ' . $this->db->quoteName('c.virtuemart_category_id') . ' = ' . $this->db->quoteName('x.category_child_id')
			);

		// Execute the query
		$this->db->setQuery($query);
		$records = $this->db->loadObjectList();

		// Check if there are any records
		if (count($records) == 0)
		{
			$this->categories = false;

			return false;
		}
		else
		{
			$this->categories = array();

			// Group all categories together according to their level
			foreach ($records as $record)
			{
				$this->categories[$record->pid][$record->cid]["category_id"] = $record->pid;
				$this->categories[$record->pid][$record->cid]["category_child_id"] = $record->cid;
			}
		}

		$catpath = array();
		krsort($this->categories);

		foreach ($this->categories as $pid => $categories)
		{
			foreach ($categories as $cid => $category)
			{
				$catpath[$cid] = $pid;
			}

			// Free up memory
			unset($this->categories[$pid]);
		}

		// Clean up unused array
		unset($this->categories);

		foreach ($catpath as $cid => $value)
		{
			$catlevel = $value;
			$this->catpaths[$cid][] = $catlevel;

			while ($catlevel > 0)
			{
				$this->catpaths[$cid][] = $catpath[$catlevel];
				$catlevel = $catpath[$catlevel];
			}

			// Clean up for some memory
			unset($catpath[$cid]);
		}

		// Clean up unused array
		unset($catpath);

		foreach ($this->catpaths as $cid => $paths)
		{
			$this->catlevels[$cid] = count($paths);
		}

		return true;
	}

	/**
	 * Construct a query to count the number of references to a category.
	 *
	 * @param   int  $catid  The ID of the category to filter on
	 *
	 * @return  JDatabaseQuery  A JDatabaseQuery object.
	 *
	 * @since   3.0
	 */
	private function getCatQuery($catid)
	{
		$query = $this->db->getQuery(true)
			->select('COUNT(*)')
			->from($this->db->quoteName('#__virtuemart_product_categories'))
			->where($this->db->quoteName('virtuemart_category_id') . ' = ' . (int) $catid);

		return $query;
	}

	/**
	 * Remove all product prices.
	 *
	 * @return  bool  Always returns true.
	 *
	 * @since   5.9.5
	 */
	public function removeProductPrices()
	{
		$this->db->truncateTable('#__virtuemart_product_prices');
		$this->log->setLineNumber(1);
		$this->log->addStats('empty', JText::_('COM_CSVI_VM_PRICES_EMPTIED'));

		return true;
	}

	/**
	 * Unpublish products in unpublished categories.
	 *
	 * @return  bool  Always returns true.
	 *
	 * @since   3.5
	 */
	public function unpublishProductByCategory()
	{
		$this->log->setLineNumber(1);

		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('p.virtuemart_product_id'))
			->from($this->db->quoteName('#__virtuemart_products', 'p'))
			->innerJoin(
				$this->db->quoteName('#__virtuemart_product_categories', 'pc')
				. ' ON ' . $this->db->quoteName('p.virtuemart_product_id') . ' = ' . $this->db->quoteName('pc.virtuemart_product_id')
			)
			->innerJoin(
				$this->db->quoteName('#__virtuemart_categories', 'c')
				. ' ON ' . $this->db->quoteName('pc.virtuemart_category_id') . ' = ' . $this->db->quoteName('c.virtuemart_category_id')
			)
			->where($this->db->quoteName('p.published') . ' = 1')
			->where($this->db->quoteName('c.published') . ' = 0');

		$this->db->setQuery($query);
		$ids = $this->db->loadColumn();

		if (!empty($ids))
		{
			// Unpublish the IDs
			$query = $this->db->getQuery(true)
				->update($this->db->quoteName('#__virtuemart_products'))
				->set($this->db->quoteName('published') . ' = 0')
				->where($this->db->quoteName('virtuemart_product_id') . ' IN (' . implode(',', $ids) . ')');
			$this->db->setQuery($query);

			if ($this->db->execute())
			{
				//$jinput->set('linesprocessed', $this->db->getAffectedRows());
				$this->log->addStats('updated', JText::sprintf('COM_CSVI_PRODUCTS_UNPUBLISHED', $this->db->getAffectedRows()));
			}
			else
			{
				$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_PRODUCTS_NOT_UNPUBLISHED', $this->db->getErrorMsg()));
			}
		}
		else
		{
			$this->log->addStats('information', 'COM_CSVI_PRODUCTS_NOT_FOUND');
		}

		return true;
	}

	/**
	 * Remove any links between products and media items.
	 *
	 * @return  bool  Always returns true.
	 *
	 * @since   3.0
	 */
	public function removeProductMediaLink()
	{
		$this->log->setLineNumber(1);
		$this->db->truncateTable('#__virtuemart_product_medias');
		$this->log->addStats('information', JText::_('COM_CSVI_PRODUCT_MEDIA_LINK_REMOVED'));

		return true;
	}

	/**
	 * Export all VirtueMart tables.
	 *
	 * @return  bool  Always returns true.
	 *
	 * @since   3.0
	 */
	public function backupVm()
	{
		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.folder');
		$jinput = JFactory::getApplication()->input;

		$filepath = JPATH_SITE . '/tmp/com_csvi';
		$filename = 'virtuemart_' . time() . '.sql';
		$file = $filepath . '/' . $filename;
		$sqlstring = '';
		$fp = fopen($file, "w+");

		if ($fp)
		{
			// Load a list of VirtueMart tables
			$q = "SHOW TABLES LIKE '" . $this->db->getPrefix() . "virtuemart\_%'";
			$this->db->setQuery($q);
			$tables = $this->db->loadColumn();
			$linenumber = 1;

			foreach ($tables as $table)
			{
				$this->log->setLinenumber($linenumber);

				// Get the create table statement
				$q = "SHOW CREATE TABLE " . $table;
				$this->db->setQuery($q);
				$tcreate = $this->db->loadAssocList();
				$sqlstring .= "-- Table structure for table " . $this->db->quoteName($table) . "\n\n";
				$sqlstring .= $tcreate[0]['Create Table'] . ";\n\n";

				// Check if there is any data in the table
				$q = "SELECT COUNT(*) FROM " . $this->db->quoteName($table);
				$this->db->setQuery($q);
				$count = $this->db->loadResult();

				if ($count > 0)
				{
					$sqlstring .= "-- Data for table " . $this->db->quoteName($table) . "\n\n";

					// Get the field names
					$q = "SHOW COLUMNS FROM " . $this->db->quoteName($table);
					$this->db->setQuery($q);
					$fields = $this->db->loadObjectList();
					$sqlstring .= 'INSERT INTO ' . $this->db->quoteName($table) . ' (';

					foreach ($fields as $field)
					{
						$sqlstring .= $this->db->quoteName($field->Field) . ',';
					}

					$sqlstring = substr(trim($sqlstring), 0, -1) . ") VALUES \n";
					$start = 0;

					while ($count > 0)
					{
						$q = "SELECT * FROM " . $table . " LIMIT " . $start . ", 50";
						$this->db->setQuery($q);
						$records = $this->db->loadAssocList();

						// Add the values
						foreach ($records as $record)
						{
							foreach ($record as $rkey => $value)
							{
								if (!is_numeric($value))
								{
									$record[$rkey] = $this->db->quote($value);
								}
								else
								{
									$record[$rkey] = $value;
								}
							}

							$sqlstring .= '(' . implode(',', $record) . "),\n";
						}

						$start += 50;
						$count -= 50;

						// Fix the end of the query
						if ($count < 1)
						{
							$sqlstring = substr(trim($sqlstring), 0, -1) . ";\n";
						}

						// Add a linebreak
						$sqlstring .= "\n\n";

						// Write the data to the file
						fwrite($fp, $sqlstring);

						// Empty the string
						$sqlstring = '';
					}

					// Update the log
					$this->log->addStats('added', JText::sprintf('COM_CSVI_BACKUP_COMPLETE_FOR', $table));
					$linenumber++;
				}
			}

			// Store the log count
			$linenumber--;
			$jinput->set('logcount', $linenumber);

			// Zip up the file
			jimport('joomla.filesystem.archive');
			$zip = JArchive::getAdapter('zip');
			$files = array();
			$files[] = array('name' => $filename, 'time' => filemtime($file), 'data' => JFile::read($file));

			if ($zip->create($filepath . '/' . $filename . '.zip', $files))
			{
				// Close the file
				fclose($fp);

				// Remove the SQL file
				JFile::delete($file);

				// Add a download link for the backup
				$this->log->setFilename(JHtml::link(JURI::root() . 'tmp/com_csvi/' . $filename . '.zip', JText::_('COM_CSVI_BACKUP_DOWNLOAD_LINK')));
			}
			else
			{
				$this->log->addStats('incorrect', 'COM_CSVI_BACKUP_NO_ZIP_CREATE');
				$this->log->setFilename($filepath . '/' . $filename);
			}
		}
		else
		{
			$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_COULD_NOT_OPEN_FILE', $file));
		}

		return true;
	}

	/**
	 * Empty VirtueMart tables.
	 *
	 * @return  bool  Always returns true.
	 *
	 * @since   3.0
	 */
	public function emptyDatabase()
	{
		$linenumber = 1;

		jimport('joomla.language.helper');
		$languages = array_keys(JLanguageHelper::getLanguages('lang_code'));
		$tables = $this->db->getTableList();

		// Empty all the necessary tables
		$this->log->setLinenumber($linenumber++);
		$q = 'TRUNCATE TABLE ' . $this->db->quoteName('#__virtuemart_products');
		$this->db->setQuery($q);
		$this->log->add('Empty product table', true);

		if ($this->db->execute())
		{
			$this->log->addStats('empty', 'COM_CSVI_PRODUCT_TABLE_HAS_BEEN_EMPTIED');
		}
		else
		{
			$this->log->addStats('incorrect', 'COM_CSVI_PRODUCT_TABLE_HAS_NOT_BEEN_EMPTIED');
		}

		foreach ($languages as $language)
		{
			$table = $this->db->getPrefix() . 'virtuemart_products_' . strtolower(str_replace('-', '_', $language));

			if (in_array($table, $tables))
			{
				$q = 'TRUNCATE TABLE ' . $this->db->quoteName($table) . ';';
				$this->db->setQuery($q);
				$this->log->add('Empty product language table', true);

				if ($this->db->execute())
				{
					$this->log->addStats('empty', JText::sprintf('COM_CSVI_PRODUCT_LANGUAGE_TABLE_HAS_BEEN_EMPTIED', $language));
				}
				else
				{
					$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_PRODUCT_LANGUAGE_TABLE_HAS_NOT_BEEN_EMPTIED', $language));
				}
			}
		}

		$this->log->setLinenumber($linenumber++);
		$q = 'TRUNCATE TABLE ' . $this->db->quoteName('#__virtuemart_product_categories');
		$this->db->setQuery($q);
		$this->log->add('Empty product category link table', true);

		if ($this->db->execute())
		{
			$this->log->addStats('empty', 'COM_CSVI_PRODUCT_CATEGORY_LINK_TABLE_HAS_BEEN_EMPTIED');
		}
		else
		{
			$this->log->addStats('incorrect', 'COM_CSVI_PRODUCT_CATEGORY_LINK_TABLE_HAS_NOT_BEEN_EMPTIED');
		}

		$this->log->setLinenumber($linenumber++);
		$q = 'TRUNCATE TABLE ' . $this->db->quoteName('#__virtuemart_product_customfields');
		$this->db->setQuery($q);
		$this->log->add('Empty product custom fields table', true);

		if ($this->db->execute())
		{
			$this->log->addStats('empty', 'COM_CSVI_PRODUCT_CUSTOMFIELDS_TABLE_HAS_BEEN_EMPTIED');
		}
		else
		{
			$this->log->addStats('incorrect', 'COM_CSVI_PRODUCT_CUSTOMFIELDS_TABLE_HAS_NOT_BEEN_EMPTIED');
		}

		$this->log->setLinenumber($linenumber++);
		$q = 'TRUNCATE TABLE ' . $this->db->quoteName('#__virtuemart_product_manufacturers');
		$this->db->setQuery($q);
		$this->log->add('Empty product manufacturer link table', true);

		if ($this->db->execute())
		{
			$this->log->addStats('empty', 'COM_CSVI_PRODUCT_MANUFACTURER_LINK_TABLE_HAS_BEEN_EMPTIED');
		}
		else
		{
			$this->log->addStats('incorrect', 'COM_CSVI_PRODUCT_MANUFACTURER_LINK_TABLE_HAS_NOT_BEEN_EMPTIED');
		}

		$this->log->setLinenumber($linenumber++);
		$q = 'TRUNCATE TABLE ' . $this->db->quoteName('#__virtuemart_product_medias');
		$this->db->setQuery($q);
		$this->log->add('Empty product medias table', true);

		if ($this->db->execute())
		{
			$this->log->addStats('empty', 'COM_CSVI_PRODUCT_MEDIAS_TABLE_HAS_BEEN_EMPTIED');
		}
		else
		{
			$this->log->addStats('incorrect', 'COM_CSVI_PRODUCT_MEDIAS_TABLE_HAS_NOT_BEEN_EMPTIED');
		}

		$this->log->setLinenumber($linenumber++);
		$q = 'TRUNCATE TABLE ' . $this->db->quoteName('#__virtuemart_product_prices');
		$this->db->setQuery($q);
		$this->log->add('Empty product price table', true);

		if ($this->db->execute())
		{
			$this->log->addStats('empty', 'COM_CSVI_PRODUCT_PRICE_TABLE_HAS_BEEN_EMPTIED');
		}
		else
		{
			$this->log->addStats('incorrect', 'COM_CSVI_PRODUCT_PRICE_TABLE_HAS_NOT_BEEN_EMPTIED');
		}

		$this->log->setLinenumber($linenumber++);
		$q = 'TRUNCATE TABLE ' . $this->db->quoteName('#__virtuemart_product_shoppergroups');
		$this->db->setQuery($q);
		$this->log->add('Empty product shoppergroups table', true);

		if ($this->db->execute())
		{
			$this->log->addStats('empty', 'COM_CSVI_PRODUCT_SHOPPERGROUPS_TABLE_HAS_BEEN_EMPTIED');
		}
		else
		{
			$this->log->addStats('incorrect', 'COM_CSVI_PRODUCT_SHOPPERGROUPS_TABLE_HAS_NOT_BEEN_EMPTIED');
		}

		$this->log->setLinenumber($linenumber++);
		$q = 'TRUNCATE TABLE ' . $this->db->quoteName('#__virtuemart_categories');
		$this->db->setQuery($q);
		$this->log->add('Empty category table', true);

		if ($this->db->execute())
		{
			$this->log->addStats('empty', 'COM_CSVI_CATEGORY_TABLE_HAS_BEEN_EMPTIED');
		}
		else
		{
			$this->log->addStats('incorrect', 'COM_CSVI_CATEGORY_TABLE_HAS_NOT_BEEN_EMPTIED');
		}

		foreach ($languages as $language)
		{
			$table = $this->db->getPrefix() . 'virtuemart_categories_' . strtolower(str_replace('-', '_', $language));

			if (in_array($table, $tables))
			{
				$q = 'TRUNCATE TABLE ' . $this->db->quoteName($table) . ';';
				$this->db->setQuery($q);
				$this->log->add('Empty category language table', true);

				if ($this->db->execute())
				{
					$this->log->addStats('empty', JText::sprintf('COM_CSVI_CATEGORY_LANGUAGE_TABLE_HAS_BEEN_EMPTIED', $language));
				}
				else
				{
					$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_CATEGORY_LANGUAGE_TABLE_HAS_NOT_BEEN_EMPTIED', $language));
				}
			}
		}

		$this->log->setLinenumber($linenumber++);
		$q = 'TRUNCATE TABLE ' . $this->db->quoteName('#__virtuemart_category_categories');
		$this->db->setQuery($q);
		$this->log->add('Empty category link table', true);

		if ($this->db->execute())
		{
			$this->log->addStats('empty', 'COM_CSVI_CATEGORY_LINK_TABLE_HAS_BEEN_EMPTIED');
		}
		else
		{
			$this->log->addStats('incorrect', 'COM_CSVI_CATEGORY_LINK_TABLE_HAS_NOT_BEEN_EMPTIED');
		}

		$this->log->setLinenumber($linenumber++);
		$q = 'TRUNCATE TABLE ' . $this->db->quoteName('#__virtuemart_category_medias');
		$this->db->setQuery($q);
		$this->log->add('Empty category medias table', true);

		if ($this->db->execute())
		{
			$this->log->addStats('empty', 'COM_CSVI_CATEGORY_MEDIAS_TABLE_HAS_BEEN_EMPTIED');
		}
		else
		{
			$this->log->addStats('incorrect', 'COM_CSVI_CATEGORY_MEDIAS_TABLE_HAS_NOT_BEEN_EMPTIED');
		}

		$this->log->setLinenumber($linenumber++);
		$q = 'TRUNCATE TABLE ' . $this->db->quoteName('#__virtuemart_manufacturers');
		$this->db->setQuery($q);
		$this->log->add('Empty manufacturers table', true);

		if ($this->db->execute())
		{
			$this->log->addStats('empty', 'COM_CSVI_MANUFACTURER_TABLE_HAS_BEEN_EMPTIED');
		}
		else
		{
			$this->log->addStats('incorrect', 'COM_CSVI_MANUFACTURER_TABLE_HAS_NOT_BEEN_EMPTIED');
		}

		// Empty manufacturer language table
		foreach ($languages as $language)
		{
			$table = $this->db->getPrefix() . 'virtuemart_manufacturers_' . strtolower(str_replace('-', '_', $language));

			if (in_array($table, $tables))
			{
				$q = 'TRUNCATE TABLE ' . $this->db->quoteName($table) . ';';
				$this->db->setQuery($q);
				$this->log->add('Empty manufacturer language table', true);

				if ($this->db->execute())
				{
					$this->log->addStats('empty', JText::sprintf('COM_CSVI_MANUFACTURER_LANGUAGE_TABLE_HAS_BEEN_EMPTIED', $language));
				}
				else
				{
					$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_MANUFACTURER_LANGUAGE_TABLE_HAS_NOT_BEEN_EMPTIED', $language));
				}
			}
		}

		$this->log->setLinenumber($linenumber++);
		$q = 'TRUNCATE TABLE ' . $this->db->quoteName('#__virtuemart_manufacturercategories');
		$this->db->setQuery($q);
		$this->log->add('Empty manufacturer categories table', true);

		if ($this->db->execute())
		{
			$this->log->addStats('empty', 'COM_CSVI_MANUFACTURER_CATEGORY_TABLE_HAS_BEEN_EMPTIED');
		}
		else
		{
			$this->log->addStats('incorrect', 'COM_CSVI_MANUFACTURER_CATEGORY_TABLE_HAS_NOT_BEEN_EMPTIED');
		}

		// Empty manufacturer language table
		foreach ($languages as $language)
		{
			$table = $this->db->getPrefix() . 'virtuemart_manufacturercategories_' . strtolower(str_replace('-', '_', $language));

			if (in_array($table, $tables))
			{
				$q = 'TRUNCATE TABLE ' . $this->db->quoteName($table) . ';';
				$this->db->setQuery($q);
				$this->log->add('Empty manufacturer categories language table', true);

				if ($this->db->execute())
				{
					$this->log->addStats('empty', JText::sprintf('COM_CSVI_MANUFACTURER_LANGUAGE_TABLE_HAS_BEEN_EMPTIED', $language));
				}
				else
				{
					$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_MANUFACTURER_LANGUAGE_TABLE_HAS_NOT_BEEN_EMPTIED', $language));
				}
			}
		}

		$this->log->setLinenumber($linenumber++);
		$q = 'TRUNCATE TABLE ' . $this->db->quoteName('#__virtuemart_ratings');
		$this->db->setQuery($q);
		$this->log->add('Empty ratings table', true);

		if ($this->db->execute())
		{
			$this->log->addStats('empty', 'COM_CSVI_RATINGS_TABLE_HAS_BEEN_EMPTIED');
		}
		else
		{
			$this->log->addStats('incorrect', 'COM_CSVI_RATINGS_TABLE_HAS_NOT_BEEN_EMPTIED');
		}

		$this->log->setLinenumber($linenumber++);
		$q = 'TRUNCATE TABLE ' . $this->db->quoteName('#__virtuemart_rating_reviews');
		$this->db->setQuery($q);
		$this->log->add('Empty rating reviews table', true);

		if ($this->db->execute())
		{
			$this->log->addStats('empty', 'COM_CSVI_RATING_REVIEWS_TABLE_HAS_BEEN_EMPTIED');
		}
		else
		{
			$this->log->addStats('incorrect', 'COM_CSVI_RATING_REVIEWS_TABLE_HAS_NOT_BEEN_EMPTIED');
		}

		$this->log->setLinenumber($linenumber++);
		$q = 'TRUNCATE TABLE ' . $this->db->quoteName('#__virtuemart_rating_votes');
		$this->db->setQuery($q);
		$this->log->add('Empty rating votes table', true);

		if ($this->db->execute())
		{
			$this->log->addStats('empty', 'COM_CSVI_RATING_VOTES_TABLE_HAS_BEEN_EMPTIED');
		}
		else
		{
			$this->log->addStats('incorrect', 'COM_CSVI_RATING_VOTES_TABLE_HAS_NOT_BEEN_EMPTIED');
		}

		// Store the log count
		$linenumber--;
		$this->log->setLineNumber($linenumber);

		return true;
	}

	/**
	 * Load the exchange rates from VirtueMart.
	 *
	 * @return  bool  Always returns true.
	 *
	 * @since   3.0
	 */
	public function vmExchangeRates()
	{
		$jinput = JFactory::getApplication()->input;
		$this->log->setLineNumber(1);

		// Empty table
		$this->db->truncateTable('#__csvi_currency');

		// Add the Euro
		$query = $this->db->getQuery(true)
			->insert($this->db->quoteName('#__csvi_currency'))
			->columns(array($this->db->quoteName('currency_code'), $this->db->quoteName('currency_rate')))
			->values($this->db->quote('EUR') . ', 1');
		$this->db->setQuery($query)->execute();

		$squery = $this->db->getQuery(true)
			->select(
				array(
					'null',
					$this->db->quoteName('currency_code_3'),
					$this->db->quoteName('currency_exchange_rate')
				)
			)
			->from($this->db->quoteName('#__virtuemart_currencies'))
			->where($this->db->quoteName('currency_exchange_rate') . ' > 0');

		$q = "INSERT INTO " .
			$this->db->quoteName('#__csvi_currency') .
			"(" . $squery->__toString() . ")";
		$this->db->setQuery($q);

		if ($this->db->execute())
		{
			$this->log->addStats('added', JText::_('COM_CSVI_VM_EXCHANGE_RATES_LOADED'));
		}
		else
		{
			$this->log->addStats('incorrect', JText::_('COM_CSVI_VM_EXCHANGE_RATES_NOT_LOADED'));
		}

		// Store the log count
		$jinput->set('linesprocessed', 1);

		return true;
	}

	/**
	 *  Add exchange rates
	 * The eurofxref-daily.xml file is updated daily between 14:15 and 15:00 CET.
	 *
	 * @return  bool  Always returns true.
	 *
	 * @since   3.0
	 */
	public function ecbExchangeRates()
	{
		$jinput = JFactory::getApplication()->input;
		$linenumber = 1;

		// Read eurofxref-daily.xml file in memory
		$XMLContent = file("http://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml");

		// Process the file
		if ($XMLContent)
		{
			// Empty table
			$this->db->truncateTable('#__csvi_currency');

			// Add the Euro
			$query = $this->db->getQuery(true)
				->insert($this->db->quoteName('#__csvi_currency'))
				->columns(array($this->db->quoteName('currency_code'), $this->db->quoteName('currency_rate')))
				->values($this->db->quote('EUR') . ', 1');
			$this->db->setQuery($query)->execute();

			$currencyCode = array();
			$rate = array();

			foreach ($XMLContent as $line)
			{
				if (preg_match("/currency='([[:alpha:]]+)'/", $line, $currencyCode))
				{
					if (preg_match("/rate='([[:graph:]]+)'/", $line, $rate))
					{
						$rate_name = 'COM_CSVI_EXCHANGE_RATE_' . $currencyCode[1] . '_ADDED';

						$this->log->setLinenumber($linenumber++);
						$query = $this->db->getQuery(true)
							->insert($this->db->quoteName('#__csvi_currency'))
							->columns(array($this->db->quoteName('currency_code'), $this->db->quoteName('currency_rate')))
							->values($this->db->quote($currencyCode[1]) . ', ' . $this->db->quote($rate[1]));
						$this->db->setQuery($query);

						if ($this->db->execute())
						{
							$this->log->addStats('added', JText::_($rate_name));
						}
						else
						{
							$this->log->addStats('incorrect', JText::_($rate_name));
						}
					}
				}
			}
		}
		else
		{
			$this->log->addStats('incorrect', 'COM_CSVI_CANNOT_LOAD_EXCHANGERATE_FILE');
		}

		// Store the log count
		$linenumber--;
		$jinput->set('linesprocessed', $linenumber);

		return true;
	}

	/**
	 * Update available fields that require extra processing.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function updateAvailableFields()
	{
		// Use the ramifications of the Multi Variant plugin as regular fields
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('cf.customfield_params'))
			->from($this->db->quoteName('#__virtuemart_customs', 'c'))
			->leftJoin(
				$this->db->quoteName('#__virtuemart_product_customfields', 'cf')
				. ' ON ' . $this->db->quoteName('cf.virtuemart_custom_id') . ' = ' . $this->db->quoteName('c.virtuemart_custom_id')
			)
			->where($this->db->quoteName('c.field_type') . ' = ' . $this->db->quote('C'));
		$this->db->setQuery($query);

		$params = $this->db->loadColumn();

		// Start the query
		$query->clear()
			->insert($this->db->quoteName('#__csvi_availablefields'))
			->columns($this->db->quoteName(array('csvi_name', 'component_name', 'component_table', 'component', 'action')));

		$fieldnames = array();

		foreach ($params as $param)
		{
			// Get the different segments
			$segments = explode('|', $param);

			// Process each segment
			foreach ($segments as $segment)
			{
				if ($firstpos = stripos($segment, '='))
				{
					// Get the values
					$group = substr($segment, 0, $firstpos);
					$value = substr($segment, $firstpos + 1);

					// Assign the value to it's group
					$values = json_decode($value);

					if ($group == 'selectoptions' && is_array($values))
					{
						foreach ($values as $ramification)
						{
							$csvi_name = trim($ramification->voption);

							if ($ramification->voption == 'clabels')
							{
								$csvi_name = trim($ramification->clabel);
							}

							$fieldnames[] = $csvi_name;
						}
					}
				}
			}
		}

		if (count($fieldnames) > 0)
		{
			$fieldnames = array_unique($fieldnames);

			foreach ($fieldnames as $csvi_name)
			{
				$query->values(
					$this->db->quote($csvi_name) . ',' .
					$this->db->quote($csvi_name) . ',' .
					$this->db->quote('product') . ',' .
					$this->db->quote('com_virtuemart') . ',' .
					$this->db->quote('import')
				);
				$query->values(
					$this->db->quote($csvi_name) . ',' .
					$this->db->quote($csvi_name) . ',' .
					$this->db->quote('product') . ',' .
					$this->db->quote('com_virtuemart') . ',' .
					$this->db->quote('export')
				);
			}

			$this->db->setQuery($query)->execute();
		}
	}
}
