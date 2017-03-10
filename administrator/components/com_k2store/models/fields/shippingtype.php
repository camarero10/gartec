<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');


class JFormFieldShippingType extends JFormFieldList {

	protected $type = 'ShippingType';

	public function getInput() {

		require_once(JPATH_ADMINISTRATOR.'/components/com_k2store/library/select.php');
		$html = K2StoreSelect::shippingtype($this->value, $this->name);
		return $html;
	}

}
