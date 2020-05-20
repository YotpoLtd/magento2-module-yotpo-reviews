<?php
namespace Yotpo\Yotpo\Model\Logger;

use Monolog\Logger;

class YotpoHandler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/yotpo_yotpo.log';
}
