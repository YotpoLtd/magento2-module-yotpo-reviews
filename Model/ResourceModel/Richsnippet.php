<?php
namespace Yotpo\Yotpo\Model\ResourceModel;

class Richsnippet extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('yotpo_rich_snippets', 'rich_snippet_id');
    }

    /**
     * Update attribute values for notifications
     *
     * @param array $entityIds
     * @param array $attrData
     * @return $this
     * @throws \Exception
     */
}