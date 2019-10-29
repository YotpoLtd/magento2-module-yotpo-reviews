<?php
namespace Yotpo\Yotpo\Model;

class Sync extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init(\Yotpo\Yotpo\Model\ResourceModel\Sync::class);
    }
}
