<?php

namespace Yotpo\Yotpo\Cron;

use Yotpo\Yotpo\Model\Jobs\OrdersSync;

class OrdersSyncJob
{
    /**
     * @var OrdersSync
     */
    private $ordersSyncModel;

    public function __construct(
        OrdersSync $_ordersSyncModel
    ) {
        $this->ordersSyncModel = $ordersSyncModel;
    }

    public function execute()
    {
        return $this->ordersSyncModel->execute();
    }
}
