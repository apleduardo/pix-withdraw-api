<?php
namespace App\Service;

class PixEmailHandler implements PixKeyHandlerInterface
{
    public function validate(array $data): ?string
    {
        $key = $data['pix']['key'] ?? '';
        if (!filter_var($key, FILTER_VALIDATE_EMAIL)) {
            return 'The pix.key field must be a valid email address.';
        }
        return null;
    }

    public function process(string $accountId, array $data): array
    {
        // Here you would call the WithdrawService logic for PIX email
        // For now, just return an empty array to be filled by WithdrawService
        return [];
    }
}
