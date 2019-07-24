<?php

namespace Yotpo\Yotpo\Helper;

use Magento\Framework\Registry;
use Yotpo\Yotpo\Model\Api\Products as YotpoApi;

/**
 * [!] This class is deprecated & will be removed on future releases, please use \Yotpo\Yotpo\Model\Api\Products instead.
 */
class RichSnippets
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
     * @param  YotpoApi $yotpoApi
     * @param  Registry $coreRegistry
     */
    public function __construct(
        YotpoApi $yotpoApi,
        Registry $coreRegistry
    ) {
        $this->yotpoApi = $yotpoApi;
        $this->registry = $coreRegistry;
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
