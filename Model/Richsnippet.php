<?php
namespace Yotpo\Yotpo\Model;

class Richsnippet extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init('Yotpo\Yotpo\Model\ResourceModel\Richsnippet');
    }

    public function isValid()
    {
        return (strtotime($this->getExpirationTime()) > time());
    }

    public function getSnippetByProductIdAndStoreId($product_id, $store_id)
    {
        $collection = $this->getCollection()
            ->addFieldToFilter('store_id', $store_id)
            ->addFieldToFilter('product_id', $product_id)
            ->setPageSize(1);
        if ($collection->count()) {
            return $collection->getFirstItem();
        }
    }
}
