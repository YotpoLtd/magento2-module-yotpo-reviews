<?php

namespace Yotpo\Yotpo\Console\Command;

use Magento\Framework\ObjectManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Yotpo - Manual update metadata
 */
class UpdateMetadataCommand extends Command
{
    /**
     * @param ObjectManagerInterface
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
        $this->setName('yotpo:update-metadata')
            ->setDescription('Manually send platform metadata to Yotpo');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $output->writeln('<info>' . 'Working on it ...' . '</info>');
            $this->objectManager->get(\Yotpo\Yotpo\Model\Jobs\UpdateMetadata::class)
                ->setCrontabAreaCode()
                ->initConfig([
                    "output" => $output,
                ])->execute();
            $output->writeln('<info>' . 'Done :)' . '</info>');
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
        }
    }
}
