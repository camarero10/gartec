<?php
/**
 * @package     CSVI
 * @subpackage  VirtueMart
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
 * Select list form field with order users.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       4.0
 */
class JFormFieldCsviVirtuemartOrderUser extends JFormFieldCsviForm
{
	/**
	 * Type of field
	 *
	 * @var    string
	 * @since  4.0
	 */
	protected $type = 'CsviVirtuemartOrderUser';

	/**
	 * Select order users.
	 *
	 * @return  array  An array of users.
	 *
	 * @since   4.0
	 */
	protected function getOptions()
	{
		$userids = $this->form->getValue('orderuser', 'jform');

		if (!empty($userids) && !empty($userids[0]))
		{
			$fullname = $this->db->quoteName('first_name') . ", " . $this->db->quoteName('middle_name') . ", " . $this->db->quoteName('last_name');
			$q = $this->db->getQuery(true)
				->select(
					array(
						$this->db->quoteName('virtuemart_user_id', 'value'),
						"IF (LENGTH(TRIM(CONCAT_WS(' ', " . $fullname . "))) = 0, "
							. $this->db->quote(JText::_('COM_CSVI_EXPORT_ORDER_USER_EMPTY'))
							. ", IF (TRIM(CONCAT_WS(' ', " . $fullname . ")) IS NULL, "
									. $this->db->quote(JText::_('COM_CSVI_EXPORT_ORDER_USER_EMPTY'))
									. ", TRIM(CONCAT_WS(' ', " . $fullname . "))
								)
						) AS " . $this->db->quoteName('text')
					)
				)
				->from($this->db->quoteName('#__virtuemart_order_userinfos'))
				->where($this->db->quoteName('virtuemart_user_id') . ' IN (' . implode(',', $userids) . ')')
				->order($this->db->quoteName('text'))
				->group($this->db->quoteName('value'));

			$this->db->setQuery($q);
			$customers = $this->db->loadObjectList();

			if (empty($customers))
			{
				$customers = array();
			}

			return array_merge(parent::getOptions(), $customers);
		}
		else
		{
			return parent::getOptions();
		}
	}
}
