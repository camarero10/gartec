<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

jimport('joomla.html.html');
jimport('joomla.form.formfield');
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');


class JFormFieldCountryList extends JFormFieldList {

        protected $type = 'CountryList';

        public function getInput() {

				require_once(JPATH_ADMINISTRATOR.'/components/com_k2store/models/zones.php');
				$model = new K2StoreModelZones;
				$countries = $model->getCountries();
               //generate country filter list
                $country_options = array();
                $country_options[] = JHTML::_('select.option', '', JText::_('K2STORE_SELECT_COUNTRY'));
                foreach($countries as $row) {
					$country_options[] =  JHTML::_('select.option', $row->country_id, $row->country_name);
				}

				return JHTML::_('select.genericlist', $country_options, $this->name, 'onchange=', 'value', 'text', $this->value);
           }

}
