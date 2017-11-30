<?php
/**
 * @package     CSVI
 * @subpackage  Views
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * The default view.
 *
 * @package     CSVI
 * @subpackage  Views
 * @since       6.0
 */
class CsviViewDefault extends FOFViewHtml
{
	/**
	 * A default detail entry.
	 *
	 * @param   string  $tpl  Subtemplate to use
	 *
	 * @return  boolean  Return true to allow rendering of the page
	 */
	public function onDetail($tpl = null)
	{
		return true;
	}
}
