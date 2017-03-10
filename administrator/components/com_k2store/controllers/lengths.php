<?php

// controller 

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
jimport('joomla.application.component.controlleradmin');

class K2StoreControllerLengths extends JControllerAdmin
{
	
	/**
	 * Proxy for getModel.
	 *
	 * @param	string	$name	The name of the model.
	 * @param	string	$prefix	The prefix for the PHP class name.
	 *
	 * @return	JModelLegacy
	 * @since	1.6
	 */
	public function getModel($name = 'Length', $prefix = 'K2StoreModel', $config = array('ignore_request' => true))
	{
		$model = parent::getModel($name, $prefix, $config);
	
		return $model;
	}
	
}
