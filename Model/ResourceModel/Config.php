<?php
/**
 * Copyright © Yotpo, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Yotpo\Yotpo\Model\ResourceModel;

class Config extends \Magento\Config\Model\ResourceModel\Config
{
    /**
     * Get config value
     * @param string $path
     * @param string $scope
     * @param int $scopeId
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getConfig($path, $scope = 'stores', $scopeId = 0)
    {
        $connection = $this->getConnection();
        $select = $connection->select()->from(
            $this->getMainTable(),
            ['value']
        )->where(
            'path = ?',
            $path
        )->where(
            'scope = ?',
            $scope
        )->where(
            'scope_id = ?',
            $scopeId
        );
        return $connection->fetchOne($select);
    }
}
