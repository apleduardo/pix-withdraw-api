<?php

namespace App\Model;

class Account extends Model
{
    protected ?string $table = 'account';
    protected string $keyType = 'string';
    public bool $incrementing = false;
    protected array $fillable = [
        'id', 'name', 'balance',
    ];
}
