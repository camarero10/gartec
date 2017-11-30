<?php
/**
 * @package     CSVI
 * @subpackage  Toolbar
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Creates the CSVI toolbars.
 *
 * @package     CSVI
 * @subpackage  Toolbar
 * @since       6.0
 */
class CsviToolbar extends FOFToolbar
{
	/**
	 * Renders the submenu (toolbar links) for all detected views of this component
	 *
	 * @return  void
	 *
	 * @since   6.0
	 */
	public function renderSubmenu()
	{
		$views = array(
				'cpanel',
				'imports',
				'exports',
				'COM_CSVI_TITLE_TEMPLATES' => array(
					'templates',
					'templatefields',
					'rules',
					'maps'
				),
				'COM_CSVI_TITLE_MAINTENANCE' => array(
					'maintenance',
					'availablefields',
					'analyzer',
					'tasks',
					'processes'
				),
				'logs',
				'settings',
				'about'
		);

		foreach ($views as $label => $view)
		{
			if (!is_array($view))
			{
				$this->addSubmenuLink($view);
			}
			else
			{
				$label = JText::_($label);
				$this->appendLink($label, '', false);

				foreach ($view as $v)
				{
					$this->addSubmenuLink($v, $label);
				}
			}
		}
	}

	/**
	 * Creates the submenu links.
	 *
	 * @param   string       $view    The name of the view.
	 * @param   string|null  $parent  The parent element (referenced by name)) This will create a dropdown list
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function addSubmenuLink($view, $parent = null)
	{
		static $activeView = null;

		if (empty($activeView))
		{
			$activeView = $this->input->getCmd('view', 'cpanel');
		}

		if ($activeView == 'cpanels')
		{
			$activeView = 'cpanel';
		}

		$key = strtoupper($this->component) . '_TITLE_' . strtoupper($view);

		if (strtoupper(JText::_($key)) == $key)
		{
			$altview = FOFInflector::isPlural($view) ? FOFInflector::singularize($view) : FOFInflector::pluralize($view);
			$key2 = strtoupper($this->component) . '_TITLE_' . strtoupper($altview);

			if (strtoupper(JText::_($key2)) == $key2)
			{
				$name = ucfirst($view);
			}
			else
			{
				$name = JText::_($key2);
			}
		}
		else
		{
			$name = JText::_($key);
		}

		$link = 'index.php?option=' . $this->component . '&view=' . $view;

		$active = $view == $activeView;

		$this->appendLink($name, $link, $active, null, $parent);
	}

	/**
	 * Creates the About toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onAboutsDetail()
	{
		$this->renderSubmenu();
		$this->toolbarTitle('info');
		JToolBarHelper::custom('fix', 'refresh', 'refresh', 'COM_CSVI_TOOLBAR_DATABASE_FIX', false, false);
	}

	/**
	 * Creates the Export toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onExportsExport()
	{
		$this->onExportsRead('download');
	}

	/**
	 * Creates the Export toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onExportsDetail()
	{
		$this->renderSubmenu();
		$this->toolbarTitle('download');
		JToolBarHelper::custom('start', 'download', 'download', JText::_('COM_CSVI_EXPORT'), false);
	}

	/**
	 * Creates the Export start toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onExportsStart()
	{
		$this->toolbarTitle('download');
		JToolBarHelper::cancel();
	}

	/**
	 * Creates the Import toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onImportsDetail()
	{
		$this->renderSubmenu();
		$this->toolbarTitle('upload');
		JToolBarHelper::custom('selectsource', 'upload', 'upload', JText::_('COM_CSVI_SELECT_IMPORTFILE'), false);
	}

	/**
	 * Creates the Import start toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onImportsStart()
	{
		$this->toolbarTitle('upload');
		JToolBarHelper::cancel();
	}

	/**
	 * Creates the Import sources toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onImportsourcesDetail()
	{
		$jinput = JFactory::getApplication()->input;
		$this->toolbarTitle('upload');
		JToolBarHelper::cancel();
		JToolBarHelper::divider();
		JToolBarHelper::custom('preview', 'eye-open', 'eye-open', JText::_('COM_CSVI_PREVIEW'), false);
	}

	/**
	 * Creates the Import preview toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onImportpreviewsDetail()
	{
		$jinput = JFactory::getApplication()->input;
		$this->toolbarTitle('upload');
		JToolBarHelper::cancel();
		JToolBarHelper::divider();
		JToolBarHelper::custom('start', 'play', 'play', JText::_('COM_CSVI_START_IMPORT'), false);
	}

	/**
	 * Creates the Analyzers toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onAnalyzersDetail()
	{
		$this->renderSubmenu();
		$this->toolbarTitle('health');
		JToolBarHelper::custom('add', 'health', 'health', JText::_('COM_CSVI_ANALYZE'), false);
	}

	/**
	 * Creates the Available fields list toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onAvailablefieldsBrowse()
	{
		$this->renderSubmenu();
		$this->toolbarTitle('grid-view-2');
		JToolBarHelper::custom('updateavailablefields', 'refresh', 'refresh', JText::_('COM_CSVI_UPDATE'), false);
	}

	/**
	 * Creates the Maintenance list toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onMaintenancesDetail()
	{
		$this->renderSubmenu();
		$this->toolbarTitle('tools');

		// Show the toolbar
		JToolBarHelper::custom('read', 'arrow-right', 'arrow-right', JText::_('COM_CSVI_CONTINUE'), false);
		JToolBarHelper::divider();
		JToolBarHelper::custom('hidetips', 'help', 'help', JText::_('COM_CSVI_HELP'), false);
	}

	/**
	 * Creates the Maintenance operation toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onMaintenancesRead()
	{
		$this->toolbarTitle('tools');
		JToolBarHelper::custom('canceloperation', 'cancel', 'cancel', JText::_('COM_CSVI_CANCEL'), false);
	}

	/**
	 * Creates the Tasks list toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onTasksBrowse()
	{
		parent::onBrowse();
		$this->toolbarTitle('list-view');
		JToolBarHelper::custom('reload', 'refresh', 'refresh', JText::_('COM_CSVI_RESET_SETTINGS'), false);
	}

	/**
	 * Creates the Tasks edit toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onTasksAdd()
	{
		parent::onAdd();
		$this->toolbarTitle('list-view', 'add');
		JToolBarHelper::divider();
		JToolBarHelper::custom('hidetips', 'help', 'help', JText::_('COM_CSVI_HELP'), false);
	}

	/**
	 * Creates the Tasks edit toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onTasksEdit()
	{
		parent::onAdd();
		$this->toolbarTitle('list-view', 'edit');
		JToolBarHelper::divider();
		JToolBarHelper::custom('hidetips', 'help', 'help', JText::_('COM_CSVI_HELP'), false);
	}

	/**
	 * Creates the Logs list toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onLogsBrowse()
	{
		$this->renderSubmenu();
		$this->toolbarTitle('clock');
		JToolBarHelper::custom('logdetails', 'zoom-in', 'zoom-in', JText::_('COM_CSVI_DETAILS'), true);
		JToolBarHelper::custom('remove', 'trash', 'trash', JText::_('COM_CSVI_DELETE'), true);
		JToolBarHelper::custom('remove_all', 'trash', 'trash', JText::_('COM_CSVI_DELETE_ALL'), false);
	}

	/**
	 * Creates the Log details toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onLogdetailsBrowse()
	{
		$this->renderSubmenu();
		$this->toolbarTitle('lamp');
		JToolBarHelper::custom('cancel', 'arrow-left', 'arrow-left', JText::_('COM_CSVI_BACK'), false);
	}

	/**
	 * Creates the Maps toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onMapsBrowse()
	{
		$this->renderSubmenu();
		$this->toolbarTitle('shuffle');

		// Add toolbar buttons
		if ($this->perms->create)
		{
			JToolBarHelper::addNew();
		}

		if ($this->perms->edit)
		{
			JToolBarHelper::editList();
		}

		if ($this->perms->create || $this->perms->edit)
		{
			JToolBarHelper::divider();
		}

		if ($this->perms->delete)
		{
			$msg = JText::_($this->input->getCmd('option', 'com_foobar') . '_CONFIRM_DELETE');
			JToolBarHelper::deleteList($msg);
		}
	}

	/**
	 * Creates the Maps edit toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onMapsEdit()
	{
		parent::onAdd();
		$this->toolbarTitle('shuffle');
		JToolBarHelper::divider();
		JToolBarHelper::custom('hidetips', 'help', 'help', JText::_('COM_CSVI_HELP'), false);
	}

	/**
	 * Creates the Settings toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onSettingsEdit()
	{
		$this->renderSubmenu();
		$this->toolbarTitle('wrench');
		JToolBarHelper::custom('reset', 'refresh', 'refresh', JText::_('COM_CSVI_RESET_SETTINGS'), false);
		JToolBarHelper::apply('save');
		JToolBarHelper::divider();
		JToolBarHelper::custom('hidetips', 'help', 'help', JText::_('COM_CSVI_HELP'), false);
	}

	/**
	 * Creates the Templates list toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onTemplatesBrowse()
	{
		parent::onBrowse();
		$this->toolbarTitle('folder');
		JToolBarHelper::custom('templatefields', 'list', 'list', JText::_('COM_CSVI_FIELDS'));
		JToolBarHelper::custom('save2copy', 'save-copy', 'save-copy', JText::_('COM_CSVI_COPY'));
		JToolBarHelper::divider();
	}

	/**
	 * Creates the Templates add toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onTemplatesAdd()
	{
		$this->toolbarTitle('folder');

		// Set toolbar icons
		if ($this->perms->edit || $this->perms->editown)
		{
			// Show the apply button only if I can edit the record, otherwise I'll return to the edit form and get a
			// 403 error since I can't do that
			JToolBarHelper::apply();
		}

		JToolBarHelper::save();
		JToolBarHelper::divider();
		JToolBarHelper::custom('hidetips', 'help', 'help', JText::_('COM_CSVI_HELP'), false);
		JToolBarHelper::custom('advanceduser', 'flash', 'flash', JText::_('COM_CSVI_ADVANCEDUSER'), false);
	}

	/**
	 * Creates the Templates edit toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onTemplatesEdit()
	{
		$this->onTemplatesAdd();
	}

	/**
	 * Creates the Tasks list toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onRulesBrowse()
	{
		parent::onBrowse();
		$this->toolbarTitle('list-view');
	}

	/**
	 * Creates the Rules add toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onRulesAdd()
	{
		$this->toolbarTitle();
		parent::onAdd();
		JToolBarHelper::divider();
		JToolBarHelper::custom('hidetips', 'help', 'help', JText::_('COM_CSVI_HELP'), false);
	}

	/**
	 * Creates the Rules edit toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onRulesEdit()
	{
		$this->toolbarTitle();
		parent::onAdd();
		JToolBarHelper::divider();
		JToolBarHelper::custom('hidetips', 'help', 'help', JText::_('COM_CSVI_HELP'), false);
	}

	/**
	 * Creates the Tasks list toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onProcessesBrowse()
	{
		$this->renderSubmenu();
		$this->toolbarTitle('cogs');

		if ($this->perms->delete)
		{
			$msg = JText::_($this->input->getCmd('option', 'com_foobar') . '_CONFIRM_DELETE');
			JToolBarHelper::deleteList($msg);
		}
	}

	/**
	 * Creates the Template fields toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onTemplateFieldsBrowse()
	{
		parent::onBrowse();
		$this->toolbarTitle('list');
		JToolBarHelper::custom('quickadd', 'list', 'list', JText::_('COM_CSVI_QUICKADD'), false);
	}

	/**
	 * Creates the Template Fields edit toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onTemplateFieldsAdd()
	{
		$this->toolbarTitle();
		parent::onAdd();
		JToolBarHelper::divider();
		JToolBarHelper::custom('hidetips', 'help', 'help', JText::_('COM_CSVI_HELP'), false);
	}

	/**
	 * Creates the Template Fields edit toolbar.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onTemplateFieldsEdit()
	{
		$this->onTemplateFieldsAdd();
	}

	/**
	 * Creates the toolbar title.
	 *
	 * @param   string  $icon  The icon to use for the title
	 * @param   string  $task  The task of the page
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function toolbarTitle($icon = '', $task= '')
	{
		// Set toolbar title
		$option = $this->input->getCmd('option', 'com_csvi');

		if (empty($icon))
		{
			$view = $this->input->getCmd('view', '');
			$icon = str_replace('com_', '', $option) . '-' . $view . '-48';
		}

		if ($task)
		{
			$task = '_' . $task;
		}

		$subtitle_key = strtoupper($option . '_TITLE_' . $this->input->getCmd('view', 'cpanel') . $task);
		JToolBarHelper::title(JText::_(strtoupper($option)) . ' - ' . JText::_($subtitle_key), $icon);
	}
}
