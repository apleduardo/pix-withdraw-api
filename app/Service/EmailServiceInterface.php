<?php

namespace App\Service;

interface EmailServiceInterface
{
    /**
     * Envia um email de notificação de saque.
     * @param string $toEmail
     * @param array $withdrawData
     * @return void
     */
    public function sendWithdrawNotification(string $toEmail, array $withdrawData): void;
}
