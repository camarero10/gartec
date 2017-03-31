<?php
// Access
defined('_JEXEC') or die('Restricted access');

// Category and Columns Counter
$iCol = 1;
$iCategory = 1;

// Calculating Categories Per Row
$categories_per_row = VmConfig::get('homepage_categories_per_row', 3);
$category_cellwidth = ' width' . floor(100 / $categories_per_row);

// Separator
$verticalseparator = " vertical-separator";
?>

<div class="category-view box bigtitle">

    <h3 class="header"><span><?php echo JText::_('COM_VIRTUEMART_CATEGORIES') ?></span></h3>
	<div class="row">
    <?php
    // Start the Output
    foreach ($this->categories as $category) {

		$show_vertical_separator = $verticalseparator;


	    // Category Link
	    $caturl = JRoute::_('index.php?option=com_virtuemart&view=category&virtuemart_category_id=' . $category->virtuemart_category_id, FALSE);
	    
	    	    // Show Category
	    ?>
    	<div class="category floatleft<?php echo $category_cellwidth . $show_vertical_separator ?>">
    	    <div class="spacer">
	    		<?php
	    		if (!empty($category->images)) {
		    		echo '<a href="'.$caturl.'" title="'.$category->category_name.'">';
		    		echo $category->images[0]->displayMediaThumb("", false);
		    		echo '</a>';
	    		}
	    		?>
	    		
	    		<h2>
	    		    <a href="<?php echo $caturl ?>" title="<?php echo $category->category_name ?>">
						<?php echo $category->category_name ?>
	    		    </a>
	    		</h2>
    	    </div>
    	</div>
	<?php

	}

?>
		</div>
</div>