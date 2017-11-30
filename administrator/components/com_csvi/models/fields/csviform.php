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

JFormHelper::loadFieldClass('list');

/**
 * Form override class.
 *
 * @package     CSVI
 * @subpackage  Forms
 * @since       6.0
 */
abstract class JFormFieldCsviForm extends JFormFieldList
{
	/**
	 * The type of field
	 *
	 * @var    string
	 * @since  6.0
	 */
	protected $type = 'CsviForm';

	/**
	 * A database connector.
	 *
	 * @var    JDatabase
	 * @since  6.0
	 */
	protected $db = null;

	/**
	 * JInput class
	 *
	 * @var    JInput
	 * @since  6.0
	 */
	protected $jinput = null;

	/**
	 * Method to instantiate the form field object.
	 *
	 * @param   object  $form  The form to attach to the form field object.
	 *
	 * @since   6.0
	 */
	public function __construct($form = null)
	{
		parent::__construct($form);

		$this->db = JFactory::getDbo();
		$this->jinput = JFactory::getApplication()->input;
	}
}
