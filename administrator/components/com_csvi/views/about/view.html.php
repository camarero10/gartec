<?php
/**
 * @package     CSVI
 * @subpackage  About
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * The About view.
 *
 * @package     CSVI
 * @subpackage  About
 * @since       6.0
 */
class CsviViewAbout extends FOFViewHtml
{
	/**
	 * Executes before rendering the page for the Read task.
	 *
	 * @param   string  $tpl  Subtemplate to use
	 *
	 * @return  boolean  Return true to allow rendering of the page
	 */
	public function onDetail($tpl = null)
	{
		/** @var CsviModelAbouts $model */
		$model = $this->getModel();

		// Assign the values
		$this->folders = $model->getFolderCheck();

		// Get the schema version
		$this->schemaVersion = $model->getSchemaVersion();

		// Check for database errors
		$changeSet = $model->getChangeSet();
		$this->errors = $changeSet->check();

		return true;
	}
}
