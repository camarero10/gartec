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
 * Select list form field with product categories.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class JFormFieldCsviVirtuemartProductCategories extends JFormFieldCsviForm
{
	/**
	 * Type of field
	 *
	 * @var    string
	 * @since  4.0
	 */
	protected $type = 'CsviVirtuemartProductCategories';

	/**
	 * Select categories.
	 *
	 * @return  array  An array of users.
	 *
	 * @since   4.0
	 */
	protected function getOptions()
	{
		$conf = JFactory::getConfig();
		$lang = strtolower(str_replace('-', '_', $this->form->getValue('language', '', $conf->get('language'))));

		require_once JPATH_ADMINISTRATOR . '/components/com_csvi/addon/com_virtuemart/helper/categorylist.php';
		$categoryList = new Com_VirtuemartHelperCategoryList;

		return $categoryList->getCategoryTree($lang);
	}
}
