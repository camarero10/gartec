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
 * Select list form field with custom fields.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class JFormFieldCsviVirtuemartCurrency extends JFormFieldCsviForm
{
	/**
	 * Type of field
	 *
	 * @var    string
	 * @since  4.0
	 */
	protected $type = 'CsviVirtuemartCurrency';

	/**
	 * Get the options.
	 *
	 * @return  array  An array of customfields.
	 *
	 * @since   4.0
	 */
	protected function getOptions()
	{
		$query = $this->db->getQuery(true)
			->select(
				$this->db->quoteName('cc.currency_code', 'value')
				. ', IF (' . $this->db->quoteName('vc.currency_name') . ' IS NULL, '
						. $this->db->quoteName('cc.currency_code')
						. ', ' . $this->db->quoteName('vc.currency_name')
				. ') AS ' . $this->db->quoteName('text')
			)
			->from($this->db->quoteName('#__csvi_currency', 'cc'))
			->leftJoin(
				$this->db->quoteName('#__virtuemart_currencies', 'vc')
				. ' ON ' .
				$this->db->quoteName('vc.currency_code_3') . ' = ' . $this->db->quoteName('cc.currency_code')
			);
		$this->db->setQuery($query);
		$options = $this->db->loadObjectList();

		if (empty($options))
		{
			$options = array();
		}

		return array_merge(parent::getOptions(), $options);
	}
}
