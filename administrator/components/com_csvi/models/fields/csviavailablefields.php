<?php
/**
 * @package     CSVI
 * @subpackage  Fields
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

jimport('joomla.form.helper');
JFormHelper::loadFieldClass('CsviForm');

/**
 * Loads a list of available fields.
 *
 * @package     CSVI
 * @subpackage  Fields
 * @since       6.0
 */
class JFormFieldCsviAvailableFields extends JFormFieldCsviForm
{
	/**
	 * The type of field
	 *
	 * @var    string
	 * @since  6.0
	 */
	protected $type = 'CsviAvailableFields';

	/**
	 * Get the available fields.
	 *
	 * @return  array  An array of available fields.
	 *
	 * @throws  Exception
	 *
	 * @since   4.3
	 */
	protected function getOptions()
	{
		$key = (isset($this->element['idfield'])) ? (string) $this->element['idfield'] : 'id';

		$template_id = $this->jinput->getInt($key, $this->form->getValue('csvi_template_id', '', 0));

		if ($template_id)
		{
			// Load the selected template
			$helper = new CsviHelperCsvi;
			$template = new CsviHelperTemplate($template_id, $helper);

			// Load the available fields
			require_once JPATH_COMPONENT_ADMINISTRATOR . '/models/availablefields.php';
			$availablefields_model = new CsviModelAvailablefields;
			$fields = $availablefields_model->getAvailableFields(
				$template->get('operation'),
				$template->get('component'),
				$template->get('action'),
				'array'
			);

			if ((!is_array($fields) || empty($fields)) && $template->get('operation') != 'custom')
			{
				throw new Exception(
					JText::sprintf(
						'COM_CSVI_NO_AVAILABLE_FIELDS_FOUND_TEMPLATE',
						$template->get('action'),
						$template->get('component'),
						$template->get('operation')
					)
				);
			}
			else
			{
				$avfields = array();

				foreach ($fields as $field)
				{
					$avfields[$field] = $field;
				}
			}

			return array_merge(parent::getOptions(), $avfields);
		}
		else
		{
			throw new Exception(JText::_('COM_CSVI_NO_TEMPLATE_ID_FOUND'));
		}
	}
}
