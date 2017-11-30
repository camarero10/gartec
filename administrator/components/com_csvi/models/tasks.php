<?php
/**
 * @package     CSVI
 * @subpackage  Tasks
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Tasks model.
 *
 * @package     CSVI
 * @subpackage  Tasks
 * @since       6.0
 */
class CsviModelTasks extends FOFModel
{
	/**
	 * Collect the values to filter on.
	 *
	 * @return  object  List of filter values.
	 *
	 * @since   6.0
	 */
	private function getFilterValues()
	{
		return (object) array(
				'name'		=> $this->getState('name', '', 'string'),
				'process'	=> $this->getState('process', '*', 'string'),
				'component'	=> $this->getState('component', '', 'string'),
				'published'	=> $this->getState('published', '*', 'string')
		);
	}

	/**
	 * Builds the SELECT query
	 *
	 * @param   boolean  $overrideLimits  Are we requested to override the set limits?
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since   6.0
	 */
	public function buildQuery($overrideLimits = false)
	{
		$db = JFactory::getDbo();

		// Get the parent query
		$this->setState('filter_order', $this->getState('filter_order', 'ordering'));
		$query = parent::buildQuery($overrideLimits);

		// Join the user table to get the editor
		$query->select($db->quoteName('u.name', 'editor'));
		$query->leftJoin(
			$db->quoteName('#__users', 'u')
			. ' ON ' . $db->quoteName('u.id') . ' = ' . $db->quoteName('#__csvi_tasks.locked_by')
		);

		// Get the filters
		$state = $this->getFilterValues();

		if ($state->name)
		{
			$query->where($db->quoteName('task_name') . ' LIKE ' . $db->quote('%' . $state->name . '%'));
		}

		if ($state->process != '*')
		{
			$query->where($db->quoteName('action') . ' = ' . $db->quote($state->process));
		}

		if ($state->component)
		{
			$query->where($db->quoteName('component') . ' = ' . $db->quote($state->component));
		}

		if ($state->published != '*')
		{
			$query->where($db->quoteName('enabled') . ' = ' . $state->published);
		}

		return $query;
	}

	/**
	 * Load the template types for a given selection.
	 *
	 * @param   string  $action     The import or export option.
	 * @param   string  $component  The component.
	 *
	 * @return  array  List of available tasks.
	 *
	 * @since   3.5
	 */
	public function loadTasks($action, $component)
	{
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select($db->quoteName('task_name'))
			->from($db->quoteName('#__csvi_tasks'))
			->where($db->quoteName('action') . ' = ' . $db->quote($action))
			->where($db->quoteName('component') . ' = ' . $db->quote($component))
			->where($db->quoteName('enabled') . ' = 1');
		$db->setQuery($query);
		$types = $db->loadColumn();

		// Get translations
		$trans = array();

		foreach ($types as $type)
		{
			$trans[$type] = JText::_('COM_CSVI_' . $component . '_' . $type);
		}

		// Sort by task name
		ksort($trans);

		return $trans;
	}

	/**
	 * Reset the tasks.
	 *
	 * @return  bool  True if no errors are found | False if an SQL error has been found.
	 *
	 * @since   5.4
	 */
	public function reload()
	{
		$db = JFactory::getDbo();

		// Empty the tasks table
		$db->truncateTable('#__csvi_availabletables');
		$db->truncateTable('#__csvi_tasks');

		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.folder');
		$files = JFolder::files(JPATH_ADMINISTRATOR . '/components/com_csvi/addon/', 'tasks.sql', true, true);

		if (!empty($files))
		{
			foreach ($files as $file)
			{
				$queries = $db->splitSql(file_get_contents($file));

				foreach ($queries as $query)
				{
					$query = trim($query);

					if (!empty($query))
					{
						$db->setQuery($query);

						if (!$db->execute())
						{
							$this->setError($db->getErrorMsg());

							return false;
						}
					}
				}
			}
		}

		return true;
	}

	/**
	 * Load the operations.
	 *
	 * @param   mixed  $type       The type of template to filter on.
	 * @param   mixed  $component  The name of the component.
	 *
	 * @return  array  List of template types.
	 *
	 * @since   3.0
	 */
	public function getOperations($type=false, $component=false)
	{
		$types = array();

		if ($type && $component)
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select(
				"CONCAT('COM_CSVI_', " . $db->quoteName('component') . ", '_', " . $db->quoteName('task_name') . ") AS " . $db->quoteName('name')
				. ',' . $db->quoteName('task_name', 'value')
			)
			->from($db->quoteName('#__csvi_tasks'));

			// Check any selectors
			if ($type)
			{
				$query->where($db->quoteName('action') . ' = ' . $db->quote($type));
			}

			if ($component)
			{
				$query->where($db->quoteName('component') . ' = ' . $db->quote($component));
			}

			// Order by name
			$query->order($db->quoteName('ordering'));
			$db->setQuery($query);
			$types = $db->loadObjectList();

			// Translate the strings
			foreach ($types as $key => $type)
			{
				$type->name = JText::_($type->name);
				$types[$key] = $type;
			}
		}

		return $types;
	}

	/**
	 * Load the option tabs for a specific task.
	 *
	 * @param   string  $component  The name of the component.
	 * @param   string  $action     The action to perform.
	 * @param   string  $operation  The operation to execute.
	 *
	 * @return  array  List of option tabs.
	 *
	 * @since   4.0
	 */

	public function getOptions($component, $action, $operation)
	{
		$options = array();

		if ($component && $action && $operation)
		{
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select($db->quoteName('options'))
				->from($db->quoteName('#__csvi_tasks'))
				->where($db->quoteName('task_name') . ' = ' . $db->quote($operation))
				->where($db->quoteName('action') . ' = ' . $db->quote($action))
				->where($db->quoteName('component') . ' = ' . $db->quote($component));
			$db->setQuery($query);
			$result = $db->loadResult();
			$options = explode(',', $result);
		}

		return $options;
	}
}
