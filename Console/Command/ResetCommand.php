<?php

namespace Yotpo\Yotpo\Console\Command;

use Composer\Console\ApplicationFactory;
use Magento\Deploy\Model\Filesystem;
use Magento\Framework\App\ResourceConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInputFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Yotpo - Manual sync
 */
class ResetCommand extends Command
{
    const RESET_FLAGS_CONFIRM_MESSAGE = "<question>Reset Yotpo sync flags? (y/n)[n]</question>\n";
    const RESET_CONFIG_CONFIRM_MESSAGE = "<question>Reset Yotpo configurations (reset to default)? (y/n)[n]</question>\n";

    /**#@+
     * Keys and shortcuts for input arguments and options
     */
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
     * @param ResourceConnection
     */
    protected $_resourceConnection;

    /**
     * @method __construct
     * @param Filesystem $filesystem
     * @param ArrayInputFactory $arrayInputFactory
     * @param ApplicationFactory $applicationFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Yotpo\Yotpo\Cron\Jobs $jobs
     * @param \Yotpo\Yotpo\Helper\Data $yotpoHelper
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        Filesystem $filesystem,
        ArrayInputFactory $arrayInputFactory,
        ApplicationFactory $applicationFactory,
        \Magento\Framework\Registry $registry,
        \Yotpo\Yotpo\Cron\Jobs $jobs,
        \Yotpo\Yotpo\Helper\Data $yotpoHelper,
        ResourceConnection $resourceConnection
    ) {
        $this->_filesystem = $filesystem;
        $this->_arrayInputFactory = $arrayInputFactory;
        $this->_applicationFactory = $applicationFactory;
        $this->_registry = $registry;
        $this->_jobs = $jobs;
        $this->_yotpoHelper = $yotpoHelper;
        $this->_resourceConnection = $resourceConnection;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('yotpo:reset')
            ->setDescription('Reset Yotpo sync flags &/or configurations')
            ->setDefinition([
                new InputOption(
                    self::ENTITY,
                    '-e',
                    InputOption::VALUE_OPTIONAL,
                    'Entity type (orders)',
                    'orders'
                )
            ]);
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->_yotpoHelper->isEnabled()) {
            $output->writeln('<error>' . 'The Yotpo module has been disabled from system configuration. Please enable it in order to run this command!' . '</error>');
            return;
        }

        $this->_registry->register('isYotpoYotpoResetCommand', true);

        try {
            if ($this->confirmQuestion(self::RESET_FLAGS_CONFIRM_MESSAGE, $input, $output)) {
                $output->writeln('<info>' . 'Resetting Yotpo sync flags ...' . '</info>');
                $this->_jobs->initConfig([
                    "output" => $output
                ])->resetSyncFlags($input->getOption(self::ENTITY));
            }

            if ($this->confirmQuestion(self::RESET_CONFIG_CONFIRM_MESSAGE, $input, $output)) {
                $output->writeln('<info>' . 'Resetting Yotpo configurations ...' . '</info>');
                $this->resetConfig();
            }
            $output->writeln('<info>' . 'Done :)' . '</info>');
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }

    /**
     * @method confirmQuestion
     * @param string $message
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return bool
     */
    private function confirmQuestion(string $message, InputInterface $input, OutputInterface $output)
    {
        $confirmationQuestion = new ConfirmationQuestion($message, false);
        return (bool)$this->getHelper('question')->ask($input, $output, $confirmationQuestion);
    }

    private function resetConfig()
    {
        $this->_resourceConnection->getConnection()->delete(
            $this->_resourceConnection->getTableName('core_config_data'),
            "path LIKE '" . \Yotpo\Yotpo\Helper\Data::XML_PATH_ALL . "/%'"
        );
    }
}
