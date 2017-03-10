<?php
/*------------------------------------------------------------------------
 # com_k2store - K2Store
# ------------------------------------------------------------------------
# author    Ramesh Elamathi - Weblogicx India http://www.weblogicxindia.com
# copyright Copyright (C) 2012 Weblogicxindia.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://k2store.org
# Technical Support:  Forum - http://k2store.org/forum/index.html
-------------------------------------------------------------------------*/
// no direct access
defined('_JEXEC') or die('Restricted access');

/*
 * 	Base class for loading all K2Store libraries
 *
 *  Since 2.6
 *
 */

abstract class K2StoreFactory {

	/**
	 * Global curency object
	 *
	 * @var    K2StoreCurrency
	 * @since  2.6
	 */
	public static $currency = null;

	/**
	 * Global weight object
	 *
	 * @var    K2StoreWeight
	 * @since  2.6
	 */
	public static $weight = null;

	/**
	 * Global length object
	 *
	 * @var    K2StoreLength
	 * @since  2.6
	 */

	public static $length = null;

	/**
	 * Global fields base object
	 *
	 * @var    K2StoreSelectableBase
	 * @since  2.6
	 */

	public static $sbase = null;

	/**
	 * Global fields object
	 *
	 * @var    K2StoreSelectableFields
	 * @since  2.6
	 */


	public static $fields = null;


	public static function getCurrencyObject() {

		if (!self::$currency)
		{
			require_once ('currency.php');
			self::$currency = K2StoreCurrency::getInstance();
		}

		return self::$currency;
	}

	public static function getWeightObject() {

		if (!self::$weight)
		{
			require_once ('weight.php');
			self::$weight = K2StoreWeight::getInstance();
		}

		return self::$weight;
	}

	public static function getLengthObject() {

		if(!self::$length)
		{
			require_once ('length.php');
			self::$length = K2StoreLength::getInstance();
		}
		return self::$length;
	}


	public static function getSelectableBase() {

		if (!self::$sbase)
		{
			require_once ('selectable/base.php');
			self::$sbase = K2StoreSelectableBase::getInstance();
		}

		return self::$sbase;
	}

	public static function getSelectableFields() {

		if (!self::$fields)
		{
			require_once ('selectable/fields.php');
			self::$fields = K2StoreSelectableFields::getInstance();
		}

		return self::$fields;
	}

}