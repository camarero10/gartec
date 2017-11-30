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

defined('_JEXEC') or die();

/**
 * CSV file importer.
 *
 * @package     CSVI
 * @subpackage  File
 * @since       6.0
 */
class CsviHelperFileImportCsv extends CsviHelperFile
{
	/**
	 * Contains the field delimiter
	 *
	 * @var    string
	 * @since  6.0
	 */
	private $field_delimiter = null;

	/**
	 * Contains the text enclosure
	 *
	 * @var    string
	 * @since  6.0
	 */
	private $text_enclosure = null;

	/**
	 * Sets to true if a file delimiters have been checked
	 *
	 * @var    bool
	 * @since  6.0
	 */
	private $checked_delimiter = false;

	/**
	 * The file pointer position in the file
	 *
	 * @var    int
	 * @since  6.0
	 */
	private $pointer = 0;

	/**
	 * The fields handler
	 *
	 * @var    CsviHelperImportFields
	 * @since  6.0
	 */
	protected $fields = null;

	/**
	 * Open the file to read.
	 *
	 * @return  bool  Return true if file can be opened | False if file cannot be opened.
	 *
	 * @since   6.0
	 */
	public function openFile()
	{
		// Open the csv file
		if (file_exists($this->filename))
		{
			if ($this->fp = fopen($this->filename, "r"))
			{
				$this->closed = false;

				return true;
			}
		}

		return false;
	}

	/**
	 * Close the file.
	 *
	 * @param   bool  $removefolder  Specify if the temporary folder should be removed
	 *
	 * @return  void.
	 *
	 * @since   3.0
	 */
	public function closeFile($removefolder = true)
	{
		fclose($this->fp);
		$this->closed = true;

		parent::closeFile($removefolder);
	}

	/**
	 * Get the file position.
	 *
	 * @return  int  The current position in the file.
	 *
	 * @since   3.0
	 */
	public function getFilePos()
	{
		return $this->pointer;
	}

	/**
	 * Set the file position.
	 *
	 * @param   int  $position  The position to move to
	 *
	 * @return  int   0 if success | -1 if not success.
	 *
	 * @since   3.0
	 */
	public function setFilePos($position)
	{
		if (!$this->fp)
		{
			$this->openFile();
		}

		$result = fseek($this->fp, $position);

		if ($result === 0)
		{
			$this->pointer = $position;
		}

		return $result;
	}

	/**
	 * Load the column headers from a file.
	 *
	 * @return   mixed    array when column headers are found | false if column headers cannot be read.
	 *
	 * @since   3.0
	 */
	public function loadColumnHeaders()
	{
		// Column headers are always the first line of the file
		// 1. Store current position
		$curpos = $this->getFilePos();

		if ($curpos > 0)
		{
			// 2. Go to the beginning of the file
			$this->setFilePos(0);
		}

		// 3. Read the line
		$columnheaders = $this->ReadNextLine(true);

		if ($curpos > 0)
		{
			// 4. Set the position back
			$this->setFilePos($curpos);
		}

		$this->linepointer++;

		return $columnheaders;
	}

	/**
	 * Read the next line in the file.
	 *
	 * @param   bool  $headers  Set if the column headers are being read.
	 *
	 * @return  array  Array with the line of data read | false if data cannot be read.
	 *
	 * @since   3.0
	 */
	public function readNextLine($headers = false)
	{
		// Check if the file is still open
		if ($this->closed || feof($this->fp))
		{
			return false;
		}

		// Make sure we have delimiters
		if (is_null($this->field_delimiter))
		{
			return false;
		}

		// Load some settings
		$columnheaders = $this->fields->getAllFieldnames();

		// Check for a valid field delimiter
		if (!empty($this->field_delimiter))
		{
			// Ignore empty records
			$csvdata = array(0 => '');

			while (is_array($csvdata) && count($csvdata) == 1 && $csvdata[0] == '')
			{
				if (!is_null($this->text_enclosure))
				{
					$csvdata = fgetcsv($this->fp, 0, $this->field_delimiter, $this->text_enclosure);
				}
				else
				{
					$csvdata = fgetcsv($this->fp, 0, $this->field_delimiter);
				}
			}

			// We read data, set the file pointer
			$this->pointer = ftell($this->fp);

			// Check if we can read the line correctly
			if (count($csvdata) == 1 && !$this->checked_delimiter)
			{
				$current_field = $this->field_delimiter;
				$current_text = $this->text_enclosure;
				$this->findDelimiters(true);

				if ($current_field != $this->field_delimiter)
				{
					$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_UNEQUAL_FIELD_DELIMITER', $current_field, $this->field_delimiter));
				}

				if ($current_text != $this->text_enclosure)
				{
					$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_UNEQUAL_FIELD_DELIMITER', $current_field, $this->field_delimiter));
				}

				$this->field_delimiter = $current_field;
				$this->text_enclosure = $current_text;
			}

			if ($csvdata)
			{
				// Do BOM check
				if ($this->input->get('currentline', 0, 'int') == 1 || is_null($this->input->get('currentline', null, null)))
				{
					// Remove text delimiters as they are not recognized by fgetcsv
					$csvdata[0] = $this->removeTextDelimiters($this->checkBom($csvdata[0]));
				}

				$this->linepointer++;

				if ($headers)
				{
					return $csvdata;
				}
				else
				{
					// Add the data to the fields
					foreach ($csvdata as $key => $value)
					{
						if (isset($columnheaders[$key]))
						{
							$this->fields->set($columnheaders[$key], $value);
						}
					}

					return true;
				}
			}
			else
			{
				return false;
			}
		}
		else
		{
			$this->log->addStats('incorrect', 'COM_CSVI_NO_FIELD_DELIMITER_FOUND');

			return false;
		}
	}

	/**
	 * Process the file to import.
	 *
	 * @return  bool  True if file can be processed | False if file cannot be processed.
	 *
	 * @since   3.0
	 */
	public function processFile()
	{
		// Open the file
		if ($this->openFile())
		{
			// Load the delimiters
			$this->findDelimiters();

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Find the delimiters used.
	 *
	 * @param   bool  $force  Force to read the delimiters from the imported file.
	 *
	 * @return  bool  True if delimiters found | False if delimiters not found.
	 *
	 * @throws  Exception
	 *
	 * @since   3.0
	 */
	private function findDelimiters($force = false)
	{
		if (!$this->checked_delimiter)
		{
			if (!$this->template->get('auto_detect_delimiters', true) && !$force)
			{
				// Set the field delimiter
				if (strtolower($this->template->get('field_delimiter')) == 't')
				{
					$this->field_delimiter = "\t";
				}
				else
				{
					$this->field_delimiter = $this->template->get('field_delimiter');
				}

				// Set the text enclosure
				$this->text_enclosure = ($this->template->get('text_enclosure', '')) ? $this->template->get('text_enclosure') : null;
			}
			else
			{
				// Read the first line
				rewind($this->fp);
				$line = fgets($this->fp);

				// 1. Is the user using text enclosures
				$first_char = substr($line, 0, 1);
				$pattern = '/[a-zA-Z0-9_]/';
				$matches = array();
				preg_match($pattern, $first_char, $matches);

				if (count($matches) == 0)
				{
					// User is using text delimiter
					$this->text_enclosure = $first_char;
					$this->log->add(JText::sprintf('COM_CSVI_FOUND_TEXT_ENCLOSURE', $first_char), false);

					// 2. What field delimiter is being used
					if (strlen($line) > 1)
					{
						$match_next_char = strpos($line, $this->text_enclosure, 1);
						$second_char = substr($line, $match_next_char + 1, 1);
					}
					else
					{
						$second_char = $first_char;
					}

					if ($first_char == $second_char)
					{
						throw new Exception(JText::_('COM_CSVI_CANNOT_FIND_TEXT_DELIMITER'), false);
					}
					else
					{
						$this->field_delimiter = $second_char;
					}
				}
				else
				{
					$totalchars = strlen($line);

					// 2. What field delimiter is being used
					for ($i = 0; $i <= $totalchars; $i++)
					{
						$current_char = substr($line, $i, 1);
						preg_match($pattern, $current_char, $matches);

						if (count($matches) == 0)
						{
							$this->field_delimiter = $current_char;
							$i = $totalchars;
						}
					}
				}

				$this->log->add(JText::sprintf('COM_CSVI_FOUND_FIELD_DELIMITER', $this->field_delimiter), false);

				rewind($this->fp);
			}

			$this->checked_delimiter = true;
		}

		return true;
	}

	/**
	 * Checks if the uploaded file has a BOM.
	 *
	 * If the uploaded file has a BOM, remove it since it only causes
	 * problems on import.
	 *
	 * @param   string  $data  The string to check for a BOM
	 *
	 * @return  string  Return the cleaned string.
	 *
	 * @since   3.0
	 */
	private function checkBom($data)
	{
		// Check the first three characters
		if (strlen($data) > 3)
		{
			if (ord($data{0}) == 239 && ord($data{1}) == 187 && ord($data{2}) == 191)
			{
				return substr($data, 3, strlen($data));
			}
			else
			{
				return $data;
			}
		}
		else
		{
			return $data;
		}
	}

	/**
	 * Removes the text delimiters when fgetcsv() has failed to do so because the file contains a BOM.
	 * This allows for the possibility that the data value contains embedded text enclosure characters
	 * (which should be doubled up for correct csv file format).
	 * The string [32" TV] (ignore brackets) should be encoded as ["32"" TV"]
	 * This function correctly decodes ["32"" TV"] back to [32" TV].
	 *
	 * @param   string  $data  The string to clean
	 *
	 * @return  string  The cleaned string.
	 *
	 * @since   3.0
	 */
	private function removeTextDelimiters($data)
	{
		if (substr($data, 0, 1) == $this->text_enclosure && substr($data, -1, 1) == $this->text_enclosure)
		{
			return str_replace($this->text_enclosure . $this->text_enclosure, $this->text_enclosure, substr($data, 1, -1));
		}
		else
		{
			return $data;
		}
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
		$this->setFilePos(0);
	}

	/**
	 * Return the number of lines in a CSV file.
	 *
	 * @return  int  The number of lines in the CSV file.
	 *
	 * @since   6.0
	 */
	public function lineCount()
	{
		$linecount = 0;

		if ($this->fp)
		{
			// Get the current location
			$filepos = $this->getFilePos();

			// Rewind the file to be sure we are at the start
			$this->rewind();

			while (!feof($this->fp))
			{
				if (fgets($this->fp))
				{
					$linecount++;
				}
			}

			// Set the file back to it's original position
			$this->setFilePos($filepos);
		}

		return $linecount;
	}
}
