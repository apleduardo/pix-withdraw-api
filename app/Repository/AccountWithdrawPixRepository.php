<?php

namespace App\Repository;

use App\Model\AccountWithdrawPix;

class AccountWithdrawPixRepository
{
    public function create(array $data): AccountWithdrawPix
    {
        return AccountWithdrawPix::create($data);
    }
}
