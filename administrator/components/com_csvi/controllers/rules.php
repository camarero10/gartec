<?php
/**
 * @package     CSVI
 * @subpackage  Rules
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Rules Controller.
 *
 * @package     CSVI
 * @subpackage  Rules
 * @since       6.0
 */
class CsviControllerRules extends FOFController
{
	/**
	 * Load the plugin form.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function loadPluginForm()
	{
		// Load the plugins
		$db = JFactory::getDbo();
		$dispatcher = new RantaiPluginDispatcher;
		$dispatcher->importPlugins('csvirules', $db);
		$output = $dispatcher->trigger('getForm', array('id' => $this->input->get('plugin')));

		// Output the form
		if (isset($output[0]))
		{
			echo $output[0];
		}

		JFactory::getApplication()->close();
	}
}
