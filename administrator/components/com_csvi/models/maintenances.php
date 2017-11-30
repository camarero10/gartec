<?php
/**
 * @package     CSVI
 * @subpackage  Maintenance
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Maintenance model.
 *
 * @package     CSVI
 * @subpackage  Maintenance
 * @since       6.0
 */
class CsviModelMaintenances extends CsviModelDefault
{
	/**
	 * Load a maintenance addon.
	 *
	 * @param   string  $component  The name of the component being processed.
	 * @param   bool    $isCli      Set if we are running CLI mode.
	 *
	 * @return  mixed	addon class if found.
	 *
	 * @throws  CsviException
	 *
	 * @since   6.0
	 */
	private function loadAddon($component, $isCli = false)
	{
		if ($component)
		{
			if (file_exists(JPATH_ADMINISTRATOR . '/components/com_csvi/addon/' . $component . '/model/maintenance.php'))
			{
				require_once JPATH_ADMINISTRATOR . '/components/com_csvi/addon/' . $component . '/model/maintenance.php';
				$classname = $component . 'Maintenance';
				$addon = new $classname($this->db, $this->log, $this->csvihelper, $isCli);

				// Load the language files
				$this->csvihelper->loadLanguage($component);

				return $addon;
			}

			throw new CsviException(JText::sprintf('COM_CSVI_ADDON_MAINTENANCE_NOT_FOUND', $component), 511);
		}

		throw new CsviException(JText::sprintf('COM_CSVI_ADDON_MAINTENANCE_NO_COMPONENT'), 518);
	}

	/**
	 * Run a maintenance operation.
	 *
	 * @param   string  $component  The name of the component being processed.
	 * @param   string  $operation  The name of the operation being performed.
	 * @param   mixed   $key        A counter that can be used by methods to keep track of status.
	 * @param   bool    $isCli      Set if we are running CLI mode.
	 *
	 * @return  bool	true if operation is executed | false if operation is not executed.
	 *
	 * @throws  CsviException
	 *
	 * @since   6.0
	 */
	public function runOperation($component, $operation, $key, $isCli = false)
	{
		// Load the addon
		if ($addon = $this->loadAddon($component, $isCli))
		{
			// Check if the operation exists
			if (method_exists($addon, $operation))
			{
				$this->log->setActive(true);

				// Prepare the operation
				$this->prepareOperation($component, $operation);

				// Fire an onbefore
				$onbefore = 'onBefore' . $operation;

				if (method_exists($addon, $onbefore))
				{
					if (!$addon->$onbefore($this->input))
					{
						throw new CsviException(JText::_('COM_CSVI_MAINTENANCE_ONBEFORE_EXECUTION_ERROR'), 512);
					}
				}

				// Execute the operation
				if (method_exists($addon, $operation))
				{
					$addon->$operation($this->input, $key);
				}
				else
				{
					throw new CsviException(JText::sprintf('COM_CSVI_OPERATION_NOT_EXIST', $component, $operation), 408);
				}

				// Fire an onafter
				$onafter = 'onAfter' . $operation;
				$options = array();

				if (method_exists($addon, $onafter))
				{
					$options = $addon->$onafter();

					if (!$options)
					{
						throw new CsviException(JText::_('COM_CSVI_MAINTENANCE_ONAFTER_EXECUTION_ERROR'));
					}
					else
					{
						if (isset($options['cancel']))
						{
							return $options;
						}
					}
				}

				// Collect the results
				$results = array();

				// Get the run ID
				$results['run_id'] = $this->log->getLogId();

				// Get the information to show
				$results['info'] = (isset($options['info'])) ? $options['info'] : '';

				// Set if the process should continue
				$results['continue'] = (isset($options['continue'])) ? $options['continue'] : false;

				// If no need to continue, set the end date
				if (!$results['continue'])
				{
					$this->finishOperation($results['run_id']);
				}

				// Set if the process has been cancelled
				$results['cancel'] = (isset($options['cancel'])) ? $options['cancel'] : false;

				// Set the key
				$results['key'] = (isset($options['key'])) ? $options['key'] : 0;

				return $results;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * Prepare maintenance.
	 *
	 * @param   string  $component  The name of the component being processed.
	 * @param   string  $operation  The name of the operation being performed.
	 *
	 * @return  void.
	 *
	 * @since   3.3
	 */
	private function prepareOperation($component, $operation)
	{
		// Start the log
		$this->log->setLogId($this->input->get('run_id', 0, 'int'));
		$this->log->SetAddon($component);
		$this->log->SetAction('Maintenance');
		$this->log->SetActionType($operation . '_LABEL');

		$this->log->initialise();
	}

	/**
	 * Maintenance operation is cancelled.
	 *
	 * @param   int  $csvi_log_id  The ID of the log entry
	 *
	 * @return  void.
	 *
	 * @throws  Exception
	 *
	 * @since   6.0
	 */
	public function cancel($csvi_log_id)
	{
		// Clean the session
		$session = JFactory::getSession();
		$session->set('form', serialize('0'), 'com_csvi');

		// Load the log details
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('addon'))
			->from($this->db->quoteName('#__csvi_logs'))
			->where($this->db->quoteName('csvi_log_id') . ' = ' . (int) $csvi_log_id);
		$component = $this->db->setQuery($query)->loadResult();

		// Load the addon
		if ($addon = $this->loadAddon($component))
		{
			// Check if the operation exists
			if (method_exists($addon, 'cancelOperation'))
			{
				// Execute the operation
				if (!$addon->cancelOperation())
				{
					// Finish the operation
					$this->cancelOperation($csvi_log_id);

					throw new Exception(JText::_('COM_CSVI_MAINTENANCE_EXECUTION_ERROR'));
				}

				// Finish the operation
				$this->cancelOperation($csvi_log_id);
			}
		}
	}

	/**
	 * Get a list of available components that have maintenance options.
	 *
	 * @return  array  Returns an array of components.
	 *
	 * @since   6.0
	 */
	public function getComponents()
	{
		// Load the components
		$components = $this->csvihelper->getComponents();

		// Check if there are any maintenance options available
		foreach ($components as $key => $component)
		{
			if (!file_exists(JPATH_ADMINISTRATOR . '/components/com_csvi/addon/' . $component->value . '/model/maintenance.php'))
			{
				unset($components[$key]);
			}
			else
			{
				require_once JPATH_ADMINISTRATOR . '/components/com_csvi/addon/' . $component->value . '/model/maintenance.php';
				$classname = $component->value . 'Maintenance';
				$maintenance = new $classname($this->db, $this->log, $this->csvihelper);

				if (!method_exists($maintenance, 'getOperations'))
				{
					unset($components[$key]);
				}
			}
		}

		$options = JHtml::_('select.option', '', JText::_('COM_CSVI_MAKE_CHOICE'), 'value', 'text', true);
		array_unshift($components, $options);

		return $components;
	}

	/**
	 * Get operations for a selected component.
	 *
	 * @param   string  $component  The name of the component to process
	 *
	 * @return  array  Returns an array of options.
	 *
	 * @since   6.0
	 */
	public function getOperations($component)
	{
		// Check for maintenance options of the addon
		if ($addon = $this->loadAddon($component))
		{
			// Load the operations
			if (method_exists($addon, 'getOperations'))
			{
				return $addon->getOperations();
			}
			else
			{
				return array('' => JText::_('COM_CSVI_NO_OPTIONS_FOUND'));
			}
		}
		else
		{
			return array('' => JText::_('COM_CSVI_NO_OPTIONS_FOUND'));
		}
	}

	/**
	 * Get options for a selected component operation.
	 *
	 * @param   string  $component  The name of the component being processed
	 * @param   string  $operation  The name of the operation being loaded
	 *
	 * @return  array  Returns an array with options.
	 *
	 * @since   6.0
	 */
	public function getOptions($component, $operation)
	{
		// Check for maintenance options of the addon
		if ($addon = $this->loadAddon($component))
		{
			// Load the operations
			if (method_exists($addon, 'getOptions'))
			{
				return array('options' => $addon->getOptions($operation));
			}
			else
			{
				return array('' => JText::_('COM_CSVI_NO_OPTIONS_FOUND'));
			}
		}
		else
		{
			return array('' => JText::_('COM_CSVI_NO_OPTIONS_FOUND'));
		}
	}

	/**
	 * Load the language of a selected component.
	 *
	 * @param   string  $component  The component to load the language for
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function loadLanguage($component)
	{
		// Load the language files
		$this->csvihelper->loadLanguage($component);
	}

	/**
	 * Store uploaded files in the CSVI temp folder for future use.
	 *
	 * @param   array  $files  The array of uploaded files
	 *
	 * @return  array  An array with cleaned up file information.
	 *
	 * @since   6.0
	 */
	public function storeUploadedFiles($files)
	{
		jimport('joomla.filesystem.file');

		foreach ($files as $key => $file)
		{
			if (is_uploaded_file($file['tmp_name']))
			{
				if (JFile::upload($file['tmp_name'], CSVIPATH_TMP . '/' . $file['name'], false, true))
				{
					$files[$key]['tmp_name'] = CSVIPATH_TMP . '/' . $file['name'];
				}
			}
		}

		return $files;
	}

	/**
	 * Handle the end of the import.
	 *
	 * @param   int  $csvi_log_id  The ID of the import process
	 *
	 * @return  void.
	 *
	 * @since   3.0
	 */
	private function finishOperation($csvi_log_id)
	{
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__csvi_logs'))
			->set($this->db->quoteName('records') . ' = ' . (int) $this->log->getLinenumber())
			->set($this->db->quoteName('end') . ' = ' . $this->db->quote(JFactory::getDate()->toSql()))
			->where($this->db->quoteName('csvi_log_id') . ' = ' . (int) $csvi_log_id);
		$this->db->setQuery($query);
		$this->db->execute();
	}

	/**
	 * Cancel a running maintenance operation.
	 *
	 * @param   int  $csvi_log_id  The ID of the log entry
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function cancelOperation($csvi_log_id)
	{
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__csvi_logs'))
			->set($this->db->quoteName('end') . ' = ' . $this->db->quote(JFactory::getDate()->toSql()))
			->set($this->db->quoteName('run_cancelled') . ' = 1')
			->where($this->db->quoteName('csvi_log_id') . ' = ' . (int) $csvi_log_id);
		$this->db->setQuery($query)->execute();
	}
}
