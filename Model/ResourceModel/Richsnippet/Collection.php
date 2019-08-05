<?php

namespace Yotpo\Yotpo\Model\ResourceModel\Richsnippet;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Yotpo\Yotpo\Model\Richsnippet', 'Yotpo\Yotpo\Model\ResourceModel\Richsnippet');
    }
}
