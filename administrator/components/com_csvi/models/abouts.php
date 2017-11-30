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
 * About model.
 *
 * @package     CSVI
 * @subpackage  About
 * @since       6.0
 */
class CsviModelAbouts extends FOFModel
{
	/**
	 * Check folder permissions.
	 *
	 * @return  array  Folders and their permissions.
	 *
	 * @since   2.3.10
	 */
	public function getFolderCheck()
	{
		jimport('joomla.filesystem.folder');
		$config = JFactory::getConfig();
		$tmp_path = JPath::clean($config->get('tmp_path'), '/');
		$folders = array();

		// Check the tmp/ path
		@touch($tmp_path . '/about.txt');
		$folders[$tmp_path] = is_writable($tmp_path . '/about.txt');
		@unlink($tmp_path . '/about.txt');

		// Check the tmp/com_csvi path
		@touch(CSVIPATH_TMP . '/about.txt');
		$folders[CSVIPATH_TMP] = is_writable(CSVIPATH_TMP . '/about.txt');
		@unlink(CSVIPATH_TMP . '/about.txt');

		// Check the tmp/com_csvi/export path
		@touch(CSVIPATH_TMP . '/export/about.txt');
		$folders[CSVIPATH_TMP . '/export'] = is_writable(CSVIPATH_TMP . '/export/about.txt');
		@unlink(CSVIPATH_TMP . '/export/about.txt');

		// Check the log path
		@touch(CSVIPATH_DEBUG . '/about.txt');
		$folders[CSVIPATH_DEBUG] = is_writable(CSVIPATH_DEBUG . '/about.txt');
		@unlink(CSVIPATH_DEBUG . '/about.txt');

		return $folders;
	}

	/**
	 * Create missing folders.
	 *
	 * @return  array  Result and result text for folder fix operation.
	 *
	 * @since   3.0
	 */
	public function fixFolder()
	{
		$jinput = JFactory::getApplication()->input;
		jimport('joomla.filesystem.folder');
		$result = false;

		// Get the folder name
		$folder = $jinput->get('folder', '', 'string');

		// Check if the folder exists
		if (!is_dir($folder))
		{
			// Try to create the folder
			JFolder::create($folder);

			$result = array(
				'result' => 'false',
				'resultText' => JText::sprintf('COM_CSVI_ABOUT_FOLDER_DOESNT_EXIST', $folder)
			);

			// Check if the folder exists now
			if (!is_dir($folder))
			{
				$result = array(
					'result' => 'false',
					'resultText' => JText::sprintf('COM_CSVI_ABOUT_FOLDER_CANNOT_CREATE', $folder));
			}
			else
			{
				$result = false;
			}
		}

		if (!$result)
		{
			// Check if the folder is writable
			@touch($folder . '/about.txt');

			if (!is_writable($folder . '/about.txt'))
			{
				$result = array(
					'result' => 'false',
					'resultText' => JText::sprintf('COM_CSVI_ABOUT_FOLDER_CANNOT_WRITE', $folder)
				);

				if (!@chmod($folder, '0755'))
				{
					$result = array(
						'result' => 'false',
						'resultText' => JText::sprintf('COM_CSVI_ABOUT_FOLDER_CANNOT_MAKE_WRITABLE', $folder)
					);
				}
			}

			// Remove the test file
			@unlink($folder . '/about.txt');

			if (!$result)
			{
				$result = array('result' => 'true');
			}
		}

		return $result;
	}

	/**
	 * Get database changeset.
	 *
	 * @return  Changeset  A Changeset class.
	 *
	 * @since   5.6
	 */
	public function getChangeSet()
	{
		$folder = JPATH_ADMINISTRATOR . '/components/com_csvi/sql/updates/';
		$changeSet = JSchemaChangeset::getInstance(JFactory::getDbo(), $folder);

		return $changeSet;
	}

	/**
	 * Get version from #__schemas table.
	 *
	 * @return  mixed  The return value from the query, or null if the query fails.
	 *
	 * @since   5.6
	 */
	public function getSchemaVersion()
	{
		$db = JFactory::getDbo();
		$version = false;

		// Get the extension id first
		$query = $db->getQuery(true);
		$query->select($db->quoteName('extension_id'))
			->from($db->quoteName('#__extensions'))
			->where($db->quoteName('type') . ' = ' . $db->quote('component'))
			->where($db->quoteName('element') . ' = ' . $db->quote('com_csvi'));
		$db->setQuery($query);
		$eid = $db->loadResult();

		if ($eid)
		{
			// Check if there is a version in the schemas table
			$query->clear()
				->select($db->quoteName('version_id'))
				->from($db->quoteName('#__schemas'))
				->where($db->quoteName('extension_id') . ' = ' . (int) $eid);
			$db->setQuery($query);
			$version = $db->loadResult();
		}

		return $version;
	}

	/**
	 * Fix database inconsistencies.
	 *
	 * @return  bool  Returns true.
	 *
	 * @since   5.7
	 */
	public function fix()
	{
		$changeSet = $this->getChangeSet();
		$changeSet->fix();

		return true;
	}
}
