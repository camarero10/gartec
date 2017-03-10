<?php

/**
 *
 * AJAX view
 *
 * @version             1.0.0
 * @package             Gavern Framework
 * @copyright			Copyright (C) 2010 - 2011 GavickPro. All rights reserved.
 *               
 */
 
// No direct access.
defined('_JEXEC') or die;
header('Content-type: application/json; charset=utf-8');
?>

<?php if($this->countModules('cart')) : ?>		
<jdoc:include type="modules" name="cart" style="gk_style" />
<?php endif; ?>	