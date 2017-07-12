<?php
namespace Yotpo\Yotpo\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class YotpoOrderStatusBuilder implements ArrayInterface
{
    protected $_manager;

    public function __construct(\Magento\Sales\Model\ResourceModel\Order\Status\Collection $manager)
    {
        $this->_manager = $manager;
    }

    /*  
     * Option getter
     * @return array
     */
    public function toOptionArray()
    {
		$manager = $this->_manager; 
		return $manager->toOptionArray();
    }


}
