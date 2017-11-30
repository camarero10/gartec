<?php
/**
 * @package     CSVI
 * @subpackage  Install
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

// Load FOF if not already loaded
if (!defined('FOF_INCLUDED'))
{
	$paths = array(
		(defined('JPATH_LIBRARIES') ? JPATH_LIBRARIES : JPATH_ROOT . '/libraries') . '/fof/include.php',
		__DIR__ . '/fof/include.php',
	);

	foreach ($paths as $filePath)
	{
		if (!defined('FOF_INCLUDED') && file_exists($filePath))
		{
			@include_once $filePath;
		}
	}
}

// Need to check if a Joomla version lower than 3.4 is used, if so we need to copy some FOF helpers
if (version_compare(JVERSION, '3.4', 'lt'))
{
	$source = __DIR__ . '/fof';

	if (file_exists($source))
	{
		$dest = JPATH_LIBRARIES . '/fof';
		JFolder::copy($source, $dest, '', true);
	}
}

// Pre-load the installer script class from our own copy of FOF
if (!class_exists('FOFUtilsInstallscript', false))
{
	@include_once __DIR__ . '/fof/utils/installscript/installscript.php';
}
// Pre-load the database schema installer class from our own copy of FOF
if (!class_exists('FOFDatabaseInstaller', false))
{
	@include_once __DIR__ . '/fof/database/installer.php';
}
// Pre-load the update utility class from our own copy of FOF
if (!class_exists('FOFUtilsUpdate', false))
{
	@include_once __DIR__ . '/fof/utils/update/update.php';
}
// Pre-load the cache cleaner utility class from our own copy of FOF
if (!class_exists('FOFUtilsCacheCleaner', false))
{
	@include_once __DIR__ . '/fof/utils/cache/cleaner.php';
}

/**
 * Script to run on installation of CSVI.
 *
 * @package     CSVI
 * @subpackage  Install
 * @since       6.0
 */
class Com_CsviInstallerScript extends FOFUtilsInstallscript
{
	/**
	 * The component's name
	 *
	 * @var string
	 */
	protected $componentName = 'com_csvi';

	/**
	 * The title of the component (printed on installation and uninstallation messages)
	 *
	 * @var string
	 */
	protected $componentTitle = 'CSVI Pro';

	/**
	 * The list of extra modules and plugins to install on component installation / update and remove on component
	 * uninstallation.
	 *
	 * @var   array
	 */
	protected $installation_queue = array(
		'modules' => array(
			'admin' => array(),
			'site'  => array()
		),
		'plugins' => array(
			'csviaddon' => array(
				'categories' => true,
				'content' => true,
				'csvi' => true,
				'users' => true
			),
			'csvirules' => array(
				'fieldcombine' => true,
				'fieldcopy' => true,
				'margin' => true,
				'replace' => true
			)
		)
	);

	/**
	 * The minimum PHP version required to install this extension
	 *
	 * @var   string
	 */
	protected $minimumPHPVersion = '5.4';

	/**
	 * The minimum PHP version required to install this extension
	 *
	 * @var   string
	 */
	protected $minimumJoomlaVersion = '3.4.6';

	/**
	 * Method to install the component
	 *
	 * @param   string  $type    Installation type (install, update, discover_install)
	 * @param   object  $parent  The parent calling class
	 *
	 * @return void
	 *
	 * @since   6.0
	 */
	public function preflight($type, $parent)
	{
		$db = JFactory::getDbo();
		$tables = $db->getTableList();
		$table = $db->getPrefix() . 'csvi_settings';

		if (in_array($table, $tables))
		{
			// Make sure the column has been renamed
			$columns = $db->getTableColumns($table);

			if (array_key_exists('id', $columns))
			{
				// User removed CSVI before installing, need to run the update scripts
				$files = JFolder::files(__DIR__ . '/admin/sql/updates/mysql', '\.sql$', 1, true, array('.svn', 'CVS', '.DS_Store', '__MACOSX'), array('^\..*', '.*~'), true);

				foreach ($files as $filename)
				{
					$queries = $db->splitSql(file_get_contents($filename));

					foreach ($queries as $query)
					{
						$query = trim($query);

						if ($query)
						{
							try
							{
								$db->setQuery($query)->execute();
							}
							catch (Exception $e)
							{
								JFactory::getApplication()->enqueueMessage($e->getMessage());
							}
						}
					}
				}
			}

			$db->setQuery(
				"INSERT IGNORE INTO " . $db->quoteName('#__csvi_settings') .
				" (" . $db->quoteName('csvi_setting_id') . ", " . $db->quoteName('params') . ") VALUES (1, '');");
			$db->execute();
		}
	}

	/**
	 * Method to run after an install/update/uninstall method
	 *
	 * @param   string  $type    The type of installation being done
	 * @param   object  $parent  The parent calling class
	 *
	 * @return void
	 *
	 * @since   6.0
	 */
	public function postflight($type, $parent)
	{
		parent::postflight($type, $parent);

		FOFPlatform::getInstance()->clearCache();

		// All Joomla loaded, set our exception handler
		require_once JPATH_BASE . '/components/com_csvi/rantai/error/exception.php';

		// Load the default classes
		require_once JPATH_ADMINISTRATOR . '/components/com_csvi/controllers/default.php';
		require_once JPATH_ADMINISTRATOR . '/components/com_csvi/models/default.php';

		// Setup the autoloader
		JLoader::registerPrefix('Csvi', JPATH_ADMINISTRATOR . '/components/com_csvi');

		// Remove any remaining language files
		if (file_exists(JPATH_ADMINISTRATOR . '/language/en-GB/en-GB.com_csvi.ini'))
		{
			JFile::delete(JPATH_ADMINISTRATOR . '/language/en-GB/en-GB.com_csvi.ini');
		}

		if (file_exists(JPATH_ADMINISTRATOR . '/language/en-GB/en-GB.com_csvi.sys.ini'))
		{
			JFile::delete(JPATH_ADMINISTRATOR . '/language/en-GB/en-GB.com_csvi.sys.ini');
		}

		// Load language files
		$jlang = JFactory::getLanguage();
		$jlang->load('com_csvi', JPATH_ADMINISTRATOR . '/components/com_csvi/', 'en-GB', true);
		$jlang->load('com_csvi', JPATH_ADMINISTRATOR . '/components/com_csvi/', $jlang->getDefault(), true);
		$jlang->load('com_csvi', JPATH_ADMINISTRATOR . '/components/com_csvi/', null, true);

		// Convert any pre version 6 templates if needed
		$this->convertTemplates();

		// Load the tasks
		$tasksModel = FOFModel::getTmpInstance('Tasks', 'CsviModel');

		if ($tasksModel->reload())
		{
			// Load the model
			$model = FOFModel::getTmpInstance('Maintenances', 'CsviModel');

			$key = 0;
			$continue = true;

			// Update the available fields
			while ($continue)
			{
				$result = $model->runOperation('com_csvi', 'updateavailablefields', $key);

				$this->results['messages'][] = $result['info'];
				$key = $result['key'];
				$continue = $result['continue'];
			}
		}
	}

	/**
	 * Convert old templates to the new CSVI 6 format.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function convertTemplates()
	{
		$db = JFactory::getDbo();

		// Load all the existing templates
		$query = $db->getQuery(true)
			->select(
				array(
					$db->quoteName('csvi_template_id'),
					$db->quoteName('settings'),
				)
			)
			->from($db->quoteName('#__csvi_templates'));
		$db->setQuery($query);
		$templates = $db->loadObjectList('csvi_template_id');

		foreach ($templates as $csvi_template_id => $template)
		{
			// Check if the template is in the old format
			if (substr($template->settings, 0, 9) == '{"options')
			{
				// Get the old data format
				$oldformat = json_decode($template->settings);

				// Store everything in the new format
				$newformat = array();

				foreach ($oldformat as $section => $settings)
				{
					$newformat = array_merge($newformat, (array) $settings);
				}

				// Perform some extra changes
				if (isset($newformat['operation']))
				{
					$newformat['operation'] = str_replace(array('import', 'export'), '', $newformat['operation']);
				}

				if (isset($newformat['exportto']))
				{
					$newformat['exportto'] = array($newformat['exportto']);
				}

				// Store the new template format
				$query->clear()
					->update($db->quoteName('#__csvi_templates'))
					->set($db->quoteName('settings') . ' = ' . $db->quote(json_encode($newformat)))
					->where($db->quoteName('csvi_template_id') . ' = ' . (int) $csvi_template_id);
				$db->setQuery($query)->execute();
			}
		}
	}
}
