<?php

namespace Yotpo\Yotpo\Cron;

use Yotpo\Yotpo\Model\Jobs\UpdateMetadata;

class UpdateMetadataJob
{
    /**
     * @var UpdateMetadata
     */
    private $updateMetadataModel;

    public function __construct(
        UpdateMetadata $updateMetadataModel
    ) {
        $this->updateMetadataModel = $updateMetadataModel;
    }

    public function execute()
    {
        return $this->updateMetadataModel->execute();
    }
}
