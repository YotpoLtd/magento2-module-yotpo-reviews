<?php

namespace Yotpo\Yotpo\Model\ResourceModel;

class Sync extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_setResource('sales');
        $this->_init('yotpo_sync', 'sync_id');
    }
}
