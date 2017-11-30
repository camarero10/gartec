<?php
/**
 * Users helper file
 *
 * @author 		Roland Dalmulder
 * @link 		http://www.csvimproved.com
 * @copyright 	Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license 	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @version 	$Id: com_virtuemart.php 2052 2012-08-02 05:44:47Z RolandD $
 */

defined('_JEXEC') or die;

/**
 * Joomla User helper class.
 *
 * @package     CSVI
 * @subpackage  JUsers
 * @since       6.0
 */
class Com_UsersHelperCom_Users
{
	/**
	 * Template helper
	 *
	 * @var    CsviHelperTemplate
	 * @since  6.0
	 */
	protected $template = null;

	/**
	 * Logger helper
	 *
	 * @var    CsviHelperLog
	 * @since  6.0
	 */
	protected $log = null;

	/**
	 * Fields helper
	 *
	 * @var    CsviHelperFields
	 * @since  6.0
	 */
	protected $fields = null;

	/**
	 * Database connector
	 *
	 * @var    JDatabase
	 * @since  6.0
	 */
	protected $db = null;

	/**
	 * Constructor.
	 *
	 * @param   CsviHelperTemplate  $template  An instance of CsviHelperTemplate.
	 * @param   CsviHelperLog       $log       An instance of CsviHelperLog.
	 * @param   CsviHelperFields    $fields    An instance of CsviHelperFields.
	 * @param   JDatabase           $db        Database connector.
	 *
	 * @since   4.0
	 */
	public function __construct(CsviHelperTemplate $template, CsviHelperLog $log, CsviHelperFields $fields, JDatabase $db)
	{
		$this->template = $template;
		$this->log = $log;
		$this->fields = $fields;
		$this->db = $db;
	}

	/**
	 * Get the user id, this is necessary for updating existing users.
	 *
	 * @return  mixed  ID of the user if found | False otherwise.
	 *
	 * @since   5.9.5
	 */
	public function getUserId()
	{
		$id = $this->fields->get('id');

		if ($id)
		{
			return $id;
		}
		else
		{
			$email = $this->fields->get('email');

			if ($email)
			{
				$query = $this->db->getQuery(true)
					->select('id')
					->from($this->db->quoteName('#__users'))
					->where($this->db->quoteName('email') . '  = ' . $this->db->quote($email));
				$this->db->setQuery($query);
				$this->log->add('COM_CSVI_FIND_USER_ID');

				return $this->db->loadResult();
			}
			else
			{
				return false;
			}
		}
	}
}
