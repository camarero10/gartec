<?php
defined('_JEXEC') or die;
abstract class JHtmlK2Store
{
	static function required1($value = 0, $i)
	{
		$states = array(
				0=>
					array(
						'disabled.png',
						'field.required',
						'',
						'Toggle to approve'
					),
				1=>
					 array(
					 		'tick.png',
					 		'field.notrequired',
					 		'',
					 		'Toggle to unapprove'
					 	),
				 );
		$state   = JArrayHelper::getValue($states, (int) $value, $states[1]);
		$html    = JHtml::_('image', 'admin/'.$state[0], JText::_($state[2]), NULL, true);
		$html    = '<a href="#" onclick="return listItemTask(\'cb'.$i.'\',\''.$state[1].'\')" title="'.JText::_($state[3]).'">'. $html.'</a>';
		return $html;
	}


	public static function required($value, $i, $enabled = true, $checkbox = 'cb')
	{
		$states = array(
				1 => array(
						'notrequired',
						'K2STORE_FIELD_REQUIRED',
						'K2STORE_FIELD_MAKE_NOTREQUIRED',
						'K2STORE_FIELD_REQUIRED',
						true,
						'publish',
						'publish'
				),
				0 => array(
						'required',
						'K2STORE_FIELD_NOTREQUIRED',
						'K2STORE_FIELD_MAKE_REQUIRED',
						'K2STORE_FIELD_NOTREQUIRED',
						true,
						'unpublish',
						'unpublish'
				),
		);

		return JHtml::_('jgrid.state', $states, $value, $i, '', $enabled, true, $checkbox);
	}


}
