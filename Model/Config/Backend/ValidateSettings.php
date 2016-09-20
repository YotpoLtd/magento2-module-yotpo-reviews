<?php

namespace Yotpo\Yotpo\Model\Config\Backend;
use Magento\Store\Model\ScopeInterface;

class ValidateSettings extends \Magento\Framework\App\Config\Value
{
	public function __construct(
	    \Magento\Framework\Model\Context $context,
	    \Magento\Framework\Registry $registry,
	    \Magento\Framework\App\Config\ScopeConfigInterface $config,
	    \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
	    \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
	    \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
	    array $data = []
	) {
	    $this->context = $context;
	    parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
	}

	public function afterSave()
    {
		if ($this->isValueChanged()) {
			$this->context->getCacheManager()->clean();
		} 
        return $this;
    }
}