<?php

namespace App\Repository;

use App\Model\AccountWithdraw;

class AccountWithdrawRepository
{
    public function create(array $data): AccountWithdraw
    {
        return AccountWithdraw::create($data);
    }
}
