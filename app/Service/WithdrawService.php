<?php

namespace App\Service;

use App\Repository\AccountRepository;
use App\Repository\AccountWithdrawRepository;
use App\Repository\AccountWithdrawPixRepository;
use Hyperf\DbConnection\Db;
use Hyperf\Stringable\Str;
use Psr\Log\LoggerInterface;
use Throwable;
use App\Service\EmailServiceInterface;

class WithdrawService
{
    public function __construct(
        protected AccountRepository $accountRepository,
        protected AccountWithdrawRepository $withdrawRepository,
        protected AccountWithdrawPixRepository $pixRepository,
        protected LoggerInterface $logger,
        protected EmailServiceInterface $emailService
    ) {}

    private function processImmediateWithdraw($account, $withdraw, $pix): array
    {
        if ($withdraw->amount > $account->balance) {
            $withdraw->error = true;
            $withdraw->error_reason = 'Insufficient balance';
            $this->withdrawRepository->save($withdraw);
            $this->logger->error("Insufficient balance for withdraw", ['accountId' => $account->id, 'withdrawId' => $withdraw->id]);
            return ['error' => 'Insufficient balance', 'status' => 422];
        }
        $account->balance -= $withdraw->amount;
        $this->accountRepository->save($account);
        $withdraw->done = true;
        $this->withdrawRepository->save($withdraw);
        if ($pix) {
            $emailService = $this->emailService;
            \Hyperf\Coroutine\Coroutine::create(function () use ($pix, $withdraw, $emailService) {
                $emailService->sendWithdrawNotification($pix->key, [
                    'amount' => $withdraw->amount,
                    'pix_type' => $pix->type,
                    'pix_key' => $pix->key,
                    'date' => date('Y-m-d H:i:s'),
                ]);
            });
        }
        $this->logger->info("Withdraw processed successfully", ['accountId' => $account->id, 'withdrawId' => $withdraw->id]);
        return ['success' => true];
    }

    public function requestWithdraw(string $accountId, array $data): array
    {
        try {
            Db::beginTransaction();
            $account = $this->accountRepository->findByIdForUpdate($accountId);
            if (!$account) {
                $this->logger->error("Account not found for withdraw", ['accountId' => $accountId]);
                Db::rollBack();
                return ['error' => 'Account not found', 'status' => 404];
            }
            $withdraw = $this->withdrawRepository->create([
                'id' => Str::uuid()->toString(),
                'account_id' => $accountId,
                'method' => $data['method'],
                'amount' => $data['amount'],
                'scheduled' => !empty($data['schedule']),
                'scheduled_for' => $data['schedule'] ?? null,
                'done' => empty($data['schedule']),
                'error' => false,
                'error_reason' => null,
            ]);
            $pix = $this->pixRepository->create([
                'account_withdraw_id' => $withdraw->id,
                'type' => $data['pix']['type'],
                'key' => $data['pix']['key'],
            ]);
            if (empty($data['schedule'])) {
                $result = $this->processImmediateWithdraw($account, $withdraw, $pix);
                if (isset($result['error'])) {
                    Db::rollBack();
                    return $result;
                }
            }
            Db::commit();
            return ['success' => true];
        } catch (Throwable $e) {
            $this->logger->error("Withdraw processing error: " . $e->getMessage(), ['exception' => $e]);
            Db::rollBack();
            return ['error' => 'Withdraw processing error', 'status' => 500];
        }
    }

    public function processScheduledWithdraws(): void
    {
        $now = date('Y-m-d H:i:s');
        $scheduledList = $this->withdrawRepository->findScheduledToProcess($now);
        foreach ($scheduledList as $withdraw) {
            Db::beginTransaction();
            try {
                $account = $this->accountRepository->findByIdForUpdate($withdraw->account_id);
                if (!$account) {
                    $this->logger->error("Account not found for scheduled withdraw", ['accountId' => $withdraw->account_id]);
                    Db::rollBack();
                    continue;
                }
                $pix = $this->pixRepository->findByWithdrawId($withdraw->id);
                $result = $this->processImmediateWithdraw($account, $withdraw, $pix);
                if (isset($result['error'])) {
                    Db::rollBack();
                    continue;
                }
                Db::commit();
            } catch (Throwable $e) {
                $this->logger->error("Scheduled withdraw processing error: " . $e->getMessage(), ['exception' => $e, 'withdrawId' => $withdraw->id]);
                Db::rollBack();
            }
        }
    }
}
