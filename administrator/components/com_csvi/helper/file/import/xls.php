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
 * XLS file processor class.
 *
 * @package     CSVI
 * @subpackage  File
 * @since       6.0
 */
class CsviHelperFileImportXls extends CsviHelperFile
{
	/**
	 * Open the file to read.
	 *
	 * @return   bool  Always returns true.
	 *
	 * @since   3.0
	 */
	public function openFile()
	{
		$this->fp = true;

		// Include the XLS reader
		require_once JPATH_ADMINISTRATOR . '/components/com_csvi/helper/file/import/excel_reader2.php';

		$this->data = new Spreadsheet_Excel_Reader($this->filename, false);
		$this->data = $this->data->sheets;

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

		// Make sure we include the empty fields
		for ($i = 1; $i <= $this->data[0]['numCols']; $i++)
		{
			if (!isset($this->data[0]['cells'][1]))
			{
				$this->data[0]['cells'][1][$i] = '';
			}
		}

		$headers = array_values($this->data[0]['cells'][1]);
		$jinput->set('columnheaders', $headers);
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
		if ($this->data[0]['numRows'] >= $this->linepointer)
		{
			$columnheaders = $this->fields->getAllFieldnames('import');
			$newdata = array();

			// Make sure we include the empty fields
			for ($i = 1; $i <= $this->data[0]['numCols']; $i++)
			{
				if (!isset($this->data[0]['cells'][$this->linepointer][$i]))
				{
					$newdata[] = '';
				}
				else
				{
					$newdata[] = $this->data[0]['cells'][$this->linepointer][$i];
				}
			}

			$this->linepointer++;

			// Add the data to the fields
			foreach ($newdata as $key => $value)
			{
				if (isset($columnheaders[$key]))
				{
					$this->fields->set($columnheaders[$key], $value);
				}
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
		return $this->data[0]['numRows'];
	}
}
