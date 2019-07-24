<?php

namespace Yotpo\Yotpo\Model\ResourceModel\Sync;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init('Yotpo\Yotpo\Model\Sync', 'Yotpo\Yotpo\Model\ResourceModel\Sync');
    }
}
