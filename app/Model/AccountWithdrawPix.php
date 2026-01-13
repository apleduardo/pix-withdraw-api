<?php

namespace App\Model;

class AccountWithdrawPix extends Model
{
    protected ?string $table = 'account_withdraw_pix';
    public bool $incrementing = false;
    protected string $keyType = 'string';
    protected array $fillable = [
        'account_withdraw_id', 'type', 'key',
    ];
}
