<?php

/**
 * @package     CSVI
 * @subpackage  Templatefield
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Template field edit screen.
 *
 * @package     CSVI
 * @subpackage  Templatefield
 * @since       6.0
 */
class CsviViewTemplatefield extends FOFViewHtml
{
	public $template = null;

	/**
	 * Executes before rendering the page for the Add task.
	 *
	 * @param   string  $tpl  Subtemplate to use
	 *
	 * @return   boolean    Return true to allow rendering of the page
	 *
	 * @throws   Exception
	 *
	 * @since  6.0
	 */
	protected function onAdd($tpl = null)
	{
		if (parent::onAdd($tpl))
		{
			// Check if we have a template ID
			if ($this->item->csvi_template_id)
			{
				// Load the helper
				$helper = new CsviHelperCsvi;

				// Load the selected template
				$this->template = new CsviHelperTemplate($this->item->csvi_template_id, $helper);

				// Load the available fields
				$component = $this->template->get('component');
				$operation = $this->template->get('operation');
				$action = $this->template->get('action');
				$template_table = $this->template->get('custom_table');

				$this->availablefields = FOFModel::getTmpInstance('Availablefields', 'CsviModel')
					->component($component)
					->operation($operation)
					->action($action)
					->template_table($template_table)
					->idfields(true)
					->filter_order('csvi_name')
					->getList();

				// Add the path of the form location
				JFormHelper::addFormPath(JPATH_COMPONENT_ADMINISTRATOR . '/models/forms/');
				JFormHelper::addFieldPath(JPATH_COMPONENT_ADMINISTRATOR . '/models/fields/');

				// Instantiate the form
				$form = FOFForm::getInstance('templatefield_' . $action, 'templatefield_' . $action);

				// Load the data
				$data = $this->item->getData();
				$data['rules'] = $this->item->rules;

				// Bind the data
				$form->bind($data);

				$this->form = $helper->renderMyForm($form, $this->getModel(), $this->input);

				// Display it all
				return true;
			}
			else
			{
				throw new Exception(JText::_('COM_CSVI_NO_TEMPLATE_ID_FOUND'));
			}
		}
		else
		{
			return false;
		}
	}
}
