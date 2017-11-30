<?php
/**
 * @package     CSVI
 * @subpackage  Addons
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Addons model.
 *
 * @package     CSVI
 * @subpackage  Addons
 * @since       6.0
 */
class CsviModelAddons extends FOFModel
{
	/**
	 * The database class
	 *
	 * @var    JDatabase
	 * @since  6.0
	 */
	protected $db = null;

	/**
	 * Public class constructor
	 *
	 * @param   array  $config  The configuration array
	 */
	public function __construct($config = array())
	{
		parent::__construct();

		// Initialise some values
		$this->db = JFactory::getDbo();
	}


	/**
	 * This method can be overriden to automatically do something with the
	 * list results array. You are supposed to modify the list which was passed
	 * in the parameters; DO NOT return a new array!
	 *
	 * @param   array  &$resultArray  An array of objects, each row representing a record
	 *
	 * @return  void
	 */
	protected function onProcessList(&$resultArray)
	{
		$helper = new CsviHelperCsvi;

		foreach ($resultArray as $result)
		{
			$helper->loadLanguage($result->name);
		}
	}

	/**
	 * Install a CSVI addon.
	 *
	 * @return  bool  True if success | False on failure.
	 *
	 * @throws  Exception on problem
	 *
	 * @since   6.0
	 */
	public function installAddon()
	{
		$package = $this->input->files->get('addon');

		// Check if there is no error
		if ($package['error'] == 0)
		{
			if (is_uploaded_file($package['tmp_name']))
			{
				// Load the Joomla classes
				jimport('joomla.filesystem.file');
				jimport('joomla.filesystem.folder');
				jimport('joomla.filesystem.archive');

				// Create the folder in the addons location
				$folder = JPATH_ADMINISTRATOR . '/components/com_csvi/addon/' . basename($package['name'], '.zip');

				if (JFolder::create($folder))
				{
					if (JFile::upload($package['tmp_name'], JPATH_SITE . '/tmp/' . $package['name']))
					{
						// Folder has been created, unpack the archive
						if (JArchive::extract(JPATH_SITE . '/tmp/' . $package['name'], $folder))
						{
							// Add the database record
							$query = $this->db->getQuery(true)
								->insert($this->db->quoteName('#__csvi_addons'))
								->columns($this->db->quoteName(array('name', 'version')))
								->values(
									$this->db->quote(basename($package['name'], '.zip')) . ', ' .
									$this->db->quote('1.0')
								);
							$this->db->setQuery($query);

							if ($this->db->execute())
							{
								return true;
							}
							else
							{
								throw new RuntimeException(JText::_('COM_CSVI_PACKAGE_UPLOADED_NO_DATABASE'));
							}
						}
						else
						{
							throw new RuntimeException(JText::_('COM_CSVI_PACKAGE_CANNOT_UNPACK'));
						}
					}
					else
					{
						throw new RuntimeException(JText::_('COM_CSVI_PACKAGE_CANNOT_MOVE_FILE'));
					}
				}
				else
				{
					throw new RuntimeException(JText::sprintf('COM_CSVI_PACKAGE_CANNOT_CREATE_FOLDER', $folder));
				}
			}
			else
			{
				throw new RuntimeException(JText::_('COM_CSVI_UPLOAD_ERROR_NOT_UPLOADED_FILE'));
			}
		}
		else
		{
			switch ($package['error'])
			{
				case UPLOAD_ERR_NO_FILE:
					throw new RuntimeException(JText::_('COM_CSVI_UPLOAD_ERROR_NO_FILE'));
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_FORM_SIZE:
					throw new RuntimeException(JText::_('COM_CSVI_UPLOAD_ERROR_EXCEEDED_FILE_LIMIT'));
				default:
					throw new RuntimeException(JText::_('COM_CSVI_UPLOAD_ERROR_UNKNOWN'));
			}
		}
	}

	/**
	 * Remove a CSVI addon package.
	 *
	 * @param   array  $ids  The array of addons to remove
	 *
	 * @return  bool  True if success | False on failure.
	 *
	 * @throws  Exception on problem
	 *
	 * @since   6.0
	 */
	public function removeAddon($ids)
	{
		if (is_array($ids))
		{
			jimport('joomla.filesystem.folder');

			foreach ($ids as $packageid)
			{
				// Get the package info
				$component = $this->getComponentName($packageid);

				if ($component && $packageid > 1)
				{
					if (JFolder::delete(JPATH_ADMINISTRATOR . '/components/com_csvi/addon/' . $component))
					{
						// Remove the entry from the database
						$query = $this->db->getQuery(true)
							->delete($this->db->quoteName('#__csvi_addons'))
							->where($this->db->quoteName('csvi_addon_id') . ' = ' . (int) $packageid);
						$this->db->setQuery($query)->execute();

						// Delete the available fields
						$this->deleteAvailableFields($component);

						// Delete the available tables
						$this->deleteAvailableTables($component);

						// Delete the tasks
						$this->deleteTasks($component);
					}
					else
					{
						throw new RuntimeException(JText::sprintf('COM_CSVI_CANNOT_REMOVE_FOLDER', JPATH_ADMINISTRATOR . '/components/com_csvi/addon/' . $component));
					}
				}
				else
				{
					throw new RuntimeException(JText::_('COM_CSVI_NO_PACKAGE_NAME_FOUND'));
				}
			}

			return true;
		}
		else
		{
			throw new RuntimeException(JText::_('COM_CSVI_NO_PACKAGES_TO_REMOVE'));
		}
	}

	/**
	 * This method runs before a record is published
	 *
	 * @param   FOFTable  &$table  The table instance of the record being published
	 *
	 * @return  boolean  True to allow the operation
	 *
	 * @since   6.0
	 */
	public function onBeforePublish(&$table)
	{
		foreach ($this->id_list as $key => $packageId)
		{
			if ($packageId === 1)
			{
				unset($this->id_list[$key]);
			}
		}
	}

	/**
	 * Clean up after enabling or disabling an add-on.
	 *
	 * @param   FOFTable  &$table  The table instance of the record which was published
	 *
	 * @return  bool  True to allow operation | False in case of an error.
	 *
	 * @since   6.0
	 */
	public function onAfterPublish(&$table)
	{
		foreach ($this->id_list as $packageId)
		{
			if ($packageId > 1)
			{
				switch ($this->input->get('task', 'publish'))
				{
					case 'unpublish':
						// Update the tasks
						$query = $this->db->getQuery(true)
							->update($this->db->quoteName('#__csvi_tasks', 't'))
							->leftJoin(
								$this->db->quoteName('#__csvi_addons', 'a')
								. ' ON ' . $this->db->quoteName('t.component') . ' = ' . $this->db->quoteName('a.name')
							)
							->set($this->db->quoteName('t.enabled') . ' = 0')
							->where($this->db->quoteName('a.csvi_addon_id') . ' = ' . (int) $packageId);
						$this->db->setQuery($query)->execute();

						// Update the available tables
						$query = $this->db->getQuery(true)
							->update($this->db->quoteName('#__csvi_availabletables', 't'))
							->leftJoin(
								$this->db->quoteName('#__csvi_addons', 'a')
								. ' ON ' . $this->db->quoteName('t.component') . ' = ' . $this->db->quoteName('a.name')
							)
							->set($this->db->quoteName('t.enabled') . ' = 0')
							->where($this->db->quoteName('a.csvi_addon_id') . ' = ' . (int) $packageId);
						$this->db->setQuery($query)->execute();

						// Delete the available fields
						$component = $this->getComponentName($packageId);
						$this->deleteAvailableFields($component);
						break;
					default:
						// Update the tasks
						$query = $this->db->getQuery(true)
							->update($this->db->quoteName('#__csvi_tasks', 't'))
							->leftJoin(
								$this->db->quoteName('#__csvi_addons', 'a')
								. ' ON ' . $this->db->quoteName('t.component') . ' = ' . $this->db->quoteName('a.name')
							)
							->set($this->db->quoteName('t.enabled') . ' = 1')
							->where($this->db->quoteName('a.csvi_addon_id') . ' = ' . (int) $packageId);
						$this->db->setQuery($query)->execute();

						// Update the available tables
						$query = $this->db->getQuery(true)
							->update($this->db->quoteName('#__csvi_availabletables', 't'))
							->leftJoin(
								$this->db->quoteName('#__csvi_addons', 'a')
								. ' ON ' . $this->db->quoteName('t.component') . ' = ' . $this->db->quoteName('a.name')
							)
							->set($this->db->quoteName('t.enabled') . ' = 1')
							->where($this->db->quoteName('a.csvi_addon_id') . ' = ' . (int) $packageId);
						$this->db->setQuery($query)->execute();
						break;
				}
			}
		}
	}

	/**
	 * Get the name of a component.
	 *
	 * @param   int  $packageId  The ID of the component.
	 *
	 * @return  string  The name of the component.
	 *
	 * @since   6.0
	 */
	private function getComponentName($packageId)
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('name'))
			->from($this->db->quoteName('#__csvi_addons'))
			->where($this->db->quoteName('csvi_addon_id') . ' = ' . (int) $packageId);
		$this->db->setQuery($query);
		$component = $this->db->loadResult();

		return $component;
	}

	/**
	 * Delete available field references.
	 *
	 * @param   string  $component  The name of the component to delete references for.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function deleteAvailableFields($component)
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName('#__csvi_availablefields'))
			->where($this->db->quoteName('component') . ' = ' . $this->db->quote($component));
		$this->db->setQuery($query)->execute();
	}

	/**
	 * Delete available table references.
	 *
	 * @param   string  $component  The name of the component to delete references for.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function deleteAvailableTables($component)
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName('#__csvi_availabletables'))
			->where($this->db->quoteName('component') . ' = ' . $this->db->quote($component));
		$this->db->setQuery($query)->execute();
	}

	/**
	 * Delete the tasks.
	 *
	 * @param   string  $component  The name of the component to delete references for.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function deleteTasks($component)
	{
		$query = $this->db->getQuery(true)
			->delete($this->db->quoteName('#__csvi_tasks'))
			->where($this->db->quoteName('component') . ' = ' . $this->db->quote($component));
		$this->db->setQuery($query)->execute();
	}
}
