<?php

namespace Yotpo\Yotpo\Console\Command;

use Magento\Framework\ObjectManagerInterface;
use Symfony\Component\Console\Command\Command;
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
     * @var array
     */
    private $jobModelsMap = [
        'orders' => \Yotpo\Yotpo\Model\Jobs\OrdersSync::class,
    ];

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * @method __construct
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
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
        try {
            $output->writeln('<info>' . 'Working on it ...' . '</info>');
            $this->objectManager->get($this->jobModelsMap[$input->getOption(self::ENTITY)])
                ->setCrontabAreaCode()
                ->initConfig([
                    "output" => $output,
                    "limit" => $input->getOption(self::LIMIT),
                ])->execute();
            $output->writeln('<info>' . 'Done :)' . '</info>');
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }
}
