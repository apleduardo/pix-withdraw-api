<?php

namespace App\Repository;

use App\Model\AccountWithdraw;

class AccountWithdrawRepository
{
    public function create(array $data): AccountWithdraw
    {
        return AccountWithdraw::create($data);
    }

    /**
     * Busca todos os saques agendados prontos para execução.
     */
    public function findScheduledToProcess(string $now): array
    {
        return AccountWithdraw::where('scheduled', true)
            ->where('done', false)
            ->where('scheduled_for', '<=', $now)
            ->get()
            ->all();
    }

    public function save(AccountWithdraw $withdraw): bool
    {
        return $withdraw->save();
    }
}
