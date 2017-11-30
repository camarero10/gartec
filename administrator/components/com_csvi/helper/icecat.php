<?php
/**
 * @package     CSVI
 * @subpackage  ICEcat
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Helper class to process ICEcat requests.
 *
 * @package     CSVI
 * @subpackage  ICEcat
 * @since       6.0
 */
class CsviHelperIcecat
{
	/**
	 * Holds the template
	 *
	 * @var    CsviHelperTemplate
	 * @since  6.2.1
	 */
	protected $template = null;

	/**
	 * Holds the logger
	 *
	 * @var    CsviHelperLog
	 * @since  6.2.1
	 */
	protected $log = null;

	/**
	 * Holds the database connector
	 *
	 * @var    JDatabaseDriver
	 * @since  6.2.1
	 */
	protected $db = null;

	/**
	 * The XML parser
	 *
	 * @var    resource
	 * @since  6.0
	 */
	private $xmlParser = null;

	/**
	 * The XML data read from ICEcat
	 *
	 * @var    string
	 * @since  6.0
	 */
	private $data = false;

	/**
	 * Array that holds the data to process by CSVI
	 *
	 * @var    array
	 * @since  6.0
	 */
	private $csviData = array();

	/**
	 * Array that holds the current open tags
	 *
	 * @var    array
	 * @since  6.0
	 */
	private $openTags = array();

	/**
	 * The ID of the feature
	 *
	 * @var    int
	 * @since  6.0
	 */
	private $featureId = null;

	/**
	 * Array that holds all the feature IDs
	 *
	 * @var    array
	 * @since  6.0
	 */
	private $featureids = array();

	/**
	 * Array that holds all the feature names
	 *
	 * @var    array
	 * @since  6.0
	 */
	private $featurenames = array();

	/**
	 * Constructor.
	 *
	 * @param   CsviHelperTemplate $template An instance of CsviHelperTemplate
	 * @param   CsviHelperLog $log An instance of CsviHelperLog
	 * @param   JDatabaseDriver $db An instance of JDatabaseDriver
	 *
	 * @since   4.6
	 */
	public function __construct(CsviHelperTemplate $template, CsviHelperLog $log, JDatabaseDriver $db)
	{
		// Set the parameters
		$this->template = $template;
		$this->log = $log;
		$this->db = $db;
	}

	/**
	 * Collect data to import.
	 *
	 * @param   string  $mpn      The MPN code to read
	 * @param   string  $mf_name  The manufacturer name
	 *
	 * @return  array  ICEcat data.
	 *
	 * @since   6.0
	 */
	public function getData($mpn, $mf_name)
	{
		$update_fields = $this->template->get('icecat_update_fields', array(), 'array');

		$icecat_id = $this->_getIcecatUrl($mpn, $mf_name);

		// See if we have an ICEcat ID
		if ($icecat_id)
		{
			// Clean some values
			$this->featurenames = array();

			// Setup the XML parser
			if ($this->setupXmlParser())
			{

				// Call ICEcat to get the data
				$this->callIcecat($icecat_id);

				// See if we have any valid data
				if ($this->data)
				{
					// Clean some data
					$this->csviData = array();

					// Parse the XML data
					if (!xml_parse($this->xmlParser, $this->data, true))
					{
						die(sprintf("XML error: %s at line %d\n",
							xml_error_string(xml_get_error_code($this->xmlParser)),
							xml_get_current_line_number($this->xmlParser)));
					}

					xml_parser_free($this->xmlParser);

					// Only return the data to be updated
					foreach ($update_fields as $fieldname)
					{
						if (array_key_exists($fieldname, $this->csviData))
						{
							unset($this->csviData[$fieldname]);
						}
					}

					// Return the ICEcat data
					return $this->csviData;
				}
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * Query for looking for ICEcat URL.
	 *
	 * @param   string  $mpn      The Manufacturer Part Number
	 * @param   string  $mf_name  The manufacturer name
	 *
	 * @return  int  The ICEcat ID.
	 *
	 * @since   6.0
	 */
	private function _getIcecatUrl($mpn, $mf_name)
	{
		// Find the ICEcat ID
		$query = $this->db->getQuery(true);
		$query->select('product_id')
			->from($this->db->quoteName('#__csvi_icecat_index', 'i'))
			->leftJoin(
				$this->db->quoteName('#__csvi_icecat_suppliers', 's') .
				' ON ' . $this->db->quoteName('s.supplier_id') . ' = ' . $this->db->quoteName('i.supplier_id')
			)
			->where($this->db->quoteName('i.prod_id') . ' = ' . $this->db->quote($mpn))
			->where($this->db->quoteName('s.supplier_name') . ' = ' . $this->db->quote($mf_name));
		$this->db->setQuery($query);
		$this->log->add('Find the ICEcat ID for manufacturer ' . $mf_name . ' and part number ' . $mpn);
		$icecat_id = $this->db->loadResult();

		// See if we have a match, otherwise try to search more liberal
		if (!$icecat_id && $this->template->get('similar_sku', false))
		{
			$query = $this->db->getQuery(true);
			$query->select('product_id')
				->from($this->db->quoteName('#__csvi_icecat_index', 'i'))
				->leftJoin(
					$this->db->quoteName('#__csvi_icecat_suppliers', 's') .
					' ON ' . $this->db->quoteName('s.supplier_id') . ' = ' . $this->db->quoteName('i.supplier_id')
				)
				->where($this->db->quoteName('i.prod_id') . ' LIKE ' . $this->db->quote($mpn . '%'))
				->where($this->db->quoteName('s.supplier_name') . ' = ' . $this->db->quote($mf_name));
			$this->db->setQuery($query);
			$this->log->add('Find the ICEcat ID by similar SKU for manufacturer ' . $mf_name . ' and part number ' . $mpn);
			$icecat_id = $this->db->loadResult();

			// Look for an alternative ID
			if (!$icecat_id)
			{
				$query = $this->db->getQuery(true);
				$query->select('product_id')
					->from($this->db->quoteName('#__csvi_icecat_index', 'i'))
					->leftJoin(
						$this->db->quoteName('#__csvi_icecat_suppliers', 's') .
						' ON ' . $this->db->quoteName('s.supplier_id') . ' = ' . $this->db->quoteName('i.supplier_id')
					)
					->where($this->db->quoteName('i.m_prod_id') . ' = ' . $this->db->quote($mpn))
					->where($this->db->quoteName('s.supplier_name') . ' = ' . $this->db->quote($mf_name));
				$this->db->setQuery($query);
				$this->log->add('Find the ICEcat ID as alternative ID for manufacturer ' . $mf_name . ' and part number ' . $mpn);
				$icecat_id = $this->db->loadResult();
			}
		}

		return $icecat_id;
	}

	/**
	 * Process start elements of the XML record.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function startElement($parser, $tagname, $attribs)
	{
		$tagname = strtolower($tagname);
		if (count($this->openTags) >= 1)
		{
			$parent_tag = $this->openTags[(count($this->openTags) - 1)];
		}
		else
		{
			$parent_tag = '';
		}

		switch ($tagname)
		{
			case 'product':
				switch ($parent_tag)
				{
					case 'productrelated':
						// Related products
						if (!array_key_exists('related_products', $this->csviData))
						{
							$this->csviData['related_products'] = '';
						}
						$this->csviData['related_products'] .= $attribs['PROD_ID'] . '|';
						break;
					default:
						// See if we have an error
						if (array_key_exists('CODE', $attribs) && $attribs['CODE'] == '-1')
						{
							$this->log->add('ICEcat error: ' . $attribs['ERRORMESSAGE'], false);
						}
						else
						{
							// Process the attributes
							// SKU should not be changed
							// $this->_csvi_data['product_sku'] = $attribs['PROD_ID'];
							// Name
							$this->csviData['product_name'] = $attribs['NAME'];
							// Images
							$this->csviData['file_url'] = $attribs['HIGHPIC'];
							$this->csviData['file_url_thumb'] = $attribs['THUMBPIC'];
							// Release date comes int he form YYYY-MM-DD
							if (strpos($attribs['RELEASEDATE'], '-'))
							{
								list($year, $month, $day) = explode('-', $attribs['RELEASEDATE']);
								$this->csviData['product_available_date'] = $day . '/' . $month . '/' . $year;
							}
						}
						break;
				}
				break;
			case 'productfeature':
				switch ($parent_tag)
				{
					default:
						$this->_presentationvalue = $attribs['PRESENTATION_VALUE'];
						$this->_categoryfeaturegroup_id = $attribs['CATEGORYFEATUREGROUP_ID'];
						break;
				}
				break;
			case 'categoryfeaturegroup':
				$this->featureId = $attribs['ID'];
				break;
			case 'name':
				switch ($parent_tag)
				{
					case 'category':
						// Category
						$this->csviData['category_path'] = $attribs['VALUE'];
						break;
					case 'featuregroup':
						$this->featurenames[$this->featureId] = $attribs['VALUE'];
						break;
					case 'feature':
						$this->log->add('Found ICEcat feature: ' . $attribs['VALUE']);
						if (isset($this->featurenames[$this->_categoryfeaturegroup_id]))
						{
							$this->csviData['features'][$this->_categoryfeaturegroup_id][$this->featurenames[$this->_categoryfeaturegroup_id]][$attribs['VALUE']] = str_replace('\n', '<br />', $this->_presentationvalue);
						}

						// Reset values
						$this->_presentationvalue = null;
						$this->_categoryfeaturegroup_id = null;
						break;
				}
				break;
			case 'productpicture':
				if (!empty($attribs))
				{
					// Process the attribs
					// <ProductPicture Pic="http://images.icecat.biz/img/gallery/525017_2053.jpg" PicHeight="480" PicWidth="600" ProductPicture_ID="702732" Size="15185" ThumbPic="http://images.icecat.biz/img/gallery_thumbs/525017_643.jpg" ThumbSize="2228"/>
					if (isset($this->csviData['file_url_thumb']))
					{
						$this->csviData['file_url_thumb'] .= '|' . $attribs['THUMBPIC'];
					}
					else
					{
						$this->csviData['file_url_thumb'] = $attribs['THUMBPIC'];
					}
					if (isset($this->csviData['file_url']))
					{
						$this->csviData['file_url'] .= '|' . $attribs['PIC'];
					}
					else
					{
						$this->csviData['file_url'] = $attribs['PIC'];
					}
				}
				break;
			case 'productdescription':
				if (isset($attribs['LONGDESC']))
				{
					$this->csviData['product_desc'] = str_ireplace('\n', '<br />', $attribs['LONGDESC']);
				}
				else
				{
					$this->csviData['product_desc'] = '';
				}
				if (isset($attribs['SHORTDESC']))
				{
					$this->csviData['product_s_desc'] = $attribs['SHORTDESC'];
				}
				else
				{
					$this->csviData['product_s_desc'] = '';
				}
				break;
			case 'shortsummarydescription':
				// $this->_csvi_data['product_s_desc'] = '';
				break;
			case 'longsummarydescription':
				// $this->_csvi_data['product_desc'] = '';
				break;
			case 'supplier':
				$this->csviData['manufacturer_name'] = $attribs['NAME'];
				break;
			case 'productdescription':
				// <ProductDescription ID="650155" LongDesc="Add a parallel port to your desktop computer through a PCI expansion slot\n\n    * Up to 3 times faster than legacy ISA or on-board parallel ports providing fast and reliable parallel communication\n    * Supports SPP, EPP, ECP and BPP communication modes for maximum compatibility with your parallel peripherals\n    * Guaranteed compatibility with any PC running Windows®, Linux® or DOS® for simple integration into your application\n\nThe PCI1P value priced EPP/ECP parallel card adds one IEEE 1284 port to your PC, with data transfer speeds of up to 2.7 Mbps – up to 3 times faster than on-board parallel ports.\n\nInstallation is a breeze with plug and play support and drivers for Windows® 7, Vista, XP, ME, 2000, 98, 95, NT4, DOS® and Linux®. IRQ sharing and hot swapping capabilities guarantee convenient, hassle-free connections to any parallel peripheral.\n\nBacked by a StarTech.com lifetime warranty and free lifetime technical support." ManualPDFSize="0" ManualPDFURL="http://pdfs.icecat.biz/pdf/650155-20-manual.pdf" PDFSize="0" PDFURL="http://pdfs.icecat.biz/pdf/650155-4896.pdf" ShortDesc="Value 1 Port PCI Parallel Adapter Card" URL="http://eu.startech.com/product/PCI1P-Value-1-Port-EPPECP-Parallel-PCI-Card" WarrantyInfo="lifetime" langid="1"/>
				if (!empty($attribs['MANUALPDFURL']))
				{
					if (isset($this->csviData['file_url']))
					{
						$this->csviData['file_url'] .= '|' . $attribs['MANUALPDFURL'];
					}
					else
					{
						$this->csviData['file_url'] = $attribs['MANUALPDFURL'];
					}

					if (isset($this->csviData['file_url_thumb']))
					{
						$this->csviData['file_url'] .= '|';
					}
					else
					{
						$this->csviData['file_url_thumb'] = '';
					}
				}
				if (!empty($attribs['PDFURL']))
				{
					if (isset($this->csviData['file_url']))
					{
						$this->csviData['file_url'] .= '|' . $attribs['PDFURL'];
					}
					else
					{
						$this->csviData['file_url'] = $attribs['PDFURL'];
					}

					if (isset($this->csviData['file_url_thumb']))
					{
						$this->csviData['file_url'] .= '|';
					}
					else
					{
						$this->csviData['file_url_thumb'] = '';
					}
				}
				break;
			default:
				break;
		}

		// Add the tagname of the list of processing tags
		$this->openTags[] = $tagname;
	}

	/**
	 * Process end elements of the XML record.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function endElement($parser, $tagname)
	{
		// Remove the current tag as we are done with it
		array_pop($this->openTags);
	}

	/**
	 * Process the inner data.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function characterData($parser, $data)
	{
		$current_tag = end($this->openTags);
		switch ($current_tag)
		{
			case 'shortsummarydescription':
				// $this->_csvi_data['product_s_desc'] .= $data;
				break;
			case 'longsummarydescription':
				// $this->_csvi_data['product_desc'] .= $data;
				break;
		}
	}

	/**
	 * Setup the XML parser.
	 *
	 * @return  bool  True on success | False on failure.
	 *
	 * @since   6.0
	 */
	private function setupXmlParser()
	{
		$this->xmlParser = xml_parser_create("UTF-8");
		xml_parser_set_option($this->xmlParser, XML_OPTION_CASE_FOLDING, 1);
		xml_set_object($this->xmlParser, $this);
		xml_set_element_handler($this->xmlParser, "startElement", "endElement");
		xml_set_character_data_handler($this->xmlParser, "characterData");

		if ($this->xmlParser)
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Request the data from ICEcat
	 *
	 * There are different URLs to get the data from:
	 *
	 * Open ICEcat users have access to:
	 * http://data.icecat.biz/export/freexml.int/INT/ for access to the standardized data files (QUALITY=ICECAT).
	 * The language-specific data-files are found here:
	 * http://data.icecat.biz/export/freexml.int/[code]/[product_id].xml, where [code] stands e.g. for NL, EN, FR, DE, IT, ES, DK etc.
	 *
	 * For the Full ICEcat subscribers, a separate directory structure is in place. The standardized files are located at:
	 * http://data.icecat.biz/export/level4/INT
	 * and the language dependent versions are found here:
	 * http://data.icecat.biz/export/level4/[code]/[product_id].xml, where [code] stands e.g. for NL, EN, FR, DE, IT, ES, DK, etc. For
	 *
	 * Products need to be matched to a product file found at http://data.icecat.biz/export/freexml/EN/
	 *
	 * an index file with references to all product data-sheets in ICEcat or Open ICEcat, also historical/obsolete products
	 * files.index.csv|xml or files.index.csv.gz|xml.gz
	 * a smaller index file with only references to the new or changed product data-sheets of the respective day
	 * daily.index.csv|xml or daily.index.csv.gz|xml.gz
	 * an index file with only the products that are currently on the market, as far as we can see that based on 100s  of distributor and reseller price files
	 * on_market.index.csv|xml or on_market.index.csv.gz|xml.gz)
	 * an index file with the products that are or were on the market for which we only have basic market data, but no complete data-sheet
	 * nobody.index.csv|xml or nobody.index.csv.gz|xml.gz.
	 *
	 * @todo Check for gzip functionality to reduce filesize
	 *
	 * @param   string  $icecat_id  the ICEcat ID to retrieve.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function callIcecat($icecat_id)
	{
		$csvisettings = new CsviHelperSettings($this->db);

		// Construct the URL
		$url = ($csvisettings->get('ice_advanced')) ? 'http://data.icecat.biz/export/level4/' : 'http://data.icecat.biz/export/freexml.int/';
		// The language to use
		$url .= $csvisettings->get('ice_lang') . '/';
		// The ID to retrieve
		$url .= $icecat_id . '.xml';
		$this->log->add('Calling ICEcat URL: ' . $url);

		// Initialise the curl call
		$curl = curl_init();

		// set URL and other appropriate options
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curl, CURLOPT_USERPWD, $csvisettings->get('ice_username') . ":" . $csvisettings->get('ice_password'));

		// grab URL and pass it to the browser
		$this->data = curl_exec($curl);

		// close cURL resource, and free up system resources
		curl_close($curl);
	}

	/**
	 * Supported ICEcat languages.
	 *
	 * @return  array  List of supported languages.
	 *
	 * @since   6.0
	 */
	public function supportdLanguages()
	{
		$codes = array();
		$codes[] = 'INT'; // - International standardized version of a data-sheet. When QUALITY = ICEcat language independent values.
		$codes[] = 'EN'; // Standard or UK English
		$codes[] = 'US'; // US English
		$codes[] = 'NL'; // Dutch
		$codes[] = 'FR'; // French
		$codes[] = 'DE'; // German
		$codes[] = 'IT'; // Italian
		$codes[] = 'ES'; // Spanish
		$codes[] = 'DK'; // Danish
		$codes[] = 'RU'; // Russian
		$codes[] = 'PT'; // Portuguese
		$codes[] = 'ZH'; // Chinese (simplified)
		$codes[] = 'SE'; // Swedish
		$codes[] = 'PL'; // Polish
		$codes[] = 'CZ'; // Czech
		$codes[] = 'HU'; // Hungarian
		$codes[] = 'FI'; // Finnish
		$codes[] = 'NO'; // Norwegian
		$codes[] = 'TR'; // Turkish
		$codes[] = 'BG'; // Bulgarian
		$codes[] = 'KA'; // Georgian
		$codes[] = 'RO'; // Romanian
		$codes[] = 'SR'; // Serbian
		$codes[] = 'JA'; // Japanese
		$codes[] = 'UK'; // Ukrainian
		$codes[] = 'CA'; // Catalan
		$codes[] = 'HR'; // Croatian

		return $codes;
	}
}
