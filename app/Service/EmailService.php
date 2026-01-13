<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Email;

class EmailService implements EmailServiceInterface
{
    public function __construct(
        protected LoggerInterface $logger
    ) {}

    public function sendWithdrawNotification(string $toEmail, array $withdrawData): void
    {
        $subject = 'PIX Withdraw Notification';
        $body = sprintf(
            "Hello, your withdraw of R$ %.2f via PIX (%s: %s) was processed at %s.",
            $withdrawData['amount'],
            $withdrawData['pix_type'],
            $withdrawData['pix_key'],
            $withdrawData['date']
        );
        try {
            $transport = Transport::fromDsn('smtp://mailhog:1025');
            $mailer = new Mailer($transport);
            $email = (new Email())
                ->from('noreply@example.com')
                ->to($toEmail)
                ->subject($subject)
                ->text($body);
            $mailer->send($email);
            $this->logger->info('Withdraw notification email sent', ['to' => $toEmail, 'withdraw' => $withdrawData]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send withdraw notification email: ' . $e->getMessage(), ['to' => $toEmail, 'withdraw' => $withdrawData]);
        }
    }
}
