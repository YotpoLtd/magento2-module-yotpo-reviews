<?php

namespace Yotpo\Yotpo\Console\Command;

use Composer\Console\ApplicationFactory;
use Magento\Deploy\Model\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInputFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Yotpo - Manual orders sync
 */
class SyncCommand extends Command
{

    /**#@+
     * Keys and shortcuts for input arguments and options
     */
    const LIMIT = 'limit';
    const ENTITY = 'entity';
    /**#@- */

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
        $this->setName('yotpo:sync')
            ->setDescription('Sync Yotpo manually (reviews module)')
            ->setDefinition([
                new InputOption(
                    self::ENTITY,
                    '-e',
                    InputOption::VALUE_REQUIRED,
                    'Entity type (allowed options: orders)',
                    'orders'
                ),
                new InputOption(
                    self::LIMIT,
                    '-l',
                    InputOption::VALUE_OPTIONAL,
                    'Max entity items to sync. WARNING: Setting a high sync limit (or no limit) may result in a high server load (0=no limit).',
                    null
                ),
            ]);
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

        $this->_registry->register('isYotpoOrdersSyncCommand', true);

        try {
            $output->writeln('<info>' . 'Working on it (Imagine a spinning gif loager) ...' . '</info>');

            //================================================================//
            $this->_jobs->initConfig([
                "output" => $output,
                "limit" => $input->getOption(self::LIMIT),
            ]);

            call_user_func([$this->_jobs, $input->getOption(self::ENTITY) . "Sync"]);
            //================================================================//

            $output->writeln('<info>' . 'Done :)' . '</info>');
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }
}
