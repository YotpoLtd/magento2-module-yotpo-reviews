<?php

namespace Yotpo\Yotpo\Model;

use Magento\Framework\App\Area;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Notification\NotifierInterface;
use Magento\Store\Model\App\Emulation as AppEmulation;
use Symfony\Component\Console\Output\OutputInterface;
use Yotpo\Yotpo\Model\Config as YotpoConfig;

class AbstractJobs
{
    /**
     * @var mixed
     */
    private $adminNotificationError = false;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var NotifierInterface
     */
    private $notifierPool;

    /**
     * @var AppState
     */
    private $appState;

    /**
     * @var YotpoConfig
     */
    protected $_yotpoConfig;

    /**
     * @var ResourceConnection
     */
    protected $_resourceConnection;

    /**
     * @var AppEmulation
     */
    protected $_appEmulation;

    /**
     * @method __construct
     * @param  NotifierInterface  $notifierPool
     * @param  AppState           $appState
     * @param  YotpoConfig        $yotpoConfig
     * @param  ResourceConnection $resourceConnection
     * @param  AppEmulation       $appEmulation
     */
    public function __construct(
        NotifierInterface $notifierPool,
        AppState $appState,
        YotpoConfig $yotpoConfig,
        ResourceConnection $resourceConnection,
        AppEmulation $appEmulation
    ) {
        $this->notifierPool = $notifierPool;
        $this->appState = $appState;
        $this->_yotpoConfig = $yotpoConfig;
        $this->_resourceConnection = $resourceConnection;
        $this->_appEmulation = $appEmulation;
    }

    /**
     * @method strToCamelCase
     * @param  string         $str
     * @param  string         $prefix
     * @param  string         $suffix
     * @return string
     */
    public function strToCamelCase($str, $prefix = '', $suffix = '')
    {
        return $prefix . str_replace('_', '', ucwords($str, '_')) . $suffix;
    }

    /**
     * @method initConfig
     * @param array $config
     * @return $this
     */
    public function initConfig(array $config)
    {
        foreach ($config as $key => $val) {
            $method = $this->strToCamelCase(strtolower($key), 'set');
            if (method_exists($this, $method)) {
                $this->{$method}($val);
            }
        }
        return $this;
    }

    /**
     * @method setOutput
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
        return $this;
    }

    /**
     * @method getOutput
     * @return OutputInterface
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * Process output messages (log to system.log / output to terminal)
     * @method _processOutput
     * @return $this
     */
    protected function _processOutput($message, $type = "info", $data = [])
    {
        if ($this->output instanceof OutputInterface) {
            //Output to terminal
            $outputType = ($type === "error") ? $type : "info";
            $this->output->writeln('<' . $outputType . '>' . json_encode($message) . '</' . $outputType . '>');
            if ($data) {
                $this->output->writeln('<comment>' . json_encode($data) . '</comment>');
            }
        } else {
            //Add admin error notification
            if ($type === 'error' && !$this->adminNotificationError) {
                $this->addAdminNotification("Yopto - An error occurred during the automated sync process! (module: Yotpo_Yotpo)", "*If you enabled debug mode on Yotpo - Reviews & Visual Marketing, you should see more details in the log file (var/log/system.log)", 'critical');
                $this->adminNotificationError = true;
            }
        }

        //Log to var/log/system.log or var/log/debug.log
        $this->_yotpoConfig->log($message, $type, $data);

        return $this;
    }

    private function addAdminNotification(string $title, $description = "", $type = 'critical')
    {
        $method = 'add' . ucfirst($type);
        $this->notifierPool->{$method}($title, $description);
        return $this;
    }

    public function setCrontabAreaCode()
    {
        try {
            $this->appState->setAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB);
        } catch (\Exception $e) {
            $this->_processOutput("AbstractJobs::setCrontabAreaCode() - Exception:  " . $e->getMessage() . "\n" . $e->getTraceAsString(), "debug");
        }
        return $this;
    }

    ///////////////////////////////
    // App Environment Emulation //
    ///////////////////////////////

    /**
     * Start environment emulation of the specified store
     *
     * Function returns information about initial store environment and emulates environment of another store
     *
     * @param  integer $storeId
     * @param  string  $area
     * @param  bool    $force   A true value will ensure that environment is always emulated, regardless of current store
     * @return \Yotpo\Yotpo\Helper\Data
     */
    public function startEnvironmentEmulation($storeId, $area = Area::AREA_FRONTEND, $force = false)
    {
        $this->stopEnvironmentEmulation();
        $this->_appEmulation->startEnvironmentEmulation($storeId, $area, $force);
        return $this;
    }

    /**
     * Stop environment emulation
     *
     * Function restores initial store environment
     *
     * @return \Yotpo\Yotpo\Helper\Data
     */
    public function stopEnvironmentEmulation()
    {
        $this->_appEmulation->stopEnvironmentEmulation();
        return $this;
    }

    public function emulateFrontendArea($storeId, $force = true)
    {
        $this->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, $force);
        return $this;
    }
}
