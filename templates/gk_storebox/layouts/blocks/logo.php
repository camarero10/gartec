<?php

// No direct access.
defined('_JEXEC') or die;
$logo_image = $this->API->get('logo_image', '');

if(($logo_image == '') || ($this->API->get('logo_type', '') == 'css')) {
     $logo_image = $this->API->URLtemplate() . '/images/logo.png';
} else {
     $logo_image = $this->API->URLbase() . $logo_image;
}

$logo_text = $this->API->get('logo_text', '');
$logo_slogan = $this->API->get('logo_slogan', '');

?>

<?php if ($this->API->get('logo_type', 'image')!=='none'): ?>
<h2>
     <?php if($this->API->get('logo_type', 'image') == 'css') : ?>
     <a href="<?php echo JURI::root(); ?>" id="gkLogo" class="cssLogo"><?php echo $this->API->get('logo_text', ''); ?></a>
     <?php elseif($this->API->get('logo_type', 'image')=='text') : ?>
     <a href="<?php echo JURI::root(); ?>" id="gkLogo" class="text">
		<span><?php echo $this->API->get('logo_text', ''); ?></span>
        <small class="gkLogoSlogan"><?php echo $this->API->get('logo_slogan', ''); ?></small>
     </a>
     <?php elseif($this->API->get('logo_type', 'image')=='image') : ?>
     <a href="<?php echo JURI::root(); ?>" id="gkLogo">
        <img src="<?php echo $logo_image; ?>" alt="<?php echo $this->API->getPageName(); ?>" />
     </a>
     <?php endif; ?>
</h2>
<?php endif; ?>

<?php if($this->API->modules('topmenu')) : ?>
<div id="gkTopMenu">
	<jdoc:include type="modules" name="topmenu" style="<?php echo $this->module_styles['topmenu']; ?>" />
</div>
<?php endif; ?>

<?php if($this->API->modules('search')) : ?>
<div id="gkSearch">
	<jdoc:include type="modules" name="search" style="<?php echo $this->module_styles['search']; ?>" />
</div>
<?php endif; ?>
