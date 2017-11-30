<?php
/**
 * @package     CSVI
 * @subpackage  Analyzer
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Analyzer view.
 *
 * @package     CSVI
 * @subpackage  Analyzer
 * @since       6.0
 */
class CsviViewAnalyzer extends FOFViewHtml
{
	/**
	 * Get the details to analyze.
	 *
	 * @param   string  $tpl  The template to use.
	 *
	 * @return  bool  Always returns true.
	 *
	 * @since   6.0
	 */
	public function onDetail($tpl = null)
	{
		$jinput = JFactory::getApplication()->input;
		$this->process = $jinput->get('process', false, 'bool');

		// Check if we need to run the analyzer
		if ($this->process)
		{
		    $this->items = FOFModel::getTmpInstance('Analyzers', 'CsviModel')->analyze();
		}

		return true;
	}
}
