<?php
/**
 * @package     CSVI
 * @subpackage  Templatefields
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Template fields Controller.
 *
 * @package     CSVI
 * @subpackage  Templatefields
 * @since       6.0
 */
class CsviControllerTemplatefields extends FOFController
{
	/**
	 * Executes a given controller task. The onBefore<task> and onAfter<task>
	 * methods are called automatically if they exist.
	 *
	 * @param   string  $task  The task to execute, e.g. "browse"
	 *
	 * @return  null|bool  False on execution failure
	 */
	public function execute($task)
	{
		try
		{
			return parent::execute($task);
		}
		catch (Exception $e)
		{
			$this->setRedirect('index.php?option=com_csvi&view=templatefields', $e->getMessage(), 'error');
		}
	}

	/**
	 * ACL check before allowing someone to browse
	 *
	 * @return  boolean  True to allow the method to run
	 */
	protected function onBeforeBrowse()
	{
		// Load the model
		$model = $this->getThisModel();

		try
		{
			// Load the list of templates
			$templates = FOFModel::getTmpInstance('Templates', 'CsviModel')->savestate(0)->limit(0)->limitstart(0)->filter_order('ordering')->getList();

			// Create a grouped list of templates
			$groupedtemplates = array();

			foreach ($templates as $template)
			{
				$groupedtemplates[JText::_('COM_CSVI_' . $template->action)]['items'][$template->csvi_template_id ] = $template->template_name;
			}

			// Load a chosen template ID
			$csvi_template_id = $model->getState('csvi_template_id', 0, 'int');

			// Check if we have a template ID, if not take the first one
			if ($csvi_template_id < 1)
			{
				$template = reset($templates);
				$csvi_template_id = $template->csvi_template_id;
			}

			// Save the state
			$model->setState('csvi_template_id', $csvi_template_id);

			// Set a default ordering
			$model->setState('filter_order', $model->getState('filter_order', 'ordering', 'cmd'));
			$model->setState('filter_order_Dir', $model->getState('filter_order_Dir', 'ASC', 'cmd'));

			// Load the selected template
			$this->db = JFactory::getDbo();
			$this->settings = new CsviHelperSettings($this->db);
			$this->log = new CsviHelperLog($this->settings, $this->db);
			$this->helper = new CsviHelperCsvi($this->log);
			$this->template = new CsviHelperTemplate($csvi_template_id, $this->helper);

			// Load the available fields
			$component = $this->template->get('component');
			$operation = $this->template->get('operation');
			$action = $this->template->get('action');
			$template_table = $this->template->get('custom_table');
			$availablefields = FOFModel::getTmpInstance('Availablefields', 'CsviModel')
				->component($component)
				->operation($operation)
				->action($action)
				->template_table($template_table)
				->idfields(true)
				->filter_order('csvi_name')
				->getList();

			// Push data into the view
			$view = $this->getThisView();
			$view->set('templates', $templates);
			$view->set('groupedtemplates', $groupedtemplates);
			$view->set('csvi_template_id', $csvi_template_id);
			$view->set('action', $action);
			$view->set('availablefields', $availablefields);

			return parent::onBeforeBrowse();
		}
		catch (Exception $e)
		{
			// Clear out the template ID, something is obviously wrong
			$session = JFactory::getSession();
			$registry = $session->get('registry');
			$registry->set('com_csvi.templatefields.csvi_template_id', 0);

			$this->setRedirect('index.php?option=com_csvi&view=templates', $e->getMessage(), 'error');

			return true;
		}
	}

	/**
	 * Process the Quick Add fields.
	 *
	 * @return  mixed  True if template field is stored | Error message in case of a problem.
	 *
	 * @since   4.2
	 */
	public function storeTemplateField()
	{
		try
		{
			$this->getThisModel()->storeTemplateField();

			echo true;
		}
		catch (Exception $e)
		{
			echo $e->getMessage();
		}
	}
}
