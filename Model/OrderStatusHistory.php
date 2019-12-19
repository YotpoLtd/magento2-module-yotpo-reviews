<?php
namespace Yotpo\Yotpo\Model;

class OrderStatusHistory extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init(\Yotpo\Yotpo\Model\ResourceModel\OrderStatusHistory::class);
    }
}
