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

// Include the calculation helper
require_once JPATH_ADMINISTRATOR . '/components/com_virtuemart/helpers/calculationh.php';

/**
 * The VirtueMart calculation helper class.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.1
 */
class CsviVmPrices extends calculationHelper
{
	/**
	 * Instantiate the class.
	 *
	 * @return  object  Returns itself so only one instance exists.
	 *
	 * @since   6.1
	 */
	public static function getInstance()
	{
		if (!is_object(self::$_instance))
		{
			$class = get_called_class();
			self::$_instance = new $class;
		}
		else
		{
			//We store in UTC and use here of course also UTC
			$jnow = JFactory::getDate();
			self::$_instance->_now = $jnow->toSQL();
		}

		return self::$_instance;
	}

	/**
	 * Constructor override because the original fails in it's original form.
	 *
	 * @since   6.1
	 */
	private function __construct()
	{
		$this->_db = JFactory::getDBO();
		$this->_app = JFactory::getApplication();

		//We store in UTC and use here of course also UTC
		$jnow = JFactory::getDate();
		$this->_now = $jnow->toSQL();
		$this->_nullDate = $this->_db->getNullDate();

		$this->productVendorId = 1;

		if (!class_exists('CurrencyDisplay'))
		{
			require VMPATH_ADMIN . '/helpers/currencydisplay.php';
		}

		$this->_currencyDisplay = CurrencyDisplay::getInstance();
		$this->_debug = false;

		if(!empty($this->_currencyDisplay->_vendorCurrency)){
			$this->vendorCurrency = $this->_currencyDisplay->_vendorCurrency;
			$this->vendorCurrency_code_3 = $this->_currencyDisplay->_vendorCurrency_code_3;
			$this->vendorCurrency_numeric = $this->_currencyDisplay->_vendorCurrency_numeric;
		}

		$this->setShopperGroupIds();

		$this->setCountryState();
		$this->setVendorId($this->productVendorId);

		$this->rules['Marge'] = array();
		$this->rules['Tax'] 	= array();
		$this->rules['VatTax'] 	= array();
		$this->rules['DBTax'] = array();
		$this->rules['DATax'] = array();

		//round only with internal digits
		$this->_roundindig = VmConfig::get('roundindig', false);
	}

	/**
	 * Set the shopper group IDs.
	 *
	 * @param   array  $shoppergroupId  An array of shoppergroup IDs to set.
	 *
	 * @return  void.
	 *
	 * @since   6.1
	 */
	public function setShopperGroup($shoppergroupId)
	{
		// Check if we have a shopper group ID, otherwise use guest
		if (empty($shoppergroupId))
		{
			$query = $this->_db->getQuery(true)
				->select($this->_db->quoteName('virtuemart_shoppergroup_id'))
				->from($this->_db->quoteName('#__virtuemart_shoppergroups'))
				->where($this->_db->quoteName('default') . ' = 2');
			$this->_db->setQuery($query);

			$shoppergroupId = $this->_db->loadResult();
		}

		// Empty any existing rules, just in case we have a new shopper group ID
		$this->allrules[$this->productVendorId] = array();

		// Set the shopper group IDs
		$this->setShopperGroupIds((array) $shoppergroupId);
	}
}
