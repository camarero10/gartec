<?php
/*------------------------------------------------------------------------
# com_k2store - K2 Store
# ------------------------------------------------------------------------
# author    Ramesh Elamathi - Weblogicx India http://www.weblogicxindia.com
# copyright Copyright (C) 2012 Weblogicxindia.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://k2store.org
# Technical Support:  Forum - http://k2store.org/forum/index.html
-------------------------------------------------------------------------*/


/** ensure this file is being included by a parent file */
defined('_JEXEC') or die('Restricted access');
jimport('joomla.html.html');
//jimport('joomla.html.html.select');
if (!version_compare(JVERSION, '3.0', 'ge'))
{
	require_once (JPATH_SITE.'/libraries/joomla/html/html/select.php');
}
require_once (JPATH_ADMINISTRATOR.'/components/com_k2store/library/prices.php');
class K2StoreSelect extends JHtmlSelect
{
	 /**
	 * Generates a +/- select list for pao prefixes
	 *
	 * @param unknown_type $selected
	 * @param unknown_type $name
	 * @param unknown_type $attribs
	 * @param unknown_type $idtag
	 * @param unknown_type $allowAny
	 * @param unknown_type $title
	 * @return unknown_type
	 */
    public static function productattributeoptionprefix( $selected, $name = 'filter_prefix', $attribs = array('class' => 'inputbox', 'size' => '1'), $idtag = null, $allowAny = false, $title = 'Select Prefix' )
    {
        $list = array();
        if($allowAny) {
            $list[] =  self::option('', "- ".JText::_( $title )." -" );
        }

        $list[] = JHTML::_('select.option',  '+', "+" );
        $list[] = JHTML::_('select.option',  '-', "-" );
       // $list[] = JHTML::_('select.option',  '=', "=" );

        return self::genericlist($list, $name, $attribs, 'value', 'text', $selected, $idtag );
    }


    /**
     * Generates shipping method type list
     *
     * @param string The value of the HTML name attribute
     * @param string Additional HTML attributes for the <select> tag
     * @param mixed The key that is selected
     * @returns string HTML for the radio list
     */
    public static function shippingtype( $selected, $name = 'filter_shipping_method_type', $attribs = array('class' => 'inputbox'), $idtag = null, $allowAny = false, $title = 'K2STORE_SELECT_SHIPPING_TYPE')
    {
    	$list = array();
    	if($allowAny) {
    		$list[] =  self::option('', "- ".JText::_( $title )." -" );
    	}
    	require_once(JPATH_ADMINISTRATOR.'/components/com_k2store/library/shipping.php');
    	$items = K2StoreShipping::getTypes();
    	foreach ($items as $item)
    	{
    		$list[] = JHTML::_('select.option', $item->id, $item->title );
    	}

    	return self::genericlist($list, $name, $attribs, 'value', 'text', $selected, $idtag );
    }

    /**
     * Generates a selectlist for shipping methods
     *
     * @param unknown_type $selected
     * @param unknown_type $name
     * @param unknown_type $attribs
     * @param unknown_type $idtag
     * @return unknown_type
     */
    public static function shippingmethod( $selected, $name = 'filter_shipping_method', $attribs = array('class' => 'inputbox'), $idtag = null )
    {
    	$list = array();

    	JModelLegacy::addIncludePath( JPATH_ADMINISTRATOR.'/components/com_k2store/models' );
    	$model = JModel::getInstance( 'shippingmethods', 'K2StoreModel' );
    	$model->setState('filter_enabled', true);
    	$items = $model->getList();
    	foreach (@$items as $item)
    	{
    		$list[] =  self::option( $item->shipping_method_id, JText::_($item->shipping_method_name));
    	}
    	return JHTML::_('select.radiolist', $list, $name, $attribs, 'value', 'text', $selected, $idtag);
    }

    public static function taxclass($default, $name) {
    	$db = JFactory::getDbo();
    	$query = $db->getQuery(true);
    	$query->select('taxprofile_id as value, taxprofile_name as text')->from('#__k2store_taxprofiles')
    	->where('state=1');
    	$db->setQuery($query);
    	$array = $db->loadObjectList();
    	$options[] = JHtml::_( 'select.option', 0, JText::_('K2STORE_SELECT_OPTION'));
    	foreach( $array as $data) {
    		$options[] = JHtml::_( 'select.option', $data->value, $data->text);
    	}
    	return	JHtml::_('select.genericlist', $options, $name, 'class="inputbox"', 'value', 'text', $default);
    }

    public static function geozones($default, $name) {
    	$db = JFactory::getDbo();
    	$query = $db->getQuery(true);
    	$query->select('geozone_id as value, geozone_name as text')->from('#__k2store_geozones')
    	->where('state=1');
    	$db->setQuery($query);
    	$array = $db->loadObjectList();
    	$options[] = JHtml::_( 'select.option', 0, JText::_('K2STORE_SRATE_SELECT_GEOZONE'));
    	foreach( $array as $data) {
    		$options[] = JHtml::_( 'select.option', $data->value, $data->text);
    	}
    	return	JHtml::_('select.genericlist', $options, $name, 'class="inputbox"', 'value', 'text', $default);

    }


}
