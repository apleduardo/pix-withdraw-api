<?php

namespace App\Repository;

use App\Model\Account;

class AccountRepository
{
    public function findById(string $id): ?Account
    {
        return Account::find($id);
    }

    public function save(Account $account): bool
    {
        return $account->save();
    }

    public function findByIdForUpdate(string $id): ?Account
    {
        return Account::where('id', $id)->lockForUpdate()->first();
    }
}
