<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');


class JFormFieldLengthList extends JFormFieldList {

	protected $type = 'LengthList';

	public function getInput() {

		require_once(JPATH_ADMINISTRATOR.'/components/com_k2store/models/lengths.php');
		$model = new K2StoreModelLengths;
		$lengths = $model->getLengths();
		//generate country filter list
		$length_options = array();
		foreach($lengths as $row) {
			$length_options[] =  JHTML::_('select.option', $row->length_class_id, $row->length_title);
		}

		return JHTML::_('select.genericlist', $length_options, $this->name, 'onchange=', 'value', 'text', $this->value);
	}

}
