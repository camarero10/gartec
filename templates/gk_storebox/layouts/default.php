<?php

/**
 *
 * Default view
 *
 * @version             1.0.0
 * @package             Gavern Framework
 * @copyright			Copyright (C) 2010 - 2011 GavickPro. All rights reserved.
 *               
 */
 
// No direct access.
defined('_JEXEC') or die;
//
$app = JFactory::getApplication();
$user = JFactory::getUser();
// getting User ID
$userID = $user->get('id');
// getting params
$option = JRequest::getCmd('option', '');
$view = JRequest::getCmd('view', '');
// defines if com_users
define('GK_COM_USERS', $option == 'com_users' && ($view == 'login' || $view == 'registration'));
// other variables
$btn_login_text = ($userID == 0) ? JText::_('TPL_GK_LANG_LOGIN') : JText::_('TPL_GK_LANG_LOGOUT');
$tpl_page_suffix = $this->page_suffix != '' ? ' class="'.$this->page_suffix.'"' : '';
// make sure that the modal will be loaded
JHTML::_('behavior.modal');
// content class
$gkContentClass = '';

if($this->API->modules('header') == 0) {
	$gkContentClass = 'gkContentTopBorder ';
}

if($this->API->modules('sidebar') == 0) {
	$gkContentClass = 'gkContentNoSidebar';
}

if($gkContentClass != '') {
	$gkContentClass = ' class="'.$gkContentClass.'"';
}

?>
<!DOCTYPE html>
<html lang="<?php echo $this->APITPL->language; ?>" <?php echo $tpl_page_suffix; ?>>
<head>
	<?php $this->layout->addTouchIcon(); ?>
	<?php if(
		$this->browser->get('browser') == 'ie6' || 
		$this->browser->get('browser') == 'ie7' || 
		$this->browser->get('browser') == 'ie8' || 
		$this->browser->get('browser') == 'ie9'
	) : ?>
	<meta http-equiv="X-UA-Compatible" content="IE=Edge,chrome=1" />
	<?php endif; ?>
	
    <?php if($this->API->get('rwd', 1)) : ?>
    	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=2.0">
	<?php else : ?>
		<meta name="viewport" content="width=<?php echo $this->API->get('template_width', 1020)+80; ?>">
	<?php endif; ?>
    <jdoc:include type="head" />
    <?php $this->layout->loadBlock('head'); ?>
	<?php $this->layout->loadBlock('cookielaw'); ?>
</head>
<body<?php echo $tpl_page_suffix; ?><?php if($this->browser->get("tablet") == true) echo ' data-tablet="true"'; ?><?php if($this->browser->get("mobile") == true) echo ' data-mobile="true"'; ?><?php $this->layout->generateLayoutWidths(); ?> <?php if($this->API->modules('cart')) echo ' data-cart="true"'; ?> data-smoothscroll="<?php echo $this->API->get('use_smoothscroll', '1'); ?>">
	<?php
	     // put Google Analytics code
	     echo $this->social->googleAnalyticsParser();
	?>	
	<?php if ($this->browser->get('browser') == 'ie7' || $this->browser->get('browser') == 'ie6') : ?>
	<!--[if lte IE 7]>
	<div id="ieToolbar"><div><?php echo JText::_('TPL_GK_LANG_IE_TOOLBAR'); ?></div></div>
	<![endif]-->
	<?php endif; ?>
	
	<?php if(count($app->getMessageQueue())) : ?>
	<jdoc:include type="message" />
	<?php endif; ?>
	
	<div id="gkMainWrap" class="gkPage">
	    <div>
		    <div id="gkHeader">
		    	<div id="gkHeaderNav"<?php if($this->API->modules('header') > 0) : ?> class="gkAbsoluteHeader"<?php endif; ?>>                    	
		    		<?php if($this->API->get('show_menu', 1)) : ?>
		    		<div id="gkMobileMenu">
		    			<?php echo JText::_('TPL_GK_LANG_MOBILE_MENU'); ?>
		    			<select id="mobileMenu" onChange="window.location.href=this.value;" class="chzn-done">
		    			<?php 
		    			    $this->mobilemenu->loadMenu($this->API->get('menu_name','mainmenu')); 
		    			    $this->mobilemenu->genMenu($this->API->get('startlevel', 0), $this->API->get('endlevel',-1));
		    			?>
		    			</select>
		    		</div>
		    		<?php endif; ?>
		    	
				    <?php $this->layout->loadBlock('logo'); ?>
				    
				    <?php if($this->API->get('show_menu', 1)) : ?>
				    <div id="gkMainMenu">
				    	<?php
				    		$this->mainmenu->loadMenu($this->API->get('menu_name','mainmenu')); 
				    	    $this->mainmenu->genMenu($this->API->get('startlevel', 0), $this->API->get('endlevel',-1));
				    	?>   
				    </div>
				    <?php endif; ?>
		    	</div>
		    	
		   		<?php if($this->API->modules('header')) : ?>
		   		<div id="gkHeaderMod">
		   			<jdoc:include type="modules" name="header" style="<?php echo $this->module_styles['header']; ?>" />
		   		</div>
		   		<?php endif; ?>
		   		
		   		<?php if($this->API->modules('cart')) : ?>
		   		<div id="btnCart"><strong>Cart</strong></div>
		   		<?php endif; ?>
		    </div>
		
			<div id="gkPageContent">
		    	<?php if($this->API->modules('header_bottom')) : ?>
		    	<div id="gkHeaderBottom">
		    		<jdoc:include type="modules" name="header_bottom" style="<?php echo $this->module_styles['header_bottom']; ?>" />
		    	</div>
		    	<?php endif; ?>
		    	
		    	<section id="gkContent"<?php echo $gkContentClass; ?>>					
					
					<div id="gkContentWrap"<?php if($this->API->get('sidebar_position', 'right') == 'left' && $this->API->modules('sidebar')) : ?> class="gkSidebarLeft"<?php endif; ?>>
						<?php if($this->API->modules('top1')) : ?>
						<div id="gkTop1" class="gkCols3<?php if($this->API->modules('top1') > 1) : ?> gkNoMargin<?php endif; ?>">
							<div>
								<jdoc:include type="modules" name="top1" style="<?php echo $this->module_styles['top1']; ?>"  modnum="<?php echo $this->API->modules('top1'); ?>" modcol="3" />
							</div>
						</div>
						<?php endif; ?>
						
						<?php if($this->API->modules('top2')) : ?>
						<div id="gkTop2" class="gkCols3<?php if($this->API->modules('top2') > 1) : ?> gkNoMargin<?php endif; ?>">
							<div>
								<jdoc:include type="modules" name="top2" style="<?php echo $this->module_styles['top2']; ?>" modnum="<?php echo $this->API->modules('top2'); ?>" modcol="3" />
							</div>
						</div>
						<?php endif; ?>
						
						<?php if($this->API->modules('breadcrumb') || $this->getToolsOverride()) : ?>
						<section id="gkBreadcrumb">
						
							<?php if($this->API->modules('breadcrumb')) : ?>
							<jdoc:include type="modules" name="breadcrumb" style="<?php echo $this->module_styles['breadcrumb']; ?>" />
							<?php endif; ?>
							
							<?php if($this->getToolsOverride()) : ?>
								<?php $this->layout->loadBlock('tools/tools'); ?>
							<?php endif; ?>
						</section>
						<?php endif; ?>
						
						<?php if($this->API->modules('mainbody_top')) : ?>
						<div id="gkMainbodyTop">
							<jdoc:include type="modules" name="mainbody_top" style="<?php echo $this->module_styles['mainbody_top']; ?>" />
						</div>
						<?php endif; ?>	
						
						<div id="gkMainbody">
							<?php if(($this->layout->isFrontpage() && !$this->API->modules('mainbody')) || !$this->layout->isFrontpage()) : ?>
								<jdoc:include type="component" />
							<?php else : ?>
								<jdoc:include type="modules" name="mainbody" style="<?php echo $this->module_styles['mainbody']; ?>" />
							<?php endif; ?>
						</div>
						
						<?php if($this->API->modules('mainbody_bottom')) : ?>
						<div id="gkMainbodyBottom">
							<jdoc:include type="modules" name="mainbody_bottom" style="<?php echo $this->module_styles['mainbody_bottom']; ?>" />
						</div>
						<?php endif; ?>
					</div>
					
					<?php if($this->API->modules('sidebar')) : ?>
					<aside id="gkSidebar"<?php if($this->API->modules('sidebar') == 1) : ?> class="gkOnlyOne"<?php endif; ?>>
						
						<div>
							<jdoc:include type="modules" name="sidebar" style="<?php echo $this->module_styles['sidebar']; ?>" />
						</div>
					</aside>
					<?php endif; ?>
		    	</section>
		    	
		    	<!--[if IE 8]>
		    	<div class="ie8clear"></div>
		    	<![endif]-->
	    	</div>
		</div>
		    
		<?php if($this->API->modules('bottom1')) : ?>
		<div id="gkBottom1">
			<div class="gkCols6<?php if($this->API->modules('bottom1') > 1) : ?> gkNoMargin<?php endif; ?> gkPage">
				<jdoc:include type="modules" name="bottom1" style="<?php echo $this->module_styles['bottom1']; ?>" modnum="<?php echo $this->API->modules('bottom1'); ?>" />
				
				<!--[if IE 8]>
				<div class="ie8clear"></div>
				<![endif]-->
			</div>
		</div>
		<?php endif; ?>
	    
	    <?php if($this->API->modules('bottom2')) : ?>
	    <div id="gkBottom2">
	    	<div class="gkCols6<?php if($this->API->modules('bottom2') > 1) : ?> gkNoMargin<?php endif; ?> gkPage">
	    		<jdoc:include type="modules" name="bottom2" style="<?php echo $this->module_styles['bottom2']; ?>" modnum="<?php echo $this->API->modules('bottom2'); ?>" />
	    		
	    		<!--[if IE 8]>
	    		<div class="ie8clear"></div>
	    		<![endif]-->
	    	</div>
	    </div>
	    <?php endif; ?>
	        
	    <?php if($this->API->modules('lang')) : ?>
	    <div id="gkLang">
	    	<div class="gkPage">
	         	<jdoc:include type="modules" name="lang" style="<?php echo $this->module_styles['lang']; ?>" />
	         </div>
	    </div>
	    <?php endif; ?>
	    
	    <?php $this->layout->loadBlock('footer'); ?>
    </div>
   	
   	<?php if($this->API->get('stylearea', '0') == '1' || $this->API->get('framework_logo', '0') == '1') : ?>
   	<div class="gkPage">	
	   	<?php if($this->API->get('stylearea', '0') == '1') : ?>
	   	<div id="gkStyleArea">
	   		<a href="#" id="gkColor1"><?php echo JText::_('TPL_GK_LANG_COLOR_1'); ?></a>
	   		<a href="#" id="gkColor2"><?php echo JText::_('TPL_GK_LANG_COLOR_2'); ?></a>
	   			<a href="#" id="gkColor3"><?php echo JText::_('TPL_GK_LANG_COLOR_3'); ?></a>
	   		</div>
	   	<?php endif; ?>
	   	
	   	<?php if($this->API->get('framework_logo', '0') == '1') : ?>
	   	<a href="//gavick.com" id="gkFrameworkLogo" title="Gavern Framework">Gavern Framework</a>
	   	<?php endif; ?>	
   	</div>
   	<?php endif; ?>
   	
   	<?php $this->layout->loadBlock('tools/login'); ?>
   	<?php $this->layout->loadBlock('tools/cart'); ?>
   	<div id="gkPopupOverlay"></div>
   	<?php $this->layout->loadBlock('social'); ?>
   		
	<jdoc:include type="modules" name="debug" />
	<script>
	jQuery(document).ready(function(){
   		// Target your .container, .wrapper, .post, etc.
   		jQuery("body").fitVids();
	});
	</script>
</body>
</html>