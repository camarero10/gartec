<?php
/**
 * @package     CSVI
 * @subpackage  Cpanel
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Control panel controller.
 *
 * @package     CSVI
 * @subpackage  Cpanel
 * @since       6.0
 */
class CsviControllerCpanel extends FOFController
{
	/**
	 * Execute the browse task.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function execute($task)
	{
		parent::execute('browse');
	}
}
