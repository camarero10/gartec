<?php
/**
 * @package     CSVI
 * @subpackage  VirtueMart
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * The VirtueMart Config Class.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartHelperCom_Virtuemart_Config
{
	/**
	 * The VirtueMart configuration file
	 *
	 * @var    string
	 * @since  4.0
	 */
	private $vmcfgfile = null;

	/**
	 * The VirtueMart configuration
	 *
	 * @var    array
	 * @since  4.0
	 */
	private $vmcfg     = array();

	/**
	 * The constructor to parse the configuration.
	 *
	 * @since   4.0
	 */
	public function __construct()
	{
		// Set the configuration path
		$this->vmcfgfile = JPATH_ADMINISTRATOR . '/components/com_virtuemart/virtuemart.cfg';
		$this->parse();

		// Load the version information
		if (file_exists(JPATH_ADMINISTRATOR . '/components/com_virtuemart/version.php'))
		{
			require_once JPATH_ADMINISTRATOR . '/components/com_virtuemart/version.php';
			$this->vmcfg['release'] = vmVersion::$RELEASE;
		}
		else
		{
			$this->_vncfg['release'] = null;
		}
	}

	/**
	 * Finds a given VirtueMart setting.
	 *
	 * @param   string  $setting  The setting to get the value for.
	 *
	 * @return  mixed  The value found | False if setting does not exist.
	 *
	 * @since   4.0
	 */
	public function get($setting)
	{
		if (isset($this->vmcfg[$setting]))
		{
			return $this->vmcfg[$setting];
		}
		else
		{
			return false;
		}
	}

	/**
	 * Parse the VirtueMart configuration.
	 *
	 * @return  void.
	 *
	 * @since   4.0
	 */
	private function parse()
	{
		$array = array();

		// First check the database configuration
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
					->select($db->qn('config'))
					->from($db->qn('#__virtuemart_configs'))
					->where($db->qn('virtuemart_config_id') . ' = 1');
		$db->setQuery($query);
		$config = $db->loadResult();

		if ($config)
		{
			$options = explode('|', $config);

			foreach ($options as $option)
			{
				list($key, $value) = explode('=', $option);

				switch ($key)
				{
					case 'offline_message':
						if (substr($value, 0, 2) == 's:')
						{
							$value = unserialize(base64_decode($value));
						}

						$array[$key] = $value;
						break;
					default:
						$array[$key] = json_decode($value);

						if (json_last_error() !== JSON_ERROR_NONE)
						{
							$array[$key] = unserialize($value);
						}
						break;
				}
			}

			$this->vmcfg = $array;
		}
		else
		{
			// Parse the configuration file
			if (file_exists($this->vmcfgfile))
			{
				$config = file_get_contents($this->vmcfgfile);

				// Do some cleanup
				$config = str_replace('#', ';', $config);

				$this->vmcfg = parse_ini_string($config);
			}
		}
	}
}
