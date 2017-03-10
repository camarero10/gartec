<?php

// No direct access.
defined('_JEXEC') or die;

?>

<footer id="gkFooter">
     <?php if($this->API->modules('footer_nav')) : ?>
     <div id="gkFooterNav">
          <jdoc:include type="modules" name="footer_nav" style="<?php echo $this->module_styles['footer_nav']; ?>" modnum="<?php echo $this->API->modules('footer_nav'); ?>" />
     </div>
     <?php endif; ?>
     <?php if($this->API->get('copyrights', '') !== '') : ?>
     <p id="gkCopyrights">
          <?php echo $this->API->get('copyrights', ''); ?>
     </p>
     <?php else : ?>
     <?php
    $app    = JFactory::getApplication();
    $menu   = $app->getMenu();
    $lang   = JFactory::getLanguage();
    if ($menu->getActive() == $menu->getDefault($lang->getTag())) : 
?>
     <p id="gkCopyrights">
          Joomla Template designed by
          <a href="https://www.gavick.com/joomla-templates" title="Joomla template designed by GavickPro" rel="nofollow">GavickPro</a>
     </p>
     <?php else : ?>
     <p id="gkCopyrights">
          Joomla Templates designed by GavickPro
     </p>
     <?php endif; ?>
     <?php endif; ?>
</footer>
