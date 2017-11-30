<?php
/**
 * @package     CSVI
 * @subpackage  Imports
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Import preview controller.
 *
 * @package     CSVI
 * @subpackage  Imports
 * @since       6.0
 */
class CsviControllerImportpreview extends CsviControllerDefault
{
	/**
	 * Show the detail page.
	 *
	 * @return  bool  Returns true.
	 *
	 * @since   6.0
	 */
	public function detail()
	{
		// Get the template ID
		$runId = $this->input->getInt('runId', false);

		if ($runId)
		{
			// Load the template
			/** @var CsviModelImportpreviews $model */
			$model = $this->getThisModel();
			$model->reset();

			try
			{
				$model->initialiseImport($runId);

				// Get the view
				$view = $this->getThisView();

				// Load the preview data
				$item = $model->getItem();

				// Push the item to the view
				$view->set('item', $item);

				// Push the file line count into the view
				$file = $model->getFile();
				$view->set('linecount', $file->lineCount());

				return parent::detail();
			}
			catch (Exception $e)
			{
				// We don't have valid data, return to the import page
				$this->setRedirect('index.php?option=com_csvi&view=imports', $e->getMessage(), 'error');
				$this->redirect();
			}
		}

		return true;
	}

	/**
	 * Cancel the import and return to the import page.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function cancel()
	{
		// Redirect back to the import page
		$this->setRedirect('index.php?option=com_csvi&view=imports', JText::_('COM_CSVI_IMPORT_CANCELED'), 'notice');
		$this->redirect();
	}
}
