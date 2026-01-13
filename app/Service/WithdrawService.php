<?php

namespace App\Service;

use App\Repository\AccountRepository;
use App\Repository\AccountWithdrawRepository;
use App\Repository\AccountWithdrawPixRepository;
use Hyperf\DbConnection\Db;
use Hyperf\Stringable\Str;
use Psr\Log\LoggerInterface;
use Throwable;

class WithdrawService
{
    public function __construct(
        protected AccountRepository $accountRepository,
        protected AccountWithdrawRepository $withdrawRepository,
        protected AccountWithdrawPixRepository $pixRepository,
        protected LoggerInterface $logger // Injeção do logger
    ) {}

    public function withdraw(string $accountId, array $data): array
    {
        try {
            Db::beginTransaction();

            $account = $this->accountRepository->findByIdForUpdate($accountId);
            if (!$account) {
                $this->logger->error("Account not found for withdraw", ['accountId' => $accountId]);
                Db::rollBack();
                return ['error' => 'Account not found', 'status' => 404];
            }
            if ($data['amount'] > $account->balance) {
                $this->logger->error("Insufficient balance for withdraw", ['accountId' => $accountId, 'amount' => $data['amount']]);
                Db::rollBack();
                return ['error' => 'Insufficient balance', 'status' => 422];
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
            $this->pixRepository->create([
                'account_withdraw_id' => $withdraw->id,
                'type' => $data['pix']['type'],
                'key' => $data['pix']['key'],
            ]);
            if (empty($data['schedule'])) {
                $account->balance -= $data['amount'];
                $this->accountRepository->save($account);
                // TODO: Enviar email assíncrono
            }
            Db::commit();
            $this->logger->info("Withdraw processed successfully", ['accountId' => $accountId, 'withdrawId' => $withdraw->id]);
            return ['success' => true];
        } catch (Throwable $e) {
            $this->logger->error("Withdraw processing error: " . $e->getMessage(), ['exception' => $e]);
            Db::rollBack();
            return ['error' => 'Withdraw processing error', 'status' => 500];
        }
    }
}
