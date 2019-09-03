<?php

namespace Yotpo\Yotpo\Console\Command;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\ObjectManagerInterface;
use Symfony\Component\Console\Command\Command;
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
     * @param ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @param ResourceConnection
     */
    private $resourceConnection;

    /**
     * @method __construct
     * @param  ObjectManagerInterface $objectManager
     * @param  ResourceConnection     $resourceConnection
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ResourceConnection $resourceConnection
    ) {
        $this->objectManager = $objectManager;
        $this->resourceConnection = $resourceConnection;
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
        try {
            if ($this->confirmQuestion(self::RESET_FLAGS_CONFIRM_MESSAGE, $input, $output)) {
                $output->writeln('<info>' . 'Resetting Yotpo sync flags ...' . '</info>');
                $this->objectManager->get(\Yotpo\Yotpo\Model\Jobs\ResetSyncFlags::class)
                    ->setCrontabAreaCode()
                    ->initConfig([
                        "output" => $output
                    ])->execute($input->getOption(self::ENTITY));
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
        $this->resourceConnection->getConnection()->delete(
            $this->resourceConnection->getTableName('core_config_data'),
            "path LIKE '" . \Yotpo\Yotpo\Model\Config::XML_PATH_YOTPO_ALL . "/%'"
        );
    }
}
