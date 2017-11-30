<?php
/**
 * @package     CSVI
 * @subpackage  SEF
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * SEF helper class for the component.
 *
 * @package     CSVI
 * @subpackage  SEF
 * @since       4.0
 */
class CsviHelperSef
{
	/**
	 * The SEF engine to ise
	 *
	 * @var    string
	 * @since  4.0
	 */
	private $sef = null;

	/**
	 * The domain name to use for URLs
	 *
	 * @var    string
	 * @since  4.0
	 */
	private $domainname = null;

	/**
	 * An instance of the CsviHelperTemplate.
	 *
	 * @var    CsviHelperTemplate
	 * @since  6.0
	 */
	private $template = null;

	/**
	 * An instance of the CsviHelperLog.
	 *
	 * @var    CsviHelperLog
	 * @since  6.0
	 */
	private $log = null;

	/**
	 * Constructor.
	 *
	 * @param   CsviHelperSettings  $settings  An instance of CsviHelperSettings.
	 * @param   CsviHelperTemplate  $template  An instance of CsviHelperTemplate.
	 * @param   CsviHelperLog       $log       An instance of CsviHelperLog.
	 *
	 * @since   4.0
	 */
	public function __construct(CsviHelperSettings $settings, CsviHelperTemplate $template, CsviHelperLog $log)
	{
		$this->domainname = $settings->get('hostname');
		$this->template = $template;
		$this->log = $log;
	}

	/**
	 * Create a SEF URL by querying the URL.
	 *
	 * @param   string  $url  The URL to change to SEF.
	 *
	 * @return  string  The SEF URL.
	 *
	 * @since   6.0
	 */
	public function getSEF($url)
	{
		if ($this->template->get('exportsef', false))
		{
			$parseurl = base64_encode($url);

			if (function_exists('curl_init'))
			{
				// Create a new cURL resource
				$ch = curl_init();

				// Set URL and other appropriate options
				curl_setopt($ch, CURLOPT_URL, JURI::root() . 'index.php?option=com_csvi&view=sefs&task=getsef&parseurl=' . $parseurl . '&format=raw');
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

				// Grab URL and pass it to the browser
				$url = curl_exec($ch);

				// Close cURL resource, and free up system resources
				curl_close($ch);
			}
			else
			{
				$url = file_get_contents(JURI::root() . 'index.php?option=com_csvi&view=sefs&task=getsef&parseurl=' . $parseurl . '&format=raw');
			}
		}

		return $this->domainname . $url;
	}

	/**
	 * Create a SEF URL by loading the SEF class directly.
	 *
	 * @param   string  $url  The URL to change to SEF.
	 *
	 * @return  string  The SEF URL.
	 *
	 * @since   3.0
	 */
	public function getSiteRoute($url)
	{
		$parsed_url = null;

		// Check which SEF component is installed
		if (empty($this->sef))
		{
			if ($this->template->get('exportsef', false))
			{
				// Joomla SEF
				if (JPluginHelper::isEnabled('system', 'sef'))
				{
					$this->sef = 'joomla';
				}

				// sh404SEF check
				if (JPluginHelper::isEnabled('system', 'shsef'))
				{
					$this->sef = 'sh404sef';
				}

				if (JPluginHelper::isEnabled('system', 'sh404sef'))
				{
					$this->sef = 'sh404sef';
				}

				// JoomSEF check
				// if (JPluginHelper::isEnabled('system', 'joomsef')) $this->_sef = 'joomsef';

				// AceSEF check
				//if (JPluginHelper::isEnabled('system', 'acesef')) $this->_sef = 'acesef';

				// There is no SEF enabled
				if (empty($this->sef))
				{
					$this->sef = 'nosef';
				}
			}
			else
			{
				$this->sef = 'nosef';
			}
		}

		switch ($this->sef)
		{
			case 'sh404sef':
				$parsed_url = $this->_sh404Sef($url);
				break;
			case 'joomsef':
				$parsed_url = $this->_joomSef($url);
				break;
			case 'joomla':
				$parsed_url = $this->_joomlaSef($url);
				break;
			case 'acesef':
				$parsed_url = $this->_aceSef($url);
				break;
			case 'nosef':
			default:
				// No SEF router found, returning regular URL
				return $this->domainname . '/' . $url;
				break;
		}

		// Clean up the parsed SEF URL
		if (!empty($parsed_url))
		{
			// Clean up the parsed SEF URL
			if (substr($parsed_url, 4) == 'http')
			{
				return $parsed_url;
			}
			else
			{
				// Check for administrator in the domain
				$adminpos = strpos($parsed_url, '/administrator/');

				if ($adminpos !== false)
				{
					$parsed_url = substr($parsed_url, $adminpos + 15);
				}

				// Check if we have a domain name in the URL
				if (!empty($this->domainname))
				{
					$check_domain = str_replace('https', 'http', $this->domainname);
					$domain = strpos($parsed_url, $check_domain);

					if ($domain === false)
					{
						if (substr($parsed_url, 0, 1) == '/')
						{
							$parsed_url = $this->domainname . $parsed_url;
						}
						else
						{
							$parsed_url = $this->domainname . '/' . $parsed_url;
						}
					}

					return $parsed_url;
				}
				else
				{
					$this->log->add(JText::_('COM_CSVI_NO_DOMAINNAME_SET'));

					return $url;
				}
			}
		}
	}

	/**
	 * Create sh404SEF URLs.
	 *
	 * @param   string  $url  The URL to change to SEF.
	 *
	 * @see     http://dev.anything-digital.com/sh404SEF/
	 *
	 * @return  string  The SEF URL.
	 *
	 * @since   3.0
	 */
	private function _sh404sef($url)
	{
		return Sh404sefHelperGeneral::getSefFromNonSef( $url, $fullyQualified = false, $xhtml = false, $ssl = null);
	}

	/**
	 * Create JoomSEF URLs.
	 *
	 * @param   string  $url  The URL to change to SEF.
	 *
	 * @see     http://www.artio.net/joomla-extensions/joomsef
	 *
	 * @return  string  The SEF URL.
	 *
	 * @since   3.0
	 */
	private function _joomSef($url)
	{
		// Include Joomla files
		jimport('joomla.application.router');
		require_once JPATH_ROOT . '/includes/application.php';

		// Include JoomSEF
		require_once JPATH_ROOT . '/components/com_sef/sef.router.php';
		$shRouter = new JRouterJoomSef();

		// Build the SEF URL
		$uri = $shRouter->build($url);

		return $uri->toString();
	}

	/**
	 * Create Joomla SEF URLs.
	 *
	 * In the backend, the languagefilter plugin is not triggered, so we need
	 * to add our own language tag to the URL.
	 *
	 * @param   string  $url  The URL to change to SEF.
	 *
	 * @see     http://www.joomla.org/
	 *
	 * @return  string  The SEF URL.
	 *
	 * @since   3.0
	 */
	private function _joomlaSef($url)
	{
		// Load Joomla core files for SEF
		jimport('joomla.application.router');

		require_once JPATH_LIBRARIES . '/cms/router/router.php';
		require_once JPATH_LIBRARIES . '/cms/router/site.php';

		$router = new JRouterSite(array('mode' => 1));
		$uri = $router->build($url);

		// Add the language tag since we can't use the languagefilter
		$jconfig = JFactory::getConfig();
		$path = str_ireplace(JURI::root(true), '', $uri->getPath());
		$adminpos = strpos($path, '/administrator/');

		// Check if the language filter is being used
		if (JPluginHelper::isEnabled('system', 'languagefilter'))
		{
			if ($jconfig->get('sef_rewrite'))
			{
				// Using SEF Rewrite
				if ($adminpos !== false)
				{
					$path = substr($this->template->get('language'), 0, 2) . '/' . substr($path, $adminpos + 15);
				}
			}
			else
			{
				//  Not using SEF Rewrite
				if ($adminpos !== false)
				{
					$path = 'index.php/' . substr($this->template->get('language'), 0, 2) . '/' . substr($path, $adminpos + 24);
				}
			}
		}
		else
		{
			if ($adminpos !== false)
			{
				$path = substr($path, $adminpos + 15);
			}
		}

		$uri->setPath($path);

		return $uri->toString();
	}

	/**
	 * Create aceSEF URLs.
	 *
	 * @param   string  $url  The URL to change to SEF.
	 *
	 * @see     http://www.joomace.net/joomla-extensions/acesef
	 *
	 * @return  string  The SEF URL.
	 *
	 * @since   3.0
	 */
	private function _aceSef($url)
	{
		jimport('joomla.application.router');
		require_once JPATH_ROOT . '/includes/application.php';
		require_once JPATH_ADMINISTRATOR . '/components/com_acesef/library/router.php';
		require_once JPATH_ADMINISTRATOR . '/components/com_acesef/library/loader.php';

		$router = new JRouterAcesef();
		$uri = $router->build($url);

		return $uri->toString();
	}
}
