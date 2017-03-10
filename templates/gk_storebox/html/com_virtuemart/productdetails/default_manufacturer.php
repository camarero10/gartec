<?php
/**
 *
 * Show the product details page
 *
 * @package	VirtueMart
 * @subpackage
 * @author Max Milbers, Valerie Isaksen

 * @link http://www.virtuemart.net
 * @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: default_manufacturer.php 8610 2014-12-02 18:53:19Z Milbo $
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

$i = 1;
$mans = array();
// Gebe die Hersteller aus
foreach($this->product->manufacturers as $manufacturers_details) {
	//Link to products
	$link = JRoute::_('index.php?option=com_virtuemart&view=manufacturer&virtuemart_manufacturer_id=' . $manufacturers_details->virtuemart_manufacturer_id. '&tmpl=component', FALSE);
	$name = $manufacturers_details->mf_name;

	// Avoid JavaScript on PDF Output
	if (strtolower(vRequest::getCmd('output')) == "pdf") {
		$mans[] = JHtml::_('link', $link, $name);
	} else {
		$mans[] = '<a class="manuModal" rel="{handler: \'iframe\', size: {x: 700, y: 850}}" href="'.$link .'">'.$name.'</a>';
	}
}

echo '<span class="manufacturer">'. vmText::_('COM_VIRTUEMART_PRODUCT_DETAILS_MANUFACTURER_LBL') . '</span> ';
echo implode(', ',$mans);
	
// EOF