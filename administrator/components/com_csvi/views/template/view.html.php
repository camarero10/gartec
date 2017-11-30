<?php
/**
 * @package     CSVI
 * @subpackage  Templates
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

require_once JPATH_ADMINISTRATOR . '/components/com_csvi/views/default/view.html.php';

/**
 * Template view.
 *
 * @package     CSVI
 * @subpackage  Templates
 * @since       6.0
 */
class CsviViewTemplate extends CsviViewDefault
{
	/**
	 * The action to perform.
	 *
	 * @var    string
	 * @since  6.0
	 */
	protected $action = null;

	/**
	 * The component to use.
	 *
	 * @var    string
	 * @since  6.0
	 */
	protected $component = null;

	/**
	 * The operation to perform.
	 *
	 * @var    string
	 * @since  6.0
	 */
	protected $operation = null;

	/**
	 * The forms handler.
	 *
	 * @var    object
	 * @since  6.0
	 */
	protected $forms = null;

	/**
	 * List of available components.
	 *
	 * @var    array
	 * @since  6.0
	 */
	protected $components = array();

	/**
	 * List of tabs to show.
	 *
	 * @var    array
	 * @since  6.0
	 */
	protected $optiontabs = array();

	/**
	 * Executes before rendering the page for the Add task.
	 *
	 * @param   string  $tpl  Subtemplate to use
	 *
	 * @return  boolean  Return true to allow rendering of the page
	 */
	protected function onAdd($tpl = null)
	{
		// Let the parent get the template details
		parent::onAdd($tpl);

		// Load the helper
		$helper = new CsviHelperCsvi;

		// Set some variables
		$post_form = $this->input->get('jform', array(), 'array');
		$this->action = (isset($post_form['action'])) ? $post_form['action'] : $this->item->options->get('action', 'import');
		$this->component = (isset($post_form['component'])) ? $post_form['component'] : $this->item->options->get('component', 'com_csvi');
		$this->operation = (isset($post_form['operation'])) ? $post_form['operation'] : $this->item->options->get('operation', 'customimport');

		// Reset the option values
		$this->item->options->set('action', $this->action);
		$this->item->options->set('component', $this->component);
		$this->item->options->set('operation', $this->operation);

		// Make the template available for the form fields
		$jinput = JFactory::getApplication()->input;
		$jinput->set('item', $this->item);

		// Add the form files
		JFormHelper::addFormPath(JPATH_ADMINISTRATOR . '/components/com_csvi/views/template/tmpl/');
		JFormHelper::addFormPath(JPATH_ADMINISTRATOR . '/components/com_csvi/views/template/tmpl/' . $this->action);
		JFormHelper::addFormPath(JPATH_ADMINISTRATOR . '/components/com_csvi/addon/' . $this->component . '/tmpl/' . $this->action);

		// Add the form paths
		JFormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_csvi/models/fields/');
		JFormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_csvi/addon/' . $this->component . '/fields/');

		$this->addTemplatePath(JPATH_ADMINISTRATOR . '/components/com_csvi/addon/' . $this->component . '/tmpl/' . $this->action);

		// Load the components
		$this->components = $helper->getComponents();
		array_unshift($this->components, JHtml::_('select.option', '', 'COM_CSVI_MAKE_CHOICE'));

		// Load the option tabs
		if ($this->component && $this->operation)
		{
			$this->optiontabs = FOFModel::getTmpInstance('Tasks', 'CsviModel')->getOptions($this->component, $this->action, $this->operation);
		}

		// Setup the autoloader
		$addon = ucfirst($this->component);
		$path = JPATH_ADMINISTRATOR . '/components/com_csvi/addon/' . strtolower($addon);
		JLoader::registerPrefix($addon, $path);

		// Load the operations
		$this->forms = new stdClass;
		$form = FOFForm::getInstance('operations', 'operations');
		$form->bind(array_merge($this->item->getData(), array('jform' => $this->item->options->toArray())));
		$form->setFieldAttribute('rules', 'action', $this->action);
		$this->forms->operations = $helper->renderMyForm($form, $this->getModel(), $this->input);

		// Load the language file for the selected component
		$helper->loadLanguage($this->component);

		// Load the forms
		foreach ($this->optiontabs as $tab)
		{
			$tabname = $tab;

			if (stripos($tab, '.'))
			{
				list($tabname, $pro) = explode('.', $tab);
			}

			if (!empty($tabname))
			{
				// We don't do the fields tab as this is special, fields are loaded separately
				if ($tabname !== 'fields' && stripos($tabname, 'custom_') === false)
				{
					$form = FOFForm::getInstance($tabname, $tabname);
					$form->bind(array('jform' => $this->item->options->toArray()));

					// Render standard XMLs
					$this->forms->$tabname = $helper->renderMyForm($form, $this->getModel(), $this->input);
				}
				elseif (($tabname == 'fields' && $this->action == 'export') || stripos($tabname, 'custom_') !== false)
				{
					// Do not render any page of the type custom, this is handled in a PHP file
					$form = FOFForm::getInstance(str_ireplace('custom_', '', $tabname), str_ireplace('custom_', '', $tabname));
					$form->bind(array('jform' => $this->item->options->toArray()));
					$this->forms->$tabname = $form;
				}
			}
		}

		// Load the associated fields
		$this->fields = FOFModel::getTmpInstance('Templatefields', 'CsviModel')
			->csvi_template_id($this->item->csvi_template_id)
			->filter_order('ordering')
			->getList();

		return true;
	}
}
