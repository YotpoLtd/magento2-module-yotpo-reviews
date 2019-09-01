<?php

namespace Yotpo\Yotpo\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Registry;
use Yotpo\Yotpo\Model\Api\Products as YotpoApi;

/**
 * [!] This class is deprecated & will be removed on future releases, please use \Yotpo\Yotpo\Model\Api\Products instead.
 */
class RichSnippets extends AbstractHelper
{
    /**
     * @var YotpoApi
     */
    private $yotpoApi;

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @method __construct
     * @param  Context     $context
     * @param  YotpoApi    $yotpoApi
     * @param  Registry    $coreRegistry
     */
    public function __construct(
        Context $context,
        YotpoApi $yotpoApi,
        Registry $coreRegistry
    ) {
        parent::__construct($context);
        $this->yotpoApi = $yotpoApi;
        $this->coreRegistry = $coreRegistry;
    }

    /**
     * @method getRichSnippet
     * @return array
     */
    public function getRichSnippet()
    {
        return $this->yotpoApi->getRichSnippet($this->coreRegistry->registry('current_product')->getId());
    }
}
