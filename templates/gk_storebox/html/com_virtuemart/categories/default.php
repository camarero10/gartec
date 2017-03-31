<?php
/**
*
* Show the products in a category
*
* @package	VirtueMart
* @subpackage
* @author RolandD
* @author Max Milbers
* @todo add pagination
* @link http://www.virtuemart.net
* @copyright Copyright (c) 2004 - 2012 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
 * @version $Id: default.php 6104 2012-06-13 14:15:29Z alatak $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

if ($this->category->haschildren) {

// Category and Columns Counter
$iCol = 1;
$iCategory = 1;

// Calculating Categories Per Row
$categories_per_row = VmConfig::get ( 'categories_per_row', 3 );
$category_cellwidth = ' width'.floor ( 100 / $categories_per_row );

// Separator
$verticalseparator = " vertical-separator";
?>

<div class="category-view">
	<div class="row">
<?php // Start the Output
if ($this->category->children ) {
    foreach ( $this->category->children as $category ) {

	$show_vertical_separator = $verticalseparator;

	    // Category Link
	    $caturl = JRoute::_ ( 'index.php?option=com_virtuemart&view=category&virtuemart_category_id=' . $category->virtuemart_category_id , FALSE);

		    // Show Category ?>
		    <div class="category floatleft<?php echo $category_cellwidth . $show_vertical_separator ?>">
			    <div class="spacer">
				    <a href="<?php echo $caturl ?>" title="<?php echo $category->category_name ?>">
				    	<?php echo $category->images[0]->displayMediaThumb("",false); ?>
				    </a>
				    
				    <h2>
					    <a href="<?php echo $caturl ?>" title="<?php echo $category->category_name ?>">
					    <?php echo $category->category_name ?>
					    </a>
				    </h2>
				    
				    <a href="<?php echo $caturl; ?>" class="category-overlay"><span><span><?php echo JText::_('TPL_GK_LANG_VM_VIEW'); ?></span></span></a>
			    </div>
		    </div>
	    <?php


    }
}
?>

	</div>
</div>
<?php } ?>