<?php
/**
 * @package     CSVI
 * @subpackage  Source
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Source processor.
 *
 * @package     CSVI
 * @subpackage  Source
 * @since       6.0
 */
class CsviHelperSource
{
	/**
	 * Contains allowed extensions for uploaded files
	 *
	 * @var    array
	 * @since  3.0
	 */
	protected $suffixes = array('txt', 'csv', 'tsv', 'xls', 'xml', 'ods');

	/**
	 * Contains allowed mime types for uploaded files
	 *
	 * @var    array
	 * @since  3.0
	 */
	protected $mimetypes = array('text/html',
		'text/plain',
		'text/csv',
		'application/octet-stream',
		'application/x-octet-stream',
		'application/vnd.ms-excel',
		'application/excel',
		'application/ms-excel',
		'application/x-excel',
		'application/x-msexcel',
		'application/force-download',
		'text/comma-separated-values',
		'text/x-csv',
		'text/x-comma-separated-values',
		'application/vnd.oasis.opendocument.spreadsheet');

	/**
	 * Contains allowed archive types for uploaded files
	 *
	 * @var    array
	 * @since  3.0
	 */
	protected $archives = array('zip', 'tgz');

	/**
	 * Constructor.
	 *
	 * @since   6.0
	 */
	public function __construct()
	{
		// Load the necessary libraries
		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.archive');
	}

	/**
	 * Validate the file.
	 *
	 * Validate the file is of the supported type
	 * Types supported are csv, txt, xls, ods, xml
	 *
	 * @param   string              $source      The origin of the file.
	 * @param   array               $data        The source data
	 * @param   CsviHelperTemplate  $template    The template
	 * @param   CsviHelperLog       $log         The log
	 * @param   CsviHelperCsvi      $csvihelper  The log
	 *
	 * @return  string The name of the file to use.
	 *
	 * @throws  CsviException
	 *
	 * @since   3.0
	 */
	public function validateFile($source, $data, CsviHelperTemplate $template, CsviHelperLog $log, CsviHelperCsvi $csvihelper)
	{
		// Set the working folder
		$folder = CSVIPATH_TMP . '/' . time();

		if (JFolder::create($folder))
		{
			switch ($source)
			{
				// Uploaded file
				case 'fromupload':
					$processfolder = $this->fromupload($data, $folder, $template, $log);
					break;
				case 'fromserver':
					$processfolder = $this->fromserver($data, $folder, $template, $log);
					break;
				case 'fromurl':
					$processfolder = $this->fromurl($data, $folder, $template, $log, $csvihelper);
					break;
				case 'fromftp':
					$processfolder = $this->fromftp($data, $folder, $template, $log);
					break;
				default:
					$log->addStats('incorrect', 'COM_CSVI_NO_FILE_PROVIDED');
					throw new CsviException(JText::_('COM_CSVI_NO_FILE_PROVIDED'));
					break;
			}

			// Clean the filename
			$processfolder = JPath::clean($processfolder, '/');

			if (!is_dir($processfolder))
			{
				$log->add(JText::sprintf('COM_CSVI_LOCAL_FOLDER_DOESNT_EXIST', $processfolder));
				throw new CsviException(JText::sprintf('COM_CSVI_LOCAL_FOLDER_DOESNT_EXIST', $processfolder), 403);
			}

			// Test the extensions
			//$extension = JFile::getExt($processfolder);

			//if (!in_array($extension, $this->suffixes))
			//{
			//	// Test the mime type
			//	if (!in_array($extension, $this->mimetypes) )
			//	{
			//		$log->addStats('information', JText::sprintf('COM_CSVI_EXTENSION_NOT_ACCEPTED', $extension));
			//
			//		throw new RuntimeException(JText::sprintf('COM_CSVI_EXTENSION_NOT_ACCEPTED', $extension));
			//	}
			//}
			//
			//// Debug message to know what filetype the user is uploading
			//$log->addDebug(JText::sprintf('COM_CSVI_IMPORT_FILETYPE', $extension));

			// All is fine
			return $processfolder;
		}
		else
		{
			throw new CsviException(JText::sprintf('COM_CSV_CANNOT_CREATE_TEMP_FOLDER', $folder), 508);
		}
	}

	/**
	 * Process file from upload.
	 *
	 * @param   array               $data      The source data
	 * @param   string              $folder    The temporary folder
	 * @param   CsviHelperTemplate  $template  The template
	 * @param   CsviHelperLog       $log       The log
	 *
	 * @return  string  The file to use.
	 *
	 * @throws  RuntimeException
	 *
	 * @since   6.0
	 */
	private function fromupload($data, $folder, CsviHelperTemplate $template, CsviHelperLog $log)
	{
		if (!empty($data))
		{
			// Check if the file upload has an error
			if ($data['error'] == 0)
			{
				if (is_uploaded_file($data['tmp_name']))
				{
					// Get some basic info
					$upload_parts = pathinfo($data['name']);

					// Force an extension if needed
					$force_ext = $template->get('use_file_extension');

					if (!empty($force_ext))
					{
						$upload_parts['extension'] = $force_ext;
					}

					// Move the uploaded file to its temp location
					if (JFile::upload($data['tmp_name'], $folder . '/' . $data['name']))
					{
						// Let's see if the uploaded file is an archive
						if (in_array($upload_parts['extension'], $this->archives))
						{
							// It is an archive, unpack first
							$files = $this->unpackZip($folder . '/' . $data['name'], $folder);

							// Check if there are multiple files
							if (!empty($files))
							{
								return $folder;
							}
						}
						else
						{
							// Just a regular file
							return $folder;
						}
					}
				}
				else
				{
					// Error warning cannot save uploaded file
					$log->addStats('incorrect', JText::sprintf('COM_CSVI_NO_UPLOADED_FILE_PROVIDED', $data['tmp_name']));

					throw new RuntimeException(JText::sprintf('COM_CSVI_NO_UPLOADED_FILE_PROVIDED', $data['tmp_name']));
				}
			}
			else
			{
				// There was a problem uploading the file
				switch ($data['error'])
				{
					case '1':
						$errormsg = 'COM_CSVI_THE_UPLOADED_FILE_EXCEEDS_THE_MAXIMUM_UPLOADED_FILE_SIZE';
						break;
					case '2':
						$errormsg = 'COM_CSVI_THE_UPLOADED_FILE_EXCEEDS_THE_MAXIMUM_UPLOADED_FILE_SIZE';
						break;
					case '3':
						$errormsg = 'COM_CSVI_THE_UPLOADED_FILE_WAS_ONLY_PARTIALLY_UPLOADED';
						break;
					case '4':
						$errormsg = 'COM_CSVI_NO_FILE_WAS_UPLOADED';
						break;
					case '6':
						$errormsg = 'COM_CSVI_MISSING_A_TEMPORARY_FOLDER';
						break;
					case '7':
						$errormsg = 'COM_CSVI_FAILED_TO_WRITE_FILE_TO_DISK';
						break;
					case '8':
						$errormsg = 'COM_CSVI_FILE_UPLOAD_STOPPED_BY_EXTENSION';
						break;
					default:
						$errormsg = 'COM_CSVI_THERE_WAS_A_PROBLEM_UPLOADING_THE_FILE';
						break;
				}

				$log->addStats('incorrect', $errormsg);

				throw new RuntimeException(JText::_($errormsg));
			}
		}
		else
		{
			throw new RuntimeException(JText::_('COM_CSVI_SOURCE_DATA_EMPTY'));
		}

		return true;
	}

	/**
	 * Process file from local server.
	 *
	 * @param   array               $data      The source data
	 * @param   string              $folder    The temporary folder
	 * @param   CsviHelperTemplate  $template  The template
	 * @param   CsviHelperLog       $log       The log
	 *
	 * @return  string  The file to use.
	 *
	 * @throws  CsviException
	 *
	 * @since   6.0
	 */
	private function fromserver($data, $folder, CsviHelperTemplate $template, CsviHelperLog $log)
	{
		if (!isset($data['file']) || empty($data['file']))
		{
			if ($template->get('local_csv_file', false))
			{
				$csv_file = JPath::clean($template->get('local_csv_file'), '/');
			}
			else
			{
				$log->add('File source not specified in templates', false);
				throw new CsviException(JText::_('COM_CSVI_FILE_SOURCE_NOT_SPECIFIED'));
			}
		}
		else
		{
			$csv_file = $data['file'];
		}

		if (is_file($csv_file))
		{
			$fileinfo = pathinfo($csv_file);

			// Let's see if the uploaded file is an archive
			if (isset($fileinfo['extension']) && in_array($fileinfo['extension'], $this->archives))
			{
				// It is an archive, unpack first
				$files = $this->unpackZip($fileinfo['dirname'] . '/' . $fileinfo['basename'], $folder);

				// Check if there are multiple files
				if (is_array($files))
				{
					return $folder;
				}
			}
			else
			{
				if (!JFile::exists($csv_file))
				{
					$log->add('[VALIDATEFILE] ' . JText::sprintf('COM_CSVI_LOCAL_FILE_DOESNT_EXIST', $csv_file));
					$log->addStats('incorrect', JText::sprintf('COM_CSVI_LOCAL_FILE_DOESNT_EXIST', $csv_file));

					throw new CsviException(JText::sprintf('COM_CSVI_LOCAL_FILE_DOESNT_EXIST', $csv_file), 404);
				}
				else
				{
					// Create the temporary file path
					$tempFile = $folder . '/' . basename($csv_file);

					// Copy the files to a temporary folder
					if (!JFile::copy($csv_file, $tempFile))
					{
						throw new CsviException(JText::sprintf('COM_CSVI_CANNOT_COPY_FILE_TO_TEMP_FOLDER', $csv_file, $folder), 507);
					}
				}
			}
		}
		elseif (is_dir($csv_file))
		{
			// Copy the files to a temporary folder
			JFolder::copy($csv_file, $folder, '', true);
		}
		else
		{
			throw new CsviException(JText::sprintf('COM_CSVI_LOCAL_FILE_IS_NOT_FILE', $csv_file), 405);
		}

		return $folder;
	}

	/**
	 * Process file from URL.
	 *
	 * @param   array               $data        The source data
	 * @param   string              $folder      The temporary folder
	 * @param   CsviHelperTemplate  $template    The template
	 * @param   CsviHelperLog       $log         The log
	 * @param   CsviHelperCsvi      $csvihelper  The log
	 *
	 * @return  string  The file to use.
	 *
	 * @throws  RuntimeException
	 *
	 * @since   6.0
	 */
	private function fromurl($data, $folder, CsviHelperTemplate $template, CsviHelperLog $log, CsviHelperCsvi $csvihelper)
	{
		// The temporary folder
		$urlfile = $template->get('urlfile', 'general', false);
		$tempfile = preg_replace('/[\?\s\/=]/', '_', basename($urlfile));
		$force = $template->get('use_file_extension');
		$extension = (!empty($force)) ?  $force : JFile::getExt($tempfile);

		// Check if the remote file exists
		if ($urlfile)
		{
			if ($csvihelper->fileExistsRemote($urlfile))
			{
				// Copy the remote file to a local location
				if (JFolder::create($folder))
				{
					if (touch($folder . '/' . $tempfile))
					{
						$sourcefile = file_get_contents($urlfile);

						if (JFile::write($folder . '/' . $tempfile, $sourcefile))
						{
							$log->add(JText::sprintf('COM_CSVI_RETRIEVE_FROM_URL', $urlfile));

							// Let's see if the uploaded file is an archive
							if (in_array($extension, $this->archives))
							{
								// It is an archive, unpack first
								$files = $this->unpackZip($folder . '/' . $tempfile, $folder);

								// Check if there are multiple files
								if (!empty($files))
								{
									return $folder;
								}
							}
							else
							{
								return $folder;
							}
						}
						else
						{
							$log->addStats('incorrect', JText::sprintf('COM_CSVI_CANNOT_READ_FROM_URL', $urlfile));
							throw new RuntimeException(JText::sprintf('COM_CSVI_CANNOT_READ_FROM_URL', $urlfile));
						}
					}
					else
					{
						$log->addStats('incorrect', JText::sprintf('COM_CSVI_CANNOT_CREATE_TEMP_FILE', $folder . '/' . $tempfile));
						throw new RuntimeException(JText::sprintf('COM_CSVI_CANNOT_CREATE_TEMP_FILE', $folder . '/' . $tempfile));
					}
				}
				else
				{
					$log->addStats('incorrect', JText::sprintf('COM_CSVI_CANNOT_CREATE_TEMP_FOLDER', $folder));
					throw new RuntimeException(JText::sprintf('COM_CSVI_CANNOT_CREATE_TEMP_FOLDER', $folder));
				}
			}
			else
			{
				$log->addStats('incorrect', JText::sprintf('COM_CSVI_CANNOT_READ_FROM_URL'));
				throw new RuntimeException(JText::sprintf('COM_CSVI_CANNOT_READ_FROM_URL', $urlfile));
			}
		}
		else
		{
			$log->addStats('incorrect', JText::_('COM_CSVI_NO_FILENAME_GIVEN'));
			throw new RuntimeException(JText::_('COM_CSVI_NO_FILENAME_GIVEN'));
		}
	}

	/**
	 * Process file from FTP.
	 *
	 * @param   array               $data      The source data
	 * @param   string              $folder    The temporary folder
	 * @param   CsviHelperTemplate  $template  The template
	 * @param   CsviHelperLog       $log       The log
	 *
	 * @return  string  The file to use.
	 *
	 * @throws  RuntimeException
	 *
	 * @since   6.0
	 */
	private function fromftp($data, $folder, CsviHelperTemplate $template, CsviHelperLog $log)
	{
		// The temporary folder
		$ftpfile = $template->get('ftpfile', false);

		if ($ftpfile)
		{
			// Create the output file
			if (JFolder::create($folder))
			{
				if (touch($folder . '/' . $ftpfile))
				{
					// Start the FTP
					jimport('joomla.client.ftp');
					$ftp = JFTP::getInstance(
						$template->get('ftphost'),
						$template->get('ftpport'),
						array(),
						$template->get('ftpusername'),
						$template->get('ftppass')
					);

					if ($ftp->get($folder . '/' . $ftpfile, $template->get('ftproot', '') . $ftpfile))
					{
						$log->add(JText::sprintf('COM_CSVI_RETRIEVE_FROM_FTP', $template->get('ftproot', '') . $ftpfile));

						// Close the FTP connection
						$ftp->quit();

						// Let's see if the uploaded file is an archive
						if (in_array(JFile::getExt($ftpfile), $this->archives))
						{
							// It is an archive, unpack first
							$files = $this->unpackZip($folder . '/' . $ftpfile, $folder);

							// Check if there are multiple files
							if (!empty($files))
							{
								return $folder;
							}
						}
						else
						{
							return $folder;
						}
					}
					else
					{
						// Close the FTP connection
						$ftp->quit();

						$log->addStats('incorrect', 'COM_CSVI_CANNOT_READ_FROM_FTP');
						throw new RuntimeException(JText::_('COM_CSVI_CANNOT_READ_FROM_FTP'));
					}
				}
				else
				{
					$log->addStats('incorrect', JText::sprintf('COM_CSVI_CANNOT_CREATE_TEMP_FILE', $folder . '/' . $ftpfile));
					throw new RuntimeException(JText::sprintf('COM_CSVI_CANNOT_CREATE_TEMP_FILE', $folder . '/' . $ftpfile));
				}
			}
			else
			{
				$log->addStats('incorrect', JText::sprintf('COM_CSVI_CANNOT_CREATE_TEMP_FOLDER', $folder));
				throw new RuntimeException(JText::sprintf('COM_CSVI_CANNOT_CREATE_TEMP_FOLDER', $folder));
			}
		}
		else
		{
			$log->addStats('incorrect', 'COM_CSVI_NO_FILENAME_GIVEN');
			throw new RuntimeException(JText::_('COM_CSVI_NO_FILENAME_GIVEN'));
		}
	}

	/**
	 * Unpack a zipped source file.
	 *
	 * @param   string  $name    The name of the archive to extract
	 * @param   string  $folder  The folder to store the extracted contents
	 *
	 * @return  string  The name of the file found in the archive.
	 *
	 * @throws  RuntimeException
	 *
	 * @since   3.0
	 */
	private function unpackZip($name, $folder)
	{
		if (JArchive::extract($name, $folder))
		{
			// File is unpacked, remove the zip file
			JFile::delete($name);

			// File is unpacked, let's get the filename
			$foundfiles = scandir($folder);
			$found = array();

			foreach ($foundfiles as $filename)
			{
				$ff_parts = pathinfo($filename);

				if (isset($ff_parts['extension']) && in_array(strtolower($ff_parts['extension']), $this->suffixes))
				{
					$found[] = $folder . '/' . $filename;
				}
			}

			if (empty($found))
			{
				throw new RuntimeException(JText::_('COM_CSVI_NO_VALID_FILES_IN_ARCHIVE'));
			}
			else
			{
				return $found;
			}
		}
		else
		{
			throw new RuntimeException(JText::_('COM_CSVI_CANNOT_UNPACK_UPLOADED_FILE'));
		}
	}
}
