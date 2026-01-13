<?php

namespace App\Model;

class AccountWithdraw extends Model
{
    protected ?string $table = 'account_withdraw';
    protected string $keyType = 'string';
    public bool $incrementing = false;
    protected array $fillable = [
        'id', 'account_id', 'method', 'amount', 'scheduled', 'scheduled_for', 'done', 'error', 'error_reason',
    ];
}
