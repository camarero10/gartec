<?php
/**
 * @package     CSVI
 * @subpackage  AvailableFields
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Available fields controller.
 *
 * @package     CSVI
 * @subpackage  AvailableFields
 * @since       6.0
 */
class CsviControllerAvailableField extends FOFController
{
	/**
	 * Redirect to maintenance to update the available fields.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function updateavailablefields()
	{
		$this->setRedirect('index.php?option=com_csvi&view=maintenance&task=read&component=com_csvi&operation=updateavailablefields');
	}
}
