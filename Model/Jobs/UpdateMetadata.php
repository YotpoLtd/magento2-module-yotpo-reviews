<?php

namespace Yotpo\Yotpo\Model\Jobs;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Notification\NotifierInterface;
use Magento\Store\Model\App\Emulation as AppEmulation;
use Yotpo\Yotpo\Model\AbstractJobs;
use Yotpo\Yotpo\Model\Api\AccountPlatform as YotpoApi;
use Yotpo\Yotpo\Model\Config as YotpoConfig;

class UpdateMetadata extends AbstractJobs
{
    /**
     * @var YotpoApi
     */
    private $yotpoApi;

    /**
     * @method __construct
     * @param  NotifierInterface      $notifierPool
     * @param  AppState               $appState
     * @param  YotpoConfig            $yotpoConfig
     * @param  ResourceConnection     $resourceConnection
     * @param  AppEmulation           $appEmulation
     * @param  YotpoApi               $yotpoApi
     */
    public function __construct(
        NotifierInterface $notifierPool,
        AppState $appState,
        YotpoConfig $yotpoConfig,
        ResourceConnection $resourceConnection,
        AppEmulation $appEmulation,
        YotpoApi $yotpoApi
    ) {
        parent::__construct($notifierPool, $appState, $yotpoConfig, $resourceConnection, $appEmulation);
        $this->yotpoApi = $yotpoApi;
    }

    public function execute()
    {
        try {
            foreach ($this->_yotpoConfig->getAllStoreIds(false) as $storeId) {
                try {
                    $this->emulateFrontendArea($storeId);
                    if (!$this->_yotpoConfig->isEnabled()) {
                        $this->_processOutput("UpdateMetadata::execute() - Skipping store ID: {$storeId} (disabled)", "debug");
                        continue;
                    }
                    $this->_processOutput("UpdateMetadata::execute() - Updating metadata for store ID: {$storeId} ...", "debug");
                    $result = $this->yotpoApi->updateMetadata($storeId);
                    $this->_processOutput("UpdateMetadata::execute() - Updating metadata for store ID: {$storeId} [SUCCESS]", "debug");
                } catch (\Exception $e) {
                    $this->_processOutput("UpdateMetadata::execute() - Exception on store ID: {$storeId} - " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
                }
                $this->stopEnvironmentEmulation();
            }
        } catch (\Exception $e) {
            $this->_processOutput("UpdateMetadata::execute() - Exception:  " . $e->getMessage() . "\n" . $e->getTraceAsString(), "error");
        }
    }
}
