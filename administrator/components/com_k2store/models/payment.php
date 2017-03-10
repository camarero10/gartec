<?php

/** ensure this file is being included by a parent file */
defined('_JEXEC') or die('Restricted access');
require_once(JPATH_SITE.'/components/com_k2store/models/_base.php');
class K2StoreModelPayment extends K2StoreModelBase
{
    protected function _buildQueryWhere($query)
    {
       	$filter     = $this->getState('filter');
        $filter_id_from = $this->getState('filter_id_from');
        $filter_id_to   = $this->getState('filter_id_to');
        $filter_name    = $this->getState('filter_name');
        $filter_enabled    = $this->getState('filter_enabled');

       	if ($filter)
       	{
       	    $key	= $this->_db->Quote('%'.$this->_db->escape( trim( strtolower( $filter ) ) ).'%');
       	    $where = array();
       	    $where[] = 'LOWER(tbl.id) LIKE '.$key;
       	    $where[] = 'LOWER(tbl.name) LIKE '.$key;
       	    $query->where('('.implode(' OR ', $where).')');
       	}

        if (strlen($filter_enabled))
        {

          $query->where('tbl.enabled = 1');
        }
        if ($filter_name)
        {
            $key    = $this->_db->q('%'.$this->_db->escape( trim( strtolower( $filter_name ) ) ).'%');
            $where = array();
            $where[] = 'LOWER(tbl.name) LIKE '.$key;
            $query->where('('.implode(' OR ', $where).')');
        }

        // force returned records to only be k2store payment
        $query->where("tbl.folder = 'k2store'");
        $query->where("tbl.element LIKE 'payment_%'");

    }

    public function getList($refresh = false)
    {
        $list = parent::getList($refresh);
        foreach(@$list as $item)
        {
            $item->id = $item->extension_id;

            $item->link_edit = 'index.php?option=com_plugins&view=plugins&task=plugin.edit&extension_id='.$item->id;
        }
        return $list;
    }

    public function getItem($pk=null, $refresh=false, $emptyState=true)
    {
        if ($item = parent::getItem($pk, $refresh, $emptyState))
        {
                $formdata = new JRegistry;
                $formdata -> loadString($item -> params);
                $item -> data = $formdata -> toArray('data');

        }
        return $item;
    }

}
