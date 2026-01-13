<?php
namespace App\Service;

interface PixKeyHandlerInterface
{
    public function validate(array $data): ?string;
    public function process(string $accountId, array $data): array;
}
