<?php

declare(strict_types=1);

namespace Tests\Smtp;

use Naf\Mail\Core\Mailer;
use Naf\Mail\Core\Transport\SmtpTransport;
use Naf\Mail\Exceptions\MailException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SmtpIntegrationTest extends TestCase
{
    public static function connections(): array
    {
        return [['starttls', 4725, 48025], ['tls', 4765, 48026]];
    }

    #[DataProvider('connections')]
    public function testCertificateVerifiedTlsLoginAndMimeDelivery(string $security, int $port, int $api): void
    {
        $transport = new SmtpTransport($this->configuration($security, $port));
        $before    = $this->messages($api)['total'];
        $transport->checkConnection();
        self::assertSame($before, $this->messages($api)['total'], 'Connection checks must not send mail.');
        $mailer  = new Mailer($transport);
        $subject = 'SMTP-Prüfung ' . bin2hex(random_bytes(8));
        $mail    = $mailer->createMail()->setFrom('support@example.test')->setReplyTo('support@example.test')
            ->addTo('requester@example.test')->addBcc('hidden@example.test')->setSubject($subject)
            ->setContent('<h1>Danke für Ihre Nachricht.</h1>')->setTextContent('Danke für Ihre Nachricht.');
        self::assertTrue($mailer->send($mail));
        $messages = $this->messages($api)['messages'];
        $matches  = array_values(array_filter($messages, static fn(array $message) => $message['Subject'] === $subject));
        self::assertCount(1, $matches);
        $message = json_decode(file_get_contents('http://127.0.0.1:' . $api . '/api/v1/message/' . $matches[0]['ID']), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('support@example.test', $message['From']['Address']);
        self::assertStringContainsString('Danke für Ihre Nachricht.', $message['Text']);
        self::assertStringContainsString('<h1>Danke für Ihre Nachricht.</h1>', $message['HTML']);
    }

    public function testWrongCredentialsFailDelivery(): void
    {
        $configuration             = $this->configuration('starttls', 4725);
        $configuration['password'] = 'incorrect-test-password';
        $mailer                    = new Mailer(new SmtpTransport($configuration));
        $this->expectException(MailException::class);
        $mailer->send($mailer->createMail()->setFrom('support@example.test')->addTo('requester@example.test')->setContent('Never accepted', false));
    }

    public function testAnUntrustedServerCertificateIsRejected(): void
    {
        $configuration = $this->configuration('starttls', 4725);
        unset($configuration['smtp']['ca_file']);
        $mailer = new Mailer(new SmtpTransport($configuration));
        $this->expectException(MailException::class);
        $mailer->send($mailer->createMail()->setFrom('support@example.test')->addTo('requester@example.test')->setContent('Never accepted', false));
    }

    private function configuration(string $security, int $port): array
    {
        return [
            'from' => 'support@example.test', 'password' => 'test-smtp-password',
            'smtp' => ['host' => '127.0.0.1', 'port' => $port, 'security' => $security,
                'ca_file'     => __DIR__ . '/certificates/cert.pem', 'timeout' => 3],
        ];
    }

    private function messages(int $port): array
    {
        return json_decode(file_get_contents('http://127.0.0.1:' . $port . '/api/v1/messages'), true, flags: JSON_THROW_ON_ERROR);
    }
}
