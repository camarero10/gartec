<?php
/**
 * @package     CSVI
 * @subpackage  Core
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Core controller for CSVI.
 *
 * @package     CSVI
 * @subpackage  Core
 * @since       6.0
 */
class CsviControllerSefs extends CsviControllerDefault
{
	/**
	 * Overwrite the Joomla default getModel to make sure the ignore_request is not set to true.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function getsef()
	{
		echo JRoute::_(base64_decode($this->input->getBase64('parseurl')));

		JFactory::getApplication()->close();
	}
}
