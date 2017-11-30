<?php
/**
 * @package     CSVI
 * @subpackage  File
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * ODS file processor class.
 *
 * @package     CSVI
 * @subpackage  File
 * @since       6.0
 */
class CsviHelperFileImportOds extends CsviHelperFile
{
	/**
	 * The ODS parser
	 *
	 * @var    ODSParser
	 * @since  6.0
	 */
	public $data = null;

	/**
	 * Sets if the ODS file has been unpacked
	 *
	 * @var    bool
	 * @since  6.0
	 */
	private $unpacked = false;

	/**
	 * Open the file to read.
	 *
	 * @return   bool  True if file can be read | False if cannot be read.
	 *
	 * @since   3.0
	 */
	public function openFile()
	{
		// Include the ODS reader
		require_once JPATH_ADMINISTRATOR . '/components/com_csvi/helper/file/import/ods_reader.php';

		$this->fp = true;
		$this->linepointer = 1;
		$parser = new ODSParser;

		if (!$this->unpacked)
		{
			jimport('joomla.filesystem.file');
			jimport('joomla.filesystem.archive');
			$jinput = JFactory::getApplication()->input;
			$csvilog = $jinput->get('csvilog', null, null);

			// First we need to unpack the zipfile
			$unpackfile = $this->unpackpath . '/ods/' . basename($this->filename) . '.zip';
			$importfile = $this->unpackpath . '/ods/content.xml';

			// Check the unpack folder
			JFolder::create($this->unpackpath . '/ods');

			// Delete the destination file if it already exists
			if (JFile::exists($unpackfile))
			{
				JFile::delete($unpackfile);
			}

			if (JFile::exists($importfile))
			{
				JFile::delete($importfile);
			}

			// Now copy the file to the folder
			JFile::copy($this->filename, $unpackfile);

			// Extract the files in the folder
			if (!JArchive::extract($unpackfile, $this->unpackpath . '/ods'))
			{
				$csvilog->addStats('incorrect', JText::_('COM_CSVI_CANNOT_UNPACK_ODS_FILE'));

				return false;
			}

			// File is always called content.xml
			$this->filename = $importfile;

			// Set the unpacked to true as we have unpacked the file
			$this->unpacked = true;
		}
		else
		{
			$this->filename = $this->unpackpath . '/ods/content.xml';
		}

		// Read the data to process
		if (!$parser->read($this->filename))
		{
			return false;
		}

		// Load the data
		$this->data = $parser->getData();

		return true;
	}

	/**
	 * Load the column headers from a file.
	 *
	 * @return  bool  Always return true.
	 *
	 * @since   3.0
	 */
	public function loadColumnHeaders()
	{
		$jinput = JFactory::getApplication()->input;
		$jinput->set('columnheaders', $this->data[1]);
		$this->linepointer++;

		return true;
	}

	/**
	 * Get the file position.
	 *
	 * @return  int	current position in the file.
	 *
	 * @since   3.0
	 */
	public function getFilePos()
	{
		return $this->linepointer;
	}

	/**
	 * Set the file position.
	 *
	 * @param   int  $pos  The position to move to
	 *
	 * @return  int  current position in the file.
	 *
	 * @since   3.0
	 */
	public function setFilePos($pos)
	{
		$this->linepointer = $pos;

		return $this->linepointer;
	}

	/**
	 * Read the next line in the file.
	 *
	 * @return  bool True if data read | false if data cannot be read.
	 *
	 * @since   3.0
	 */
	public function readNextLine()
	{
		if ($this->lineCount() >= $this->linepointer)
		{
			$jinput = JFactory::getApplication()->input;
			$csvifields = $jinput->get('csvifields', null, null);
			$columnheaders = $csvifields->getAllFieldnames('import');

			$newdata = $this->data[$this->linepointer];
			$this->linepointer++;

			// Add the data to the fields
			foreach ($newdata as $key => $value)
			{
				$csvifields->set($columnheaders[$key], $value);
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Process the file to import.
	 *
	 * @return  bool True if file can be processed.
	 *
	 * @since   3.0
	 */
	public function processFile()
	{
		// Open the file
		$this->openFile();

		// All good return true
		return true;
	}

	/**
	 * Sets the file pointer back to beginning.
	 *
	 * @return  void.
	 *
	 * @since   3.0
	 */
	public function rewind()
	{
		// Set the line pointer to 1 as that is the first entry in the data array
		$this->setFilePos(1);
	}

	/**
	 * Return the number of lines in a XLS file.
	 *
	 * @return  int	the number of lines in the XLS file.
	 *
	 * @since   6.0
	 */
	public function lineCount()
	{
		return count($this->data);
	}
}
