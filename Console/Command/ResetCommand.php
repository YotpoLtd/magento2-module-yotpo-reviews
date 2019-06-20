<?php

namespace Yotpo\Yotpo\Console\Command;

use Composer\Console\ApplicationFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInputFactory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Yotpo\Yotpo\Helper\Data as YotpoHelper;

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
     * @param YotpoHelper
     */
    protected $_yotpoHelper;

    /**
     * @param \Yotpo\Yotpo\Cron\Jobs
     */
    protected $_jobs;

    /**
     * @param ResourceConnection
     */
    protected $_resourceConnection;

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
        $this->_yotpoHelper = $this->_objectManager->get('\Yotpo\Yotpo\Helper\Data');
        $this->_jobs = $this->_objectManager->get('\Yotpo\Yotpo\Cron\Jobs');
        $this->_resourceConnection = $this->_objectManager->get('\Magento\Framework\App\ResourceConnection');

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
