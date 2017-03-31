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
    
     <?php endif; ?>
</footer>
