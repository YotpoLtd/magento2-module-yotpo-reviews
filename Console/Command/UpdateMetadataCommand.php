<?php

namespace Yotpo\Yotpo\Console\Command;

use Composer\Console\ApplicationFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInputFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yotpo\Yotpo\Helper\Data as YotpoHelper;

/**
 * Yotpo - Manual update metadata
 */
class UpdateMetadataCommand extends Command
{
    /**
     * @param ObjectManagerInterface
     */
    protected $_objectManager;

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
     * @var Registry
     */
    protected $_registry;

    /**
     * @param \Yotpo\Yotpo\Cron\Jobs
     */
    protected $_jobs;

    /**
     * @param YotpoHelper
     */
    protected $_yotpoHelper;

    /**
     * @method __construct
     * @param ObjectManagerInterface $objectManager
     * @param ArrayInputFactory $arrayInputFactory
     * @param ApplicationFactory $applicationFactory
     * @param Registry $registry
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ArrayInputFactory $arrayInputFactory,
        ApplicationFactory $applicationFactory,
        Registry $registry
    ) {
        $this->_objectManager = $objectManager;
        $this->_arrayInputFactory = $arrayInputFactory;
        $this->_applicationFactory = $applicationFactory;
        $this->_registry = $registry;
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
        $this->_yotpoHelper = $this->_objectManager->get('\Yotpo\Yotpo\Helper\Data');

        if (!$this->_yotpoHelper->isEnabled()) {
            $output->writeln('<error>' . 'The Yotpo Yotpo module has been disabled from system configuration. Please enable it in order to run this command!' . '</error>');
            return;
        }

        $this->_jobs = $this->_objectManager->get('\Yotpo\Yotpo\Cron\Jobs');

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
