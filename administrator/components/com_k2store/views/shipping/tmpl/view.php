<?php defined('_JEXEC') or die('Restricted access'); ?>
<?php $row = $this->row; ?>

	<h3>
	    <?php echo JText::_($row->name); ?>
	</h3>

	<?php
		$dispatcher = JDispatcher::getInstance();
		$results = $dispatcher->trigger( 'onK2StoreGetShippingView', array( $row ) );

        for ($i=0; $i<count($results); $i++)
        {
            $result = $results[$i];
            echo $result;
        }
	?>