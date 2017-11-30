<?php
/**
 * @package     CSVI
 * @subpackage  Addons
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Addons controller.
 *
 * @package     CSVI
 * @subpackage  Addons
 * @since       6.0
 */
class CsviControllerAddons extends FOFController
{
	/**
	 * Install a CSVI addon package.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function install()
	{
		$model = $this->getThisModel();

		try
		{
			$model->installAddon();

			$message = JText::_('COM_CSVI_ADDON_INSTALL_OK');
			$msgtype = 'message';
		}
		catch (Exception $e)
		{
			$message = JText::_('COM_CSVI_ADDON_INSTALL_NOK');
			$msgtype = 'error';
		}

		$this->setRedirect('index.php?option=com_csvi&view=addons', $message, $msgtype);
	}

	/**
	 * Remove a CSVI addon package.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function remove()
	{
		$model = $this->getThisModel();

		try
		{
			$id = $this->input->get('cid', array());

			$model->removeAddon($id);

			$message = JText::_('COM_CSVI_ADDON_REMOVE_OK');
			$msgtype = 'message';
		}
		catch (Exception $e)
		{
			$message = JText::_('COM_CSVI_ADDON_REMOVE_NOK');
			$msgtype = 'error';
		}

		$this->setRedirect('index.php?option=com_csvi&view=addons', $message, $msgtype);
	}
}
