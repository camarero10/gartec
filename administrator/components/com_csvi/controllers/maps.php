<?php
/**
 * @package     CSVI
 * @subpackage  Fieldmapper
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Field mapper controller.
 *
 * @package     CSVI
 * @subpackage  Fieldmapper
 * @since       5.8
 */
class CsviControllerMaps extends FOFController
{
	/**
	 * Create a template from a field map.
	 *
	 * @return  string  JSON encoded result string.
	 *
	 * @since   5.8
	 */
	public function createTemplate()
	{
		// Get the map ID
		$id = $this->input->getInt('id', 0);

		// Get the template name
		$title = $this->input->getString('templateName', 0);

		if ($id)
		{
			// Create the template
			$result = $this->getThisModel()->createTemplate($id, $title);
		}
		else
		{
			$result = false;
		}

		echo json_encode($result);

		JFactory::getApplication()->close();
	}
}
