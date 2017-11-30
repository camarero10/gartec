<?php
/**
 * @package     CSVI
 * @subpackage  Forms
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
 * Select list met operations.
 *
 * @package     CSVI
 * @subpackage  Forms
 * @since       6.0
 */
class JFormFieldCsviOperations extends JFormFieldCsviForm
{
	/**
	 * The type of field
	 *
	 * @var    string
	 * @since  6.0
	 */
	protected $type = 'CsviOperations';

	/**
	 * Get the list of operations.
	 *
	 * @return  array  The sorted list of operations.
	 *
	 * @since   4.0
	 */
	protected function getOptions()
	{
		$trans = array();
		$types = FOFModel::getTmpInstance('Tasks', 'CsviModel')
			->getOperations($this->form->getValue('jform.action'), $this->form->getValue('jform.component'));

		// Create an array
		foreach ($types as $type)
		{
			$trans[$type->value] = $type->name;
		}

		ksort($trans);

		return $trans;
	}
}
