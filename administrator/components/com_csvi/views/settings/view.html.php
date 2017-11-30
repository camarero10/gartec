<?php
/**
 * @package     CSVI
 * @subpackage  Settings
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

require_once JPATH_ADMINISTRATOR . '/components/com_csvi/views/default/view.html.php';

/**
 * Settings view.
 *
 * @package     CSVI
 * @subpackage  Settings
 * @since       6.0
 */
class CsviViewSettings extends CsviViewDefault
{
	/**
	 * Method to get the field options.
	 *
	 * @param   string  $tpl  Subtemplate to use.
	 *
	 * @return  boolean  Return true to allow rendering of the page.
	 *
	 * @since   6.0
	 */
	protected function onAdd($tpl = null)
	{
		// Let the parent get the setting details
		parent::onAdd($tpl);

		// Load the helper
		$helper = new CsviHelperCsvi;

		// Load the forms
		$forms = array('site', 'google', 'yandex', 'icecat', 'log');
		$this->forms = array();

		// Add the path of the form location
		JFormHelper::addFormPath(JPATH_COMPONENT_ADMINISTRATOR . '/models/forms/');
		JFormHelper::addFieldPath(JPATH_COMPONENT_ADMINISTRATOR . '/models/fields/');

		// Render each tab
		foreach ($forms as $tab)
		{
			$form = FOFForm::getInstance('settings_' . $tab, 'settings_' . $tab);
			$form->bind(array_merge($this->item->getData(), array('jform' => $this->item->options)));
			$o = $helper->renderMyForm($form, $this->getModel(), $this->input);
			$this->forms[$tab] = $helper->renderMyForm($form, $this->getModel(), $this->input);
		}

		// Load the model
		$model = $this->getModel();

		// Get ICEcat statistics
		$this->icecat_stats = $model->icecatStats();

		// Display it all
		return true;
	}
}
