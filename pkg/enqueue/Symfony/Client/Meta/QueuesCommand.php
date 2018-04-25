<?php

namespace Enqueue\Symfony\Client\Meta;

use Enqueue\Client\Meta\QueueMetaRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class QueuesCommand extends Command
{
    /**
     * @var QueueMetaRegistry
     */
    private $queueMetaRegistry;

    /**
     * @param QueueMetaRegistry $queueRegistry
     */
    public function __construct(QueueMetaRegistry $queueRegistry)
    {
        parent::__construct(null);

        $this->queueMetaRegistry = $queueRegistry;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('enqueue:queues')
            ->setAliases([
                'enq:m:q',
                'debug:enqueue:queues',
            ])
            ->setDescription('A command shows all available queues and some information about them.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $table->setHeaders(['Client Name', 'Transport Name', 'processors']);

        $count = 0;
        $firstRow = true;
        foreach ($this->queueMetaRegistry->getQueuesMeta() as $queueMeta) {
            if (false == $firstRow) {
                $table->addRow(new TableSeparator());
            }

            $table->addRow([
                $queueMeta->getClientName(),
                $queueMeta->getClientName() == $queueMeta->getTransportName() ? '(same)' : $queueMeta->getTransportName(),
                implode(PHP_EOL, $queueMeta->getProcessors()),
            ]);

            ++$count;
            $firstRow = false;
        }

        $output->writeln(sprintf('Found %s destinations', $count));
        $output->writeln('');
        $table->render();
    }
}
