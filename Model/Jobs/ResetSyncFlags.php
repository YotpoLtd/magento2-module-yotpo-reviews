<?php

namespace Yotpo\Yotpo\Model\Jobs;

use Yotpo\Yotpo\Model\AbstractJobs;

class ResetSyncFlags extends AbstractJobs
{
    public function execute($entityType = null)
    {
        try {
            $this->_processOutput("ResetSyncFlags::execute() - (entity: {$entityType}) [STARTED]", "debug");
            $this->_resourceConnection->getConnection('sales')->update(
                $this->_resourceConnection->getTableName('yotpo_sync', 'sales'),
                ['sync_flag' => 0],
                (($entityType) ? ['entity_type = ?' => "{$entityType}"] : [])
            );
            $this->_processOutput("Yotpo - resetSyncFlags (entity: {$entityType}) [DONE]", "debug");
        } catch (\Exception $e) {
            $this->_processOutput("ResetSyncFlags::execute() - Exception:  " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
        }
    }
}
