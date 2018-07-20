<?php
namespace Yotpo\Yotpo\Model;

class Richsnippet extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init('Innovadeltech\Wishlist\Model\ResourceModel\Management');
    }

    public function isValid()
    {
        $expirationTime = strtotime($this->getExpirationTime());
        return ($expirationTime > time());
    }

    public function getSnippetByProductIdAndStoreId($product_id, $store_id)
    {
        $col = $this->getCollection()->addFieldToFilter('store_id', $store_id);
        if ($col->getSize() == 0) {
            return null;
        }
        $snippet = $col->getItemByColumnValue('product_id', $product_id);
        return $snippet;
    }
}