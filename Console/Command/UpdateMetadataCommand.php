<?php

namespace Yotpo\Yotpo\Console\Command;

use Composer\Console\ApplicationFactory;
use Magento\Deploy\Model\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInputFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Yotpo - Manual update metadata
 */
class UpdateMetadataCommand extends Command
{
    /**
     * @var Magento\Deploy\Model\Filesystem
     */
    private $_filesystem;

    /**
     * @var ArrayInputFactory
     * @deprecated
     */
    private $_arrayInputFactory;

    /**
     * @var ApplicationFactory
     */
    private $_applicationFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @param \Yotpo\Yotpo\Cron\Jobs
     */
    protected $_jobs;

    /**
     * @param \Yotpo\Yotpo\Helper\Data
     */
    protected $_yotpoHelper;

    /**
     * @method __construct
     * @param Filesystem $filesystem
     * @param ArrayInputFactory $arrayInputFactory
     * @param ApplicationFactory $applicationFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Yotpo\Yotpo\Cron\Jobs $jobs
     * @param \Yotpo\Yotpo\Helper\Data $yotpoHelper
     */
    public function __construct(
        Filesystem\Proxy $filesystem,
        ArrayInputFactory\Proxy $arrayInputFactory,
        ApplicationFactory\Proxy $applicationFactory,
        \Magento\Framework\Registry\Proxy $registry,
        \Yotpo\Yotpo\Cron\Jobs\Proxy $jobs,
        \Yotpo\Yotpo\Helper\Data\Proxy $yotpoHelper
    ) {
        $this->_filesystem = $filesystem;
        $this->_arrayInputFactory = $arrayInputFactory;
        $this->_applicationFactory = $applicationFactory;
        $this->_registry = $registry;
        $this->_jobs = $jobs;
        $this->_yotpoHelper = $yotpoHelper;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('yotpo:update-metadata')
            ->setDescription('Manually send platform metadata to Yotpo');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->_yotpoHelper->isEnabled()) {
            $output->writeln('<error>' . 'The Yotpo Yotpo module has been disabled from system configuration. Please enable it in order to run this command!' . '</error>');
            return;
        }

        $this->_registry->register('isUpdateMetadataCommand', true);

        try {
            $output->writeln('<info>' . 'Working on it (Imagine a spinning gif loager) ...' . '</info>');

            //================================================================//
            $this->_jobs->initConfig([
                "output" => $output,
            ])->updateMetadata();
            //================================================================//

            $output->writeln('<info>' . 'Done :)' . '</info>');
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }
}
