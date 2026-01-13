<?php

namespace App\Command;

use App\Service\WithdrawService;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @Command
 * Command to process scheduled PIX withdraws
 */
class WithdrawProcessScheduledCommand extends HyperfCommand
{
    protected ?string $name = 'withdraw:process-scheduled';

    public function __construct(protected WithdrawService $withdrawService, protected LoggerInterface $logger)
    {
        parent::__construct($this->name);
    }

    public function configure()
    {
        $this->setDescription('Process all scheduled PIX withdraws ready for execution');
    }

    public function handle()
    {
        $this->logger->info('Processing scheduled PIX withdraws...');
        $this->withdrawService->processScheduledWithdraws();
        $this->logger->info('Finished processing scheduled PIX withdraws.');
    }
}
